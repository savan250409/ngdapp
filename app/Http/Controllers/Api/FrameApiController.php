<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Frame;
use App\Models\FrameCategory;
use App\Support\AppContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FrameApiController extends Controller
{
    /**
     * GET /v1/getFrameCategories
     *
     * Every active category that has frames, newest first, each with its
     * first 5 frames (ordered by frame_index).
     */
    public function getFrameCategories()
    {
        $categories = FrameCategory::select('id', 'category_name', 'category_image', 'sort_order')
            ->where('status', 1)
            ->whereIn('id', fn ($q) => $q->select('category_id')->from('frames'))
            ->orderBy('sort_order')   // custom drag-and-drop sequence (1 = first)
            ->orderBy('id', 'desc')
            ->get();

        if ($categories->isEmpty()) {
            return response()->json([
                'status'  => false,
                'message' => 'No frame categories found',
                'data'    => [],
            ], 404);
        }

        $frameRecords = Frame::whereIn('category_id', $categories->pluck('id'))
            ->get()
            ->groupBy('category_id');

        $categories->transform(function ($category) use ($frameRecords) {
            $encoded = $category->category_name;
            $up      = url('/') . '/upload/' . AppContext::code() . '/'; // per-app base
            $path    = $category->category_image
                ? "frame/{$encoded}/category_thumbnail_frame/{$category->category_image}"
                : null;
            // Encode every space in the final URL as %20.
            $category->category_thumbnail_image_full_url = $path ? str_replace(' ', '%20', $up . $path) : null;
            unset($category->category_image, $category->sort_order, $category->app_code);

            $allFrames = [];
            foreach ($frameRecords->get($category->id, collect()) as $record) {
                foreach ($record->frames ?? [] as $f) {
                    $thumb = $f['thumbnail_path'] ?? null;
                    $allFrames[] = [
                        'sort'               => $f['frame_index'] ?? 0,
                        'frame_full_url'     => str_replace(' ', '%20', $up . "frame/{$encoded}/frame/" . basename($f['image_path'])),
                        'thumbnail_full_url' => $thumb ? str_replace(' ', '%20', $up . "frame/{$encoded}/frame_thumbnail/" . basename($thumb)) : null,
                    ];
                }
            }
            usort($allFrames, fn ($a, $b) => $a['sort'] <=> $b['sort']);

            $category->frames = array_values(array_map(fn ($i) => [
                'frame_full_url'     => $i['frame_full_url'],
                'thumbnail_full_url' => $i['thumbnail_full_url'],
            ], array_slice($allFrames, 0, 5)));

            return $category;
        });

        // Drop categories that have fewer than one frame (empty frame record).
        $categories = $categories->filter(fn ($c) => ! empty($c->frames))->values();

        if ($categories->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'No frame categories found', 'data' => []], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Frame categories fetched successfully',
            'data'    => $categories,
        ]);
    }

    /**
     * POST /v1/getFramesByCategoryId
     *
     * Body: category_id (required). Returns all frames of that category,
     * ordered by frame_index, as two parallel arrays.
     */
    public function getFramesByCategoryId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:frame_categories,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        // find() is app-scoped; a category_id from another app resolves to null here.
        $category = FrameCategory::find($request->category_id);
        if (! $category) {
            return response()->json(['status' => false, 'message' => 'Category not found', 'data' => []], 404);
        }
        if ($category->status == 0) {
            return response()->json([
                'status'  => false,
                'message' => 'This category is inactive',
                'data'    => [],
            ], 403);
        }

        $encoded   = $category->category_name;
        $up        = url('/') . '/upload/' . AppContext::code() . '/'; // per-app base
        $allFrames = [];
        foreach (Frame::where('category_id', $request->category_id)->get() as $record) {
            foreach ($record->frames ?? [] as $f) {
                $thumb = $f['thumbnail_path'] ?? null;
                $allFrames[] = [
                    'sort'               => $f['frame_index'] ?? 0,
                    // Encode every space in the final URL as %20.
                    'frame_full_url'     => str_replace(' ', '%20', $up . "frame/{$encoded}/frame/" . basename($f['image_path'])),
                    'thumbnail_full_url' => $thumb ? str_replace(' ', '%20', $up . "frame/{$encoded}/frame_thumbnail/" . basename($thumb)) : null,
                ];
            }
        }

        if (empty($allFrames)) {
            return response()->json([
                'status'  => false,
                'message' => 'No frames found for this category',
                'data'    => [],
            ], 404);
        }
        usort($allFrames, fn ($a, $b) => $a['sort'] <=> $b['sort']);

        return response()->json([
            'status'  => true,
            'message' => 'Frames fetched successfully',
            'data'    => [
                'frame'     => array_values(array_column($allFrames, 'frame_full_url')),
                'thumbnail' => array_values(array_column($allFrames, 'thumbnail_full_url')),
            ],
        ]);
    }
}
