<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ngd001MilestoneFrame;
use App\Support\AppContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class Ngd001MilestoneFrameApiController extends Controller
{
    /**
     * POST /v1/getMonthlyFrameByCategoryId
     *
     * Returns all milestone frames for one gender, grouped month-wise.
     * Request field `category_id`: 1 = Boy Month Collection, 2 = Girl Month Collection.
     */
    public function getMonthlyFrameByCategoryId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|integer|in:1,2',
        ], [
            'category_id.in' => 'category_id must be 1 (Boy Month Collection) or 2 (Girl Month Collection)',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first(), 'data' => []], 422);
        }

        $gender  = (int) $request->category_id;
        $slug    = Ngd001MilestoneFrame::genderSlug($gender);
        $records = Ngd001MilestoneFrame::where('gender', $gender)->orderBy('month')->get();

        if ($records->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'No data found for this category', 'data' => []], 404);
        }

        $baseUrl = url('/');
        $data = $records->map(function ($record) use ($baseUrl, $slug) {
            $appBase   = $baseUrl . '/upload/' . (AppContext::code() ?: 'ngd001') . '/milestone_frame/' . $slug . '/' . $record->month;
            $framePath = $appBase . '/frame/';
            $thumbPath = $appBase . '/frame_thumbnail/';

            $frames = [];
            $thumbnails = [];
            foreach (collect($record->frames ?? [])->sortByDesc('frame_index') as $item) { // newest first
                if (! empty($item['image_path'])) {
                    $frames[] = str_replace(' ', '%20', $framePath) . rawurlencode($item['image_path']);
                }
                if (! empty($item['thumbnail_path'])) {
                    $thumbnails[] = str_replace(' ', '%20', $thumbPath) . rawurlencode($item['thumbnail_path']);
                }
            }

            return ['month' => $record->month, 'frame' => array_values($frames), 'thumbnail' => array_values($thumbnails)];
        })->values();

        return response()->json([
            'status'        => true,
            'message'       => 'Data fetched successfully',
            'category_name' => $gender === 1 ? 'Boy Month Collection' : 'Girl Month Collection',
            'data'          => $data,
        ]);
    }
}
