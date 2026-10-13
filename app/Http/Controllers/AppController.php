<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\Frame;
use App\Models\FrameCategory;
use App\Models\MilestoneFrame;
use App\Models\User;
use App\Support\AppContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class AppController extends Controller
{
    private function ensureSuperAdmin(): void
    {
        abort_unless(auth()->user() && auth()->user()->isSuperAdmin(), 403);
    }

    /** Super-admin switches the app they're viewing. */
    public function switch(Request $request)
    {
        $this->ensureSuperAdmin();
        $request->validate(['code' => ['required', 'exists:apps,code']]);

        session(['current_app' => $request->code]);

        return $request->ajax()
            ? response()->json(['success' => true])
            : back();
    }

    public function index()
    {
        $this->ensureSuperAdmin();
        $apps = App::orderBy('code')->get();

        return view('apps.index', compact('apps'));
    }

    /** Create a new app + its scoped admin login ({code}@admin / {code}@2026). */
    public function store(Request $request)
    {
        $this->ensureSuperAdmin();
        $request->validate([
            'code' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_-]+$/', 'unique:apps,code'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $code = $request->code;

        DB::transaction(function () use ($code, $request) {
            App::create(['code' => $code, 'name' => $request->name ?: strtoupper($code), 'status' => 1]);

            // Auto-create the app admin login (withoutGlobalScopes not needed: users aren't app-scoped models).
            User::updateOrCreate(
                ['email' => $code . '@admin'],
                ['name' => strtoupper($code) . ' Admin', 'password' => Hash::make($code . '@2026'), 'app_code' => $code]
            );
        });

        return $request->ajax()
            ? response()->json(['success' => true, 'message' => "App {$code} created. Login: {$code}@admin / {$code}@2026"])
            : back()->with('success', "App {$code} created. Login: {$code}@admin / {$code}@2026");
    }

    /** Delete an app, its admin users, its scoped data and its uploads. */
    public function destroy(Request $request, $id)
    {
        $this->ensureSuperAdmin();
        $app = App::findOrFail($id);

        DB::transaction(function () use ($app) {
            // Remove scoped data (ignore global scope so we hit the right app regardless of current context).
            FrameCategory::withoutGlobalScopes()->where('app_code', $app->code)->delete();
            Frame::withoutGlobalScopes()->where('app_code', $app->code)->delete();
            MilestoneFrame::withoutGlobalScopes()->where('app_code', $app->code)->delete();
            User::where('app_code', $app->code)->delete();
            $app->delete();
        });

        $folder = public_path('upload/' . $app->code);
        if (File::exists($folder)) {
            File::deleteDirectory($folder);
        }

        if (session('current_app') === $app->code) {
            session()->forget('current_app');
        }

        return $request->ajax()
            ? response()->json(['success' => true, 'message' => 'App deleted successfully!'])
            : back()->with('success', 'App deleted successfully!');
    }
}
