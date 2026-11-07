@extends('layouts.settings')

@section('settings_content')
<style>
    .security-card {
        border-radius: 14px;
        border: 1px solid #e2e8f0;
    }
    .input-group-text {
        background-color: #f8fafc;
        border-color: #cbd5e1;
        cursor: pointer;
    }
    .form-control:focus {
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    }
    .requirement-item {
        font-size: 12.5px;
        color: #64748b;
        transition: all 0.2s ease;
    }
    .requirement-item.valid {
        color: #10b981;
        font-weight: 600;
    }
    .requirement-item i {
        font-size: 14px;
    }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show py-2 px-3 small mb-3 border-0 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show py-2 px-3 small mb-3 border-0 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
                    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card security-card shadow-sm border-0">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-3">
                            <i class="bi bi-shield-lock-fill fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Set New Password</h5>
                            <small class="text-muted">Enter a new secure password for your account.</small>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form action="{{ route('password.update.custom') }}" method="POST" id="changePasswordForm">
                        @csrf
                        @method('PUT')

                        <!-- 1. New Password -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark mb-1" for="new_password">
                                New Password
                            </label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control @error('new_password') is-invalid @enderror" 
                                       id="new_password" 
                                       name="new_password" 
                                       placeholder="Enter new password" 
                                       onkeyup="checkPasswordStrength(this.value)"
                                       required>
                                <span class="input-group-text" onclick="togglePasswordVisibility('new_password', 'new_toggle_icon')">
                                    <i class="bi bi-eye text-muted" id="new_toggle_icon"></i>
                                </span>
                                @error('new_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Live Password Checklist -->
                            <div class="bg-light rounded-3 p-3 mt-2 border">
                                <span class="d-block text-muted small fw-semibold mb-2" style="font-size: 11.5px;">Password requirements:</span>
                                <div class="row g-2">
                                    <div class="col-6 requirement-item" id="req-length">
                                        <i class="bi bi-circle me-1"></i> 8+ characters
                                    </div>
                                    <div class="col-6 requirement-item" id="req-upper">
                                        <i class="bi bi-circle me-1"></i> Uppercase letter
                                    </div>
                                    <div class="col-6 requirement-item" id="req-number">
                                        <i class="bi bi-circle me-1"></i> Number
                                    </div>
                                    <div class="col-6 requirement-item" id="req-special">
                                        <i class="bi bi-circle me-1"></i> Special symbol
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Confirm New Password -->
                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-dark mb-1" for="new_password_confirmation">
                                Confirm New Password
                            </label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="new_password_confirmation" 
                                       name="new_password_confirmation" 
                                       placeholder="Repeat new password" 
                                       required>
                                <span class="input-group-text" onclick="togglePasswordVisibility('new_password_confirmation', 'confirm_toggle_icon')">
                                    <i class="bi bi-eye text-muted" id="confirm_toggle_icon"></i>
                                </span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm px-3 fw-medium">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-sm px-4 py-2 fw-semibold shadow-sm d-flex align-items-center gap-1">
                                <i class="bi bi-key-fill"></i> Save Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function togglePasswordVisibility(fieldId, iconId) {
    const input = document.getElementById(fieldId);
    const icon = document.getElementById(iconId);

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

function checkPasswordStrength(password) {
    const rules = {
        'req-length': password.length >= 8,
        'req-upper': /[A-Z]/.test(password),
        'req-number': /[0-9]/.test(password),
        'req-special': /[^A-Za-z0-9]/.test(password)
    };

    for (const [id, isValid] of Object.entries(rules)) {
        const element = document.getElementById(id);
        const icon = element.querySelector('i');

        if (isValid) {
            element.classList.add('valid');
            icon.className = 'bi bi-check-circle-fill me-1';
        } else {
            element.classList.remove('valid');
            icon.className = 'bi bi-circle me-1';
        }
    }
}
</script>
@endsection