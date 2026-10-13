<?php

namespace App\Http\Controllers;

use App\Support\AppContext;

class ApiDocController extends Controller
{
    public function index()
    {
        $app  = AppContext::code() ?: 'ngd001';
        $base = rtrim(url('/'), '/') . '/api/' . $app;

        // API documentation grouped into sections (matches the card layout).
        $groups = [
            [
                'label' => 'v1',
                'badge' => 'primary',
                'endpoints' => [
                    [
                        'no'          => 1,
                        'title'       => 'Get Frame Categories',
                        'method'      => 'GET',
                        'url'         => $base . '/v1/getFrameCategories',
                        'params'      => [],
                        'description' => 'Returns every active category that has frames, newest first, each with its first 5 frames (ordered by frame_index).',
                    ],
                    [
                        'no'          => 2,
                        'title'       => 'Get Frames By Category Id',
                        'method'      => 'POST',
                        'url'         => $base . '/v1/getFramesByCategoryId',
                        'params'      => [
                            ['name' => 'category_id', 'required' => true],
                        ],
                        'description' => 'Returns all frames of the given category, ordered by frame_index, split into parallel "frame" and "thumbnail" URL arrays.',
                    ],
                    [
                        'no'          => 3,
                        'title'       => 'Get Monthly Frames (Milestone)',
                        'method'      => 'POST',
                        'url'         => $base . '/v1/getMonthlyFrameByCategoryId',
                        'params'      => [
                            ['name' => 'category_id', 'required' => true],
                        ],
                        'description' => 'Milestone frames for one gender (category_id = 1 Boy Month Collection, 2 Girl Month Collection), grouped month-wise with parallel "frame" and "thumbnail" arrays per month.',
                    ],
                ],
            ],
        ];

        // Every endpoint is protected by the Bearer token.
        $token = (string) config('services.api_token');

        return view('api-docs', compact('groups', 'token', 'base'));
    }
}
