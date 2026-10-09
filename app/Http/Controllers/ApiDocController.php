<?php

namespace App\Http\Controllers;

class ApiDocController extends Controller
{
    public function index()
    {
        $base = rtrim(url('/'), '/');

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
                        'url'         => $base . '/api/v1/getFrameCategories',
                        'params'      => [],
                        'description' => 'Returns every active category that has frames, newest first, each with its first 5 frames (ordered by frame_index).',
                    ],
                    [
                        'no'          => 2,
                        'title'       => 'Get Frames By Category Id',
                        'method'      => 'POST',
                        'url'         => $base . '/api/v1/getFramesByCategoryId',
                        'params'      => [
                            ['name' => 'category_id', 'required' => true],
                        ],
                        'description' => 'Returns all frames of the given category, ordered by frame_index, split into parallel "frame" and "thumbnail" URL arrays.',
                    ],
                ],
            ],
        ];

        // Every endpoint is protected by the Bearer token.
        $token = (string) config('services.api_token');

        return view('api-docs', compact('groups', 'token', 'base'));
    }
}
