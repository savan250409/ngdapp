<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\User;
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

    /**
     * Create a new app:
     *  1. Record in apps table.
     *  2. Create module tables ({code}_frames_category, {code}_frames, {code}_milestone_frame).
     *  3. Auto-create the app-admin login ({code}@admin / {code}@2026).
     */
    public function store(Request $request)
    {
        $this->ensureSuperAdmin();
        $request->validate([
            'code' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_]+$/', 'unique:apps,code'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $code = strtolower(trim($request->code));

        DB::transaction(function () use ($code, $request) {
            // 1. Insert into apps registry.
            App::create([
                'code'   => $code,
                'name'   => $request->name ?: strtoupper($code),
                'status' => 1,
            ]);

            // 2. Auto-create the app admin login.
            User::updateOrCreate(
                ['email' => $code . '@admin'],
                [
                    'name'     => strtoupper($code) . ' Admin',
                    'password' => Hash::make($code . '@2026'),
                    'app_code' => $code,
                ]
            );
        });

        // 3. Create module tables for this app.
        App::createAppTables($code);

        $msg = "App {$code} created. Login: {$code}@admin / {$code}@2026";

        return $request->ajax()
            ? response()->json(['success' => true, 'message' => $msg])
            : back()->with('success', $msg);
    }

    /**
     * Delete an app:
     *  1. Drop module tables ({code}_frames, {code}_frames_category, {code}_milestone_frame).
     *  2. Remove the app-admin user(s).
     *  3. Delete uploaded files.
     *  4. Remove from apps registry.
     */
    public function destroy(Request $request, $id)
    {
        $this->ensureSuperAdmin();
        $app = App::findOrFail($id);

        DB::transaction(function () use ($app) {
            // 1. Remove app-admin users from the main DB.
            User::where('app_code', $app->code)->delete();

            // 2. Remove from apps registry.
            $app->delete();
        });

        // 3. Drop module tables.
        App::dropAppTables($app->code);

        // 4. Remove uploaded files.
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
