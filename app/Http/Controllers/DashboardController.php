<?php

namespace App\Http\Controllers;

use App\Models\Ngd001Frame;
use App\Models\Ngd001FrameCategory;

class DashboardController extends Controller
{
    public function index()
    {
        // Lightweight aggregate queries — safe even with very large tables.
        $totalCategories    = Ngd001FrameCategory::count();
        $activeCategories   = Ngd001FrameCategory::where('status', 1)->count();
        $inactiveCategories = $totalCategories - $activeCategories;
        $totalRecords       = Ngd001Frame::count();

        $recentCategories = Ngd001FrameCategory::latest()->limit(5)->get(['id', 'category_name', 'status', 'created_at']);

        return view('dashboard', compact(
            'totalCategories',
            'activeCategories',
            'inactiveCategories',
            'totalRecords',
            'recentCategories'
        ));
    }
}
