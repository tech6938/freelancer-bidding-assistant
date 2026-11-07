<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ config('app.name', 'Freelancer Dashboard') }}</title>

    <!-- Bootstrap 5 CSS, Icons & Inter Font -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { 
            font-family: 'Inter', sans-serif; 
            box-sizing: border-box;
        }

        html, body {
            height: 100vh;
            max-height: 100vh;
            margin: 0;
            padding: 0;
            overflow: hidden; /* Strict 100vh with no scrolling */
        }

        body {
            background: radial-gradient(circle at 10% 20%, #f1f5f9 0%, #eef2ff 45%, #f8fafc 90%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            color: #1e293b;
        }

        .auth-container {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.08), 0 0 1px 1px rgba(226, 232, 240, 0.7);
            max-width: 940px;
            width: 100%;
            max-height: calc(100vh - 2rem);
            overflow: hidden;
        }

        /* Left Side: Form */
        .form-side {
            padding: 2.2rem 2.8rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            margin-bottom: 0.9rem;
        }

        .logo-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            color: #ffffff;
            box-shadow: 0 8px 16px -4px rgba(79, 70, 229, 0.35);
        }

        .brand-name {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.02em;
        }

        .title {
            font-size: 1.48rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.025em;
            margin-bottom: 0.2rem;
        }

        .subtitle {
            color: #64748b;
            font-size: 0.83rem;
            margin-bottom: 1.15rem;
        }

        .form-label {
            color: #334155;
            font-weight: 600;
            font-size: 0.8rem;
            margin-bottom: 0.25rem;
        }

        .form-control {
            background-color: #f8fafc;
            border: 1.5px solid #e2e8f0;
            color: #0f172a;
            border-radius: 10px;
            padding: 0.58rem 0.85rem;
            font-size: 0.88rem;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            background-color: #ffffff;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
            color: #0f172a;
        }

        .form-control::placeholder {
            color: #94a3b8;
            font-size: 0.84rem;
        }

        .form-check-input {
            border-color: #cbd5e1;
            width: 0.95em;
            height: 0.95em;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: #4f46e5;
            border-color: #4f46e5;
        }

        .form-check-label {
            color: #64748b;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
        }

        .btn-login {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
            border: none;
            color: #ffffff;
            font-weight: 600;
            padding: 0.62rem 1rem;
            border-radius: 10px;
            font-size: 0.88rem;
            box-shadow: 0 8px 18px -4px rgba(79, 70, 229, 0.35);
            transition: all 0.2s ease;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #4338ca, #3730a3);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin: 0.85rem 0;
            color: #94a3b8;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }

        .btn-freelancer {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #ea580c;
            font-weight: 600;
            padding: 0.62rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-freelancer:hover {
            background: #ffedd5;
            border-color: #fdba74;
            color: #c2410c;
        }

        /* Right Side: Visual Scene */
        .visual-side {
            background: linear-gradient(150deg, #f0f4ff 0%, #e0e7ff 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.8rem;
            position: relative;
            overflow: hidden;
            border-left: 1px solid #e2e8f0;
        }

        .scene-wrapper {
            position: relative;
            width: 100%;
            max-width: 360px;
            height: 290px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .illustration-svg {
            width: 100%;
            max-width: 280px;
            height: auto;
            z-index: 1;
        }

        /* Glassmorphism Earning Cards */
        .glass-card {
            position: absolute;
            background: rgba(255, 255, 255, 0.72);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.95);
            border-radius: 14px;
            padding: 0.5rem 0.75rem;
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.12), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
            display: flex;
            align-items: center;
            gap: 0.55rem;
            z-index: 3;
            transition: transform 0.2s ease;
            animation: float 4s ease-in-out infinite;
        }

        .glass-card:hover {
            transform: scale(1.04) translateY(-2px);
        }

        .glass-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            font-weight: 700;
        }

        .glass-info .glass-title {
            font-size: 0.68rem;
            color: #64748b;
            font-weight: 600;
            line-height: 1.1;
            margin: 0;
            white-space: nowrap;
        }

        .glass-info .glass-amount {
            font-size: 0.85rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
            margin: 0;
        }

        /* Positions & Variations of 3 Glassy Cards */
        .glass-1 {
            top: 10px;
            left: -5px;
            animation-delay: 0s;
        }
        .glass-1 .glass-icon {
            background: #dcfce7;
            color: #15803d;
        }

        .glass-2 {
            top: 25px;
            right: -10px;
            animation-delay: 1.5s;
        }
        .glass-2 .glass-icon {
            background: #e0e7ff;
            color: #4338ca;
        }

        .glass-3 {
            bottom: 22px;
            left: 20px;
            animation-delay: 2.7s;
        }
        .glass-3 .glass-icon {
            background: #ffedd5;
            color: #c2410c;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-6px);
            }
        }

        .feature-text {
            text-align: center;
            margin-top: 0.6rem;
            z-index: 2;
        }

        .feature-text h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e1b4b;
            margin-bottom: 0.2rem;
        }

        .feature-text p {
            font-size: 0.78rem;
            color: #4338ca;
            margin: 0;
            line-height: 1.35;
        }

        /* Ambient Glow Blobs */
        .ambient-glow {
            position: absolute;
            border-radius: 50%;
            filter: blur(40px);
            z-index: 0;
            opacity: 0.65;
        }

        .glow-1 {
            width: 170px;
            height: 170px;
            background: #818cf8;
            top: -20px;
            right: -20px;
        }

        .glow-2 {
            width: 140px;
            height: 140px;
            background: #fed7aa;
            bottom: -20px;
            left: -20px;
        }

        @media (max-width: 767.98px) {
            html, body {
                height: auto;
                min-height: 100vh;
                overflow-y: auto;
            }
            .form-side {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>

    <div class="auth-container">
        <div class="row g-0 align-items-stretch h-100">
            <!-- Left Side: Login Form -->
            <div class="col-md-6 form-side">
                <div class="brand-badge">
                    <div class="logo-icon">
                        <i class="bi bi-person-workspace"></i>
                    </div>
                    <span class="brand-name">{{ config('app.name', 'FreelanceHub') }}</span>
                </div>

                <h1 class="title">Welcome back</h1>
                <p class="subtitle">Log in to manage projects, invoices, and payouts</p>

                @if(session('success'))
                    <div class="alert alert-success border-0 bg-success-subtle text-success py-1 px-3 rounded-2 mb-2 small">
                        <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger border-0 bg-danger-subtle text-danger py-1 px-3 rounded-2 mb-2 small">
                        <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger border-0 bg-danger-subtle text-danger py-1 px-3 rounded-2 mb-2 small">
                        @foreach($errors->all() as $error)
                            <div><i class="bi bi-dot"></i> {{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-2">
                        <label for="login" class="form-label">Username or Email</label>
                        <input type="text"
                               id="login"
                               name="login"
                               class="form-control"
                               placeholder="e.g. freelancer@studio.com"
                               value="{{ old('login') }}"
                               required
                               autofocus>
                    </div>

                    <div class="mb-2">
                        <label for="password" class="form-label">Password</label>
                        <input type="password"
                               id="password"
                               name="password"
                               class="form-control"
                               placeholder="••••••••"
                               required>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2 pt-1">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">Keep me logged in</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-login w-100">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                    </button>
                </form>

                <div class="divider">or continue with</div>

                <a href="{{ route('freelancer.connect') }}" class="btn btn-freelancer w-100">
                    <i class="bi bi-briefcase me-2"></i> Connect Freelancer Account
                </a>
            </div>

            <!-- Right Side: 2D Freelancer + 3 Glassy Notifications -->
            <div class="col-md-6 d-none d-md-flex visual-side">
                <div class="ambient-glow glow-1"></div>
                <div class="ambient-glow glow-2"></div>

                <div class="scene-wrapper">
                    <!-- Glass Earning Notification 1 -->
                    <div class="glass-card glass-1">
                        <div class="glass-icon">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <div class="glass-info">
                            <p class="glass-title">Milestone Released</p>
                            <p class="glass-amount">+$1,450.00</p>
                        </div>
                    </div>

                    <!-- Glass Earning Notification 2 -->
                    <div class="glass-card glass-2">
                        <div class="glass-icon">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                        <div class="glass-info">
                            <p class="glass-title">Project Paid</p>
                            <p class="glass-amount">+$2,800.00</p>
                        </div>
                    </div>

                    <!-- Glass Earning Notification 3 -->
                    <div class="glass-card glass-3">
                        <div class="glass-icon">
                            <i class="bi bi-arrow-down-left-circle"></i>
                        </div>
                        <div class="glass-info">
                            <p class="glass-title">Hourly Payout</p>
                            <p class="glass-amount">+$620.00</p>
                        </div>
                    </div>

                    <!-- 2D Freelancer Character on Laptop (SVG) -->
                    <svg class="illustration-svg" viewBox="0 0 360 280" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Modern Curved Ergonomic Chair Back -->
                        <path d="M140 100C140 70 220 70 220 100V190H140V100Z" fill="#CBD5E1"/>
                        <path d="M146 106C146 82 214 82 214 106V178H146V106Z" fill="#94A3B8"/>
                        <rect x="176" y="190" width="8" height="40" rx="4" fill="#64748b"/>

                        <!-- Desk Table Top -->
                        <rect x="50" y="210" width="260" height="8" rx="4" fill="#64748B"/>
                        <!-- Legs -->
                        <rect x="75" y="218" width="6" height="42" rx="3" fill="#94A3B8"/>
                        <rect x="279" y="218" width="6" height="42" rx="3" fill="#94A3B8"/>

                        <!-- Freelancer Body / Hoodie -->
                        <path d="M145 210C145 165 215 165 215 210H145Z" fill="#4F46E5"/>
                        <!-- Neck -->
                        <rect x="173" y="148" width="14" height="14" fill="#FBBF24" rx="3"/>
                        <!-- Head -->
                        <circle cx="180" cy="132" r="18" fill="#FCD34D"/>
                        <!-- Hair -->
                        <path d="M162 128C162 114 172 108 184 108C196 108 199 114 199 124C193 118 180 119 173 123C168 126 164 125 162 128Z" fill="#1E1B4B"/>
                        <!-- Modern Headphones -->
                        <path d="M162 126C160 126 158 132 158 136C158 140 160 144 163 144" stroke="#6366F1" stroke-width="4" stroke-linecap="round"/>
                        <path d="M198 126C200 126 202 132 202 136C202 140 200 144 197 144" stroke="#6366F1" stroke-width="4" stroke-linecap="round"/>
                        <path d="M162 127C162 116 170 110 180 110C190 110 198 116 198 127" stroke="#6366F1" stroke-width="3" stroke-linecap="round"/>
                        <!-- Eyeglasses -->
                        <rect x="169" y="130" width="8" height="6" rx="2" stroke="#1E1B4B" stroke-width="1.8"/>
                        <rect x="183" y="130" width="8" height="6" rx="2" stroke="#1E1B4B" stroke-width="1.8"/>
                        <line x1="177" y1="133" x2="183" y2="133" stroke="#1E1B4B" stroke-width="1.5"/>

                        <!-- Arms typing on laptop -->
                        <path d="M152 195C158 190 170 203 175 205" stroke="#FBBF24" stroke-width="5" stroke-linecap="round"/>
                        <path d="M208 195C202 190 190 203 185 205" stroke="#FBBF24" stroke-width="5" stroke-linecap="round"/>

                        <!-- Laptop -->
                        <polygon points="163,184 197,184 204,206 156,206" fill="#3B82F6"/>
                        <rect x="150" y="206" width="60" height="4" rx="2" fill="#94A3B8"/>
                        <!-- Screen Logo/Glow -->
                        <polygon points="166,186 194,186 199,203 161,203" fill="#60A5FA"/>
                        <circle cx="180" cy="195" r="3" fill="#FFFFFF"/>

                        <!-- Coffee Mug with steam -->
                        <rect x="236" y="196" width="12" height="14" rx="3" fill="#FFFFFF"/>
                        <path d="M248 200C250 200 251 202 251 204C251 206 250 208 248 208" stroke="#FFFFFF" stroke-width="2"/>
                        <path d="M240 190C240 188 242 187 242 185" stroke="#94A3B8" stroke-width="1.5" stroke-linecap="round"/>
                        <path d="M244 192C244 190 246 189 246 187" stroke="#94A3B8" stroke-width="1.5" stroke-linecap="round"/>

                        <!-- Plant on Desk -->
                        <path d="M102 210H116L113 194H105L102 210Z" fill="#F97316"/>
                        <path d="M109 194C109 180 98 176 98 176C98 176 109 185 109 194Z" fill="#10B981"/>
                        <path d="M109 190C109 174 120 170 120 170C120 170 109 180 109 190Z" fill="#059669"/>
                    </svg>
                </div>

                <div class="feature-text">
                    <h3>Track & Collect Earnings</h3>
                    <p>Manage multiple clients, deliver contracts, and withdraw your funds effortlessly.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>