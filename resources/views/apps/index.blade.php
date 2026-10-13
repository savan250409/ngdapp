@extends('layouts.admin')
@section('title', 'Apps')
@section('breadcrumb', 'Main / ')

@section('content')
<div class="page-head">
    <div>
        <h1>Apps</h1>
        <p>Each app has its own data, uploads folder and admin login. Switch apps from the top bar.</p>
    </div>
    <button class="btn btn-primary" id="btnAddApp"><i class="bi bi-plus-lg me-1"></i> Add App</button>
</div>

<div class="card data-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th style="width:60px">#</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Admin Login</th>
                        <th>Uploads</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($apps as $app)
                        <tr>
                            <td class="text-muted">{{ $app->id }}</td>
                            <td><span class="badge-pill" style="background:var(--brand-50);color:var(--brand-600);font-weight:700">{{ $app->code }}</span></td>
                            <td class="fw-semibold">{{ $app->name }}</td>
                            <td class="mono small">{{ $app->code . '@admin' }}</td>
                            <td class="text-muted small">upload/{{ $app->code }}/</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-light border text-danger btn-del-app" data-id="{{ $app->id }}" data-code="{{ $app->code }}" title="Delete"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state"><i class="bi bi-grid-3x3-gap"></i>No apps yet.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add App modal -->
<div class="modal fade" id="appModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="appForm">
            @csrf
            <div class="modal-content" style="border:none;border-radius:14px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold">Add App</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">App Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" placeholder="e.g. ngd003" required>
                        <div class="form-text">Letters, numbers, - and _ only. Creates login <b><span id="loginPreview">ngd003</span>{{ '@' }}admin</b> / <b><span id="pwPreview">ngd003</span>{{ '@' }}2026</b>.</div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-semibold">Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. NGD 003">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Create App</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    $('#btnAddApp').on('click', function () {
        document.getElementById('appForm').reset();
        $('#loginPreview, #pwPreview').text('ngd003');
        new bootstrap.Modal('#appModal').show();
    });

    $('#appForm input[name="code"]').on('input', function () {
        const v = this.value || 'ngd003';
        $('#loginPreview, #pwPreview').text(v);
    });

    $('#appForm').on('submit', function (e) {
        e.preventDefault();
        const form = this;
        const btn = form.querySelector('button[type="submit"]');
        window.setBtnLoading(btn, true, 'Creating…');
        $.ajax({
            url: @json(route('apps.store')),
            method: 'POST',
            data: new FormData(form),
            processData: false, contentType: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function (res) {
                bootstrap.Modal.getInstance(document.getElementById('appModal'))?.hide();
                Swal.fire({ icon: 'success', title: 'App created', text: res.message || '' }).then(() => location.reload());
            },
            error: function (xhr) { window.showAjaxError(xhr); },
            complete: function () { window.setBtnLoading(btn, false); }
        });
    });

    $(document).on('click', '.btn-del-app', function () {
        const id = $(this).data('id');
        const code = $(this).data('code');
        Swal.fire({
            title: 'Delete app "' + code + '"?',
            text: 'Its data, admin login and uploads will be permanently removed.',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, delete it'
        }).then((r) => {
            if (!r.isConfirmed) return;
            $.ajax({
                url: '{{ url('apps') }}/' + id,
                method: 'POST',
                data: { _method: 'DELETE', _token: '{{ csrf_token() }}' },
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function (res) {
                    Swal.fire({ icon: 'success', title: 'Deleted!', text: res.message || '', timer: 1600, showConfirmButton: false }).then(() => location.reload());
                },
                error: function (xhr) { window.showAjaxError(xhr); }
            });
        });
    });
})();
</script>
@endpush
