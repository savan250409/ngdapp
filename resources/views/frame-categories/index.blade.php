@extends('layouts.admin')
@section('title', 'Frame Categories')
@section('breadcrumb', 'Frames Module / ')

@php
    $thumbUrl = function ($cat) {
        if (! $cat->category_image) return null;
        return url('upload/frame/' . rawurlencode($cat->category_name) . '/category_thumbnail_frame/' . rawurlencode($cat->category_image));
    };
@endphp

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
    <button class="btn btn-primary" id="btnAdd"><i class="bi bi-plus-lg me-1"></i> Add Category</button>
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
@endsection

@push('scripts')
<script>
(function () {
    const listUrl = @json(route('frame-categories.index'));

    function reload(page) {
        const params = {
            search: $('#search').val(),
            status_filter: $('#status_filter').val(),
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
    $('#status_filter, #per_page').on('change', () => reload());

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
        if (d.thumb) {
            $('#currentThumb').removeClass('d-none').attr('src', d.thumb);
        } else {
            $('#currentThumb').addClass('d-none').attr('src', '');
        }
        new bootstrap.Modal('#categoryModal').show();
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

    // Delete confirm
    $(document).on('submit', '.form-delete', function (e) {
        e.preventDefault();
        const form = this;
        Swal.fire({
            title: 'Delete this category?',
            text: 'Its frame record and uploaded files will be removed.',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, delete it'
        }).then((r) => { if (r.isConfirmed) form.submit(); });
    });
})();
</script>
@endpush
