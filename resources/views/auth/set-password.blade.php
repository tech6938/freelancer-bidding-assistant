<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Password - {{ config('app.name', 'Freelancer Dashboard') }}</title>

    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Inter', sans-serif; }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-card {
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            max-width: 440px;
            width: 100%;
            padding: 2.5rem;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
        }

        .password-card .logo-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem;
            font-size: 1.6rem;
            color: #fff;
        }

        .password-card h2 {
            color: #f1f5f9;
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 0.3rem;
        }

        .password-card .subtitle {
            color: #94a3b8;
            font-size: 0.88rem;
            margin-bottom: 1.8rem;
        }

        .user-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(59, 130, 246, 0.12);
            border: 1px solid rgba(59, 130, 246, 0.25);
            color: #93c5fd;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        .form-label {
            color: #cbd5e1;
            font-weight: 500;
            font-size: 0.85rem;
            margin-bottom: 0.35rem;
        }

        .form-control {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(148, 163, 184, 0.2);
            color: #f1f5f9;
            border-radius: 10px;
            padding: 0.65rem 0.9rem;
            font-size: 0.92rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            background: rgba(15, 23, 42, 0.8);
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
            color: #f1f5f9;
        }

        .form-control::placeholder {
            color: #64748b;
        }

        .btn-set-password {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            color: #fff;
            font-weight: 600;
            padding: 0.7rem;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .btn-set-password:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.35);
            color: #fff;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            border-radius: 10px;
            font-size: 0.85rem;
        }

        .info-note {
            background: rgba(59, 130, 246, 0.08);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            color: #93c5fd;
            font-size: 0.82rem;
            margin-top: 1.2rem;
        }

        .info-note i {
            color: #3b82f6;
        }
    </style>
</head>
<body>

    <div class="password-card">
        <div class="text-center">
            <div class="logo-icon">
                <i class="bi bi-shield-lock"></i>
            </div>
            <h2>Set Your Password</h2>
            <p class="subtitle">Your Freelancer account is connected! Set a password to log in directly next time.</p>

            <div class="user-badge">
                <i class="bi bi-person-fill"></i>
                {{ Auth::user()->username ?? Auth::user()->name }}
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger py-2">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.store') }}">
            @csrf

            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <input type="password"
                       id="password"
                       name="password"
                       class="form-control"
                       placeholder="Minimum 8 characters"
                       required
                       autofocus>
            </div>

            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirm Password</label>
                <input type="password"
                       id="password_confirmation"
                       name="password_confirmation"
                       class="form-control"
                       placeholder="Re-enter your password"
                       required>
            </div>

            <button type="submit" class="btn btn-set-password w-100">
                <i class="bi bi-check-lg me-1"></i> Set Password & Continue
            </button>
        </form>

        <div class="info-note">
            <i class="bi bi-info-circle me-1"></i>
            After setting your password, you can log in anytime using your <strong>username or email</strong> and this password from the login page.
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
