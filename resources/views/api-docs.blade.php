@extends('layouts.admin')
@section('title', 'API URL')
@section('breadcrumb', 'API / ')

@push('styles')
<style>
    .api-doc .mono { font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-size:.82rem; }
    .api-card { height:100%; }
    .api-card .no-title { font-weight:700; font-size:1rem; letter-spacing:-.01em; }
    .api-label { font-size:.74rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#0ea5a3; margin-bottom:3px; }
    .api-url { background:var(--surface-2); border:1px solid var(--line); border-radius:8px; padding:9px 11px; word-break:break-all; color:#3b82f6; position:relative; }
    .api-url .copy { position:absolute; top:6px; right:6px; }
    .method-badge { font-size:.7rem; font-weight:700; letter-spacing:.04em; padding:.25em .6em; border-radius:6px; }
    .method-GET { background:rgba(34,197,94,.16); color:#22c55e; }
    .method-POST { background:rgba(59,130,246,.16); color:#3b82f6; }
    .method-PUT { background:rgba(245,158,11,.16); color:#f59e0b; }
    .method-DELETE { background:rgba(239,68,68,.16); color:#ef4444; }
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
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h1 class="mb-0">API Documentation</h1>
                @if(!empty($appCode))
                    <span class="badge-pill" style="background:var(--brand-50);color:var(--brand-600);font-weight:700;font-size:.9rem;"><i class="bi bi-grid-3x3-gap-fill me-1"></i>{{ $appCode }}</span>
                @endif
            </div>
            <p class="mt-1">All endpoints for <b>{{ $appCode ?? 'your app' }}</b>.</p>
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
