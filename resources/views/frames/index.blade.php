@extends('layouts.admin')
@section('title', 'Frames')
@section('breadcrumb', 'Frames Module / ')

@php
    $frameImgUrl = function ($categoryName, $f) {
        $cat = rawurlencode($categoryName);
        if (! empty($f['thumbnail_path'])) {
            return url('upload/frame/' . $cat . '/frame_thumbnail/' . rawurlencode($f['thumbnail_path']));
        }
        return url('upload/frame/' . $cat . '/frame/' . rawurlencode($f['image_path']));
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
                    <div class="frame-strip d-flex flex-wrap align-items-center">
                        @foreach(collect($record->frames ?? [])->sortBy('frame_index')->take(6) as $f)
                            <img src="{{ $frameImgUrl($record->category->category_name ?? '', $f) }}" alt="" loading="lazy" decoding="async" width="42" height="42">
                        @endforeach
                        @php $count = count($record->frames ?? []); @endphp
                        @if($count > 6)
                            <span class="badge-pill badge-soft-muted ms-1">+{{ $count - 6 }}</span>
                        @endif
                        @if($count === 0)
                            <span class="text-muted small">No frames</span>
                        @else
                            <span class="text-muted small ms-2">{{ $count }} frame(s)</span>
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
    <a href="{{ route('frames.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add Frame Record</a>
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
@endsection

@push('scripts')
<script>
(function () {
    const listUrl = @json(route('frames.index'));

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
