<?php

namespace App\Http\Controllers;

use App\Models\Ngd001Frame;
use App\Models\Ngd001FrameCategory;
use App\Support\AppContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class Ngd001FrameController extends Controller
{
    /** Save an uploaded image under a guaranteed-unique, cache-busting name. */
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

    private function frameDir(Ngd001FrameCategory $category): string
    {
        return public_path('upload/' . (AppContext::code() ?: 'ngd001') . '/frame/' . $category->category_name . '/frame');
    }

    private function thumbDir(Ngd001FrameCategory $category): string
    {
        return public_path('upload/' . (AppContext::code() ?: 'ngd001') . '/frame/' . $category->category_name . '/frame_thumbnail');
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

    private function decodeOrder($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Build the ordered frames array from the request, honouring the drag order.
     */
    private function assembleFrames(Request $request, Ngd001FrameCategory $category, ?Ngd001FrameCategory $old = null): array
    {
        $old = $old ?: $category;
        $frameDest   = $this->frameDir($category);
        $thumbDest   = $this->thumbDir($category);
        $oldFrameDir = $this->frameDir($old);
        $oldThumbDir = $this->thumbDir($old);
        $categoryChanged = $old->id !== $category->id;

        $order          = $this->decodeOrder($request->input('frame_order'));
        $keepImagePaths = $request->input('keep_image_paths', []);
        $keepThumbPaths = $request->input('keep_thumbnail_paths', []);
        $keepImages     = $request->file('keep_images', []);
        $keepThumbs     = $request->file('keep_thumbnails', []);

        $newImages = $request->file('new_images', []);
        $newThumbs = $request->file('new_thumbnails', []);

        $frames = [];
        $i = 0;

        foreach ($order as $key) {
            // ----- Kept (existing) frame -----
            if (array_key_exists($key, $keepImagePaths)) {
                $imagePath = $keepImagePaths[$key];
                if (empty($imagePath)) {
                    continue;
                }
                $thumbPath = $keepThumbPaths[$key] ?? null;

                if (! empty($keepImages[$key])) {
                    $oldFull = $oldFrameDir . '/' . $imagePath;
                    if (File::exists($oldFull)) {
                        File::delete($oldFull);
                    }
                    $imagePath = $this->saveImage($keepImages[$key], $frameDest);
                } elseif ($categoryChanged) {
                    $this->moveFile($oldFrameDir . '/' . $imagePath, $frameDest . '/' . $imagePath);
                }

                if (! empty($keepThumbs[$key])) {
                    if (! empty($thumbPath) && File::exists($oldThumbDir . '/' . $thumbPath)) {
                        File::delete($oldThumbDir . '/' . $thumbPath);
                    }
                    $thumbPath = $this->saveImage($keepThumbs[$key], $thumbDest);
                } elseif ($categoryChanged && ! empty($thumbPath)) {
                    $this->moveFile($oldThumbDir . '/' . $thumbPath, $thumbDest . '/' . $thumbPath);
                }

                $frames[] = [
                    'image_path'     => $imagePath,
                    'frame_index'    => $i,
                    'thumbnail_path' => ! empty($thumbPath) ? $thumbPath : null,
                ];
                $i++;

            // ----- New frame -----
            } elseif (! empty($newImages[$key])) {
                $imageName = $this->saveImage($newImages[$key], $frameDest);
                $thumbName = ! empty($newThumbs[$key]) ? $this->saveImage($newThumbs[$key], $thumbDest) : null;

                $frames[] = [
                    'image_path'     => $imageName,
                    'frame_index'    => $i,
                    'thumbnail_path' => $thumbName,
                ];
                $i++;
            }
        }

        // Top of the builder (newest / last-edited) gets the HIGHEST frame_index,
        // so the descending API & lists return the last added/edited frame first.
        $n = count($frames);
        foreach ($frames as $pos => $f) {
            $frames[$pos]['frame_index'] = $n - 1 - $pos;
        }

        return $frames;
    }

    public function index()
    {
        $categories      = Ngd001FrameCategory::orderBy('sort_order')->orderBy('id', 'desc')->get();
        $usedCategoryIds = Ngd001Frame::pluck('category_id')->toArray();
        $query           = Ngd001Frame::with('category')->orderBy('id', 'DESC');

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
            $view = view('ngd001.frames.index', compact('categories', 'frames', 'usedCategoryIds'))->renderSections();

            return response()->json([
                'table'      => $view['table'] ?? '',
                'pagination' => $view['pagination'] ?? '',
                'total'      => $frames->total(),
            ]);
        }

        return view('ngd001.frames.index', compact('categories', 'frames', 'usedCategoryIds'));
    }

    public function create()
    {
        $categories      = Ngd001FrameCategory::orderBy('sort_order')->orderBy('id', 'desc')->get();
        $usedCategoryIds = Ngd001Frame::pluck('category_id')->toArray();

        return view('ngd001.frames.form', compact('categories', 'usedCategoryIds'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id'      => ['required', 'exists:' . Ngd001FrameCategory::tableName() . ',id', 'unique:' . Ngd001Frame::tableName() . ',category_id'],
            'frame_order'      => ['required', 'string'],
            'new_images'       => ['required', 'array', 'min:1'],
            'new_images.*'     => ['required', 'image', 'mimes:webp', 'max:20048'],
            'new_thumbnails.*' => ['nullable', 'image', 'mimes:webp', 'max:20048'],
        ], ['category_id.unique' => 'A frame record for this category already exists.']);

        $category = Ngd001FrameCategory::findOrFail($request->category_id);

        DB::beginTransaction();
        try {
            $framesArray = $this->assembleFrames($request, $category, $category);
            if (empty($framesArray)) {
                DB::rollBack();
                if ($request->ajax()) {
                    return response()->json(['message' => 'Add at least one frame.', 'errors' => ['new_images' => ['Add at least one frame.']]], 422);
                }

                return back()->withInput()->withErrors(['new_images' => 'Add at least one frame.']);
            }

            Ngd001Frame::create(['category_id' => $category->id, 'frames' => $framesArray]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $request->ajax()
            ? response()->json(['success' => true, 'message' => 'Frame(s) added successfully!'])
            : redirect()->route('frames.index')->with('success', 'Frame(s) added successfully!');
    }

    public function edit($id)
    {
        $frame           = Ngd001Frame::with('category')->findOrFail($id);
        $categories      = Ngd001FrameCategory::orderBy('sort_order')->orderBy('id', 'desc')->get();
        $usedCategoryIds = Ngd001Frame::where('id', '!=', $id)->pluck('category_id')->toArray();

        return view('ngd001.frames.form', compact('frame', 'categories', 'usedCategoryIds'));
    }

    public function update(Request $request, $id)
    {
        $frame = Ngd001Frame::with('category')->findOrFail($id);

        $request->validate([
            'category_id'       => ['required', 'exists:' . Ngd001FrameCategory::tableName() . ',id', 'unique:' . Ngd001Frame::tableName() . ',category_id,' . $id . ',id'],
            'frame_order'       => ['required', 'string'],
            'keep_images.*'     => ['nullable', 'image', 'mimes:webp', 'max:20048'],
            'keep_thumbnails.*' => ['nullable', 'image', 'mimes:webp', 'max:20048'],
            'new_images.*'      => ['nullable', 'image', 'mimes:webp', 'max:20048'],
            'new_thumbnails.*'  => ['nullable', 'image', 'mimes:webp', 'max:20048'],
        ], ['category_id.unique' => 'A frame record for this category already exists.']);

        $oldCategory = $frame->category;
        $newCategory = Ngd001FrameCategory::findOrFail($request->category_id);

        DB::beginTransaction();
        try {
            $keptImageSet = array_values(array_filter($request->input('keep_image_paths', [])));
            $oldFrameDir  = $this->frameDir($oldCategory);
            $oldThumbDir  = $this->thumbDir($oldCategory);

            foreach ($frame->frames ?? [] as $existing) {
                if (! in_array($existing['image_path'], $keptImageSet, true)) {
                    if (File::exists($oldFrameDir . '/' . $existing['image_path'])) {
                        File::delete($oldFrameDir . '/' . $existing['image_path']);
                    }
                    if (! empty($existing['thumbnail_path']) && File::exists($oldThumbDir . '/' . $existing['thumbnail_path'])) {
                        File::delete($oldThumbDir . '/' . $existing['thumbnail_path']);
                    }
                }
            }

            $framesArray = $this->assembleFrames($request, $newCategory, $oldCategory);
            if (empty($framesArray)) {
                DB::rollBack();
                if ($request->ajax()) {
                    return response()->json(['message' => 'A frame record must have at least one frame.', 'errors' => ['frame_order' => ['A frame record must have at least one frame.']]], 422);
                }

                return back()->withInput()->withErrors(['frame_order' => 'A frame record must have at least one frame.']);
            }

            $frame->update(['category_id' => $newCategory->id, 'frames' => $framesArray]);

            if ($oldCategory->id !== $newCategory->id && Ngd001Frame::where('category_id', $oldCategory->id)->count() < 1) {
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

        $params = array_filter([
            'page'            => $request->input('page') > 1 ? $request->input('page') : null,
            'search'          => $request->input('search'),
            'category_filter' => $request->input('category_filter'),
            'per_page'        => $request->input('per_page'),
        ]);
        $redirectUrl = route('frames.index', $params);

        return ($request->ajax() || $request->wantsJson())
            ? response()->json(['success' => true, 'message' => 'Frame record updated successfully!', 'redirect' => $redirectUrl])
            : redirect()->to($redirectUrl)->with('success', 'Frame record updated successfully!');
    }

    /**
     * JSON list of a category's frames (ordered), for the Index reorder popup.
     */
    public function categoryFrames($id)
    {
        $category = Ngd001FrameCategory::findOrFail($id);
        $record   = Ngd001Frame::where('category_id', $id)->first();
        $cat      = rawurlencode($category->category_name);
        $appBase  = 'upload/' . (AppContext::code() ?: 'ngd001') . '/frame/' . $cat;

        $frames = collect($record->frames ?? [])
            ->sortByDesc('frame_index')
            ->values()
            ->map(function ($f) use ($appBase) {
                $thumb     = $f['thumbnail_path'] ?? null;
                $frameUrl  = url($appBase . '/frame/' . rawurlencode($f['image_path']));
                $thumbUrl  = $thumb ? url($appBase . '/frame_thumbnail/' . rawurlencode($thumb)) : null;

                return [
                    'image_path'    => $f['image_path'],
                    'frame_url'     => $frameUrl,
                    'thumbnail_url' => $thumbUrl,
                    'thumb_url'     => $thumbUrl ?: $frameUrl,
                ];
            });

        return response()->json(['frames' => $frames]);
    }

    /**
     * Save the drag-and-drop order of frames within one category (Index popup).
     */
    public function updateOrder(Request $request)
    {
        $request->validate([
            'category_id' => ['required', 'exists:' . Ngd001FrameCategory::tableName() . ',id'],
            'order'       => ['required', 'array', 'min:1'],
            'order.*'     => ['string'],
        ]);

        $record = Ngd001Frame::where('category_id', $request->category_id)->firstOrFail();
        $byPath = collect($record->frames ?? [])->keyBy('image_path');

        $ordered = [];
        $i = 0;
        foreach ($request->input('order') as $imagePath) {
            if ($byPath->has($imagePath)) {
                $f = $byPath->get($imagePath);
                $f['frame_index'] = $i++;
                $ordered[] = $f;
                $byPath->forget($imagePath);
            }
        }
        foreach ($byPath as $f) {
            $f['frame_index'] = $i++;
            $ordered[] = $f;
        }

        // Top of the reorder list (newest) gets the highest frame_index (descending API).
        $n = count($ordered);
        foreach ($ordered as $pos => $f) {
            $ordered[$pos]['frame_index'] = $n - 1 - $pos;
        }

        $record->update(['frames' => $ordered]);

        return response()->json(['success' => true, 'message' => 'Frame order saved successfully!']);
    }

    public function destroy($id)
    {
        $frame     = Ngd001Frame::with('category')->findOrFail($id);
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

        if (Ngd001Frame::where('category_id', $category->id)->count() < 1) {
            if (File::exists($frameDest)) {
                File::deleteDirectory($frameDest);
            }
            if (File::exists($thumbDest)) {
                File::deleteDirectory($thumbDest);
            }
        }

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Frame record deleted successfully!']);
        }

        $total = Ngd001Frame::count();
        $perPage = (int) request('per_page', 10);
        $lastPage = (int) max(1, ceil($total / $perPage));
        $page = (int) request('page', 1);
        if ($page > $lastPage) {
            $page = $lastPage;
        }

        return redirect()->route('frames.index', array_filter([
            'page'            => $page > 1 ? $page : null,
            'search'          => request('search'),
            'category_filter' => request('category_filter'),
            'per_page'        => request('per_page'),
        ]))->with('success', 'Frame record deleted successfully!');
    }
}
