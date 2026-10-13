@extends('layouts.admin')
@section('title', 'Dashboard')
@section('breadcrumb', 'Main / ')

@section('content')
<div class="page-head">
    <div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h1 class="mb-0">Welcome back 👋</h1>
            @if(!empty($appCode))
                <span class="badge-pill" style="background:var(--brand-50);color:var(--brand-600);font-weight:700;font-size:.9rem;"><i class="bi bi-grid-3x3-gap-fill me-1"></i>{{ $appCode }}</span>
            @endif
        </div>
        <p class="mt-1">Here's an overview of <b>{{ $appCode ?? 'your app' }}</b>.</p>
    </div>
    <a href="{{ route('frames.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New Frame Record</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon" style="background:#eef2ff;color:#4f46e5"><i class="bi bi-collection-fill"></i></div>
                <div>
                    <div class="lbl">Total Categories</div>
                    <div class="val">{{ number_format($totalCategories) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon" style="background:#dcfce7;color:#16a34a"><i class="bi bi-check-circle-fill"></i></div>
                <div>
                    <div class="lbl">Active Categories</div>
                    <div class="val">{{ number_format($activeCategories) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon" style="background:#fee2e2;color:#dc2626"><i class="bi bi-slash-circle-fill"></i></div>
                <div>
                    <div class="lbl">Inactive Categories</div>
                    <div class="val">{{ number_format($inactiveCategories) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon" style="background:#fef3c7;color:#d97706"><i class="bi bi-images"></i></div>
                <div>
                    <div class="lbl">Frame Records</div>
                    <div class="val">{{ number_format($totalRecords) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card data-card h-100">
            <div class="card-body">
                <div class="toolbar d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold">Recent Categories</h6>
                    <a href="{{ route('frame-categories.index') }}" class="btn btn-sm btn-light border">View all</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th>#</th><th>Name</th><th>Status</th><th>Created</th></tr></thead>
                        <tbody>
                            @forelse($recentCategories as $c)
                                <tr>
                                    <td class="text-muted">{{ $c->id }}</td>
                                    <td class="fw-500">{{ $c->category_name }}</td>
                                    <td>
                                        @if($c->status)
                                            <span class="badge-pill badge-soft-success">Active</span>
                                        @else
                                            <span class="badge-pill badge-soft-muted">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $c->created_at?->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><div class="empty-state"><i class="bi bi-inbox"></i>No categories yet.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="mb-3 fw-semibold">Quick Actions</h6>
                <div class="d-grid gap-2">
                    <a href="{{ route('frame-categories.index') }}" class="btn btn-light border text-start d-flex align-items-center gap-2 py-2">
                        <i class="bi bi-plus-circle text-primary"></i> Add a frame category
                    </a>
                    <a href="{{ route('frames.create') }}" class="btn btn-light border text-start d-flex align-items-center gap-2 py-2">
                        <i class="bi bi-images text-primary"></i> Build a frame record
                    </a>
                </div>
                <div class="mt-3 p-3 rounded" style="background:var(--brand-50)">
                    <div class="d-flex gap-2">
                        <i class="bi bi-lightning-charge-fill text-primary"></i>
                        <p class="small text-muted mb-0">
                            All lists use server-side pagination, search and filtering, so the panel stays fast as your data grows.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
