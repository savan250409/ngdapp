<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NGD Admin · Login</title>
    <link rel="icon" href="{{ asset('images/ngd-logo.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { min-height:100vh; display:flex; align-items:center; font-family:'Inter',system-ui,sans-serif;
               background:radial-gradient(1200px 600px at 10% -10%, #312e81 0%, transparent 55%), linear-gradient(135deg,#0f172a,#1e1b4b); }
        .login-card { max-width:420px; width:100%; border:none; border-radius:18px; box-shadow:0 20px 60px rgba(0,0,0,.45); }
        .login-card .form-control { border-radius:10px; }
        .login-card .form-control:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.2); }
        .btn-primary { background:#4f46e5; border-color:#4f46e5; border-radius:10px; font-weight:600; }
        .btn-primary:hover { background:#4338ca; border-color:#4338ca; }
        .brand-ico { width:58px; height:58px; border-radius:16px; background:linear-gradient(135deg,#6366f1,#4f46e5); color:#fff;
                     display:flex; align-items:center; justify-content:center; font-size:27px; box-shadow:0 10px 26px rgba(79,70,229,.5); }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-center">
        <div class="card login-card">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <img src="{{ asset('images/ngd-logo.png') }}" alt="NGD TechnoLab" style="height:58px;width:auto;max-width:80%" class="mb-3">
                    <h5 class="mb-1 fw-semibold">Admin Panel</h5>
                    <p class="text-muted small mb-0">Sign in to continue</p>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login.attempt') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="admin@gmail.com" required autofocus>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label small" for="remember">Remember me</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2"><i class="bi bi-box-arrow-in-right"></i> Sign In</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
