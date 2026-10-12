@extends('layouts.admin')
@php $isEdit = isset($frame); @endphp
@section('title', $isEdit ? 'Edit Milestone Record' : 'Add Milestone Record')
@section('breadcrumb', 'Frames Module / Milestone Frames / ')

@php
    $mUrl = function ($record, $f, $which = 'thumb') {
        $base = 'upload/milestone_frame/' . $record->gender_slug . '/' . (int) $record->month;
        if ($which === 'thumb' && ! empty($f['thumbnail_path'])) {
            return url($base . '/frame_thumbnail/' . rawurlencode($f['thumbnail_path']));
        }
        return url($base . '/frame/' . rawurlencode($f['image_path']));
    };
    $existing = $isEdit ? collect($frame->frames ?? [])->sortBy('frame_index')->values() : collect();
@endphp

@push('styles')
<style>
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
        <h1>{{ $isEdit ? 'Edit Milestone Record' : 'Add Milestone Record' }}</h1>
        <p>Pick a gender &amp; month, add frames, then <b>drag</b> to set the order.</p>
    </div>
    <a href="{{ route('milestone-frames.index') }}" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>

<form id="frameForm" method="POST" enctype="multipart/form-data"
      action="{{ $isEdit ? route('milestone-frames.update', $frame->id) : route('milestone-frames.store') }}">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <input type="hidden" name="frame_order" id="frameOrder">

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Gender <span class="text-danger">*</span></label>
                    <select name="gender" id="gender" class="form-select" required>
                        <option value="">— Select gender —</option>
                        @foreach($genders as $gid => $gname)
                            <option value="{{ $gid }}" @selected($isEdit && $frame->gender == $gid)>{{ $gname }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Month <span class="text-danger">*</span></label>
                    <select name="month" id="month" class="form-select" required>
                        <option value="">— Select month —</option>
                        @foreach($months as $m)
                            <option value="{{ $m }}" @selected($isEdit && $frame->month == $m)>Month {{ $m }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-text">One record per gender + month. Existing combinations can't be reused.</div>
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
                                    <img src="{{ $mUrl($frame, $f, 'frame') }}" class="preview-frame thumb-preview" style="width:46px;height:46px;flex:0 0 auto" alt="" loading="lazy" decoding="async">
                                    <input type="file" name="keep_images[e{{ $k }}]" class="form-control form-control-sm input-frame" accept="image/webp">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Thumbnail (.webp)</label>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ !empty($f['thumbnail_path']) ? $mUrl($frame, $f, 'thumb') : '' }}" class="preview-thumb thumb-preview {{ !empty($f['thumbnail_path']) ? '' : 'd-none' }}" style="width:46px;height:46px;flex:0 0 auto" alt="" loading="lazy">
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
        <a href="{{ route('milestone-frames.index') }}" class="btn btn-light border">Cancel</a>
    </div>
</form>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
(function () {
    const isEdit = @json($isEdit);
    const usedCombos = @json($usedCombos);
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
        rows.insertAdjacentHTML('beforeend', newRowHtml('n' + (idx++)));
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

    // Live preview on file select.
    rows.addEventListener('change', function (e) {
        const inp = e.target;
        const isFrame = inp.classList && inp.classList.contains('input-frame');
        const isThumb = inp.classList && inp.classList.contains('input-thumb');
        if (!isFrame && !isThumb) return;
        const file = inp.files && inp.files[0];
        const target = inp.closest('.frame-row').querySelector(isFrame ? '.preview-frame' : '.preview-thumb');
        if (file && target) { target.src = URL.createObjectURL(file); target.classList.remove('d-none'); }
    });

    if (!isEdit) addRow(); else renumber();

    document.getElementById('frameForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const keys = Array.from(rows.querySelectorAll('.frame-row')).map(r => r.dataset.key);
        if (keys.length === 0) { Swal.fire({ icon: 'warning', title: 'Add at least one frame' }); return; }

        // Client-side duplicate (gender+month) hint.
        const combo = $('#gender').val() + '-' + $('#month').val();
        if ($('#gender').val() && $('#month').val() && usedCombos.includes(combo)) {
            Swal.fire({ icon: 'warning', title: 'This gender + month already has a record' });
            return;
        }

        document.getElementById('frameOrder').value = JSON.stringify(keys);
        const form = this;
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        $.ajax({
            url: form.action, method: 'POST', data: new FormData(form),
            processData: false, contentType: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function (res) {
                try { sessionStorage.setItem('ngd_flash', res.message || 'Saved'); } catch (err) {}
                window.location = @json(route('milestone-frames.index'));
            },
            error: function (xhr) { window.showAjaxError(xhr); btn.disabled = false; }
        });
    });
})();
</script>
@endpush
