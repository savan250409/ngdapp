@extends('layouts.admin')
@section('title', 'Frames')
@section('breadcrumb', 'Frames Module / ')

@php
    $effectiveApp = ($appCode && $appCode !== '001') ? $appCode : 'ngd001';
    $frameImgUrl = function ($categoryName, $f) use ($effectiveApp) {
        $cat = rawurlencode($categoryName);
        if (! empty($f['thumbnail_path'])) {
            return url('upload/' . $effectiveApp . '/frame/' . $cat . '/frame_thumbnail/' . rawurlencode($f['thumbnail_path']));
        }
        return url('upload/' . $effectiveApp . '/frame/' . $cat . '/frame/' . rawurlencode($f['image_path']));
    };
@endphp

{{-- Defined before @section('content') so @yield('table')/@yield('pagination') resolve on full-page render. --}}
@section('table')
<table class="table align-middle">
    <thead>
        <tr>
            <th style="width:60px">#</th>
            <th>Category</th>
            <th>Frames</th>
            <th class="text-end">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($frames as $record)
            <tr>
                <td class="text-muted">{{ $record->id }}</td>
                <td class="fw-semibold">{{ $record->category->category_name ?? '—' }}</td>
                <td>
                    @php $count = count($record->frames ?? []); @endphp
                    <div class="frame-strip d-flex flex-wrap align-items-center gap-2">
                        @foreach(collect($record->frames ?? [])->sortBy('frame_index')->take(3) as $f)
                            <img src="{{ $frameImgUrl($record->category->category_name ?? '', $f) }}" alt="" loading="lazy" decoding="async" width="42" height="42">
                        @endforeach
                        @if($count > 0)
                            <button type="button" class="btn btn-sm btn-light border btn-viewmore"
                                    data-category-id="{{ $record->category_id }}"
                                    data-category-name="{{ $record->category->category_name ?? '' }}">
                                <i class="bi bi-grid-3x3-gap me-1"></i>View more @if($count > 3)<span class="badge-pill badge-soft-muted ms-1">+{{ $count - 3 }}</span>@endif
                            </button>
                        @else
                            <span class="text-muted small">No frames</span>
                        @endif
                    </div>
                </td>
                <td class="text-end">
                    <a href="{{ route('frames.edit', $record->id) }}" class="btn btn-sm btn-light border btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                    <form class="d-inline form-delete" method="POST" action="{{ route('frames.destroy', $record->id) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-light border btn-icon text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4"><div class="empty-state"><i class="bi bi-images"></i>No frame records found.</div></td></tr>
        @endforelse
    </tbody>
</table>
@endsection

@section('pagination')
<div class="list-foot">
    <span class="text-muted small">
        Showing {{ $frames->firstItem() ?? 0 }}–{{ $frames->lastItem() ?? 0 }} of {{ $frames->total() }}
    </span>
    {{ $frames->onEachSide(1)->links() }}
</div>
@endsection

@section('content')
<div class="page-head">
    <div>
        <h1>Frames</h1>
        <p>One ordered set of frames per category.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-light border" id="btnIndex"><i class="bi bi-list-ol me-1"></i> Index</button>
        <a href="{{ route('frames.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add Frame Record</a>
    </div>
</div>

<div class="card data-card">
    <div class="card-body">
        <div class="toolbar">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-icon">
                        <i class="bi bi-search"></i>
                        <input type="text" id="search" class="form-control" placeholder="Search by category name..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <select id="category_filter" class="form-select">
                        <option value="">All categories</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" @selected((string)request('category_filter')===(string)$c->id)>{{ $c->category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <select id="per_page" class="form-select">
                        @foreach([10,25,50,100] as $pp)
                            <option value="{{ $pp }}" @selected((int)request('per_page',10)===$pp)>{{ $pp }} / page</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 text-md-end">
                    <span class="badge-pill badge-soft-muted">Total: <b id="totalCount">{{ $frames->total() }}</b></span>
                </div>
            </div>
        </div>

        <div class="table-responsive" id="tableWrap">
            @yield('table')
        </div>

        <div id="paginationWrap">
            @yield('pagination')
        </div>
    </div>
</div>

<!-- Index / Reorder frames modal -->
<div class="modal fade" id="framesIndexModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content" style="border:none;border-radius:14px;">
            <div class="modal-header border-0 pb-1">
                <div>
                    <h5 class="modal-title fw-semibold mb-0">Frame Order</h5>
                    <p class="text-muted small mb-0">Pick a category, then drag frames — top is first in the API.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small fw-semibold">Category</label>
                <select id="idxCategory" class="form-select mb-3">
                    <option value="">— Select a category —</option>
                    @foreach($categories as $c)
                        @if(in_array($c->id, $usedCategoryIds))
                            <option value="{{ $c->id }}">{{ $c->category_name }}</option>
                        @endif
                    @endforeach
                </select>

                <div id="idxState" class="empty-state"><i class="bi bi-arrow-up-circle"></i>Select a category to load its frames.</div>
                <div id="idxFramesGrid" class="d-flex flex-wrap gap-2"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveFramesOrderBtn" disabled><i class="bi bi-check-lg me-1"></i>Save Order</button>
            </div>
        </div>
    </div>
</div>

<!-- View more: all frames + thumbnails of one record -->
<div class="modal fade" id="viewFramesModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content" style="border:none;border-radius:14px;">
            <div class="modal-header border-0 pb-1">
                <div>
                    <h5 class="modal-title fw-semibold mb-0" id="vmTitle">Frames</h5>
                    <p class="text-muted small mb-0" id="vmSubtitle">All frames and thumbnails in this category.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="vmState" class="empty-state"><i class="bi bi-hourglass-split"></i>Loading…</div>
                <div id="vmGrid" class="row g-3"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    #idxFramesGrid .idx-frame { position:relative; width:92px; border:1px solid var(--line); border-radius:10px; padding:6px; background:var(--surface); cursor:grab; text-align:center; }
    #idxFramesGrid .idx-frame:active { cursor:grabbing; }
    #idxFramesGrid .idx-frame img { width:100%; height:78px; object-fit:cover; border-radius:7px; background:var(--surface-2); }
    #idxFramesGrid .idx-frame .pos { position:absolute; top:-8px; left:-8px; width:24px; height:24px; border-radius:50%; background:var(--brand-600); color:#fff; font-size:.72rem; font-weight:700; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 6px rgba(79,70,229,.5); }
    #idxFramesGrid .idx-frame .pro { position:absolute; top:4px; right:4px; }
    #idxFramesGrid .sortable-ghost { opacity:.4; }

    #vmGrid .vm-card { border:1px solid var(--line); border-radius:11px; padding:10px; background:var(--surface); height:100%; }
    #vmGrid .vm-imgs { display:flex; gap:10px; }
    #vmGrid .vm-col { flex:1; text-align:center; }
    #vmGrid .vm-label { font-size:.68rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); margin-bottom:4px; }
    #vmGrid .vm-col img { width:100%; height:92px; object-fit:cover; border-radius:8px; border:1px solid var(--line); background:var(--surface-2); }
    #vmGrid .vm-none { height:92px; border:1px dashed var(--line); border-radius:8px; display:flex; align-items:center; justify-content:center; color:var(--muted); font-size:.74rem; background:var(--surface-2); }
    #vmGrid .vm-meta { display:flex; align-items:center; justify-content:space-between; margin-top:8px; }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
(function () {
    const listUrl = @json(route('frames.index'));
    const orderUrl = @json(route('frames.order'));
    const framesUrlBase = @json(url('frames/category'));

    const grid = document.getElementById('idxFramesGrid');
    const stateEl = document.getElementById('idxState');
    const saveBtn = document.getElementById('saveFramesOrderBtn');
    let sortable = null;

    function renumber() {
        grid.querySelectorAll('.idx-frame .pos').forEach((p, i) => { p.textContent = i + 1; });
    }

    $('#btnIndex').on('click', function () {
        new bootstrap.Modal('#framesIndexModal').show();
    });

    // View more — show all frames + thumbnails for a record
    $(document).on('click', '.btn-viewmore', function () {
        const id = $(this).data('category-id');
        const name = $(this).data('category-name');
        $('#vmTitle').text(name ? name + ' — Frames' : 'Frames');
        const vmGrid = document.getElementById('vmGrid');
        const vmState = document.getElementById('vmState');
        vmGrid.innerHTML = '';
        vmState.style.display = '';
        vmState.innerHTML = '<i class="bi bi-hourglass-split"></i>Loading…';
        new bootstrap.Modal('#viewFramesModal').show();
        $.get(framesUrlBase + '/' + id + '/frames', function (res) {
            const frames = res.frames || [];
            if (!frames.length) { vmState.innerHTML = '<i class="bi bi-inbox"></i>No frames in this category.'; return; }
            vmState.style.display = 'none';
            vmGrid.innerHTML = frames.map((f, i) => `
                <div class="col-sm-6 col-lg-4">
                    <div class="vm-card">
                        <div class="vm-imgs">
                            <div class="vm-col">
                                <div class="vm-label">Frame</div>
                                <img src="${f.frame_url}" alt="" loading="lazy">
                            </div>
                            <div class="vm-col">
                                <div class="vm-label">Thumbnail</div>
                                ${f.thumbnail_url ? `<img src="${f.thumbnail_url}" alt="" loading="lazy">` : '<div class="vm-none">None</div>'}
                            </div>
                        </div>
                        <div class="vm-meta">
                            <span class="text-muted small">#${i + 1}</span>
                        </div>
                    </div>
                </div>`).join('');
        }).fail(function () {
            vmState.innerHTML = '<i class="bi bi-exclamation-triangle"></i>Could not load frames.';
        });
    });

    $('#idxCategory').on('change', function () {
        const id = this.value;
        grid.innerHTML = '';
        saveBtn.disabled = true;
        if (!id) { stateEl.style.display = ''; stateEl.innerHTML = '<i class="bi bi-arrow-up-circle"></i>Select a category to load its frames.'; return; }
        stateEl.style.display = '';
        stateEl.innerHTML = '<i class="bi bi-hourglass-split"></i>Loading…';
        $.get(framesUrlBase + '/' + id + '/frames', function (res) {
            const frames = res.frames || [];
            if (!frames.length) { stateEl.innerHTML = '<i class="bi bi-inbox"></i>No frames in this category.'; return; }
            stateEl.style.display = 'none';
            grid.innerHTML = frames.map((f, i) => `
                <div class="idx-frame" data-path="${f.image_path}">
                    <span class="pos">${i + 1}</span>
                    <img src="${f.thumb_url}" alt="" loading="lazy">
                </div>`).join('');
            if (sortable) sortable.destroy();
            sortable = Sortable.create(grid, { animation: 150, ghostClass: 'sortable-ghost', onSort: renumber });
            saveBtn.disabled = false;
        }).fail(function () {
            stateEl.innerHTML = '<i class="bi bi-exclamation-triangle"></i>Could not load frames.';
        });
    });

    $('#saveFramesOrderBtn').on('click', function () {
        const id = $('#idxCategory').val();
        const order = Array.from(grid.querySelectorAll('.idx-frame')).map(el => el.dataset.path);
        if (!id || !order.length) return;
        const btn = this; btn.disabled = true;
        $.post(orderUrl, { category_id: id, order: order }, function (res) {
            bootstrap.Modal.getInstance(document.getElementById('framesIndexModal'))?.hide();
            Toast.fire({ icon: 'success', title: res.message || 'Frame order saved' });
            reload();
        }).fail(function () {
            Swal.fire({ icon: 'error', title: 'Could not save order' });
        }).always(function () { btn.disabled = false; });
    });

    function reload(page) {
        const params = {
            search: $('#search').val(),
            category_filter: $('#category_filter').val(),
            per_page: $('#per_page').val(),
        };
        if (page) params.page = page;
        $.get(listUrl, params, function (res) {
            $('#tableWrap').html(res.table);
            $('#paginationWrap').html(res.pagination);
            $('#totalCount').text(res.total);
        });
    }

    let t;
    $('#search').on('input', function () { clearTimeout(t); t = setTimeout(() => reload(), 350); });
    $('#category_filter, #per_page').on('change', () => reload());

    $(document).on('click', '#paginationWrap a', function (e) {
        e.preventDefault();
        const url = new URL(this.href);
        reload(url.searchParams.get('page'));
    });

    $(document).on('submit', '.form-delete', function (e) {
        e.preventDefault();
        const form = this;
        Swal.fire({
            title: 'Delete this frame record?',
            text: 'All its frame images and thumbnails will be removed.',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, delete it'
        }).then((r) => { if (r.isConfirmed) form.submit(); });
    });
})();
</script>
@endpush
