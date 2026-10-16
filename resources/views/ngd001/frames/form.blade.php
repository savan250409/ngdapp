@extends('layouts.admin')
@php $isEdit = isset($frame); @endphp
@section('title', $isEdit ? 'Edit Frame Record' : 'Add Frame Record')
@section('breadcrumb', 'Frames Module / Frames / ')

@php
    $effectiveApp = ($appCode && $appCode !== '001') ? $appCode : 'ngd001';
    $frameImgUrl = function ($categoryName, $f, $which = 'thumb') use ($effectiveApp) {
        $cat = rawurlencode($categoryName);
        if ($which === 'thumb' && ! empty($f['thumbnail_path'])) {
            return url('upload/' . $effectiveApp . '/frame/' . $cat . '/frame_thumbnail/' . rawurlencode($f['thumbnail_path']));
        }
        return url('upload/' . $effectiveApp . '/frame/' . $cat . '/frame/' . rawurlencode($f['image_path']));
    };
    $existing = $isEdit ? collect($frame->frames ?? [])->sortByDesc('frame_index')->values() : collect();
    $framesListUrl = route('frames.index', array_filter([
        'page' => request('page'), 'search' => request('search'),
        'category_filter' => request('category_filter'), 'per_page' => request('per_page'),
    ]));
@endphp

@push('styles')
<style>
    #frameRows .frame-row { position:relative; }
    #frameRows .drag-handle { cursor:grab; color:var(--muted); font-size:1.2rem; }
    #frameRows .drag-handle:active { cursor:grabbing; }
    #frameRows .pos { width:26px; height:26px; flex-shrink:0; border-radius:7px; background:var(--brand-50); color:var(--brand-600); font-size:.78rem; font-weight:700; display:inline-flex; align-items:center; justify-content:center; }
    #frameRows .sortable-ghost { opacity:.45; }
    #frameRows .sortable-chosen { box-shadow:0 10px 24px rgba(2,6,23,.18); }
</style>
@endpush

@section('content')
<div class="page-head">
    <div>
        <h1>{{ $isEdit ? 'Edit Frame Record' : 'Add Frame Record' }}</h1>
        <p>Add frames, then <b>drag</b> them to set the order (top = first in the API).</p>
    </div>
    <a href="{{ route('frames.index', array_filter(['page' => request('page'), 'search' => request('search'), 'category_filter' => request('category_filter'), 'per_page' => request('per_page')])) }}" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>

<form id="frameForm" method="POST" enctype="multipart/form-data"
      action="{{ $isEdit ? route('frames.update', array_merge(['id' => $frame->id], array_filter(['page' => request('page'), 'search' => request('search'), 'category_filter' => request('category_filter'), 'per_page' => request('per_page')]))) : route('frames.store') }}">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <input type="hidden" name="frame_order" id="frameOrder">
    <input type="hidden" name="page" value="{{ request('page', 1) }}">
    <input type="hidden" name="search" value="{{ request('search') }}">
    <input type="hidden" name="category_filter" value="{{ request('category_filter') }}">
    <input type="hidden" name="per_page" value="{{ request('per_page') }}">

    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Category <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <option value="">— Select category —</option>
                        @foreach($categories as $c)
                            @php
                                $disabled = in_array($c->id, $usedCategoryIds);
                                $selected = $isEdit ? $frame->category_id === $c->id : false;
                            @endphp
                            <option value="{{ $c->id }}" @disabled($disabled && !$selected) @selected($selected)>
                                {{ $c->category_name }}{{ $disabled && !$selected ? ' (already has a record)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Only categories without an existing frame record can be chosen.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-semibold mb-0"><i class="bi bi-images me-1 text-primary"></i>Frames</h6>
                <button type="button" class="btn btn-sm btn-primary" id="addRow"><i class="bi bi-plus-lg me-1"></i> Add Frame</button>
            </div>

            <div id="frameRows">
                @foreach($existing as $k => $f)
                    <div class="builder-row frame-row" data-key="e{{ $k }}">
                        <div class="row g-3 align-items-center">
                            <div class="col-auto d-flex align-items-center gap-2">
                                <span class="drag-handle" title="Drag to reorder"><i class="bi bi-grip-vertical"></i></span>
                                <span class="pos"></span>
                                <input type="hidden" name="keep_image_paths[e{{ $k }}]" value="{{ $f['image_path'] }}">
                                <input type="hidden" name="keep_thumbnail_paths[e{{ $k }}]" value="{{ $f['thumbnail_path'] ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Frame image (.webp)</label>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ $frameImgUrl($frame->category->category_name, $f, 'frame') }}" class="preview-frame thumb-preview" style="width:46px;height:46px;flex:0 0 auto" alt="" loading="lazy" decoding="async">
                                    <input type="file" name="keep_images[e{{ $k }}]" class="form-control form-control-sm input-frame" accept="image/webp">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Thumbnail (.webp)</label>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ !empty($f['thumbnail_path']) ? $frameImgUrl($frame->category->category_name, $f, 'thumb') : '' }}" class="preview-thumb thumb-preview {{ !empty($f['thumbnail_path']) ? '' : 'd-none' }}" style="width:46px;height:46px;flex:0 0 auto" alt="" loading="lazy">
                                    <input type="file" name="keep_thumbnails[e{{ $k }}]" class="form-control form-control-sm input-thumb" accept="image/webp">
                                </div>
                            </div>
                            <div class="col-md-auto ms-auto">
                                <button type="button" class="btn btn-sm btn-light border text-danger remove-row"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="text-muted small mb-0" id="emptyHint">No frames yet. Click <b>Add Frame</b> to add one.</p>
        </div>
    </div>

    <div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> {{ $isEdit ? 'Update Record' : 'Save Record' }}</button>
        <a href="{{ route('frames.index', array_filter(['page' => request('page'), 'search' => request('search'), 'category_filter' => request('category_filter'), 'per_page' => request('per_page')])) }}" class="btn btn-light border">Cancel</a>
    </div>
</form>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
(function () {
    const isEdit = @json($isEdit);
    const rows = document.getElementById('frameRows');
    let idx = 0;

    function newRowHtml(key) {
        return `
        <div class="builder-row frame-row" data-key="${key}">
            <div class="row g-3 align-items-center">
                <div class="col-auto d-flex align-items-center gap-2">
                    <span class="drag-handle" title="Drag to reorder"><i class="bi bi-grip-vertical"></i></span>
                    <span class="pos"></span>
                    <span class="badge-pill badge-soft-success">New</span>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">Frame image (.webp) <span class="text-danger">*</span></label>
                    <div class="d-flex align-items-center gap-2">
                        <img src="" class="preview-frame thumb-preview d-none" style="width:46px;height:46px;flex:0 0 auto" alt="">
                        <input type="file" name="new_images[${key}]" class="form-control form-control-sm input-frame" accept="image/webp" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">Thumbnail (.webp)</label>
                    <div class="d-flex align-items-center gap-2">
                        <img src="" class="preview-thumb thumb-preview d-none" style="width:46px;height:46px;flex:0 0 auto" alt="">
                        <input type="file" name="new_thumbnails[${key}]" class="form-control form-control-sm input-thumb" accept="image/webp">
                    </div>
                </div>
                <div class="col-md-auto ms-auto">
                    <button type="button" class="btn btn-sm btn-light border text-danger remove-row"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
        </div>`;
    }

    function renumber() {
        rows.querySelectorAll('.frame-row').forEach((r, i) => {
            const pos = r.querySelector('.pos');
            if (pos) pos.textContent = i + 1;
        });
        document.getElementById('emptyHint').style.display = rows.querySelectorAll('.frame-row').length ? 'none' : '';
    }

    function addRow() {
        const key = 'n' + (idx++);
        // Newest frame goes first (top).
        rows.insertAdjacentHTML('afterbegin', newRowHtml(key));
        renumber();
    }

    if (window.Sortable) {
        Sortable.create(rows, { handle: '.drag-handle', animation: 150, ghostClass: 'sortable-ghost', chosenClass: 'sortable-chosen', onSort: renumber });
    }

    document.getElementById('addRow').addEventListener('click', addRow);
    rows.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-row');
        if (btn) { btn.closest('.frame-row').remove(); renumber(); }
    });

    // Live preview when a frame image / thumbnail file is chosen.
    rows.addEventListener('change', function (e) {
        const inp = e.target;
        const isFrame = inp.classList && inp.classList.contains('input-frame');
        const isThumb = inp.classList && inp.classList.contains('input-thumb');
        if (!isFrame && !isThumb) return;
        const file = inp.files && inp.files[0];
        const row = inp.closest('.frame-row');
        const target = row.querySelector(isFrame ? '.preview-frame' : '.preview-thumb');
        if (file && target) {
            target.src = URL.createObjectURL(file);
            target.classList.remove('d-none');
        }
    });

    if (!isEdit) addRow(); else renumber();

    document.getElementById('frameForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const keys = Array.from(rows.querySelectorAll('.frame-row')).map(r => r.dataset.key);
        if (keys.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Add at least one frame' });
            return;
        }
        document.getElementById('frameOrder').value = JSON.stringify(keys);

        const form = this;
        const btn = form.querySelector('button[type="submit"]');
        window.setBtnLoading(btn, true);
        $.ajax({
            url: form.action,
            method: 'POST',
            data: new FormData(form),
            processData: false,
            contentType: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function (res) {
                // Errors never reach here; on success go to the list with a toast.
                try { sessionStorage.setItem('ngd_flash', res.message || 'Saved'); } catch (err) {}
                window.location = res.redirect || @json($framesListUrl);
            },
            error: function (xhr) { window.showAjaxError(xhr); window.setBtnLoading(btn, false); }
        });
    });
})();
</script>
@endpush
