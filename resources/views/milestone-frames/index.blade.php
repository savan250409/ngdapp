@extends('layouts.admin')
@section('title', 'Milestone Frames')
@section('breadcrumb', 'Frames Module / ')

@php
    $mThumb = function ($record, $f) use ($appCode) {
        $base = 'upload/' . $appCode . '/milestone_frame/' . $record->gender_slug . '/' . (int) $record->month;
        if (! empty($f['thumbnail_path'])) {
            return url($base . '/frame_thumbnail/' . rawurlencode($f['thumbnail_path']));
        }
        return url($base . '/frame/' . rawurlencode($f['image_path']));
    };
@endphp

{{-- Sections defined before @section('content') so @yield resolves on full-page render. --}}
@section('table')
<table class="table align-middle">
    <thead>
        <tr>
            <th style="width:60px">#</th>
            <th>Gender</th>
            <th>Month</th>
            <th>Frames</th>
            <th class="text-end">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($frames as $record)
            <tr>
                <td class="text-muted">{{ $record->id }}</td>
                <td class="fw-semibold">
                    @if($record->gender == 1)
                        <span class="badge-pill" style="background:rgba(59,130,246,.16);color:#3b82f6">Boy</span>
                    @else
                        <span class="badge-pill" style="background:rgba(236,72,153,.16);color:#ec4899">Girl</span>
                    @endif
                </td>
                <td>Month {{ $record->month }}</td>
                <td>
                    @php $count = count($record->frames ?? []); @endphp
                    <div class="frame-strip d-flex flex-wrap align-items-center gap-2">
                        @foreach(collect($record->frames ?? [])->sortBy('frame_index')->take(3) as $f)
                            <img src="{{ $mThumb($record, $f) }}" alt="" loading="lazy" decoding="async" width="42" height="42">
                        @endforeach
                        @if($count > 0)
                            <button type="button" class="btn btn-sm btn-light border btn-viewmore"
                                    data-id="{{ $record->id }}"
                                    data-label="{{ $record->gender_label }} · Month {{ $record->month }}">
                                <i class="bi bi-grid-3x3-gap me-1"></i>View more @if($count > 3)<span class="badge-pill badge-soft-muted ms-1">+{{ $count - 3 }}</span>@endif
                            </button>
                        @else
                            <span class="text-muted small">No frames</span>
                        @endif
                    </div>
                </td>
                <td class="text-end">
                    <a href="{{ route('milestone-frames.edit', $record->id) }}" class="btn btn-sm btn-light border btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                    <form class="d-inline form-delete" method="POST" action="{{ route('milestone-frames.destroy', $record->id) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-light border btn-icon text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="empty-state"><i class="bi bi-calendar-heart"></i>No milestone frame records found.</div></td></tr>
        @endforelse
    </tbody>
</table>
@endsection

@section('pagination')
<div class="list-foot">
    <span class="text-muted small">Showing {{ $frames->firstItem() ?? 0 }}–{{ $frames->lastItem() ?? 0 }} of {{ $frames->total() }}</span>
    {{ $frames->onEachSide(1)->links() }}
</div>
@endsection

@section('content')
<div class="page-head">
    <div>
        <h1>Milestone Frames</h1>
        <p>Baby-photo frames per gender &amp; month.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-light border" id="btnIndex"><i class="bi bi-list-ol me-1"></i> Index</button>
        <a href="{{ route('milestone-frames.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add Record</a>
    </div>
</div>

<div class="card data-card">
    <div class="card-body">
        <div class="toolbar">
            <div class="row g-2 align-items-center">
                <div class="col-6 col-md-3">
                    <select id="gender_filter" class="form-select">
                        <option value="">All genders</option>
                        @foreach($genders as $gid => $gname)
                            <option value="{{ $gid }}" @selected((string)request('gender_filter')===(string)$gid)>{{ $gname }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <select id="month_filter" class="form-select">
                        <option value="">All months</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected((string)request('month_filter')===(string)$m)>Month {{ $m }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <select id="per_page" class="form-select">
                        @foreach([10,25,50,100] as $pp)
                            <option value="{{ $pp }}" @selected((int)request('per_page',10)===$pp)>{{ $pp }} / page</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 text-md-end">
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
<div class="modal fade" id="msIndexModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content" style="border:none;border-radius:14px;">
            <div class="modal-header border-0 pb-1">
                <div>
                    <h5 class="modal-title fw-semibold mb-0">Frame Order</h5>
                    <p class="text-muted small mb-0">Pick a record, then drag frames — top is first in the API.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small fw-semibold">Record</label>
                <select id="idxRecord" class="form-select mb-3">
                    <option value="">— Select a record —</option>
                    @foreach($allRecords as $r)
                        <option value="{{ $r->id }}">{{ $r->gender_label }} · Month {{ $r->month }}</option>
                    @endforeach
                </select>
                <div id="idxState" class="empty-state"><i class="bi bi-arrow-up-circle"></i>Select a record to load its frames.</div>
                <div id="idxFramesGrid" class="d-flex flex-wrap gap-2"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveMsOrderBtn" disabled><i class="bi bi-check-lg me-1"></i>Save Order</button>
            </div>
        </div>
    </div>
</div>

<!-- View more -->
<div class="modal fade" id="msViewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content" style="border:none;border-radius:14px;">
            <div class="modal-header border-0 pb-1">
                <div>
                    <h5 class="modal-title fw-semibold mb-0" id="vmTitle">Frames</h5>
                    <p class="text-muted small mb-0">All frames and thumbnails in this record.</p>
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
    #idxFramesGrid .sortable-ghost { opacity:.4; }
    #vmGrid .vm-card { border:1px solid var(--line); border-radius:11px; padding:10px; background:var(--surface); height:100%; }
    #vmGrid .vm-imgs { display:flex; gap:10px; }
    #vmGrid .vm-col { flex:1; text-align:center; }
    #vmGrid .vm-label { font-size:.68rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); margin-bottom:4px; }
    #vmGrid .vm-col img { width:100%; height:92px; object-fit:cover; border-radius:8px; border:1px solid var(--line); background:var(--surface-2); }
    #vmGrid .vm-none { height:92px; border:1px dashed var(--line); border-radius:8px; display:flex; align-items:center; justify-content:center; color:var(--muted); font-size:.74rem; background:var(--surface-2); }
    #vmGrid .vm-meta { margin-top:8px; }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
(function () {
    const listUrl = @json(route('milestone-frames.index'));
    const orderUrl = @json(route('milestone-frames.order'));
    const framesUrlBase = @json(url('milestone-frames'));

    function reload(page) {
        const params = {
            gender_filter: $('#gender_filter').val(),
            month_filter: $('#month_filter').val(),
            per_page: $('#per_page').val(),
        };
        if (page) params.page = page;
        $.get(listUrl, params, function (res) {
            $('#tableWrap').html(res.table);
            $('#paginationWrap').html(res.pagination);
            $('#totalCount').text(res.total);
        });
    }

    $('#gender_filter, #month_filter, #per_page').on('change', () => reload());
    $(document).on('click', '#paginationWrap a', function (e) {
        e.preventDefault();
        reload(new URL(this.href).searchParams.get('page'));
    });

    // ----- Index reorder popup -----
    const grid = document.getElementById('idxFramesGrid');
    const stateEl = document.getElementById('idxState');
    const saveBtn = document.getElementById('saveMsOrderBtn');
    let sortable = null;
    function renumber() { grid.querySelectorAll('.idx-frame .pos').forEach((p, i) => { p.textContent = i + 1; }); }

    $('#btnIndex').on('click', () => new bootstrap.Modal('#msIndexModal').show());

    $('#idxRecord').on('change', function () {
        const id = this.value;
        grid.innerHTML = '';
        saveBtn.disabled = true;
        if (!id) { stateEl.style.display = ''; stateEl.innerHTML = '<i class="bi bi-arrow-up-circle"></i>Select a record to load its frames.'; return; }
        stateEl.style.display = '';
        stateEl.innerHTML = '<i class="bi bi-hourglass-split"></i>Loading…';
        $.get(framesUrlBase + '/' + id + '/frames', function (res) {
            const frames = res.frames || [];
            if (!frames.length) { stateEl.innerHTML = '<i class="bi bi-inbox"></i>No frames in this record.'; return; }
            stateEl.style.display = 'none';
            grid.innerHTML = frames.map((f, i) => `
                <div class="idx-frame" data-path="${f.image_path}">
                    <span class="pos">${i + 1}</span>
                    <img src="${f.thumb_url}" alt="" loading="lazy">
                </div>`).join('');
            if (sortable) sortable.destroy();
            sortable = Sortable.create(grid, { animation: 150, ghostClass: 'sortable-ghost', onSort: renumber });
            saveBtn.disabled = false;
        }).fail(() => { stateEl.innerHTML = '<i class="bi bi-exclamation-triangle"></i>Could not load frames.'; });
    });

    $('#saveMsOrderBtn').on('click', function () {
        const id = $('#idxRecord').val();
        const order = Array.from(grid.querySelectorAll('.idx-frame')).map(el => el.dataset.path);
        if (!id || !order.length) return;
        const btn = this; btn.disabled = true;
        $.post(orderUrl, { id: id, order: order }, function (res) {
            bootstrap.Modal.getInstance(document.getElementById('msIndexModal'))?.hide();
            Toast.fire({ icon: 'success', title: res.message || 'Frame order saved' });
            reload();
        }).fail(() => Swal.fire({ icon: 'error', title: 'Could not save order' })).always(() => { btn.disabled = false; });
    });

    // ----- View more -----
    $(document).on('click', '.btn-viewmore', function () {
        const id = $(this).data('id');
        $('#vmTitle').text($(this).data('label') || 'Frames');
        const vmGrid = document.getElementById('vmGrid');
        const vmState = document.getElementById('vmState');
        vmGrid.innerHTML = '';
        vmState.style.display = '';
        vmState.innerHTML = '<i class="bi bi-hourglass-split"></i>Loading…';
        new bootstrap.Modal('#msViewModal').show();
        $.get(framesUrlBase + '/' + id + '/frames', function (res) {
            const frames = res.frames || [];
            if (!frames.length) { vmState.innerHTML = '<i class="bi bi-inbox"></i>No frames in this record.'; return; }
            vmState.style.display = 'none';
            vmGrid.innerHTML = frames.map((f, i) => `
                <div class="col-sm-6 col-lg-4">
                    <div class="vm-card">
                        <div class="vm-imgs">
                            <div class="vm-col"><div class="vm-label">Frame</div><img src="${f.frame_url}" alt="" loading="lazy"></div>
                            <div class="vm-col"><div class="vm-label">Thumbnail</div>${f.thumbnail_url ? `<img src="${f.thumbnail_url}" alt="" loading="lazy">` : '<div class="vm-none">None</div>'}</div>
                        </div>
                        <div class="vm-meta"><span class="text-muted small">#${i + 1}</span></div>
                    </div>
                </div>`).join('');
        }).fail(() => { vmState.innerHTML = '<i class="bi bi-exclamation-triangle"></i>Could not load frames.'; });
    });

    // ----- Delete — confirm then success alert (AJAX) -----
    $(document).on('submit', '.form-delete', function (e) {
        e.preventDefault();
        const form = this;
        Swal.fire({
            title: 'Delete this record?',
            text: 'All its frame images and thumbnails will be removed.',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, delete it'
        }).then((r) => {
            if (!r.isConfirmed) return;
            $.ajax({
                url: form.action, method: 'POST', data: new FormData(form),
                processData: false, contentType: false,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function (res) {
                    Swal.fire({ icon: 'success', title: 'Deleted!', text: res.message || 'Record deleted.', timer: 1800, showConfirmButton: false });
                    reload();
                },
                error: function (xhr) { window.showAjaxError(xhr); }
            });
        });
    });
})();
</script>
@endpush
