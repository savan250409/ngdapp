<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('images/ngd-logo.png') }}" type="image/png">
    <title>@yield('title', 'Dashboard') · NGD Admin</title>

    {{-- Set theme + sidebar state before paint to avoid flash --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme') || 'light';
                document.documentElement.setAttribute('data-bs-theme', t);
                if (localStorage.getItem('sidebarCollapsed') === '1') {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (e) {}
        })();
    </script>

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
            --radius: 14px;
        }
        /* ---------- Theme tokens ---------- */
        :root, [data-bs-theme="light"] {
            --bg: #f6f8fb;
            --surface: #ffffff;
            --surface-2: #f8fafc;
            --line: #e9edf3;
            --ink: #0f172a;
            --muted: #64748b;
            --sidebar-bg: #ffffff;
            --sidebar-ink: #64748b;
            --sidebar-strong: #0f172a;
            --sidebar-hover: #f1f5f9;
            --sidebar-section: #94a3b8;
            --sidebar-line: #e9edf3;
            --shadow: 0 1px 2px rgba(16,24,40,.04), 0 4px 16px rgba(16,24,40,.06);
        }
        [data-bs-theme="dark"] {
            --brand-50: rgba(99,102,241,.14);
            --bg: #0a0f1a;
            --surface: #111a2b;
            --surface-2: #0f1626;
            --line: #233047;
            --ink: #e6ebf2;
            --muted: #94a3b8;
            --sidebar-bg: #0a1120;
            --sidebar-ink: #94a3b8;
            --sidebar-strong: #f1f5f9;
            --sidebar-hover: rgba(255,255,255,.07);
            --sidebar-section: #64748b;
            --sidebar-line: rgba(255,255,255,.07);
            --shadow: 0 1px 2px rgba(0,0,0,.3), 0 6px 20px rgba(0,0,0,.4);
            color-scheme: dark;
        }

        * { -webkit-font-smoothing:antialiased; }
        body { background:var(--bg); color:var(--ink); font-family:'Inter',system-ui,-apple-system,"Segoe UI",sans-serif; }
        a { text-decoration:none; }
        ::-webkit-scrollbar { width:9px; height:9px; }
        ::-webkit-scrollbar-thumb { background:#94a3b8; border-radius:20px; opacity:.5; }

        /* ---------- Sidebar ---------- */
        .sidebar {
            position:fixed; inset:0 auto 0 0; width:var(--sidebar-w);
            background:var(--sidebar-bg); color:var(--sidebar-ink); display:flex; flex-direction:column; z-index:1040;
            border-right:1px solid var(--sidebar-line); transition:width .22s ease, transform .22s ease; overflow:hidden;
        }
        .sidebar .brand { padding:16px 18px; display:flex; align-items:center; justify-content:flex-start; min-height:62px; }
        .sidebar .brand-logo-full { height:38px; width:auto; max-width:100%; display:block; }
        .sidebar .brand-logo-mini {
            display:none; width:40px; height:40px; border-radius:10px; align-items:center; justify-content:center;
            background:linear-gradient(135deg,var(--brand),var(--brand-600)); color:#fff; font-weight:700; font-size:12px; letter-spacing:.02em;
            box-shadow:0 4px 12px rgba(99,102,241,.45); flex-shrink:0;
        }
        .sidebar .nav-section { font-size:.68rem; font-weight:600; text-transform:uppercase; letter-spacing:.11em; color:var(--sidebar-section); padding:16px 20px 8px; white-space:nowrap; }
        .sidebar .nav-link {
            color:var(--sidebar-ink); margin:1px 12px; padding:10px 12px; border-radius:9px;
            display:flex; align-items:center; gap:12px; font-size:.92rem; font-weight:500; transition:background .15s,color .15s; white-space:nowrap;
        }
        .sidebar .nav-link i { font-size:1.12rem; width:22px; text-align:center; flex-shrink:0; }
        .sidebar .nav-link:hover { background:var(--sidebar-hover); color:var(--sidebar-strong); }
        .sidebar .nav-link.active { background:linear-gradient(135deg,var(--brand),var(--brand-600)); color:#fff; box-shadow:0 6px 16px rgba(79,70,229,.4); }
        .sidebar .side-foot { margin-top:auto; padding:14px 18px; border-top:1px solid var(--sidebar-line); }
        .sidebar .side-foot .u { display:flex; align-items:center; gap:10px; }
        .sidebar .side-foot .avatar { width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg,var(--brand),var(--brand-600)); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:600; flex-shrink:0; }
        .sidebar .side-foot .u .nm { color:var(--sidebar-strong); font-size:.86rem; font-weight:600; }
        .sidebar .side-foot .u .em { font-size:.76rem; color:var(--sidebar-ink); }

        /* ---------- Collapsed (desktop) ---------- */
        html.sidebar-collapsed { --sidebar-w: 78px; }
        html.sidebar-collapsed .sidebar .brand { justify-content:center; padding-left:0; padding-right:0; }
        html.sidebar-collapsed .sidebar .brand-logo-full { display:none; }
        html.sidebar-collapsed .sidebar .brand-logo-mini { display:flex; }
        html.sidebar-collapsed .sidebar .nav-link { justify-content:center; margin:1px 10px; padding:11px 0; }
        html.sidebar-collapsed .sidebar .nav-link .label,
        html.sidebar-collapsed .sidebar .nav-section,
        html.sidebar-collapsed .sidebar .side-foot .u .txt { display:none; }
        html.sidebar-collapsed .sidebar .side-foot { display:flex; justify-content:center; }

        /* ---------- Main ---------- */
        .main { margin-left:var(--sidebar-w); min-height:100vh; display:flex; flex-direction:column; transition:margin-left .22s ease; }
        .topbar {
            background:var(--surface); border-bottom:1px solid var(--line); padding:0 20px; height:62px;
            display:flex; align-items:center; gap:12px; position:sticky; top:0; z-index:1030;
        }
        .topbar .crumb { font-size:.82rem; color:var(--muted); }
        .topbar .crumb b { color:var(--ink); font-weight:600; }
        .icon-btn {
            width:38px; height:38px; border:1px solid var(--line); background:var(--surface); color:var(--ink);
            border-radius:9px; display:inline-flex; align-items:center; justify-content:center; font-size:1.05rem; transition:background .15s;
        }
        .icon-btn:hover { background:var(--surface-2); }
        .content { padding:24px; flex:1; }

        /* ---------- Page header ---------- */
        .page-head { display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:14px; margin-bottom:20px; }
        .page-head h1 { font-size:1.4rem; font-weight:700; letter-spacing:-.02em; margin:0; color:var(--ink); }
        .page-head p { color:var(--muted); font-size:.88rem; margin:3px 0 0; }

        /* ---------- Cards ---------- */
        .card { border:1px solid var(--line); box-shadow:var(--shadow); border-radius:var(--radius); background:var(--surface); color:var(--ink); }
        .card-body { padding:20px; }

        /* ---------- Stat cards ---------- */
        .stat-card .icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0; }
        .stat-card .val { font-size:1.6rem; font-weight:700; line-height:1.1; letter-spacing:-.02em; color:var(--ink); }
        .stat-card .lbl { color:var(--muted); font-size:.82rem; font-weight:500; }

        /* ---------- Tables ---------- */
        .table { margin:0; color:var(--ink); --bs-table-bg:transparent; }
        .table thead th {
            background:var(--surface-2); color:var(--muted); font-size:.72rem; font-weight:600; text-transform:uppercase;
            letter-spacing:.06em; border-bottom:1px solid var(--line); padding:12px 16px; white-space:nowrap;
        }
        .table tbody td { padding:13px 16px; border-bottom:1px solid var(--line); vertical-align:middle; font-size:.9rem; color:var(--ink); }
        .table tbody tr:last-child td { border-bottom:0; }
        .table tbody tr:hover td { background:var(--surface-2); }
        .data-card { overflow:hidden; }
        .data-card .card-body { padding:0; }
        .toolbar { padding:16px; border-bottom:1px solid var(--line); }

        /* ---------- Bits ---------- */
        .thumb-preview { width:46px; height:46px; object-fit:cover; border-radius:9px; border:1px solid var(--line); background:var(--surface); }
        .thumb-empty { width:46px; height:46px; border-radius:9px; border:1px dashed var(--line); display:inline-flex; align-items:center; justify-content:center; color:var(--muted); background:var(--surface-2); }
        .frame-strip img { width:42px; height:42px; object-fit:cover; border-radius:8px; border:1px solid var(--line); margin-right:5px; background:var(--surface); }
        .badge-soft-success { background:rgba(34,197,94,.15); color:#16a34a; }
        .badge-soft-muted { background:rgba(100,116,139,.15); color:var(--muted); }
        .badge-soft-pro { background:rgba(245,158,11,.18); color:#d97706; }
        .badge-pill { font-weight:600; padding:.35em .7em; border-radius:50rem; font-size:.72rem; }
        .empty-state { padding:46px 20px; text-align:center; color:var(--muted); }
        .empty-state .bi { font-size:2.3rem; color:var(--muted); opacity:.5; display:block; margin-bottom:10px; }
        .builder-row { background:var(--surface-2); border:1px solid var(--line); border-radius:11px; padding:14px; margin-bottom:10px; }
        .btn { font-weight:500; border-radius:9px; }
        .btn-primary { background:var(--brand-600); border-color:var(--brand-600); color:#fff; }
        .btn-primary:hover { background:#4338ca; border-color:#4338ca; color:#fff; }
        .btn-icon { width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; padding:0; }
        .form-control, .form-select { border-color:var(--line); border-radius:9px; background:var(--surface); color:var(--ink); }
        .form-control:focus, .form-select:focus { border-color:var(--brand); box-shadow:0 0 0 3px rgba(99,102,241,.15); }
        .input-icon { position:relative; }
        .input-icon .bi { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--muted); }
        .input-icon .form-control { padding-left:36px; }
        .avatar-btn { width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg,var(--brand),var(--brand-600)); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:600; font-size:.9rem; }
        .pagination { margin:0; }
        .pagination .page-link { border:none; color:var(--muted); background:transparent; border-radius:8px !important; margin:0 2px; font-size:.86rem; }
        .pagination .page-item.active .page-link { background:var(--brand-600); color:#fff; }
        .pagination .page-link:hover { background:var(--surface-2); }
        .pagination .page-link:focus { box-shadow:none; }
        .list-foot { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; padding:14px 16px; border-top:1px solid var(--line); }
        .btn-light { background:var(--surface); border-color:var(--line); color:var(--ink); }
        .btn-light:hover { background:var(--surface-2); color:var(--ink); }

        /* ---------- Responsive ---------- */
        .sidebar-backdrop { display:none; }
        @media (max-width: 991.98px) {
            html.sidebar-collapsed { --sidebar-w: 260px; }
            .sidebar { width:260px; transform:translateX(-100%); }
            .sidebar.open { transform:translateX(0); }
            .main { margin-left:0 !important; }
            html.sidebar-collapsed .sidebar .brand-logo-full { display:block; }
            html.sidebar-collapsed .sidebar .brand-logo-mini { display:none; }
            html.sidebar-collapsed .sidebar .brand { justify-content:flex-start; padding:16px 18px; }
            html.sidebar-collapsed .sidebar .nav-link { justify-content:flex-start; margin:1px 12px; padding:10px 12px; }
            html.sidebar-collapsed .sidebar .nav-link .label,
            html.sidebar-collapsed .sidebar .nav-section,
            html.sidebar-collapsed .sidebar .side-foot .u .txt { display:revert; }
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
        <div class="brand">
            <img src="{{ asset('images/ngd-logo.png') }}" alt="NGD TechnoLab" class="brand-logo-full">
            <span class="brand-logo-mini">NGD</span>
        </div>
        <nav class="nav flex-column">
            <span class="nav-section">Main</span>
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" title="Dashboard">
                <i class="bi bi-grid-1x2-fill"></i> <span class="label">Dashboard</span>
            </a>
            <span class="nav-section">Frames Module</span>
            <a class="nav-link {{ request()->routeIs('frame-categories.*') ? 'active' : '' }}" href="{{ route('frame-categories.index') }}" title="Frame Categories">
                <i class="bi bi-collection-fill"></i> <span class="label">Frame Categories</span>
            </a>
            <a class="nav-link {{ request()->routeIs('frames.*') ? 'active' : '' }}" href="{{ route('frames.index') }}" title="Frames">
                <i class="bi bi-images"></i> <span class="label">Frames</span>
            </a>
            <a class="nav-link {{ request()->routeIs('milestone-frames.*') ? 'active' : '' }}" href="{{ route('milestone-frames.index') }}" title="Milestone Frames">
                <i class="bi bi-calendar-heart"></i> <span class="label">Milestone Frames</span>
            </a>
            <span class="nav-section">API</span>
            <a class="nav-link {{ request()->routeIs('api-docs') ? 'active' : '' }}" href="{{ route('api-docs') }}" title="API URL">
                <i class="bi bi-braces"></i> <span class="label">API URL</span>
            </a>
        </nav>
        <div class="side-foot">
            <div class="u">
                <span class="avatar">{{ $initials }}</span>
                <div class="txt overflow-hidden">
                    <div class="nm text-truncate">{{ $user->name ?? 'Admin' }}</div>
                    <div class="em text-truncate">{{ $user->email ?? '' }}</div>
                </div>
            </div>
        </div>
    </aside>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="main">
        <header class="topbar">
            <button class="icon-btn" id="sidebarToggle" title="Toggle sidebar"><i class="bi bi-list"></i></button>
            <div class="crumb d-none d-sm-block">@yield('breadcrumb')<b>@yield('title', 'Dashboard')</b></div>
            <div class="ms-auto d-flex align-items-center gap-2">
                <button class="icon-btn" id="themeToggle" title="Toggle light / dark"><i class="bi bi-moon-stars" id="themeIcon"></i></button>
                <div class="dropdown">
                    <button class="btn d-flex align-items-center gap-2 p-1 pe-2 dropdown-toggle" data-bs-toggle="dropdown" style="border:1px solid var(--line);border-radius:50rem;color:var(--ink);">
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

        // Show a success toast carried across an AJAX-then-redirect navigation.
        try {
            const flash = sessionStorage.getItem('ngd_flash');
            if (flash) { sessionStorage.removeItem('ngd_flash'); Toast.fire({ icon:'success', title: flash }); }
        } catch (e) {}

        // Turn a validation/XHR error into an on-screen popup (no redirect, form kept intact).
        window.showAjaxError = function (xhr) {
            if (xhr && xhr.status === 422) {
                const errs = (xhr.responseJSON && xhr.responseJSON.errors) || {};
                const list = Object.values(errs).flat();
                Swal.fire({ icon:'error', title:'Please fix the following', html: list.length ? list.join('<br>') : (xhr.responseJSON?.message || 'Validation error') });
            } else {
                Swal.fire({ icon:'error', title:'Something went wrong', text:'Please try again.' });
            }
        };

        // ----- Theme toggle -----
        (function () {
            const root = document.documentElement;
            const icon = document.getElementById('themeIcon');
            function paint() {
                const dark = root.getAttribute('data-bs-theme') === 'dark';
                icon.className = dark ? 'bi bi-sun-fill' : 'bi bi-moon-stars';
            }
            paint();
            document.getElementById('themeToggle').addEventListener('click', function () {
                const next = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
                root.setAttribute('data-bs-theme', next);
                try { localStorage.setItem('theme', next); } catch (e) {}
                paint();
            });
        })();

        // ----- Sidebar open / close -----
        (function () {
            const root = document.documentElement;
            const sb = document.getElementById('sidebar');
            const bd = document.getElementById('sidebarBackdrop');
            const isDesktop = () => window.matchMedia('(min-width: 992px)').matches;
            document.getElementById('sidebarToggle').addEventListener('click', function () {
                if (isDesktop()) {
                    root.classList.toggle('sidebar-collapsed');
                    try { localStorage.setItem('sidebarCollapsed', root.classList.contains('sidebar-collapsed') ? '1' : '0'); } catch (e) {}
                } else {
                    sb.classList.add('open'); bd.classList.add('show');
                }
            });
            bd.addEventListener('click', () => { sb.classList.remove('open'); bd.classList.remove('show'); });
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
