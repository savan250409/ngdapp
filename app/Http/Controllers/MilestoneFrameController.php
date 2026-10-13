<?php

namespace App\Http\Controllers;

use App\Models\MilestoneFrame;
use App\Support\AppContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class MilestoneFrameController extends Controller
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

    private function frameDir($gender, $month): string
    {
        return public_path('upload/' . AppContext::code() . '/milestone_frame/' . MilestoneFrame::genderSlug($gender) . '/' . (int) $month . '/frame');
    }

    private function thumbDir($gender, $month): string
    {
        return public_path('upload/' . AppContext::code() . '/milestone_frame/' . MilestoneFrame::genderSlug($gender) . '/' . (int) $month . '/frame_thumbnail');
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

    private function usedCombos($exceptId = null): array
    {
        return MilestoneFrame::when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->get(['gender', 'month'])
            ->map(fn ($r) => $r->gender . '-' . $r->month)
            ->toArray();
    }

    /**
     * Build the ordered frames array from the request, honouring the drag order
     * (frame_order = ordered list of row keys). Handles kept frames (optionally
     * replaced or moved across gender/month) and brand-new uploads, interleaved.
     */
    private function assembleFrames(Request $request, $gender, $month, $oldGender = null, $oldMonth = null): array
    {
        $oldGender = $oldGender ?? $gender;
        $oldMonth  = $oldMonth ?? $month;

        $frameDest   = $this->frameDir($gender, $month);
        $thumbDest   = $this->thumbDir($gender, $month);
        $oldFrameDir = $this->frameDir($oldGender, $oldMonth);
        $oldThumbDir = $this->thumbDir($oldGender, $oldMonth);
        $moved       = ((int) $oldGender !== (int) $gender) || ((int) $oldMonth !== (int) $month);

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
                    if (File::exists($oldFrameDir . '/' . $imagePath)) {
                        File::delete($oldFrameDir . '/' . $imagePath);
                    }
                    $imagePath = $this->saveImage($keepImages[$key], $frameDest);
                } elseif ($moved) {
                    $this->moveFile($oldFrameDir . '/' . $imagePath, $frameDest . '/' . $imagePath);
                }

                if (! empty($keepThumbs[$key])) {
                    if (! empty($thumbPath) && File::exists($oldThumbDir . '/' . $thumbPath)) {
                        File::delete($oldThumbDir . '/' . $thumbPath);
                    }
                    $thumbPath = $this->saveImage($keepThumbs[$key], $thumbDest);
                } elseif ($moved && ! empty($thumbPath)) {
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

        return $frames;
    }

    public function index()
    {
        $genders = MilestoneFrame::GENDERS;
        $query   = MilestoneFrame::orderBy('gender')->orderBy('month');

        if (($g = request('gender_filter')) !== null && $g !== '') {
            $query->where('gender', $g);
        }
        if (($m = request('month_filter')) !== null && $m !== '') {
            $query->where('month', $m);
        }

        $perPage = request('per_page', 10);
        $frames  = $query->paginate($perPage)->appends([
            'per_page'      => $perPage,
            'gender_filter' => request('gender_filter'),
            'month_filter'  => request('month_filter'),
        ]);

        // All records (for the Index reorder popup) — passed in BOTH branches so the
        // content section renders without an undefined-variable error on AJAX reload.
        $allRecords = MilestoneFrame::orderBy('gender')->orderBy('month')->get();

        if (request()->ajax()) {
            $view = view('milestone-frames.index', compact('genders', 'frames', 'allRecords'))->renderSections();

            return response()->json([
                'table'      => $view['table'] ?? '',
                'pagination' => $view['pagination'] ?? '',
                'total'      => $frames->total(),
            ]);
        }

        return view('milestone-frames.index', compact('genders', 'frames', 'allRecords'));
    }

    public function create()
    {
        $genders    = MilestoneFrame::GENDERS;
        $months     = range(1, 12);
        $usedCombos = $this->usedCombos();

        return view('milestone-frames.form', compact('genders', 'months', 'usedCombos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'gender'           => ['required', Rule::in(array_keys(MilestoneFrame::GENDERS))],
            'month'            => ['required', 'integer', 'between:1,12'],
            'frame_order'      => ['required', 'string'],
            'new_images'       => ['required', 'array', 'min:1'],
            'new_images.*'     => ['required', 'image', 'mimes:webp', 'max:20048'],
            'new_thumbnails.*' => ['nullable', 'image', 'mimes:webp', 'max:20048'],
        ]);

        if (MilestoneFrame::where('gender', $request->gender)->where('month', $request->month)->exists()) {
            $msg = 'A milestone frame record for this gender and month already exists.';

            return $request->ajax()
                ? response()->json(['message' => $msg, 'errors' => ['gender' => [$msg]]], 422)
                : back()->withErrors(['gender' => $msg])->withInput();
        }

        DB::beginTransaction();
        try {
            $framesArray = $this->assembleFrames($request, $request->gender, $request->month);
            if (empty($framesArray)) {
                DB::rollBack();
                if ($request->ajax()) {
                    return response()->json(['message' => 'Add at least one frame.', 'errors' => ['new_images' => ['Add at least one frame.']]], 422);
                }

                return back()->withInput()->withErrors(['new_images' => 'Add at least one frame.']);
            }

            MilestoneFrame::create([
                'gender' => $request->gender,
                'month'  => $request->month,
                'frames' => $framesArray,
            ]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $request->ajax()
            ? response()->json(['success' => true, 'message' => 'Milestone frame(s) added successfully!'])
            : redirect()->route('milestone-frames.index')->with('success', 'Milestone frame(s) added successfully!');
    }

    public function edit($id)
    {
        $frame      = MilestoneFrame::findOrFail($id);
        $genders    = MilestoneFrame::GENDERS;
        $months     = range(1, 12);
        $usedCombos = $this->usedCombos($id);

        return view('milestone-frames.form', compact('frame', 'genders', 'months', 'usedCombos'));
    }

    public function update(Request $request, $id)
    {
        $frame = MilestoneFrame::findOrFail($id);

        $request->validate([
            'gender'            => ['required', Rule::in(array_keys(MilestoneFrame::GENDERS))],
            'month'             => ['required', 'integer', 'between:1,12'],
            'frame_order'       => ['required', 'string'],
            'keep_images.*'     => ['nullable', 'image', 'mimes:webp', 'max:20048'],
            'keep_thumbnails.*' => ['nullable', 'image', 'mimes:webp', 'max:20048'],
            'new_images.*'      => ['nullable', 'image', 'mimes:webp', 'max:20048'],
            'new_thumbnails.*'  => ['nullable', 'image', 'mimes:webp', 'max:20048'],
        ]);

        $dup = MilestoneFrame::where('gender', $request->gender)
            ->where('month', $request->month)
            ->where('id', '!=', $id)
            ->exists();
        if ($dup) {
            $msg = 'A milestone frame record for this gender and month already exists.';

            return $request->ajax()
                ? response()->json(['message' => $msg, 'errors' => ['gender' => [$msg]]], 422)
                : back()->withErrors(['gender' => $msg])->withInput();
        }

        $oldGender = $frame->gender;
        $oldMonth  = $frame->month;

        DB::beginTransaction();
        try {
            // Delete files for existing frames that were removed (not in the kept set).
            $keptImageSet = array_values(array_filter($request->input('keep_image_paths', [])));
            $oldFrameDir  = $this->frameDir($oldGender, $oldMonth);
            $oldThumbDir  = $this->thumbDir($oldGender, $oldMonth);

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

            $framesArray = $this->assembleFrames($request, $request->gender, $request->month, $oldGender, $oldMonth);
            if (empty($framesArray)) {
                DB::rollBack();
                if ($request->ajax()) {
                    return response()->json(['message' => 'A record must have at least one frame.', 'errors' => ['frame_order' => ['A record must have at least one frame.']]], 422);
                }

                return back()->withInput()->withErrors(['frame_order' => 'A record must have at least one frame.']);
            }

            $frame->update([
                'gender' => $request->gender,
                'month'  => $request->month,
                'frames' => $framesArray,
            ]);

            // If gender/month changed, remove the now-empty old folders.
            $moved = ((int) $oldGender !== (int) $request->gender) || ((int) $oldMonth !== (int) $request->month);
            if ($moved) {
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
            ? response()->json(['success' => true, 'message' => 'Milestone frame record updated successfully!'])
            : redirect()->route('milestone-frames.index')->with('success', 'Milestone frame record updated successfully!');
    }

    /** JSON list of a record's frames (ordered), for the Index / View-more popups. */
    public function recordFrames($id)
    {
        $record = MilestoneFrame::findOrFail($id);
        $base   = 'upload/' . AppContext::code() . '/milestone_frame/' . $record->gender_slug . '/' . (int) $record->month;

        $frames = collect($record->frames ?? [])
            ->sortBy('frame_index')
            ->values()
            ->map(function ($f) use ($base) {
                $thumb    = $f['thumbnail_path'] ?? null;
                $frameUrl = url($base . '/frame/' . rawurlencode($f['image_path']));
                $thumbUrl = $thumb ? url($base . '/frame_thumbnail/' . rawurlencode($thumb)) : null;

                return [
                    'image_path'    => $f['image_path'],
                    'frame_url'     => $frameUrl,
                    'thumbnail_url' => $thumbUrl,
                    'thumb_url'     => $thumbUrl ?: $frameUrl,
                ];
            });

        return response()->json(['frames' => $frames]);
    }

    /** Save the drag-and-drop order of frames within one record (Index popup). */
    public function updateOrder(Request $request)
    {
        $request->validate([
            'id'      => ['required', 'exists:milestone_frames,id'],
            'order'   => ['required', 'array', 'min:1'],
            'order.*' => ['string'],
        ]);

        $record = MilestoneFrame::findOrFail($request->id);
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

        $record->update(['frames' => $ordered]);

        return response()->json(['success' => true, 'message' => 'Frame order saved successfully!']);
    }

    public function destroy($id)
    {
        $frame     = MilestoneFrame::findOrFail($id);
        $frameDest = $this->frameDir($frame->gender, $frame->month);
        $thumbDest = $this->thumbDir($frame->gender, $frame->month);

        foreach ($frame->frames ?? [] as $f) {
            if (File::exists($frameDest . '/' . $f['image_path'])) {
                File::delete($frameDest . '/' . $f['image_path']);
            }
            if (! empty($f['thumbnail_path']) && File::exists($thumbDest . '/' . $f['thumbnail_path'])) {
                File::delete($thumbDest . '/' . $f['thumbnail_path']);
            }
        }

        $frame->delete();

        if (File::exists($frameDest)) {
            File::deleteDirectory($frameDest);
        }
        if (File::exists($thumbDest)) {
            File::deleteDirectory($thumbDest);
        }

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Milestone frame record deleted successfully!']);
        }

        return redirect()->route('milestone-frames.index')->with('success', 'Milestone frame record deleted successfully!');
    }
}
