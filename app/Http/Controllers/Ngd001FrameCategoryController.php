<?php

namespace App\Http\Controllers;

use App\Models\Ngd001FrameCategory;
use App\Support\AppContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class Ngd001FrameCategoryController extends Controller
{
    /** Per-app frame storage base, e.g. "upload/ngd001/frame". */
    private function base(): string
    {
        return 'upload/' . (AppContext::code() ?: 'ngd001') . '/frame';
    }

    public function index()
    {
        $query = Ngd001FrameCategory::latest();

        if ($search = request('search')) {
            $query->where('category_name', 'like', "%{$search}%");
        }
        if (request('status_filter') !== null && request('status_filter') !== '') {
            $query->where('status', request('status_filter'));
        }

        $perPage = request('per_page', 10);
        $categories = $query->paginate($perPage)->appends([
            'search' => request('search'),
            'per_page' => $perPage,
            'status_filter' => request('status_filter'),
        ]);

        // All categories in their saved sequence (for the "Index" reorder popup).
        $allCategories = Ngd001FrameCategory::orderBy('sort_order')->orderBy('id', 'desc')->get();

        if (request()->ajax()) {
            $view = view('ngd001.frame-categories.index', compact('categories', 'allCategories'))->renderSections();

            return response()->json([
                'table' => $view['table'],
                'pagination' => $view['pagination'],
                'total' => $categories->total(),
            ]);
        }

        return view('ngd001.frame-categories.index', compact('categories', 'allCategories'));
    }

    /**
     * Save the drag-and-drop sequence from the Index popup.
     */
    public function updateOrder(Request $request)
    {
        $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'integer|exists:' . Ngd001FrameCategory::tableName() . ',id',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->input('order') as $i => $id) {
                Ngd001FrameCategory::where('id', $id)->update(['sort_order' => $i + 1]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Order saved successfully!']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_name' => ['required', 'string', 'max:255', Rule::unique(Ngd001FrameCategory::tableName(), 'category_name')],
            'category_image' => 'nullable|image|mimes:webp|max:20048',
            'status' => 'nullable|in:0,1',
        ]);

        $fileName = null;
        if ($request->hasFile('category_image')) {
            $dest = public_path($this->base() . '/' . $request->category_name . '/category_thumbnail_frame');
            if (!File::exists($dest)) {
                File::makeDirectory($dest, 0777, true, true);
            }
            $fileName = $request->file('category_image')->getClientOriginalName();
            $request->file('category_image')->move($dest, $fileName);
        }

        DB::transaction(function () use ($request, $fileName) {
            Ngd001FrameCategory::query()->increment('sort_order');

            Ngd001FrameCategory::create([
                'category_name' => $request->category_name,
                'category_image' => $fileName,
                'status' => $request->status ?? 1,
                'sort_order' => 1, // last added category placed first
            ]);
        });

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Frame category added successfully!']);
        }

        return redirect()->route('frame-categories.index')
            ->with('success', 'Frame category added successfully!');
    }

    public function update(Request $request, $id)
    {
        $category = Ngd001FrameCategory::findOrFail($id);

        $request->validate([
            'category_name' => ['required', 'string', 'max:255', Rule::unique(Ngd001FrameCategory::tableName(), 'category_name')->ignore($id)],
            'category_image' => 'nullable|image|mimes:webp|max:20048',
            'status' => 'required|in:0,1',
        ]);

        $oldName = $category->category_name;
        $newName = $request->category_name;
        $path = $category->category_image;

        // Rename the whole category folder when the name changes.
        if ($oldName !== $newName) {
            $oldFolder = public_path($this->base() . "/{$oldName}");
            $newFolder = public_path($this->base() . "/{$newName}");
            if (File::exists($oldFolder)) {
                if (!File::exists($newFolder)) {
                    File::move($oldFolder, $newFolder);
                } else {
                    File::copyDirectory($oldFolder, $newFolder);
                    File::deleteDirectory($oldFolder);
                }
            }
        }

        if ($request->hasFile('category_image')) {
            $dest = public_path($this->base() . "/{$newName}/category_thumbnail_frame");
            if (!File::exists($dest)) {
                File::makeDirectory($dest, 0777, true, true);
            }
            if ($path && File::exists($dest . '/' . $path)) {
                File::delete($dest . '/' . $path);
            }
            $file = $request->file('category_image');
            $path = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
                . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move($dest, $path);
        }

        $category->update([
            'category_name' => $newName,
            'category_image' => $path,
            'status' => $request->status,
        ]);

        $params = array_filter([
            'page'          => $request->input('page') > 1 ? $request->input('page') : null,
            'search'        => $request->input('search'),
            'status_filter' => $request->input('status_filter'),
            'per_page'      => $request->input('per_page'),
        ]);
        $redirectUrl = route('frame-categories.index', $params);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Frame category updated successfully!',
                'redirect' => $redirectUrl,
            ]);
        }

        return redirect()->to($redirectUrl)
            ->with('success', 'Frame category updated successfully!');
    }

    public function destroy($id)
    {
        $category = Ngd001FrameCategory::findOrFail($id);

        $folder = public_path($this->base() . '/' . $category->category_name);
        if (File::exists($folder)) {
            File::deleteDirectory($folder);
        }

        $category->delete(); // cascade removes its frame record

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Frame category deleted successfully!']);
        }

        $total = Ngd001FrameCategory::count();
        $perPage = (int) request('per_page', 10);
        $lastPage = (int) max(1, ceil($total / $perPage));
        $page = (int) request('page', 1);
        if ($page > $lastPage) {
            $page = $lastPage;
        }

        return redirect()->route('frame-categories.index', array_filter([
            'page'          => $page > 1 ? $page : null,
            'search'        => request('search'),
            'status_filter' => request('status_filter'),
            'per_page'      => request('per_page'),
        ]))->with('success', 'Frame category deleted successfully!');
    }

    public function toggleStatus($id)
    {
        $category = Ngd001FrameCategory::findOrFail($id);
        $category->status = $category->status ? 0 : 1;
        $category->save();

        return response()->json(['status' => $category->status]);
    }
}
