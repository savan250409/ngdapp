<?php

namespace App\Http\Controllers;

use App\Models\Frame;
use App\Models\FrameCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class FrameController extends Controller
{
    /**
     * Save an uploaded image under a guaranteed-unique, cache-busting name.
     */
    private function saveImage($file, string $destPath): string
    {
        if (! File::exists($destPath)) {
            File::makeDirectory($destPath, 0777, true, true);
        }

        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = $file->getClientOriginalExtension();

        do {
            $name = $base . '_' . (int) (microtime(true) * 1000) . '_' . random_int(1000, 9999) . '.' . $ext;
        } while (File::exists($destPath . '/' . $name));

        $file->move($destPath, $name);

        return $name;
    }

    private function frameDir(FrameCategory $category): string
    {
        return public_path('upload/frame/' . $category->category_name . '/frame');
    }

    private function thumbDir(FrameCategory $category): string
    {
        return public_path('upload/frame/' . $category->category_name . '/frame_thumbnail');
    }

    public function index()
    {
        $categories      = FrameCategory::all();
        $usedCategoryIds = Frame::pluck('category_id')->toArray();
        $query           = Frame::with('category')->orderBy('id', 'DESC');

        if ($search = request('search')) {
            $query->whereHas('category', fn ($q) => $q->where('category_name', 'like', "%{$search}%"));
        }
        if ($categoryFilter = request('category_filter')) {
            $query->where('category_id', $categoryFilter);
        }

        $perPage = request('per_page', 10);
        $frames  = $query->paginate($perPage)->appends([
            'search'          => request('search'),
            'per_page'        => $perPage,
            'category_filter' => request('category_filter'),
        ]);

        if (request()->ajax()) {
            $view = view('frames.index', compact('categories', 'frames', 'usedCategoryIds'))->renderSections();

            return response()->json([
                'table'      => $view['table'] ?? '',
                'pagination' => $view['pagination'] ?? '',
                'total'      => $frames->total(),
            ]);
        }

        return view('frames.index', compact('categories', 'frames', 'usedCategoryIds'));
    }

    public function create()
    {
        $categories      = FrameCategory::all();
        $usedCategoryIds = Frame::pluck('category_id')->toArray();

        return view('frames.form', compact('categories', 'usedCategoryIds'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id'  => ['required', 'exists:frame_categories,id', 'unique:frames,category_id'],
            'images'       => 'required|array|min:1',
            'images.*'     => 'required|image|mimes:webp|max:20048',
            'thumbnails'   => 'nullable|array',
            'thumbnails.*' => 'nullable|image|mimes:webp|max:20048',
        ], ['category_id.unique' => 'A frame record for this category already exists.']);

        $category   = FrameCategory::findOrFail($request->category_id);
        $frameDest  = $this->frameDir($category);
        $thumbDest  = $this->thumbDir($category);
        $thumbnails = $request->file('thumbnails') ?? [];
        $counts     = $request->input('counts', []);
        $is_pros    = $request->input('is_pros', []);

        $framesArray = [];
        foreach ($request->file('images') as $i => $file) {
            $imageName = $this->saveImage($file, $frameDest);
            $thumbName = ! empty($thumbnails[$i]) ? $this->saveImage($thumbnails[$i], $thumbDest) : null;

            $framesArray[] = [
                'image_path'     => $imageName,
                'frame_index'    => $i,
                'thumbnail_path' => $thumbName,
                'count'          => (int) ($counts[$i] ?? 0),
                'is_pro'         => ! empty($is_pros[$i]),
            ];
        }

        Frame::create(['category_id' => $request->category_id, 'frames' => $framesArray]);

        return $request->ajax()
            ? response()->json(['success' => true, 'message' => 'Frame(s) added successfully!'])
            : redirect()->route('frames.index')->with('success', 'Frame(s) added successfully!');
    }

    public function edit($id)
    {
        $frame           = Frame::with('category')->findOrFail($id);
        $categories      = FrameCategory::all();
        $usedCategoryIds = Frame::where('id', '!=', $id)->pluck('category_id')->toArray();

        return view('frames.form', compact('frame', 'categories', 'usedCategoryIds'));
    }

    public function update(Request $request, $id)
    {
        $frame = Frame::with('category')->findOrFail($id);

        $request->validate([
            'category_id'          => ['required', 'exists:frame_categories,id', 'unique:frames,category_id,' . $id . ',id'],
            'keep_images.*'        => 'nullable|image|mimes:webp|max:20048',
            'keep_thumbnails.*'    => 'nullable|image|mimes:webp|max:20048',
            'new_images'           => 'nullable|array',
            'new_images.*'         => 'required|image|mimes:webp|max:20048',
            'new_thumbnails.*'     => 'nullable|image|mimes:webp|max:20048',
        ], ['category_id.unique' => 'A frame record for this category already exists.']);

        $oldCategory = $frame->category;
        $newCategory = FrameCategory::findOrFail($request->category_id);
        $categoryChanged = $oldCategory->id !== $newCategory->id;

        $oldFrameDir = $this->frameDir($oldCategory);
        $oldThumbDir = $this->thumbDir($oldCategory);
        $newFrameDir = $this->frameDir($newCategory);
        $newThumbDir = $this->thumbDir($newCategory);

        $keepImagePaths     = $request->input('keep_image_paths', []);
        $keepThumbnailPaths = $request->input('keep_thumbnail_paths', []);
        $keepCounts         = $request->input('keep_counts', []);
        $keepIsPros         = $request->input('keep_is_pros', []);
        $keepImages         = $request->file('keep_images', []);
        $keepThumbnails     = $request->file('keep_thumbnails', []);

        $newImages     = $request->file('new_images', []);
        $newThumbnails = $request->file('new_thumbnails', []);
        $newCounts     = $request->input('new_counts', []);
        $newIsPros     = $request->input('new_is_pros', []);

        DB::beginTransaction();
        try {
            // 2. Delete files for existing frames NOT present in the kept set.
            $keptImageSet = array_values(array_filter($keepImagePaths));
            foreach ($frame->frames ?? [] as $existing) {
                if (! in_array($existing['image_path'], $keptImageSet, true)) {
                    $imgPath = $oldFrameDir . '/' . $existing['image_path'];
                    if (File::exists($imgPath)) {
                        File::delete($imgPath);
                    }
                    if (! empty($existing['thumbnail_path'])) {
                        $thumbPath = $oldThumbDir . '/' . $existing['thumbnail_path'];
                        if (File::exists($thumbPath)) {
                            File::delete($thumbPath);
                        }
                    }
                }
            }

            // 3. Upload new frames (added first).
            $newFrames = [];
            foreach ($newImages as $i => $file) {
                if (! $file) {
                    continue;
                }
                $imageName = $this->saveImage($file, $newFrameDir);
                $thumbName = ! empty($newThumbnails[$i]) ? $this->saveImage($newThumbnails[$i], $newThumbDir) : null;

                $newFrames[] = [
                    'image_path'     => $imageName,
                    'thumbnail_path' => $thumbName,
                    'count'          => (int) ($newCounts[$i] ?? 0),
                    'is_pro'         => ! empty($newIsPros[$i]),
                ];
            }

            // 4. Process kept frames (replace file, or move when the category changed).
            $keptFrames = [];
            foreach ($keepImagePaths as $k => $imagePath) {
                if (empty($imagePath)) {
                    continue;
                }
                $thumbPath = $keepThumbnailPaths[$k] ?? null;

                // Image: replacement wins, otherwise move when category changed.
                if (! empty($keepImages[$k])) {
                    $oldImg = $oldFrameDir . '/' . $imagePath;
                    if (File::exists($oldImg)) {
                        File::delete($oldImg);
                    }
                    $imagePath = $this->saveImage($keepImages[$k], $newFrameDir);
                } elseif ($categoryChanged) {
                    $this->moveFile($oldFrameDir . '/' . $imagePath, $newFrameDir . '/' . $imagePath);
                }

                // Thumbnail: replacement wins, otherwise move when category changed.
                if (! empty($keepThumbnails[$k])) {
                    if (! empty($thumbPath)) {
                        $oldThumb = $oldThumbDir . '/' . $thumbPath;
                        if (File::exists($oldThumb)) {
                            File::delete($oldThumb);
                        }
                    }
                    $thumbPath = $this->saveImage($keepThumbnails[$k], $newThumbDir);
                } elseif ($categoryChanged && ! empty($thumbPath)) {
                    $this->moveFile($oldThumbDir . '/' . $thumbPath, $newThumbDir . '/' . $thumbPath);
                }

                $keptFrames[] = [
                    'image_path'     => $imagePath,
                    'thumbnail_path' => ! empty($thumbPath) ? $thumbPath : null,
                    'count'          => (int) ($keepCounts[$k] ?? 0),
                    'is_pro'         => ! empty($keepIsPros[$k]),
                ];
            }

            // 5. Merge new-first then kept, and re-index frame_index 0..n.
            $merged = array_merge($newFrames, $keptFrames);
            $framesArray = [];
            foreach ($merged as $i => $f) {
                $f['frame_index'] = $i;
                $framesArray[] = [
                    'image_path'     => $f['image_path'],
                    'frame_index'    => $i,
                    'thumbnail_path' => $f['thumbnail_path'],
                    'count'          => $f['count'],
                    'is_pro'         => $f['is_pro'],
                ];
            }

            // 6. Save the record.
            $frame->update([
                'category_id' => $newCategory->id,
                'frames'      => $framesArray,
            ]);

            // 7. If the category changed and the old category now has no frame record, clean its folders.
            if ($categoryChanged && Frame::where('category_id', $oldCategory->id)->count() < 1) {
                if (File::exists($oldFrameDir)) {
                    File::deleteDirectory($oldFrameDir);
                }
                if (File::exists($oldThumbDir)) {
                    File::deleteDirectory($oldThumbDir);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $request->ajax()
            ? response()->json(['success' => true, 'message' => 'Frame record updated successfully!'])
            : redirect()->route('frames.index')->with('success', 'Frame record updated successfully!');
    }

    private function moveFile(string $from, string $to): void
    {
        if (! File::exists($from)) {
            return;
        }
        $dir = dirname($to);
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0777, true, true);
        }
        File::move($from, $to);
    }

    public function destroy($id)
    {
        $frame     = Frame::with('category')->findOrFail($id);
        $category  = $frame->category;
        $frameDest = $this->frameDir($category);
        $thumbDest = $this->thumbDir($category);

        foreach ($frame->frames ?? [] as $f) {
            if (File::exists($frameDest . '/' . $f['image_path'])) {
                File::delete($frameDest . '/' . $f['image_path']);
            }
            if (! empty($f['thumbnail_path']) && File::exists($thumbDest . '/' . $f['thumbnail_path'])) {
                File::delete($thumbDest . '/' . $f['thumbnail_path']);
            }
        }

        $frame->delete();

        if (Frame::where('category_id', $category->id)->count() < 1) {
            if (File::exists($frameDest)) {
                File::deleteDirectory($frameDest);
            }
            if (File::exists($thumbDest)) {
                File::deleteDirectory($thumbDest);
            }
        }

        return redirect()->route('frames.index')
            ->with('success', 'Frame record deleted successfully!');
    }
}
