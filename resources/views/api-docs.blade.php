@extends('layouts.admin')
@section('title', 'API URL')
@section('breadcrumb', 'API / ')

@push('styles')
<style>
    .api-doc .mono { font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-size:.82rem; }
    .api-card { height:100%; }
    .api-card .no-title { font-weight:700; font-size:1rem; letter-spacing:-.01em; }
    .api-label { font-size:.74rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#0ea5a3; margin-bottom:3px; }
    .api-url { background:#f8fafc; border:1px solid var(--line); border-radius:8px; padding:9px 11px; word-break:break-all; color:#1d4ed8; position:relative; }
    .api-url .copy { position:absolute; top:6px; right:6px; }
    .method-badge { font-size:.7rem; font-weight:700; letter-spacing:.04em; padding:.25em .6em; border-radius:6px; }
    .method-GET { background:#dcfce7; color:#15803d; }
    .method-POST { background:#dbeafe; color:#1d4ed8; }
    .method-PUT { background:#fef3c7; color:#b45309; }
    .method-DELETE { background:#fee2e2; color:#dc2626; }
    .param-name { color:#dc2626; font-weight:600; }
    .param-req { color:#94a3b8; font-size:.78rem; }
    .param-opt { color:#64748b; font-size:.78rem; }
    .group-divider { display:flex; align-items:center; justify-content:center; margin:10px 0 18px; }
    .group-divider span { background:linear-gradient(135deg,var(--brand),var(--brand-600)); color:#fff; font-weight:600; font-size:.74rem; padding:.3em .9em; border-radius:50rem; box-shadow:0 6px 14px rgba(79,70,229,.35); }
</style>
@endpush

@section('content')
<div class="api-doc">
    <div class="page-head">
        <div>
            <h1>API Documentation</h1>
            <p>All endpoints for the Frames module. Send the Bearer token with every request.</p>
        </div>
    </div>

    {{-- Authentication --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-shield-lock-fill text-primary"></i>
                <h6 class="fw-semibold mb-0">Authentication</h6>
            </div>
            <p class="text-muted small mb-2">Every request must include this header:</p>
            <div class="api-url mono">
                Authorization: Bearer <span id="tokenText">{{ $token !== '' ? $token : '<API_TOKEN>' }}</span>
                @if($token !== '')
                    <button type="button" class="btn btn-sm btn-light border copy" data-copy="{{ 'Authorization: Bearer ' . $token }}"><i class="bi bi-clipboard"></i></button>
                @endif
            </div>
            <p class="text-muted small mb-0 mt-2">Base URL: <span class="mono">{{ $base }}</span></p>
        </div>
    </div>

    @foreach($groups as $group)
        <div class="group-divider"><span>{{ $group['label'] }}</span></div>

        <div class="row g-3 mb-2">
            @foreach($group['endpoints'] as $ep)
                <div class="col-lg-6">
                    <div class="card api-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="no-title">{{ $ep['no'] }}. {{ $ep['title'] }}</span>
                                <span class="method-badge method-{{ $ep['method'] }}">{{ $ep['method'] }}</span>
                            </div>

                            <div class="mb-3">
                                <div class="api-label">URL</div>
                                <div class="api-url mono">
                                    {{ $ep['url'] }}
                                    <button type="button" class="btn btn-sm btn-light border copy" data-copy="{{ $ep['url'] }}"><i class="bi bi-clipboard"></i></button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="api-label">Parameters</div>
                                @if(count($ep['params']))
                                    <ul class="list-unstyled mb-0 mono">
                                        @foreach($ep['params'] as $p)
                                            <li>
                                                <span class="param-name">{{ $p['name'] }}</span>
                                                @if($p['required'])
                                                    <span class="param-req">(required)</span>
                                                @else
                                                    <span class="param-opt">(optional)</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-muted small">None</span>
                                @endif
                            </div>

                            <div>
                                <div class="api-label">Description</div>
                                <p class="text-muted small mb-0">{{ $ep['description'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
</div>
@endsection

@push('scripts')
<script>
$(document).on('click', '.copy', function () {
    const text = $(this).data('copy');
    navigator.clipboard.writeText(text).then(() => {
        Toast.fire({ icon:'success', title:'Copied to clipboard' });
    }).catch(() => {
        Toast.fire({ icon:'error', title:'Copy failed' });
    });
});
</script>
@endpush
