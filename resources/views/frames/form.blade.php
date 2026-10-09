@extends('layouts.admin')
@php $isEdit = isset($frame); @endphp
@section('title', $isEdit ? 'Edit Frame Record' : 'Add Frame Record')
@section('breadcrumb', 'Frames Module / Frames / ')

@php
    $frameImgUrl = function ($categoryName, $f, $which = 'thumb') {
        $cat = rawurlencode($categoryName);
        if ($which === 'thumb' && ! empty($f['thumbnail_path'])) {
            return url('upload/frame/' . $cat . '/frame_thumbnail/' . rawurlencode($f['thumbnail_path']));
        }
        return url('upload/frame/' . $cat . '/frame/' . rawurlencode($f['image_path']));
    };
    $existing = $isEdit ? collect($frame->frames ?? [])->sortBy('frame_index')->values() : collect();
@endphp

@section('content')
<div class="page-head">
    <div>
        <h1>{{ $isEdit ? 'Edit Frame Record' : 'Add Frame Record' }}</h1>
        <p>{{ $isEdit ? 'Add, replace, reorder or remove frames in this set.' : 'Pick a category and add one or more frames.' }}</p>
    </div>
    <a href="{{ route('frames.index') }}" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>

<form id="frameForm" method="POST" enctype="multipart/form-data"
      action="{{ $isEdit ? route('frames.update', $frame->id) : route('frames.store') }}">
    @csrf
    @if($isEdit) @method('PUT') @endif

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

    @if($isEdit && $existing->count())
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="fw-semibold mb-3"><i class="bi bi-collection me-1 text-primary"></i>Existing frames</h6>
                <div id="keepRows">
                    @foreach($existing as $k => $f)
                        <div class="builder-row keep-row" data-k="{{ $k }}">
                            <div class="row g-3 align-items-center">
                                <div class="col-auto">
                                    <img src="{{ $frameImgUrl($frame->category->category_name, $f) }}" class="thumb-preview" style="width:60px;height:60px" alt="" loading="lazy" decoding="async">
                                    <input type="hidden" name="keep_image_paths[{{ $k }}]" value="{{ $f['image_path'] }}">
                                    <input type="hidden" name="keep_thumbnail_paths[{{ $k }}]" value="{{ $f['thumbnail_path'] ?? '' }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Replace image (.webp)</label>
                                    <input type="file" name="keep_images[{{ $k }}]" class="form-control form-control-sm" accept="image/webp">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Replace thumbnail (.webp)</label>
                                    <input type="file" name="keep_thumbnails[{{ $k }}]" class="form-control form-control-sm" accept="image/webp">
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label small text-muted">Count</label>
                                    <input type="number" name="keep_counts[{{ $k }}]" class="form-control form-control-sm" value="{{ (int)($f['count'] ?? 0) }}" min="0">
                                </div>
                                <div class="col-6 col-md-1">
                                    <label class="form-label small text-muted d-block">Pro</label>
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="keep_is_pros[{{ $k }}]" value="0">
                                        <input type="checkbox" class="form-check-input" role="switch" name="keep_is_pros[{{ $k }}]" value="1" @checked(!empty($f['is_pro']))>
                                    </div>
                                </div>
                                <div class="col-md-auto ms-auto">
                                    <button type="button" class="btn btn-sm btn-light border text-danger remove-keep"><i class="bi bi-trash me-1"></i>Remove</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-semibold mb-0"><i class="bi bi-plus-square me-1 text-primary"></i>{{ $isEdit ? 'Add new frames' : 'Frames' }}</h6>
                <button type="button" class="btn btn-sm btn-primary" id="addRow"><i class="bi bi-plus-lg me-1"></i> Add Frame</button>
            </div>
            <div id="rows"></div>
            <p class="text-muted small mb-0" id="emptyHint">No new frames added yet. Click <b>Add Frame</b> to add one.</p>
        </div>
    </div>

    <div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> {{ $isEdit ? 'Update Record' : 'Save Record' }}</button>
        <a href="{{ route('frames.index') }}" class="btn btn-light border">Cancel</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const isEdit = @json($isEdit);
    // In edit mode new frames use the new_* prefix; in create mode they use the plain prefix.
    const P = isEdit
        ? { img:'new_images', thumb:'new_thumbnails', count:'new_counts', pro:'new_is_pros' }
        : { img:'images',     thumb:'thumbnails',     count:'counts',     pro:'is_pros' };

    let idx = 0;

    function rowHtml(i) {
        return `
        <div class="builder-row" data-i="${i}">
            <div class="row g-3 align-items-center">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Frame image (.webp) <span class="text-danger">*</span></label>
                    <input type="file" name="${P.img}[${i}]" class="form-control form-control-sm row-image" accept="image/webp" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Thumbnail (.webp)</label>
                    <input type="file" name="${P.thumb}[${i}]" class="form-control form-control-sm" accept="image/webp">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted">Count</label>
                    <input type="number" name="${P.count}[${i}]" class="form-control form-control-sm" value="0" min="0">
                </div>
                <div class="col-6 col-md-1">
                    <label class="form-label small text-muted d-block">Pro</label>
                    <div class="form-check form-switch">
                        <input type="hidden" name="${P.pro}[${i}]" value="0">
                        <input type="checkbox" class="form-check-input" role="switch" name="${P.pro}[${i}]" value="1">
                    </div>
                </div>
                <div class="col-md-auto ms-auto">
                    <button type="button" class="btn btn-sm btn-light border text-danger remove-row"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
        </div>`;
    }

    function syncHint() {
        $('#emptyHint').toggle($('#rows .builder-row').length === 0);
    }

    function addRow() {
        $('#rows').append(rowHtml(idx));
        idx++;
        syncHint();
    }

    $('#addRow').on('click', addRow);
    $(document).on('click', '.remove-row', function () { $(this).closest('.builder-row').remove(); syncHint(); });
    $(document).on('click', '.remove-keep', function () { $(this).closest('.keep-row').remove(); });

    // Start with one empty row on create (edit may start with none and rely on existing).
    if (!isEdit) addRow(); else syncHint();

    // Guard: require at least one frame (existing kept OR new) before submit.
    $('#frameForm').on('submit', function (e) {
        const newRows  = $('#rows .builder-row').length;
        const keepRows = $('#keepRows .keep-row').length;
        if (newRows + keepRows === 0) {
            e.preventDefault();
            Swal.fire({ icon:'warning', title:'Add at least one frame' });
        }
    });
})();
</script>
@endpush
