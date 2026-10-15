<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, viewport-fit=cover">
    @php
        $companyName = (isset($companySettings) && $companySettings?->company_name)
            ? $companySettings->company_name
            : config('app.name', 'STGlobal');
        $logoLight = (isset($companySettings) && $companySettings?->logo)
            ? \Storage::disk('public_uploads')->url($companySettings->logo)
            : asset('assets/images/logo.png');
        $logoDark = (isset($companySettings) && $companySettings?->logo)
            ? \Storage::disk('public_uploads')->url($companySettings->logo)
            : (file_exists(public_path('assets/images/logo-black.png')) ? asset('assets/images/logo-black.png') : asset('assets/images/logo.png'));
    @endphp
    <title>Sign In &mdash; {{ $companyName }}</title>
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="manifest" href="{{ asset('manifest.json') }}">

    <style>
        /* ── RESET & BASE VARIABLES ── */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }

        :root {
            --primary-900: #0e3922;
            --primary-800: #14532d;
            --primary-700: #15803d;
            --primary-600: #16a34a;
            --primary-500: #22c55e;
            --primary-400: #4ade80;
            --primary-100: #dcfce7;
            --primary-50:  #f0fdf4;
            --accent-lime:  #bef264;
            --accent-glow:  rgba(34, 197, 94, 0.28);

            --slate-900:   #0f172a;
            --slate-800:   #1e293b;
            --slate-700:   #334155;
            --slate-600:   #475569;
            --slate-500:   #64748b;
            --slate-400:   #94a3b8;
            --slate-300:   #cbd5e1;
            --slate-200:   #e2e8f0;
            --slate-100:   #f1f5f9;
            --slate-50:    #f8fafc;

            --danger-500:  #ef4444;
            --danger-50:   #fef2f2;
            --danger-200:  #fecaca;
            --danger-700:  #b91c1c;

            --font-sans: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-display: 'Outfit', var(--font-sans);
            --radius-lg: 16px;
            --radius-md: 12px;
            --radius-sm: 8px;
            --shadow-subtle: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            --shadow-card: 0 20px 40px -15px rgba(15, 23, 42, 0.07), 0 0 1px 1px rgba(15, 23, 42, 0.05);
            --shadow-primary: 0 10px 25px -5px rgba(22, 163, 74, 0.35);
        }

        html, body {
            height: 100%;
            font-family: var(--font-sans);
            color: var(--slate-800);
            background-color: var(--slate-50);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* ── OVERALL LAYOUT CONTAINER ── */
        .auth-container {
            display: flex;
            min-height: 100vh;
            min-height: 100dvh;
            width: 100%;
            position: relative;
            overflow-x: hidden;
        }

        /* ── HERO BRANDING PANEL (DESKTOP) ── */
        .hero-panel {
            display: none;
            position: relative;
            background: radial-gradient(circle at 10% 20%, #15803d 0%, #0e3922 90%);
            color: #ffffff;
            padding: clamp(32px, 5vw, 64px);
            overflow: hidden;
            flex-direction: column;
            justify-content: space-between;
            z-index: 1;
        }

        @media (min-width: 1024px) {
            .hero-panel {
                display: flex;
                flex: 1 1 50%;
                max-width: 55%;
            }
        }

        /* Hero Ambient & Decorative Elements */
        .hero-mesh {
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(at 0% 0%, rgba(74, 222, 128, 0.25) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(190, 242, 100, 0.2) 0px, transparent 40%),
                radial-gradient(at 100% 100%, rgba(21, 128, 61, 0.4) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(14, 57, 34, 0.5) 0px, transparent 50%);
            pointer-events: none;
            z-index: 1;
        }

        .hero-pattern {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.12) 1.2px, transparent 1.2px);
            background-size: 32px 32px;
            opacity: 0.8;
            pointer-events: none;
            z-index: 1;
        }

        .hero-glow-circle {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            z-index: 1;
        }
        .glow-1 {
            width: 440px;
            height: 440px;
            background: rgba(74, 222, 128, 0.18);
            top: -120px;
            right: -100px;
        }
        .glow-2 {
            width: 320px;
            height: 320px;
            background: rgba(190, 242, 100, 0.15);
            bottom: 40px;
            left: -80px;
        }

        /* Floating decorative geometric rings */
        .geo-ring {
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.12);
            pointer-events: none;
            z-index: 1;
        }
        .ring-1 { width: 480px; height: 480px; top: -160px; right: -160px; }
        .ring-2 { width: 280px; height: 280px; top: -60px; right: -60px; border-color: rgba(255,255,255,0.07); }
        .ring-3 { width: 340px; height: 340px; bottom: -80px; left: -100px; border-color: rgba(255,255,255,0.08); }

        /* Hero Content Sections */
        .hero-header {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .hero-logo-box {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .hero-logo-img {
            max-height: 52px;
            width: auto;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }
        .hero-brand-name {
            font-family: var(--font-display);
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.02em;
        }

        .hero-center {
            position: relative;
            z-index: 2;
            padding: 32px 0;
            max-width: 540px;
        }
        .hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            color: var(--accent-lime);
            margin-bottom: 24px;
        }
        .hero-pill-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--accent-lime);
            box-shadow: 0 0 10px var(--accent-lime);
            animation: pulse-dot 2s infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.85); }
        }

        .hero-title {
            font-family: var(--font-display);
            font-size: clamp(32px, 3.2vw, 44px);
            font-weight: 800;
            line-height: 1.18;
            letter-spacing: -0.03em;
            margin-bottom: 16px;
            color: #ffffff;
        }
        .hero-title span {
            background: linear-gradient(135deg, #ffffff 40%, var(--accent-lime) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-desc {
            font-size: 16px;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.82);
            margin-bottom: 36px;
            font-weight: 400;
        }

        /* Hero Feature Grid Cards */
        .feature-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
            margin-bottom: 36px;
        }
        .feature-card {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: var(--radius-md);
            transition: transform 0.2s ease, background 0.2s ease;
        }
        .feature-card:hover {
            transform: translateX(4px);
            background: rgba(255, 255, 255, 0.12);
        }
        .feature-card-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: var(--accent-lime);
        }
        .feature-card-icon svg {
            width: 20px;
            height: 20px;
            stroke-width: 2.2;
        }
        .feature-card-content h4 {
            font-size: 14px;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 2px;
        }
        .feature-card-content p {
            font-size: 12.5px;
            color: rgba(255, 255, 255, 0.72);
            line-height: 1.4;
        }

        .hero-footer {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12.5px;
            color: rgba(255, 255, 255, 0.55);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
        }

        /* ── AUTH FORM PANEL (RESPONSIVE) ── */
        .form-panel {
            flex: 1 1 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            background-color: #ffffff;
            padding: clamp(24px, 4vw, 48px) clamp(20px, 4vw, 40px);
            position: relative;
            min-height: 100vh;
            min-height: 100dvh;
        }

        @media (min-width: 1024px) {
            .form-panel {
                flex: 1 1 50%;
                background-color: var(--slate-50);
                justify-content: center;
                padding: 48px 40px;
            }
        }

        /* Ambient Mobile Backdrop Glow */
        .mobile-glow-top {
            display: block;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 260px;
            background: linear-gradient(180deg, rgba(220, 252, 231, 0.55) 0%, rgba(255, 255, 255, 0) 100%);
            pointer-events: none;
            z-index: 0;
        }
        @media (min-width: 1024px) {
            .mobile-glow-top { display: none; }
        }

        /* Form Content Container */
        .form-content {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 440px;
            margin: auto 0;
        }

        /* Form Card wrapper for larger screens */
        @media (min-width: 1024px) {
            .form-content {
                background: #ffffff;
                padding: 44px 40px;
                border-radius: var(--radius-lg);
                box-shadow: var(--shadow-card);
                border: 1px solid var(--slate-200);
            }
        }

        /* Mobile Brand Header */
        .mobile-brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-bottom: 28px;
        }
        @media (min-width: 1024px) {
            .mobile-brand {
                display: none;
            }
        }
        .mobile-brand-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }
        .mobile-brand-logo img {
            max-height: 56px;
            width: auto;
            object-fit: contain;
        }
        .mobile-brand-title {
            font-family: var(--font-display);
            font-size: 20px;
            font-weight: 700;
            color: var(--slate-900);
            letter-spacing: -0.02em;
        }

        /* Form Header */
        .form-header {
            margin-bottom: 28px;
            text-align: left;
        }
        .form-header-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--primary-700);
            background: var(--primary-100);
            padding: 4px 10px;
            border-radius: 6px;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .form-title {
            font-family: var(--font-display);
            font-size: clamp(24px, 2.5vw, 30px);
            font-weight: 700;
            color: var(--slate-900);
            letter-spacing: -0.03em;
            line-height: 1.25;
            margin-bottom: 8px;
        }
        .form-subtitle {
            font-size: 14.5px;
            color: var(--slate-500);
            line-height: 1.5;
        }

        /* Alerts & Feedback */
        .alert-box {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            border-radius: var(--radius-md);
            margin-bottom: 24px;
            font-size: 13.5px;
            line-height: 1.45;
            animation: fadeIn 0.3s ease;
        }
        .alert-error {
            background-color: var(--danger-50);
            border: 1px solid var(--danger-200);
            color: var(--danger-700);
        }
        .alert-success {
            background-color: var(--primary-50);
            border: 1px solid var(--primary-100);
            color: var(--primary-800);
        }
        .alert-icon {
            flex-shrink: 0;
            margin-top: 1px;
        }
        .alert-icon svg {
            width: 18px;
            height: 18px;
        }
        .alert-content ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .alert-content li + li {
            margin-top: 4px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Form Inputs */
        .input-group {
            margin-bottom: 20px;
        }
        .input-label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            font-weight: 600;
            color: var(--slate-700);
            margin-bottom: 8px;
        }
        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-icon-left {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--slate-400);
            pointer-events: none;
            transition: color 0.2s ease;
        }
        .input-icon-left svg {
            width: 18px;
            height: 18px;
        }

        .form-control {
            width: 100%;
            height: 48px;
            padding: 0 16px 0 44px;
            font-family: var(--font-sans);
            font-size: 14.5px;
            color: var(--slate-900);
            background-color: #ffffff;
            border: 1.5px solid var(--slate-200);
            border-radius: var(--radius-md);
            outline: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            -webkit-appearance: none;
            appearance: none;
        }
        .form-control::placeholder {
            color: var(--slate-400);
            font-weight: 400;
        }
        .form-control:hover {
            border-color: var(--slate-300);
        }
        .form-control:focus {
            border-color: var(--primary-600);
            box-shadow: 0 0 0 3.5px var(--accent-glow);
        }
        .form-control:focus + .input-icon-left,
        .input-wrapper:focus-within .input-icon-left {
            color: var(--primary-600);
        }

        .form-control.is-invalid {
            border-color: var(--danger-500);
            background-color: #fffbfa;
        }
        .form-control.is-invalid:focus {
            box-shadow: 0 0 0 3.5px rgba(239, 68, 68, 0.15);
        }

        .input-password-field {
            padding-right: 46px;
        }

        /* Password toggle button */
        .password-toggle-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: var(--slate-400);
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.15s ease, background-color 0.15s ease;
        }
        .password-toggle-btn:hover {
            color: var(--slate-700);
            background-color: var(--slate-100);
        }
        .password-toggle-btn:focus-visible {
            outline: 2px solid var(--primary-600);
            outline-offset: 1px;
        }
        .password-toggle-btn svg {
            width: 18px;
            height: 18px;
        }

        .field-feedback {
            font-size: 12.5px;
            color: var(--danger-500);
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
        }
        .field-feedback svg {
            width: 14px;
            height: 14px;
            flex-shrink: 0;
        }

        /* Remember & Options Row */
        .form-options-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 22px 0 26px;
            font-size: 13.5px;
            gap: 12px;
            flex-wrap: wrap;
        }
        .checkbox-container {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            cursor: pointer;
            user-select: none;
            color: var(--slate-600);
            font-weight: 500;
            padding: 4px 0;
        }
        .custom-checkbox {
            position: relative;
            width: 18px;
            height: 18px;
            border: 1.8px solid var(--slate-300);
            border-radius: 5px;
            background: #ffffff;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .checkbox-input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        .checkbox-input:checked + .custom-checkbox {
            background-color: var(--primary-600);
            border-color: var(--primary-600);
        }
        .checkbox-input:focus-visible + .custom-checkbox {
            box-shadow: 0 0 0 3px var(--accent-glow);
        }
        .checkbox-check-icon {
            width: 12px;
            height: 12px;
            stroke: #ffffff;
            stroke-width: 2.8;
            stroke-linecap: round;
            stroke-linejoin: round;
            opacity: 0;
            transform: scale(0.6);
            transition: all 0.15s ease;
        }
        .checkbox-input:checked + .custom-checkbox .checkbox-check-icon {
            opacity: 1;
            transform: scale(1);
        }
        .checkbox-container:hover .custom-checkbox {
            border-color: var(--primary-500);
        }

        /* Submit Button */
        .btn-auth-submit {
            width: 100%;
            height: 48px;
            border: none;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, #15803d 0%, #16a34a 50%, #22c55e 100%);
            color: #ffffff;
            font-family: var(--font-sans);
            font-size: 15px;
            font-weight: 600;
            letter-spacing: -0.01em;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: var(--shadow-primary);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .btn-auth-submit::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0);
            transition: background 0.2s;
        }
        .btn-auth-submit:hover {
            transform: translateY(-1.5px);
            box-shadow: 0 14px 28px -6px rgba(22, 163, 74, 0.42);
        }
        .btn-auth-submit:hover::after {
            background: rgba(255, 255, 255, 0.08);
        }
        .btn-auth-submit:active {
            transform: translateY(0);
            box-shadow: 0 6px 16px -2px rgba(22, 163, 74, 0.35);
        }
        .btn-auth-submit:focus-visible {
            outline: 2px solid var(--primary-600);
            outline-offset: 3px;
        }
        .btn-auth-submit:disabled {
            opacity: 0.75;
            cursor: not-allowed;
            transform: none;
        }

        /* Loading Spinner inside Button */
        .btn-spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2.2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #ffffff;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Form Footer / Badges */
        .form-panel-footer {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 440px;
            padding-top: 24px;
            margin-top: 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            font-size: 12.5px;
            color: var(--slate-400);
            text-align: center;
        }
        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--slate-500);
            font-size: 12px;
            font-weight: 500;
        }
        .security-badge svg {
            width: 14px;
            height: 14px;
            color: var(--primary-600);
        }
        .credit-link {
            color: var(--slate-500);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.15s;
        }
        .credit-link:hover {
            color: var(--primary-700);
        }

        /* ── RESPONSIVE TWEAKS ── */
        @media (max-width: 480px) {
            .form-panel {
                padding: 24px 16px;
            }
            .form-content {
                padding: 0;
            }
            .form-title {
                font-size: 23px;
            }
            .form-subtitle {
                font-size: 13.5px;
            }
            .form-control {
                height: 46px;
                font-size: 14px;
            }
            .btn-auth-submit {
                height: 46px;
                font-size: 14.5px;
            }
        }

        @media (max-height: 700px) and (min-width: 1024px) {
            .hero-panel {
                padding: 24px 36px;
            }
            .feature-grid {
                margin-bottom: 20px;
            }
            .hero-desc {
                margin-bottom: 20px;
            }
            .form-content {
                padding: 32px 36px;
            }
        }
    </style>
</head>
<body>

<div class="auth-container">

    {{-- ════════════════════════════════════════════════════
         LEFT HERO BRANDING PANEL (Desktop >= 1024px)
         ════════════════════════════════════════════════════ --}}
    <div class="hero-panel" aria-hidden="true">
        <div class="hero-mesh"></div>
        <div class="hero-pattern"></div>
        <div class="hero-glow-circle glow-1"></div>
        <div class="hero-glow-circle glow-2"></div>
        <div class="geo-ring ring-1"></div>
        <div class="geo-ring ring-2"></div>
        <div class="geo-ring ring-3"></div>

        <!-- Top Brand / Logo -->
        <header class="hero-header">
            <div class="hero-logo-box">
                @if(isset($companySettings) && $companySettings?->logo)
                    <img src="{{ $logoLight }}" alt="{{ $companyName }}" class="hero-logo-img">
                @else
                    <img src="{{ $logoLight }}" alt="{{ $companyName }}" class="hero-logo-img">
                    <span class="hero-brand-name">{{ $companyName }}</span>
                @endif
            </div>
        </header>

        <!-- Center Value Proposition -->
        <div class="hero-center">
            <div class="hero-pill">
                <span class="hero-pill-dot"></span>
                <span>Enterprise B2B Commerce</span>
            </div>

            <h1 class="hero-title">
                Smarter Trading.<br>
                <span>Seamless Operations.</span>
            </h1>

            <p class="hero-desc">
                Streamline wholesale distribution, real-time inventory tracking, multi-tier pricing, and automated invoicing with effortless precision.
            </p>

            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-card-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <div class="feature-card-content">
                        <h4>Live Inventory &amp; Stock Synchronization</h4>
                        <p>Track warehouse stock, dual quantities, and movements instantly.</p>
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-card-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="feature-card-content">
                        <h4>Invoicing &amp; Thermal Print Dispatch</h4>
                        <p>Generate sales invoices, proformas, delivery orders, and print jobs.</p>
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-card-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <div class="feature-card-content">
                        <h4>Multi-Tenant Role-Based Security</h4>
                        <p>Granular staff permissions, subscriber isolation, and audit trail logs.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hero Footer -->
        <footer class="hero-footer">
            <span>&copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.</span>
            <span>Version 2.0</span>
        </footer>
    </div>

    {{-- ════════════════════════════════════════════════════
         RIGHT AUTHENTICATION FORM PANEL (All Screen Sizes)
         ════════════════════════════════════════════════════ --}}
    <main class="form-panel">
        <div class="mobile-glow-top"></div>

        <div class="form-content">

            <!-- Mobile & Tablet Branding Header -->
            <div class="mobile-brand">
                <div class="mobile-brand-logo">
                    <img src="{{ $logoDark }}" alt="{{ $companyName }}">
                </div>
                <div class="mobile-brand-title">{{ $companyName }}</div>
            </div>

            <!-- Form Title & Greeting -->
            <div class="form-header">
                <div class="form-header-badge">Portal Access</div>
                <h2 class="form-title">Welcome back</h2>
                <p class="form-subtitle">Enter your credentials to access your dashboard</p>
            </div>

            <!-- Session Status / Notification -->
            @if (session('status'))
                <div class="alert-box alert-success" role="alert">
                    <span class="alert-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                    <div class="alert-content">
                        {{ session('status') }}
                    </div>
                </div>
            @endif

            <!-- Validation Errors Alert -->
            @if (isset($errors) && $errors->any())
                <div class="alert-box alert-error" role="alert">
                    <span class="alert-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </span>
                    <div class="alert-content">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Sign In Form -->
            <form action="{{ route('login') }}" method="POST" id="loginForm" autocomplete="on">
                @csrf

                <!-- Login (Email or Username) -->
                <div class="input-group">
                    <label for="loginInput" class="input-label">
                        <span>Email or Username</span>
                    </label>
                    <div class="input-wrapper">
                        <input
                            type="text"
                            name="login"
                            id="loginInput"
                            class="form-control @error('login') is-invalid @enderror"
                            value="{{ old('login') }}"
                            placeholder="admin@example.com or staff_username"
                            required
                            autofocus
                            autocomplete="username"
                            autocapitalize="none"
                            autocorrect="off"
                            spellcheck="false"
                            aria-required="true"
                        >
                        <span class="input-icon-left" aria-hidden="true">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </span>
                    </div>
                    @error('login')
                        <div class="field-feedback" role="alert">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="input-group">
                    <div class="input-label">
                        <label for="passwordInput">Password</label>
                    </div>
                    <div class="input-wrapper">
                        <input
                            type="password"
                            name="password"
                            id="passwordInput"
                            class="form-control input-password-field @error('password') is-invalid @enderror"
                            placeholder="Enter your account password"
                            required
                            autocomplete="current-password"
                            aria-required="true"
                        >
                        <span class="input-icon-left" aria-hidden="true">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </span>
                        <button
                            type="button"
                            class="password-toggle-btn"
                            id="togglePasswordBtn"
                            aria-label="Show password"
                            title="Show/Hide password"
                            tabindex="-1"
                        >
                            <!-- Eye icon (shown by default) -->
                            <svg id="eyeIconShow" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <!-- Eye off icon (hidden by default) -->
                            <svg id="eyeIconHide" style="display: none;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <div class="field-feedback" role="alert">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>

                <!-- Remember Me Checkbox -->
                <div class="form-options-row">
                    <label class="checkbox-container">
                        <input
                            type="checkbox"
                            name="remember"
                            id="rememberMe"
                            class="checkbox-input"
                            {{ old('remember') ? 'checked' : '' }}
                        >
                        <span class="custom-checkbox" aria-hidden="true">
                            <svg class="checkbox-check-icon" viewBox="0 0 16 16" fill="none">
                                <path d="M3.5 8.5L6.5 11.5L12.5 4.5" />
                            </svg>
                        </span>
                        <span>Remember my session</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-auth-submit" id="submitBtn">
                    <span class="btn-spinner" id="btnSpinner" aria-hidden="true"></span>
                    <span id="btnText">Sign In to Account</span>
                    <svg id="btnArrow" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
            </form>
        </div>

        <!-- Footer / Credits -->
        <footer class="form-panel-footer">
            <div class="security-badge">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span>End-to-End Encrypted Session</span>
            </div>
            <div>
                Powered by <a href="#" class="credit-link">Techgeeks</a> &bull; &copy; {{ date('Y') }} {{ $companyName }}
            </div>
        </footer>
    </main>

</div>

<script>
    // Toggle Password Visibility
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const pwdInput = document.getElementById('passwordInput');
    const eyeShow = document.getElementById('eyeIconShow');
    const eyeHide = document.getElementById('eyeIconHide');

    if (toggleBtn && pwdInput) {
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const isPassword = pwdInput.type === 'password';
            pwdInput.type = isPassword ? 'text' : 'password';
            eyeShow.style.display = isPassword ? 'none' : 'block';
            eyeHide.style.display = isPassword ? 'block' : 'none';
            toggleBtn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            pwdInput.focus();
        });
    }

    // Submit state loading indicator
    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnSpinner = document.getElementById('btnSpinner');
    const btnText = document.getElementById('btnText');
    const btnArrow = document.getElementById('btnArrow');

    if (loginForm && submitBtn) {
        loginForm.addEventListener('submit', function() {
            if (loginForm.checkValidity()) {
                submitBtn.disabled = true;
                if (btnSpinner) btnSpinner.style.display = 'inline-block';
                if (btnArrow) btnArrow.style.display = 'none';
                if (btnText) btnText.textContent = 'Signing in...';
            }
        });
    }
</script>
</body>
</html>
