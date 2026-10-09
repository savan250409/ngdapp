<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('images/ngd-logo.png') }}" type="image/png">
    <title>@yield('title', 'Dashboard') · NGD Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-w: 260px;
            --brand: #6366f1;
            --brand-600: #4f46e5;
            --brand-50: #eef2ff;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e9edf3;
            --bg: #f6f8fb;
            --radius: 14px;
            --shadow: 0 1px 2px rgba(16,24,40,.04), 0 4px 16px rgba(16,24,40,.06);
        }
        * { -webkit-font-smoothing:antialiased; }
        body { background:var(--bg); font-family:'Inter',system-ui,-apple-system,"Segoe UI",sans-serif; color:var(--ink); }
        a { text-decoration:none; }
        ::-webkit-scrollbar { width:9px; height:9px; }
        ::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:20px; }

        /* ---------- Sidebar ---------- */
        .sidebar {
            position:fixed; inset:0 auto 0 0; width:var(--sidebar-w);
            background:#0b1220; color:#94a3b8; display:flex; flex-direction:column; z-index:1040;
            border-right:1px solid rgba(255,255,255,.05); transition:transform .25s ease;
        }
        .sidebar .brand {
            padding:20px 22px; font-weight:700; color:#fff; font-size:1.08rem;
            display:flex; align-items:center; gap:11px; letter-spacing:-.01em;
        }
        .sidebar .brand { padding:18px 22px; }
        .sidebar .brand-logo { height:40px; width:auto; max-width:100%; display:block; }
        .sidebar .nav-section { font-size:.68rem; font-weight:600; text-transform:uppercase; letter-spacing:.11em; color:#475569; padding:18px 22px 8px; }
        .sidebar .nav-link {
            color:#94a3b8; margin:1px 12px; padding:10px 12px; border-radius:9px;
            display:flex; align-items:center; gap:12px; font-size:.92rem; font-weight:500; transition:all .15s;
        }
        .sidebar .nav-link i { font-size:1.1rem; width:20px; text-align:center; }
        .sidebar .nav-link:hover { background:rgba(255,255,255,.05); color:#e2e8f0; }
        .sidebar .nav-link.active { background:linear-gradient(135deg,var(--brand),var(--brand-600)); color:#fff; box-shadow:0 6px 16px rgba(79,70,229,.4); }
        .sidebar .side-foot { margin-top:auto; padding:16px 20px; border-top:1px solid rgba(255,255,255,.06); font-size:.8rem; }
        .sidebar .side-foot .u { display:flex; align-items:center; gap:10px; }
        .sidebar .side-foot .avatar { width:34px; height:34px; border-radius:50%; background:#1e293b; color:#cbd5e1; display:flex; align-items:center; justify-content:center; font-weight:600; }

        /* ---------- Main ---------- */
        .main { margin-left:var(--sidebar-w); min-height:100vh; display:flex; flex-direction:column; }
        .topbar {
            background:rgba(255,255,255,.85); backdrop-filter:saturate(180%) blur(8px);
            border-bottom:1px solid var(--line); padding:0 26px; height:62px;
            display:flex; align-items:center; gap:14px; position:sticky; top:0; z-index:1030;
        }
        .topbar .crumb { font-size:.82rem; color:var(--muted); }
        .topbar .crumb b { color:var(--ink); font-weight:600; }
        .content { padding:26px; flex:1; }

        /* ---------- Page header ---------- */
        .page-head { display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:14px; margin-bottom:20px; }
        .page-head h1 { font-size:1.4rem; font-weight:700; letter-spacing:-.02em; margin:0; }
        .page-head p { color:var(--muted); font-size:.88rem; margin:3px 0 0; }

        /* ---------- Cards ---------- */
        .card { border:1px solid var(--line); box-shadow:var(--shadow); border-radius:var(--radius); }
        .card-body { padding:20px; }

        /* ---------- Stat cards ---------- */
        .stat-card .icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0; }
        .stat-card .val { font-size:1.6rem; font-weight:700; line-height:1.1; letter-spacing:-.02em; }
        .stat-card .lbl { color:var(--muted); font-size:.82rem; font-weight:500; }

        /* ---------- Tables ---------- */
        .table { margin:0; }
        .table thead th {
            background:#fbfcfe; color:var(--muted); font-size:.72rem; font-weight:600; text-transform:uppercase;
            letter-spacing:.06em; border-bottom:1px solid var(--line); padding:12px 16px; white-space:nowrap;
        }
        .table tbody td { padding:13px 16px; border-bottom:1px solid #f1f5f9; vertical-align:middle; font-size:.9rem; }
        .table tbody tr:last-child td { border-bottom:0; }
        .table tbody tr:hover { background:#fafbfe; }
        .data-card { overflow:hidden; }
        .data-card .card-body { padding:0; }
        .toolbar { padding:16px 16px; border-bottom:1px solid var(--line); }

        /* ---------- Bits ---------- */
        .thumb-preview { width:46px; height:46px; object-fit:cover; border-radius:9px; border:1px solid var(--line); background:#fff; }
        .thumb-empty { width:46px; height:46px; border-radius:9px; border:1px dashed #cbd5e1; display:inline-flex; align-items:center; justify-content:center; color:#cbd5e1; background:#fbfcfe; }
        .frame-strip img { width:42px; height:42px; object-fit:cover; border-radius:8px; border:1px solid var(--line); margin-right:5px; background:#fff; }
        .badge-soft-success { background:#dcfce7; color:#15803d; }
        .badge-soft-muted { background:#f1f5f9; color:#64748b; }
        .badge-soft-pro { background:#fef3c7; color:#b45309; }
        .badge-pill { font-weight:600; padding:.35em .7em; border-radius:50rem; font-size:.72rem; }
        .empty-state { padding:46px 20px; text-align:center; color:var(--muted); }
        .empty-state .bi { font-size:2.3rem; color:#cbd5e1; display:block; margin-bottom:10px; }
        .builder-row { background:#fff; border:1px solid var(--line); border-radius:11px; padding:14px; margin-bottom:10px; }
        .btn { font-weight:500; border-radius:9px; }
        .btn-primary { background:var(--brand-600); border-color:var(--brand-600); }
        .btn-primary:hover { background:#4338ca; border-color:#4338ca; }
        .btn-icon { width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; padding:0; }
        .form-control, .form-select { border-color:#dbe2ea; border-radius:9px; }
        .form-control:focus, .form-select:focus { border-color:var(--brand); box-shadow:0 0 0 3px rgba(99,102,241,.15); }
        .input-icon { position:relative; }
        .input-icon .bi { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; }
        .input-icon .form-control { padding-left:36px; }
        .avatar-btn { width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg,var(--brand),var(--brand-600)); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:600; font-size:.9rem; }
        .pagination { margin:0; }
        .pagination .page-link { border:none; color:var(--muted); border-radius:8px !important; margin:0 2px; font-size:.86rem; }
        .pagination .page-item.active .page-link { background:var(--brand-600); color:#fff; }
        .pagination .page-link:focus { box-shadow:none; }
        .list-foot { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; padding:14px 16px; border-top:1px solid var(--line); }

        /* ---------- Responsive ---------- */
        .sidebar-backdrop { display:none; }
        @media (max-width: 991.98px) {
            .sidebar { transform:translateX(-100%); }
            .sidebar.open { transform:translateX(0); }
            .main { margin-left:0; }
            .sidebar-backdrop.show { display:block; position:fixed; inset:0; background:rgba(2,6,23,.5); z-index:1035; }
        }
        @stack('styles')
    </style>
</head>
<body>
    @php
        $user = auth()->user();
        $initials = strtoupper(substr($user->name ?? 'A', 0, 1));
    @endphp

    <aside class="sidebar" id="sidebar">
        <div class="brand"><img src="{{ asset('images/ngd-logo.png') }}" alt="NGD TechnoLab" class="brand-logo"></div>
        <nav class="nav flex-column">
            <span class="nav-section">Main</span>
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
            <span class="nav-section">Frames Module</span>
            <a class="nav-link {{ request()->routeIs('frame-categories.*') ? 'active' : '' }}" href="{{ route('frame-categories.index') }}">
                <i class="bi bi-collection-fill"></i> Frame Categories
            </a>
            <a class="nav-link {{ request()->routeIs('frames.*') ? 'active' : '' }}" href="{{ route('frames.index') }}">
                <i class="bi bi-images"></i> Frames
            </a>
            <span class="nav-section">API</span>
            <a class="nav-link {{ request()->routeIs('api-docs') ? 'active' : '' }}" href="{{ route('api-docs') }}">
                <i class="bi bi-braces"></i> API URL
            </a>
        </nav>
        <div class="side-foot">
            <div class="u">
                <span class="avatar">{{ $initials }}</span>
                <div class="overflow-hidden">
                    <div class="text-light text-truncate" style="font-size:.86rem;font-weight:600">{{ $user->name ?? 'Admin' }}</div>
                    <div class="text-truncate" style="font-size:.76rem">{{ $user->email ?? '' }}</div>
                </div>
            </div>
        </div>
    </aside>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="main">
        <header class="topbar">
            <button class="btn btn-sm btn-light border d-lg-none" id="sidebarToggle"><i class="bi bi-list"></i></button>
            <div class="crumb">@yield('breadcrumb')<b>@yield('title', 'Dashboard')</b></div>
            <div class="dropdown ms-auto">
                <button class="btn d-flex align-items-center gap-2 p-1 pe-2 dropdown-toggle" data-bs-toggle="dropdown" style="border:1px solid var(--line);border-radius:50rem;">
                    <span class="avatar-btn">{{ $initials }}</span>
                    <span class="d-none d-sm-inline small fw-semibold">{{ $user->name ?? 'Admin' }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><span class="dropdown-item-text small text-muted">{{ $user->email ?? '' }}</span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-1"></i> Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </header>

        <div class="content">
            @yield('content')
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.12.4/sweetalert2.all.min.js"></script>
    <script>
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        const Toast = Swal.mixin({ toast:true, position:'top-end', showConfirmButton:false, timer:2200, timerProgressBar:true });

        (function () {
            const sb = document.getElementById('sidebar');
            const bd = document.getElementById('sidebarBackdrop');
            document.getElementById('sidebarToggle')?.addEventListener('click', () => { sb.classList.add('open'); bd.classList.add('show'); });
            bd?.addEventListener('click', () => { sb.classList.remove('open'); bd.classList.remove('show'); });
        })();

        @if(session('success'))
            Toast.fire({ icon:'success', title:@json(session('success')) });
        @endif
        @if($errors->any() && !request()->routeIs('login*'))
            Swal.fire({ icon:'error', title:'Please fix the following', html:@json(implode('<br>', $errors->all())) });
        @endif
    </script>
    @stack('scripts')
</body>
</html>
