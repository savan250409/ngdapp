<?php

namespace App\Http\Controllers;

use App\Models\Frame;
use App\Models\FrameCategory;

class DashboardController extends Controller
{
    public function index()
    {
        // Lightweight aggregate queries — safe even with very large tables.
        $totalCategories    = FrameCategory::count();
        $activeCategories   = FrameCategory::where('status', 1)->count();
        $inactiveCategories = $totalCategories - $activeCategories;
        $totalRecords       = Frame::count();

        $recentCategories = FrameCategory::latest()->limit(5)->get(['id', 'category_name', 'status', 'created_at']);

        return view('dashboard', compact(
            'totalCategories',
            'activeCategories',
            'inactiveCategories',
            'totalRecords',
            'recentCategories'
        ));
    }
}
