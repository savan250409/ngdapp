@extends('layouts.admin')
@section('title', 'Frame Categories')
@section('breadcrumb', 'Frames Module / ')

@php
    $effectiveApp = ($appCode && $appCode !== '001') ? $appCode : 'ngd001';
    $thumbUrl = function ($cat) use ($effectiveApp) {
        if (! $cat->category_image) return null;
        return url('upload/' . $effectiveApp . '/frame/' . rawurlencode($cat->category_name) . '/category_thumbnail_frame/' . rawurlencode($cat->category_image));
    };
@endphp

@push('styles')
<style>
    #sortableCategories .sort-item {
        display:flex; align-items:center; gap:12px; padding:9px 12px; margin-bottom:8px;
        border:1px solid var(--line); border-radius:11px; background:var(--surface); transition:box-shadow .15s;
    }
    #sortableCategories .sort-item:hover { box-shadow:var(--shadow); }
    #sortableCategories .drag-handle { cursor:grab; color:var(--muted); font-size:1.15rem; }
    #sortableCategories .drag-handle:active { cursor:grabbing; }
    #sortableCategories .pos {
        width:26px; height:26px; flex-shrink:0; border-radius:7px; background:var(--brand-50); color:var(--brand-600);
        font-size:.78rem; font-weight:700; display:inline-flex; align-items:center; justify-content:center;
    }
    #sortableCategories .sortable-ghost { opacity:.45; }
    #sortableCategories .sortable-chosen { box-shadow:0 8px 20px rgba(2,6,23,.18); }
</style>
@endpush

{{-- Defined before @section('content') so @yield('table')/@yield('pagination') resolve on full-page render. --}}
@section('table')
<table class="table align-middle">
    <thead>
        <tr>
            <th style="width:60px">#</th>
            <th>Category</th>
            <th>Thumbnail</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($categories as $cat)
            <tr>
                <td class="text-muted">{{ $cat->id }}</td>
                <td class="fw-semibold">{{ $cat->category_name }}</td>
                <td>
                    @if($thumbUrl($cat))
                        <img src="{{ $thumbUrl($cat) }}" class="thumb-preview" alt="" loading="lazy" decoding="async" width="46" height="46">
                    @else
                        <span class="thumb-empty"><i class="bi bi-image"></i></span>
                    @endif
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input toggle-status" type="checkbox" role="switch" data-id="{{ $cat->id }}" {{ $cat->status ? 'checked' : '' }}>
                        </div>
                        <span class="badge-pill {{ $cat->status ? 'badge-soft-success' : 'badge-soft-muted' }} status-label">{{ $cat->status ? 'Active' : 'Inactive' }}</span>
                    </div>
                </td>
                <td class="text-end">
                    <button class="btn btn-sm btn-light border btn-icon btn-edit"
                            data-id="{{ $cat->id }}"
                            data-name="{{ $cat->category_name }}"
                            data-status="{{ $cat->status }}"
                            data-thumb="{{ $thumbUrl($cat) }}" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <form class="d-inline form-delete" method="POST" action="{{ route('frame-categories.destroy', $cat->id) }}">
                        @csrf @method('DELETE')
                        <input type="hidden" name="page" class="input-current-page" value="{{ $categories->currentPage() }}">
                        <button type="submit" class="btn btn-sm btn-light border btn-icon text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="empty-state"><i class="bi bi-collection"></i>No frame categories found.</div></td></tr>
        @endforelse
    </tbody>
</table>
@endsection

@section('pagination')
<div class="list-foot">
    <span class="text-muted small">
        Showing {{ $categories->firstItem() ?? 0 }}–{{ $categories->lastItem() ?? 0 }} of {{ $categories->total() }}
    </span>
    {{ $categories->onEachSide(1)->links() }}
</div>
@endsection

@section('content')
<div class="page-head">
    <div>
        <h1>Frame Categories</h1>
        <p>Manage categories, their thumbnails and visibility.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-light border" id="btnIndex"><i class="bi bi-list-ol me-1"></i> Index</button>
        <button class="btn btn-primary" id="btnAdd"><i class="bi bi-plus-lg me-1"></i> Add Category</button>
    </div>
</div>

<div class="card data-card">
    <div class="card-body">
        <div class="toolbar">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-icon">
                        <i class="bi bi-search"></i>
                        <input type="text" id="search" class="form-control" placeholder="Search by name..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <select id="status_filter" class="form-select">
                        <option value="">All statuses</option>
                        <option value="1" @selected(request('status_filter')==='1')>Active</option>
                        <option value="0" @selected(request('status_filter')==='0')>Inactive</option>
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
                    <span class="badge-pill badge-soft-muted">Total: <b id="totalCount">{{ $categories->total() }}</b></span>
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

<!-- Add / Edit modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="categoryForm" method="POST" enctype="multipart/form-data" action="{{ route('frame-categories.store') }}">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <input type="hidden" name="page" id="categoryFormPage" value="{{ $categories->currentPage() }}">
            <div class="modal-content" style="border:none;border-radius:14px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold" id="modalTitle">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="category_name" id="category_name" class="form-control" placeholder="e.g. Birthday" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Thumbnail <span class="text-muted fw-normal">(.webp)</span></label>
                        <input type="file" name="category_image" id="category_image" class="form-control" accept="image/webp">
                        <div class="mt-2"><img id="currentThumb" src="" class="thumb-preview d-none"></div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-semibold d-block">Status</label>
                        <div class="form-check form-switch">
                            <input type="hidden" name="status" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" name="status" value="1" id="statusSwitch" checked>
                            <label class="form-check-label" for="statusSwitch">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Index / Reorder modal -->
<div class="modal fade" id="indexModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border:none;border-radius:14px;">
            <div class="modal-header border-0 pb-1">
                <div>
                    <h5 class="modal-title fw-semibold mb-0">Category Order</h5>
                    <p class="text-muted small mb-0">Drag to reorder — top is first in the API response.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="list-unstyled mb-0" id="sortableCategories">
                    @forelse($allCategories as $cat)
                        <li class="sort-item" data-id="{{ $cat->id }}">
                            <span class="drag-handle"><i class="bi bi-grip-vertical"></i></span>
                            <span class="pos"></span>
                            @if($thumbUrl($cat))
                                <img src="{{ $thumbUrl($cat) }}" class="thumb-preview" style="width:38px;height:38px" alt="" loading="lazy">
                            @else
                                <span class="thumb-empty" style="width:38px;height:38px"><i class="bi bi-image"></i></span>
                            @endif
                            <span class="nm flex-grow-1 fw-semibold text-truncate">{{ $cat->category_name }}</span>
                            <span class="badge-pill {{ $cat->status ? 'badge-soft-success' : 'badge-soft-muted' }}">{{ $cat->status ? 'Active' : 'Inactive' }}</span>
                        </li>
                    @empty
                        <li class="text-muted text-center py-4">No categories to order yet.</li>
                    @endforelse
                </ul>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveOrderBtn"><i class="bi bi-check-lg me-1"></i>Save Order</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
(function () {
    const listUrl = @json(route('frame-categories.index'));
    const orderUrl = @json(route('frame-categories.order'));

    // ----- Index / reorder popup -----
    const sortEl = document.getElementById('sortableCategories');
    function renumber() {
        sortEl.querySelectorAll('.sort-item').forEach((li, i) => {
            const pos = li.querySelector('.pos');
            if (pos) pos.textContent = i + 1;
        });
    }
    if (sortEl && window.Sortable) {
        Sortable.create(sortEl, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onSort: renumber,
        });
        renumber();
    }
    $('#btnIndex').on('click', function () {
        new bootstrap.Modal('#indexModal').show();
    });
    $('#saveOrderBtn').on('click', function () {
        const order = Array.from(sortEl.querySelectorAll('.sort-item')).map(li => li.dataset.id);
        if (!order.length) { return; }
        const btn = this;
        btn.disabled = true;
        $.post(orderUrl, { order: order }, function (res) {
            bootstrap.Modal.getInstance(document.getElementById('indexModal'))?.hide();
            Toast.fire({ icon: 'success', title: res.message || 'Order saved' });
        }).fail(function () {
            Swal.fire({ icon: 'error', title: 'Could not save order' });
        }).always(function () { btn.disabled = false; });
    });

    let currentPage = parseInt(new URL(window.location.href).searchParams.get('page'), 10) || {{ $categories->currentPage() }};

    function syncUrl(page) {
        const url = new URL(window.location.href);
        if (page > 1) {
            url.searchParams.set('page', page);
        } else {
            url.searchParams.delete('page');
        }
        const search = $('#search').val();
        const status = $('#status_filter').val();
        const perPage = $('#per_page').val();
        if (search) url.searchParams.set('search', search); else url.searchParams.delete('search');
        if (status !== '' && status !== null && status !== undefined) url.searchParams.set('status_filter', status); else url.searchParams.delete('status_filter');
        if (perPage && perPage != 10) url.searchParams.set('per_page', perPage); else url.searchParams.delete('per_page');
        window.history.replaceState({}, '', url.toString());
    }

    function reload(page) {
        if (typeof page !== 'undefined' && page !== null) {
            currentPage = parseInt(page, 10) || 1;
        }
        const params = {
            search: $('#search').val(),
            status_filter: $('#status_filter').val(),
            per_page: $('#per_page').val(),
        };
        if (currentPage > 1) params.page = currentPage;

        syncUrl(currentPage);

        $.get(listUrl, params, function (res) {
            $('#tableWrap').html(res.table);
            $('#paginationWrap').html(res.pagination);
            $('#totalCount').text(res.total);
            $('.input-current-page').val(currentPage);
            $('#categoryFormPage').val(currentPage);

            const perPage = parseInt($('#per_page').val(), 10) || 10;
            const maxPage = Math.max(1, Math.ceil(res.total / perPage));
            if (currentPage > maxPage && res.total > 0) {
                reload(maxPage);
            }
        });
    }

    function reloadAfterDelete() {
        const rowCount = $('#tableWrap tbody tr').not(':has(.empty-state)').length;
        if (rowCount <= 1 && currentPage > 1) {
            currentPage = currentPage - 1;
        }
        reload(currentPage);
    }

    let t;
    $('#search').on('input', function () { clearTimeout(t); t = setTimeout(() => reload(1), 350); });
    $('#status_filter, #per_page').on('change', () => reload(1));

    // AJAX pagination links
    $(document).on('click', '#paginationWrap a', function (e) {
        e.preventDefault();
        const url = new URL(this.href);
        reload(url.searchParams.get('page'));
    });

    // Add
    $('#btnAdd').on('click', function () {
        const f = document.getElementById('categoryForm');
        f.reset();
        f.action = @json(route('frame-categories.store'));
        $('#formMethod').val('POST');
        $('#modalTitle').text('Add Category');
        $('#category_name').prop('readonly', false);
        $('#currentThumb').addClass('d-none').attr('src', '');
        $('#statusSwitch').prop('checked', true);
        $('#categoryFormPage').val(currentPage);
        new bootstrap.Modal('#categoryModal').show();
    });

    // Edit
    $(document).on('click', '.btn-edit', function () {
        const d = $(this).data();
        const f = document.getElementById('categoryForm');
        f.reset();
        f.action = '{{ url('frame-categories') }}/' + d.id;
        $('#formMethod').val('PUT');
        $('#modalTitle').text('Edit Category');
        $('#category_name').val(d.name);
        $('#statusSwitch').prop('checked', String(d.status) === '1');
        $('#categoryFormPage').val(currentPage);
        if (d.thumb) {
            $('#currentThumb').removeClass('d-none').attr('src', d.thumb);
        } else {
            $('#currentThumb').addClass('d-none').attr('src', '');
        }
        new bootstrap.Modal('#categoryModal').show();
    });

    // Submit Add/Edit via AJAX — errors show a popup here without losing the form.
    $('#categoryForm').on('submit', function (e) {
        e.preventDefault();
        const form = this;
        const btn = $(form).find('button[type="submit"]')[0];
        window.setBtnLoading(btn, true);
        $.ajax({
            url: form.action,
            method: 'POST',
            data: new FormData(form),
            processData: false,
            contentType: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function (res) {
                bootstrap.Modal.getInstance(document.getElementById('categoryModal'))?.hide();
                reload(currentPage);
                Toast.fire({ icon: 'success', title: res.message || 'Saved' });
            },
            error: function (xhr) { window.showAjaxError(xhr); },
            complete: function () { window.setBtnLoading(btn, false); }
        });
    });

    // Live preview of a newly chosen thumbnail
    $('#category_image').on('change', function () {
        const file = this.files && this.files[0];
        if (file) {
            $('#currentThumb').removeClass('d-none').attr('src', URL.createObjectURL(file));
        }
    });

    // Toggle status
    $(document).on('change', '.toggle-status', function () {
        const id = $(this).data('id');
        const el = this;
        const $label = $(this).closest('td').find('.status-label');
        $.post('{{ url('frame-categories') }}/' + id + '/toggle-status', {}, function (res) {
            if (res.status) {
                $label.removeClass('badge-soft-muted').addClass('badge-soft-success').text('Active');
            } else {
                $label.removeClass('badge-soft-success').addClass('badge-soft-muted').text('Inactive');
            }
            Toast.fire({ icon:'success', title: res.status ? 'Activated' : 'Deactivated' });
        }).fail(function () {
            el.checked = !el.checked;
            Swal.fire({ icon:'error', title:'Could not update status' });
        });
    });

    // Delete — 1) confirm alert, then 2) success alert (AJAX, no redirect)
    $(document).on('submit', '.form-delete', function (e) {
        e.preventDefault();
        const form = this;
        Swal.fire({
            title: 'Delete this category?',
            text: 'Its frame record and uploaded files will be removed.',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, delete it'
        }).then((r) => {
            if (!r.isConfirmed) return;
            $.ajax({
                url: form.action,
                method: 'POST',
                data: new FormData(form),
                processData: false,
                contentType: false,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function (res) {
                    Swal.fire({ icon: 'success', title: 'Deleted!', text: res.message || 'Category deleted.', timer: 1800, showConfirmButton: false });
                    reloadAfterDelete();
                },
                error: function (xhr) { window.showAjaxError(xhr); }
            });
        });
    });
})();
</script>
@endpush
