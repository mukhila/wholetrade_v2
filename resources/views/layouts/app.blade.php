<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-topbar-color="light" data-menu-color="dark" data-sidenav-size="default" data-skin="default">

<head>
    <meta charset="utf-8">
    @php
        $companyName = (isset($companySettings) && $companySettings?->company_name)
            ? $companySettings->company_name
            : config('app.name', 'STGlobal');
        $navLogoUrl = (isset($companySettings) && $companySettings?->logo)
            ? \Storage::disk('public_uploads')->url($companySettings->logo)
            : asset('assets/images/logo.png');
        $currentUser = Auth::user();
    @endphp
    <title>@yield('title', 'Dashboard') &mdash; {{ $companyName }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $companyName }} B2B Wholesale Trading & Management System">
    <meta name="author" content="Techgeeks">

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">

    <!-- PWA Meta -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#15803d">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ $companyName }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo-sm.png') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- Clear any old horizontal layout preference from storage -->
    <script>
        try {
            sessionStorage.removeItem("__UBOLD_CONFIG__");
        } catch(e) {}
    </script>

    <!-- Theme Config Js -->
    <script src="{{ asset('assets/js/config.js') }}"></script>
    <script>
        window.APP_URL = "{{ url('/') }}";
        if (window.APP_URL.includes('localhost') && !window.location.hostname.includes('localhost')) {
            window.APP_URL = window.location.origin;
        }
    </script>

    <!-- Vendor css -->
    <link href="{{ asset('assets/css/vendors.min.css') }}" rel="stylesheet" type="text/css">

    <!-- App css -->
    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css">

    @stack('styles')

    <style>
        /* ── MODERN VERTICAL SIDEBAR LAYOUT STYLES ── */
        :root {
            --app-font-sans: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            --app-font-heading: 'Outfit', var(--app-font-sans);
            --sidebar-width: 260px;
            --topbar-height: 70px;
            --sidebar-bg: #0f172a;
            --sidebar-hover-bg: rgba(255, 255, 255, 0.06);
            --sidebar-border: rgba(255, 255, 255, 0.08);
            --sidebar-text: #94a3b8;
            --sidebar-text-hover: #ffffff;
            --sidebar-active-bg: linear-gradient(135deg, #15803d 0%, #16a34a 100%);
            --sidebar-active-color: #ffffff;

            --emerald-800: #14532d;
            --emerald-700: #15803d;
            --emerald-600: #16a34a;
            --emerald-500: #22c55e;
            --emerald-50:  #f0fdf4;
            --slate-900:   #0f172a;
            --slate-800:   #1e293b;
            --slate-700:   #334155;
            --slate-600:   #475569;
            --slate-500:   #64748b;
            --slate-200:   #e2e8f0;
            --slate-100:   #f1f5f9;
            --slate-50:    #f8fafc;
        }

        body {
            font-family: var(--app-font-sans) !important;
            background-color: #f8fafc !important;
            color: var(--slate-800) !important;
            margin: 0;
            padding: 0;
        }

        /* ── LEFT SIDEBAR (SIDENAV) ── */
        .sidenav-menu {
            position: fixed !important;
            top: 0 !important;
            bottom: 0 !important;
            left: 0 !important;
            width: var(--sidebar-width) !important;
            background: var(--sidebar-bg) !important;
            border-right: 1px solid var(--sidebar-border) !important;
            z-index: 1040 !important;
            display: flex !important;
            flex-direction: column !important;
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.15) !important;
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), width 0.25s ease !important;
        }

        /* Sidebar Brand Header */
        .sidenav-brand {
            height: var(--topbar-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 18px;
            border-bottom: 1px solid var(--sidebar-border);
            flex-shrink: 0;
            background: rgba(15, 23, 42, 0.95);
        }
        .sidenav-brand a {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            overflow: hidden;
        }
        .sidenav-brand-logo {
            max-height: 42px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.25));
        }
        .sidenav-brand-text {
            font-family: var(--app-font-heading);
            font-size: 18px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.02em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .btn-close-sidebar {
            background: transparent;
            border: none;
            color: var(--sidebar-text);
            font-size: 18px;
            padding: 6px;
            border-radius: 6px;
            cursor: pointer;
            display: none;
            line-height: 1;
        }
        .btn-close-sidebar:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.1);
        }

        /* Sidebar Navigation Scroll Area */
        .sidenav-scroll {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 14px 12px 30px;
        }
        .sidenav-scroll::-webkit-scrollbar {
            width: 5px;
        }
        .sidenav-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidenav-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 4px;
        }
        .sidenav-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Navigation List & Items */
        .side-nav {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .side-nav-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(148, 163, 184, 0.6);
            padding: 16px 14px 6px;
            pointer-events: none;
            user-select: none;
        }

        .side-nav-item {
            position: relative;
        }

        .side-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 9px 14px;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.15s ease;
            position: relative;
        }

        .side-nav-link:hover {
            color: var(--sidebar-text-hover);
            background-color: var(--sidebar-hover-bg);
        }

        /* Active Main Link */
        .side-nav-link.active,
        .side-nav-item.active > .side-nav-link {
            background: var(--sidebar-active-bg) !important;
            color: var(--sidebar-active-color) !important;
            font-weight: 600;
            box-shadow: 0 4px 14px rgba(22, 163, 74, 0.35);
        }
        .side-nav-link.active .menu-icon,
        .side-nav-item.active > .side-nav-link .menu-icon {
            color: #ffffff !important;
        }

        .side-nav-link .menu-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            color: var(--sidebar-text);
            transition: color 0.15s ease;
        }
        .side-nav-link:hover .menu-icon {
            color: #ffffff;
        }
        .side-nav-link .menu-icon svg {
            width: 18px;
            height: 18px;
            stroke-width: 2.1;
        }

        .side-nav-link .menu-text {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Submenu Chevron Arrow */
        .side-nav-link .menu-arrow {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: auto;
            font-size: 11px;
            transition: transform 0.2s ease;
            opacity: 0.65;
        }
        .side-nav-link:hover .menu-arrow {
            opacity: 1;
        }
        .side-nav-link[aria-expanded="true"] .menu-arrow {
            transform: rotate(180deg);
        }

        /* Submenu Dropdown Items */
        .side-nav .sub-menu {
            list-style: none;
            padding: 4px 0 6px 18px;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 2px;
            border-left: 1.5px solid rgba(255, 255, 255, 0.1);
            margin-left: 22px;
        }
        .side-nav .sub-menu .side-nav-link {
            padding: 7px 12px;
            font-size: 13px;
            color: #94a3b8;
            border-radius: 6px;
        }
        .side-nav .sub-menu .side-nav-link:hover {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.05);
            transform: translateX(2px);
        }
        .side-nav .sub-menu .side-nav-link.active {
            background: rgba(22, 163, 74, 0.25) !important;
            color: #4ade80 !important;
            font-weight: 600;
            box-shadow: none !important;
        }

        /* ── TOPBAR (HEADER) IN VERTICAL LAYOUT ── */
        .app-topbar {
            height: var(--topbar-height) !important;
            background: #ffffff !important;
            border-bottom: 1px solid var(--slate-200) !important;
            margin-left: var(--sidebar-width) !important;
            position: sticky !important;
            top: 0 !important;
            z-index: 1020 !important;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04) !important;
            display: flex !important;
            align-items: center !important;
            transition: margin-left 0.25s ease !important;
        }

        .topbar-content {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
        }

        /* ── Global Page Search ── */
        #global-search-wrap {
            flex: 1; max-width: 420px; margin: 0 24px; position: relative;
        }
        #global-search-input {
            width: 100%; border: 1px solid var(--slate-200); border-radius: 10px;
            padding: 8px 36px 8px 38px; font-size: .875rem; outline: none;
            background: var(--slate-50); color: var(--slate-800);
            transition: border-color .15s, box-shadow .15s;
        }
        #global-search-input:focus {
            border-color: #15803d; box-shadow: 0 0 0 3px rgba(21,128,61,.12);
            background: #fff;
        }
        #global-search-icon {
            position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
            color: var(--slate-400); pointer-events: none; line-height: 1;
        }
        #global-search-clear {
            position: absolute; right: 9px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: var(--slate-400); cursor: pointer;
            padding: 2px; line-height: 1; display: none;
        }
        #global-search-clear:hover { color: var(--slate-600); }
        #global-search-dropdown {
            position: absolute; top: calc(100% + 6px); left: 0; right: 0;
            background: #fff; border: 1px solid var(--slate-200); border-radius: 10px;
            box-shadow: 0 8px 30px rgba(0,0,0,.12); z-index: 2000;
            max-height: 320px; overflow-y: auto; display: none;
        }
        .gs-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 14px; cursor: pointer; text-decoration: none;
            color: var(--slate-700); font-size: .875rem; border-radius: 0;
            transition: background .1s;
        }
        .gs-item:first-child { border-radius: 10px 10px 0 0; }
        .gs-item:last-child  { border-radius: 0 0 10px 10px; }
        .gs-item:only-child  { border-radius: 10px; }
        .gs-item:hover, .gs-item.gs-active {
            background: #f0fdf4; color: #15803d;
        }
        .gs-item .gs-icon {
            width: 30px; height: 30px; border-radius: 8px;
            background: #f0fdf4; color: #15803d;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; font-size: 14px;
        }
        .gs-item.gs-active .gs-icon { background: #dcfce7; }
        .gs-item .gs-label { font-weight: 500; }
        .gs-item .gs-category { font-size: .75rem; color: var(--slate-400); margin-left: auto; }
        .gs-no-result { padding: 12px 14px; color: var(--slate-400); font-size: .875rem; text-align: center; }
        @media (max-width: 576px) { #global-search-wrap { max-width: 160px; margin: 0 10px; } }

        /* Sidebar Toggle Hamburger */
        .sidenav-toggle-btn {
            background: var(--slate-100);
            border: 1px solid var(--slate-200);
            color: var(--slate-700);
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .sidenav-toggle-btn:hover {
            background: var(--emerald-50);
            color: var(--emerald-700);
            border-color: rgba(22, 163, 74, 0.3);
        }

        /* PWA Install Button */
        #pwa-install-btn {
            background: var(--emerald-50) !important;
            border: 1px solid rgba(22, 163, 74, 0.25) !important;
            color: var(--emerald-700) !important;
            border-radius: 20px !important;
            padding: 6px 14px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            transition: all 0.2s ease !important;
        }
        #pwa-install-btn:hover {
            background: var(--emerald-700) !important;
            color: #ffffff !important;
        }

        /* Topbar User Dropdown Pill */
        .topbar-user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 4px 12px 4px 5px;
            background: var(--slate-50);
            border: 1px solid var(--slate-200);
            border-radius: 9999px;
            text-decoration: none !important;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .topbar-user-pill:hover {
            background: #ffffff;
            border-color: var(--slate-300);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .user-avatar-circle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #15803d 0%, #16a34a 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            flex-shrink: 0;
            border: 2px solid #ffffff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .user-text-wrap {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
            text-align: left;
        }
        .user-name-label {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--slate-900);
            letter-spacing: -0.01em;
        }
        .user-role-label {
            font-size: 10.5px;
            font-weight: 600;
            color: var(--emerald-700);
            text-transform: capitalize;
        }

        /* Dropdown Menu */
        .dropdown-menu {
            border: 1px solid var(--slate-200) !important;
            border-radius: 12px !important;
            box-shadow: 0 16px 36px -4px rgba(15, 23, 42, 0.12), 0 0 1px 1px rgba(15, 23, 42, 0.05) !important;
            padding: 8px !important;
            background-color: #ffffff !important;
            animation: dropdownSlideIn 0.15s ease-out;
        }
        @keyframes dropdownSlideIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .dropdown-item {
            font-size: 13.5px !important;
            font-weight: 500 !important;
            color: var(--slate-700) !important;
            padding: 8px 14px !important;
            border-radius: 8px !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            transition: all 0.15s ease !important;
        }
        .dropdown-item:hover {
            background-color: var(--emerald-50) !important;
            color: var(--emerald-700) !important;
            transform: translateX(2px);
        }
        .dropdown-item.dropdown-danger:hover {
            background-color: #fef2f2 !important;
            color: #dc2626 !important;
        }

        /* ── MAIN CONTENT PAGE ── */
        .content-page {
            margin-left: var(--sidebar-width) !important;
            min-height: calc(100vh - var(--topbar-height)) !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            transition: margin-left 0.25s ease !important;
        }

        .content-page > .container-fluid {
            padding: 24px !important;
            flex: 1 0 auto;
        }

        /* Prevent double margin or broken layout if views contain nested .content-page */
        .content-page .content-page {
            margin-left: 0 !important;
            min-height: auto !important;
            padding: 0 !important;
            display: block !important;
            transition: none !important;
        }
        .content-page .content-page > .container-fluid {
            padding: 0 !important;
        }

        /* ── FOOTER ── */
        .footer {
            background: #ffffff !important;
            border-top: 1px solid var(--slate-200) !important;
            padding: 16px 24px !important;
            color: var(--slate-500) !important;
            font-size: 13px !important;
            margin-top: auto;
        }
        .footer a {
            color: var(--emerald-700);
            text-decoration: none;
            font-weight: 600;
        }

        /* Backdrop overlay for mobile drawer */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1030;
            transition: opacity 0.25s ease;
        }

        /* ── RESPONSIVE ADAPTATIONS (MOBILE & TABLET) ── */
        @media (max-width: 991.98px) {
            .sidenav-menu {
                transform: translateX(-100%) !important;
            }
            .sidebar-open .sidenav-menu {
                transform: translateX(0) !important;
            }
            .sidebar-open .sidebar-backdrop {
                display: block !important;
            }
            .btn-close-sidebar {
                display: inline-flex !important;
            }
            .app-topbar {
                margin-left: 0 !important;
            }
            .content-page {
                margin-left: 0 !important;
            }
            .topbar-content {
                padding: 0 16px;
            }
            .content-page > .container-fluid {
                padding: 16px !important;
            }
        }

        /* ── PRINT MEDIA RULES (PRESERVED) ── */
        @media print {
            body * {
                visibility: hidden;
            }
            .sidenav-menu, .app-topbar, .footer, .page-title-box, .auth-fluid-right, .sidebar-backdrop {
                display: none !important;
            }
            .wrapper, .content-page, .content, .container-fluid, body, html {
                margin: 0 !important;
                padding: 0 !important;
                height: auto !important;
                overflow: visible !important;
            }
            #print-area, #print-area * {
                visibility: visible;
                position: relative !important;
            }
            #print-area {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: white;
                z-index: 9999;
            }
            .card-body {
                padding: 0 !important;
            }
            .d-print-none {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <!-- Begin page -->
    <div class="wrapper">

        <!-- Mobile Drawer Backdrop Overlay -->
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <!-- ════════════════════════════════════════════════════
             LEFT SIDEBAR (SIDENAV MENU)
             ════════════════════════════════════════════════════ -->
        <aside class="sidenav-menu" id="sideNavMenu">
            <!-- Brand Logo Header -->
            <div class="sidenav-brand">
                <a href="{{ route('dashboard') }}" title="{{ $companyName }}">
                    <img src="{{ $navLogoUrl }}" alt="{{ $companyName }}" class="sidenav-brand-logo">
                    <span class="sidenav-brand-text">{{ $companyName }}</span>
                </a>
                <button type="button" class="btn-close-sidebar" id="closeSidebarBtn" aria-label="Close Sidebar">
                    <i class="ti ti-x"></i>
                </button>
            </div>

            <!-- Scrollable Navigation Items -->
            <div class="sidenav-scroll" data-simplebar>
                <ul class="side-nav">

                    <!-- MAIN SECTION -->
                    <li class="side-nav-title">Main</li>

                    <!-- Dashboard -->
                    <li class="side-nav-item">
                        <a href="{{ route('dashboard') }}" class="side-nav-link {{ request()->routeIs('dashboard*') ? 'active' : '' }}">
                            <span class="menu-icon"><i data-lucide="layout-dashboard"></i></span>
                            <span class="menu-text">Dashboard</span>
                        </a>
                    </li>

                    @php
                        $isSuperAdmin = $currentUser?->isSuperAdmin();
                        $isAdmin = $currentUser?->hasRole('admin');
                        $isSales = $currentUser?->hasRole('salesperson');
                        $isStore = $currentUser?->hasRole('store_person');
                        $isStaff = $currentUser?->hasRole('staff');
                        if ($isStaff) { $currentUser->loadMissing('staffPermissions', 'staffRole.modulePermissions'); }
                    @endphp

                    <!-- SUPER ADMIN -->
                    @if($isSuperAdmin)
                    <li class="side-nav-title">Administration</li>
                    <li class="side-nav-item">
                        @php $superAdminActive = request()->routeIs('super_admin.*'); @endphp
                        <a data-bs-toggle="collapse" href="#sidebarSuperAdmin" aria-expanded="{{ $superAdminActive ? 'true' : 'false' }}" aria-controls="sidebarSuperAdmin" class="side-nav-link {{ $superAdminActive ? 'active' : '' }}">
                            <span class="menu-icon"><i data-lucide="shield"></i></span>
                            <span class="menu-text">Super Admin</span>
                            <span class="menu-arrow"><i class="ti ti-chevron-down"></i></span>
                        </a>
                        <div class="collapse {{ $superAdminActive ? 'show' : '' }}" id="sidebarSuperAdmin">
                            <ul class="sub-menu">
                                <li class="side-nav-item">
                                    <a href="{{ route('super_admin.dashboard') }}" class="side-nav-link {{ request()->routeIs('super_admin.dashboard') ? 'active' : '' }}">Dashboard</a>
                                </li>
                                <li class="side-nav-item">
                                    <a href="{{ route('super_admin.subscribers.index') }}" class="side-nav-link {{ request()->routeIs('super_admin.subscribers.index') ? 'active' : '' }}">All Subscribers</a>
                                </li>
                                <li class="side-nav-item">
                                    <a href="{{ route('super_admin.subscribers.create') }}" class="side-nav-link {{ request()->routeIs('super_admin.subscribers.create') ? 'active' : '' }}">Add Subscriber</a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    @endif

                    <!-- STAFF MANAGEMENT (Admin only) -->
                    @if($isAdmin)
                    <li class="side-nav-item">
                        @php $staffActive = request()->routeIs('staff*') || request()->routeIs('staff-roles*'); @endphp
                        <a data-bs-toggle="collapse" href="#sidebarStaff" aria-expanded="{{ $staffActive ? 'true' : 'false' }}" aria-controls="sidebarStaff" class="side-nav-link {{ $staffActive ? 'active' : '' }}">
                            <span class="menu-icon"><i data-lucide="users"></i></span>
                            <span class="menu-text">Staff Management</span>
                            <span class="menu-arrow"><i class="ti ti-chevron-down"></i></span>
                        </a>
                        <div class="collapse {{ $staffActive ? 'show' : '' }}" id="sidebarStaff">
                            <ul class="sub-menu">
                                <li class="side-nav-item">
                                    <a href="{{ route('staff.index') }}" class="side-nav-link {{ request()->routeIs('staff.index') ? 'active' : '' }}">Staff Members</a>
                                </li>
                                <li class="side-nav-item">
                                    <a href="{{ route('staff-roles.index') }}" class="side-nav-link {{ request()->routeIs('staff-roles.index') ? 'active' : '' }}">Roles &amp; Permissions</a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    @endif

                    <!-- SALES & COMMERCE -->
                    @php
                        $showSales = $isAdmin || $isSales || $isSuperAdmin
                            || ($isStaff && (
                                $currentUser->canViewModule('sales-invoices') ||
                                $currentUser->canViewModule('sales-receipts') ||
                                $currentUser->canViewModule('sales-credit-notes') ||
                                $currentUser->canViewModule('sales-debit-notes') ||
                                $currentUser->canViewModule('proforma-invoices') ||
                                $currentUser->canViewModule('quotations') ||
                                $currentUser->canViewModule('old-sales-invoices')
                            ));
                    @endphp
                    @if($showSales)
                    <li class="side-nav-title">Commerce</li>
                    <li class="side-nav-item">
                        @php
                            $salesActive = request()->routeIs('quotations.*') ||
                                request()->routeIs('proforma-invoices.*') ||
                                request()->routeIs('sales-invoices.*') ||
                                request()->routeIs('sales-receipts.*') ||
                                request()->routeIs('old-sales-invoices.*') ||
                                request()->routeIs('credit-notes.*') ||
                                request()->routeIs('debit-notes.*');
                        @endphp
                        <a data-bs-toggle="collapse" href="#sidebarSales" aria-expanded="{{ $salesActive ? 'true' : 'false' }}" aria-controls="sidebarSales" class="side-nav-link {{ $salesActive ? 'active' : '' }}">
                            <span class="menu-icon"><i data-lucide="shopping-bag"></i></span>
                            <span class="menu-text">Sales Operations</span>
                            <span class="menu-arrow"><i class="ti ti-chevron-down"></i></span>
                        </a>
                        <div class="collapse {{ $salesActive ? 'show' : '' }}" id="sidebarSales">
                            <ul class="sub-menu">
                                @if($isSales || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('products')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('products.index') }}" class="side-nav-link {{ request()->routeIs('products.index') ? 'active' : '' }}">Products (View)</a>
                                    </li>
                                @endif
                                @if($isAdmin || $isSales || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('quotations')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('quotations.create') }}" class="side-nav-link {{ request()->routeIs('quotations.create') ? 'active' : '' }}">Sales Quote</a>
                                    </li>
                                    <li class="side-nav-item">
                                        <a href="{{ route('quotations.index') }}" class="side-nav-link {{ request()->routeIs('quotations.index') ? 'active' : '' }}">Sales Order</a>
                                    </li>
                                @endif
                                @if($isAdmin || $isSales || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('proforma-invoices')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('proforma-invoices.index') }}" class="side-nav-link {{ request()->routeIs('proforma-invoices.index') ? 'active' : '' }}">Proforma Invoice</a>
                                    </li>
                                @endif
                                @if($isAdmin || $isSales || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('sales-invoices')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('sales-invoices.index') }}" class="side-nav-link {{ request()->routeIs('sales-invoices.index') ? 'active' : '' }}">Sales Invoice (Tax)</a>
                                    </li>
                                @endif
                                @if($isAdmin || $isSales || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('sales-receipts')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('sales-receipts.index') }}" class="side-nav-link {{ request()->routeIs('sales-receipts.index') ? 'active' : '' }}">Payment Receipt</a>
                                    </li>
                                @endif
                                @if($isAdmin || $isSales || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('old-sales-invoices')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('old-sales-invoices.index') }}" class="side-nav-link {{ request()->routeIs('old-sales-invoices.index') ? 'active' : '' }}">Old Sales Invoice</a>
                                    </li>
                                @endif
                                @if($isAdmin || $isSales || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('sales-credit-notes')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('credit-notes.index') }}" class="side-nav-link {{ request()->routeIs('credit-notes.index') ? 'active' : '' }}">Sales Credit Note</a>
                                    </li>
                                @endif
                                @if($isAdmin || $isSales || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('sales-debit-notes')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('debit-notes.index') }}" class="side-nav-link {{ request()->routeIs('debit-notes.index') ? 'active' : '' }}">Sales Debit Note</a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </li>
                    @endif

                    <!-- EXPENSES & PROCUREMENT -->
                    @php
                        $showExpenses = $isAdmin || $isSuperAdmin
                            || ($isStaff && (
                                $currentUser->canViewModule('purchase-orders') ||
                                $currentUser->canViewModule('supplier-invoices') ||
                                $currentUser->canViewModule('credit-notes') ||
                                $currentUser->canViewModule('debit-notes') ||
                                $currentUser->canViewModule('supplier-purchases') ||
                                $currentUser->canViewModule('supplier-payments') ||
                                $currentUser->canViewModule('supplier-reports') ||
                                $currentUser->canViewModule('quotations')
                            ));
                    @endphp
                    @if($showExpenses)
                    <li class="side-nav-item">
                        @php
                            $expensesActive = request()->routeIs('admin.quotations.*') ||
                                request()->routeIs('purchase-orders.*') ||
                                request()->routeIs('supplier-invoices.*') ||
                                request()->routeIs('admin.credit-notes.*') ||
                                request()->routeIs('admin.debit-notes.*') ||
                                request()->routeIs('supplier-purchases.*') ||
                                request()->routeIs('supplier-payments.*') ||
                                request()->routeIs('supplier-reports.*');
                        @endphp
                        <a data-bs-toggle="collapse" href="#sidebarExpenses" aria-expanded="{{ $expensesActive ? 'true' : 'false' }}" aria-controls="sidebarExpenses" class="side-nav-link {{ $expensesActive ? 'active' : '' }}">
                            <span class="menu-icon"><i data-lucide="wallet"></i></span>
                            <span class="menu-text">Expenses &amp; Purchases</span>
                            <span class="menu-arrow"><i class="ti ti-chevron-down"></i></span>
                        </a>
                        <div class="collapse {{ $expensesActive ? 'show' : '' }}" id="sidebarExpenses">
                            <ul class="sub-menu">
                                @if($isAdmin || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('quotations')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('admin.quotations.index') }}" class="side-nav-link {{ request()->routeIs('admin.quotations.index') ? 'active' : '' }}">Purchase Quote</a>
                                    </li>
                                @endif
                                @if($isAdmin || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('purchase-orders')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('purchase-orders.index') }}" class="side-nav-link {{ request()->routeIs('purchase-orders.index') ? 'active' : '' }}">Purchase Order</a>
                                    </li>
                                @endif
                                @if($isAdmin || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('supplier-invoices')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('supplier-invoices.index') }}" class="side-nav-link {{ request()->routeIs('supplier-invoices.index') ? 'active' : '' }}">Supplier Invoice</a>
                                    </li>
                                @endif
                                @if($isAdmin || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('credit-notes')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('admin.credit-notes.index') }}" class="side-nav-link {{ request()->routeIs('admin.credit-notes.index') ? 'active' : '' }}">Supplier Credit Note</a>
                                    </li>
                                @endif
                                @if($isAdmin || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('debit-notes')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('admin.debit-notes.index') }}" class="side-nav-link {{ request()->routeIs('admin.debit-notes.index') ? 'active' : '' }}">Supplier Debit Note</a>
                                    </li>
                                @endif
                                @if($isAdmin || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('supplier-purchases')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('supplier-purchases.index') }}" class="side-nav-link {{ request()->routeIs('supplier-purchases.index') ? 'active' : '' }}">Add Purchase</a>
                                    </li>
                                @endif
                                @if($isAdmin || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('supplier-payments')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('supplier-payments.create') }}" class="side-nav-link {{ request()->routeIs('supplier-payments.create') ? 'active' : '' }}">Add Payment</a>
                                    </li>
                                    <li class="side-nav-item">
                                        <a href="{{ route('supplier-payments.index') }}" class="side-nav-link {{ request()->routeIs('supplier-payments.index') ? 'active' : '' }}">Payment History</a>
                                    </li>
                                @endif
                                @if($isAdmin || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('supplier-reports')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('supplier-reports.outstanding') }}" class="side-nav-link {{ request()->routeIs('supplier-reports.outstanding') ? 'active' : '' }}">Outstanding Report</a>
                                    </li>
                                    <li class="side-nav-item">
                                        <a href="{{ route('supplier-reports.ledger') }}" class="side-nav-link {{ request()->routeIs('supplier-reports.ledger') ? 'active' : '' }}">Supplier Ledger</a>
                                    </li>
                                    <li class="side-nav-item">
                                        <a href="{{ route('supplier-reports.giro') }}" class="side-nav-link {{ request()->routeIs('supplier-reports.giro') ? 'active' : '' }}">GIRO Report</a>
                                    </li>
                                    <li class="side-nav-item">
                                        <a href="{{ route('supplier-reports.gst') }}" class="side-nav-link {{ request()->routeIs('supplier-reports.gst') ? 'active' : '' }}">GST Report</a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </li>
                    @endif

                    <!-- LOGISTICS & DELIVERY -->
                    @if($isAdmin || $isSales || $isStore || $isSuperAdmin || ($isStaff && ($currentUser->canViewModule('delivery-orders') || $currentUser->canViewModule('purchase-delivery-notes'))))
                    <li class="side-nav-item">
                        @php $deliveryActive = request()->routeIs('delivery-orders.*') || request()->routeIs('purchase-delivery-notes.*'); @endphp
                        <a data-bs-toggle="collapse" href="#sidebarDelivery" aria-expanded="{{ $deliveryActive ? 'true' : 'false' }}" aria-controls="sidebarDelivery" class="side-nav-link {{ $deliveryActive ? 'active' : '' }}">
                            <span class="menu-icon"><i data-lucide="truck"></i></span>
                            <span class="menu-text">Logistics &amp; Delivery</span>
                            <span class="menu-arrow"><i class="ti ti-chevron-down"></i></span>
                        </a>
                        <div class="collapse {{ $deliveryActive ? 'show' : '' }}" id="sidebarDelivery">
                            <ul class="sub-menu">
                                @if($isAdmin || $isSales || $isStore || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('delivery-orders')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('delivery-orders.index') }}" class="side-nav-link {{ request()->routeIs('delivery-orders.index') ? 'active' : '' }}">Sales Delivery Order</a>
                                    </li>
                                @endif
                                @if($isAdmin || $isSales || $isStore || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('purchase-delivery-notes')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('purchase-delivery-notes.index') }}" class="side-nav-link {{ request()->routeIs('purchase-delivery-notes.index') ? 'active' : '' }}">Purchase Delivery Note</a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </li>
                    @endif

                    <!-- CATALOG & INVENTORY SECTION -->
                    @if($isAdmin || $isSales || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('products')))
                    <li class="side-nav-item">
                        <a href="{{ route('product-list.index') }}" class="side-nav-link {{ request()->routeIs('product-list.*') ? 'active' : '' }}">
                            <span class="menu-icon"><i data-lucide="shopping-cart"></i></span>
                            <span class="menu-text">Product List</span>
                        </a>
                    </li>
                    @endif

                    <!-- MASTER DATA & ENTITIES -->
                    @php
                        $showMasterData = $isAdmin || $isStore || $isSuperAdmin
                            || ($isStaff && (
                                $currentUser->canViewModule('products') ||
                                $currentUser->canViewModule('associates') ||
                                $currentUser->canViewModule('associate-groups') ||
                                $currentUser->canViewModule('stock-list') ||
                                $currentUser->canViewModule('product-labels') ||
                                $currentUser->canViewModule('price-settings') ||
                                $currentUser->canViewModule('price-updates') ||
                                $currentUser->canViewModule('cost-price-updates') ||
                                $currentUser->canViewModule('company-settings') ||
                                $currentUser->canViewModule('master-data')
                            ));
                    @endphp
                    @if($showMasterData)
                    <li class="side-nav-title">Inventory &amp; Master Data</li>

                    <!-- Catalog Management -->
                    <li class="side-nav-item">
                        @php
                            $catalogActive = request()->routeIs('products.*') ||
                                request()->routeIs('stock-list.*') ||
                                request()->routeIs('product-labels.*') ||
                                request()->routeIs('product-aliases.*') ||
                                request()->routeIs('price-settings.*') ||
                                request()->routeIs('price-updates.*') ||
                                request()->routeIs('cost-price-updates.*');
                        @endphp
                        <a data-bs-toggle="collapse" href="#sidebarCatalog" aria-expanded="{{ $catalogActive ? 'true' : 'false' }}" aria-controls="sidebarCatalog" class="side-nav-link {{ $catalogActive ? 'active' : '' }}">
                            <span class="menu-icon"><i data-lucide="boxes"></i></span>
                            <span class="menu-text">Catalog &amp; Inventory</span>
                            <span class="menu-arrow"><i class="ti ti-chevron-down"></i></span>
                        </a>
                        <div class="collapse {{ $catalogActive ? 'show' : '' }}" id="sidebarCatalog">
                            <ul class="sub-menu">
                                @if($isAdmin || $isStore || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('products')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('products.index') }}" class="side-nav-link {{ request()->routeIs('products.index') ? 'active' : '' }}">Products</a>
                                    </li>
                                @endif
                                @if($isAdmin || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('stock-list')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('stock-list.index') }}" class="side-nav-link {{ request()->routeIs('stock-list.index') ? 'active' : '' }}">Stock List</a>
                                    </li>
                                @endif
                                @if($isAdmin || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('product-labels')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('product-labels.index') }}" class="side-nav-link {{ request()->routeIs('product-labels.index') ? 'active' : '' }}">Product Label</a>
                                    </li>
                                @endif
                                @if($isAdmin || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('products')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('product-aliases.index') }}" class="side-nav-link {{ request()->routeIs('product-aliases.*') ? 'active' : '' }}">Product Aliases</a>
                                    </li>
                                @endif
                                @if($isAdmin || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('price-settings')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('price-settings.index') }}" class="side-nav-link {{ request()->routeIs('price-settings.index') ? 'active' : '' }}">Price Settings</a>
                                    </li>
                                @endif
                                @if($isAdmin || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('price-updates')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('price-updates.index') }}" class="side-nav-link {{ request()->routeIs('price-updates.index') ? 'active' : '' }}">Price Update (Selling)</a>
                                    </li>
                                @endif
                                @if($isAdmin || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('cost-price-updates')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('cost-price-updates.index') }}" class="side-nav-link {{ request()->routeIs('cost-price-updates.index') ? 'active' : '' }}">Cost Price Update</a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </li>

                    <!-- Business Entities (Customers, Suppliers, Groups) -->
                    @if($isAdmin || $isSuperAdmin || ($isStaff && ($currentUser->canViewModule('associates') || $currentUser->canViewModule('associate-groups'))))
                    <li class="side-nav-item">
                        @php $entitiesActive = request()->routeIs('associates.*') || request()->routeIs('associate-groups.*'); @endphp
                        <a data-bs-toggle="collapse" href="#sidebarEntities" aria-expanded="{{ $entitiesActive ? 'true' : 'false' }}" aria-controls="sidebarEntities" class="side-nav-link {{ $entitiesActive ? 'active' : '' }}">
                            <span class="menu-icon"><i data-lucide="user-check"></i></span>
                            <span class="menu-text">Business Partners</span>
                            <span class="menu-arrow"><i class="ti ti-chevron-down"></i></span>
                        </a>
                        <div class="collapse {{ $entitiesActive ? 'show' : '' }}" id="sidebarEntities">
                            <ul class="sub-menu">
                                @if($isAdmin || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('associates')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('associates.index') }}" class="side-nav-link {{ request()->routeIs('associates.index') ? 'active' : '' }}">Customer / Supplier</a>
                                    </li>
                                @endif
                                @if($isAdmin || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('associate-groups')))
                                    <li class="side-nav-item">
                                        <a href="{{ route('associate-groups.index') }}" class="side-nav-link {{ request()->routeIs('associate-groups.index') ? 'active' : '' }}">Associative Group</a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </li>
                    @endif

                    <!-- Master Configuration Tables -->
                    @if($isAdmin || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('master-data')))
                    <li class="side-nav-item">
                        @php $masterActive = request()->routeIs('master-data.*'); @endphp
                        <a data-bs-toggle="collapse" href="#sidebarMasterData" aria-expanded="{{ $masterActive ? 'true' : 'false' }}" aria-controls="sidebarMasterData" class="side-nav-link {{ $masterActive ? 'active' : '' }}">
                            <span class="menu-icon"><i data-lucide="database"></i></span>
                            <span class="menu-text">Master Data</span>
                            <span class="menu-arrow"><i class="ti ti-chevron-down"></i></span>
                        </a>
                        <div class="collapse {{ $masterActive ? 'show' : '' }}" id="sidebarMasterData">
                            <ul class="sub-menu">
                                <li class="side-nav-item"><a href="{{ route('master-data.index', 'currencies') }}" class="side-nav-link">Currency</a></li>
                                <li class="side-nav-item"><a href="{{ route('master-data.index', 'taxes') }}" class="side-nav-link">Tax</a></li>
                                <li class="side-nav-item"><a href="{{ route('master-data.index', 'terms') }}" class="side-nav-link">Term</a></li>
                                <li class="side-nav-item"><a href="{{ route('master-data.index', 'incoterms') }}" class="side-nav-link">Incoterms</a></li>
                                <li class="side-nav-item"><a href="{{ route('master-data.index', 'paymodes') }}" class="side-nav-link">Paymode</a></li>
                                <li class="side-nav-item"><a href="{{ route('master-data.index', 'banks') }}" class="side-nav-link">Bank</a></li>
                                <li class="side-nav-item"><a href="{{ route('master-data.index', 'product-brands') }}" class="side-nav-link">Product Brands</a></li>
                                <li class="side-nav-item"><a href="{{ route('master-data.index', 'item-categories') }}" class="side-nav-link">Item Category</a></li>
                                <li class="side-nav-item"><a href="{{ route('master-data.index', 'uoms') }}" class="side-nav-link">UOM</a></li>
                                <li class="side-nav-item"><a href="{{ route('master-data.index', 'colors') }}" class="side-nav-link">Color</a></li>
                                <li class="side-nav-item"><a href="{{ route('master-data.index', 'sizes') }}" class="side-nav-link">Size</a></li>
                                <li class="side-nav-item"><a href="{{ route('master-data.index', 'timezones') }}" class="side-nav-link">Timezone</a></li>
                            </ul>
                        </div>
                    </li>
                    @endif

                    @if($isAdmin || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('company-settings')))
                    <li class="side-nav-item">
                        <a href="{{ route('company-settings.index') }}" class="side-nav-link {{ request()->routeIs('company-settings.*') ? 'active' : '' }}">
                            <span class="menu-icon"><i data-lucide="settings"></i></span>
                            <span class="menu-text">Company Settings</span>
                        </a>
                    </li>
                    @endif
                    @endif

                    <!-- REPORTS SECTION -->
                    @if($isAdmin || $isSales || $isStore || $isSuperAdmin || ($isStaff && $currentUser->canViewModule('reports')))
                    <li class="side-nav-title">Analytics</li>
                    <li class="side-nav-item">
                        @php $reportsActive = request()->routeIs('reports.*'); @endphp
                        <a data-bs-toggle="collapse" href="#sidebarReports" aria-expanded="{{ $reportsActive ? 'true' : 'false' }}" aria-controls="sidebarReports" class="side-nav-link {{ $reportsActive ? 'active' : '' }}">
                            <span class="menu-icon"><i data-lucide="file-text"></i></span>
                            <span class="menu-text">Reports</span>
                            <span class="menu-arrow"><i class="ti ti-chevron-down"></i></span>
                        </a>
                        <div class="collapse {{ $reportsActive ? 'show' : '' }}" id="sidebarReports">
                            <ul class="sub-menu">
                                <li class="side-nav-item">
                                    <a href="{{ route('reports.index') }}" class="side-nav-link {{ request()->routeIs('reports.index') ? 'active' : '' }}">Business Reports</a>
                                </li>
                                <li class="side-nav-item">
                                    <a href="{{ route('reports.customers.index') }}" class="side-nav-link {{ request()->routeIs('reports.customers.index') ? 'active' : '' }}">Customer-wise Report</a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    @endif

                </ul>
            </div>
        </aside>
        <!-- ═════════ END LEFT SIDEBAR ═════════ -->


        <!-- ════════════════════════════════════════════════════
             TOPBAR (HEADER)
             ════════════════════════════════════════════════════ -->
        <header class="app-topbar">
            <div class="topbar-content">
                <!-- Left: Hamburger toggle button -->
                <div class="d-flex align-items-center gap-3">
                    <button type="button" class="sidenav-toggle-btn" id="sidebarToggleBtn" aria-label="Toggle Navigation Sidebar">
                        <i class="ti ti-menu-2 fs-20"></i>
                    </button>
                    <div class="d-none d-sm-block">
                        <h5 class="my-0 text-dark fw-bold" style="font-family: var(--app-font-heading); font-size: 17px;">
                            @yield('page_title', 'Wholesale Operations Portal')
                        </h5>
                    </div>
                </div>

                <!-- Centre: Global Page Search -->
                @auth
                <div id="global-search-wrap">
                    <span id="global-search-icon"><i class="ti ti-search fs-16"></i></span>
                    <input id="global-search-input" type="text" placeholder="Search pages…" autocomplete="off" aria-label="Search pages" aria-autocomplete="list" aria-controls="global-search-dropdown">
                    <button id="global-search-clear" tabindex="-1" aria-label="Clear search"><i class="ti ti-x fs-14"></i></button>
                    <div id="global-search-dropdown" role="listbox"></div>
                </div>
                @endauth

                <!-- Right: PWA & User Profile -->
                <div class="d-flex align-items-center gap-3">

                    <!-- PWA Install Button -->
                    <div id="pwa-install-wrapper" style="display:none!important;">
                        <button id="pwa-install-btn" type="button" title="Install App">
                            <i class="ti ti-download fs-15"></i>
                            <span class="d-none d-sm-inline">Install App</span>
                        </button>
                    </div>

                    <!-- User Profile Dropdown -->
                    <div class="dropdown">
                        <a class="topbar-user-pill dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                            <div class="user-avatar-circle">
                                {{ strtoupper(substr($currentUser?->name ?? 'U', 0, 1)) }}
                            </div>
                            <div class="user-text-wrap d-none d-md-flex">
                                <span class="user-name-label">{{ $currentUser?->name ?? 'User' }}</span>
                                <span class="user-role-label">
                                    @if($currentUser?->isSuperAdmin())
                                        Super Admin
                                    @elseif($currentUser?->hasRole('admin'))
                                        Administrator
                                    @elseif($currentUser?->hasRole('salesperson'))
                                        Sales
                                    @elseif($currentUser?->hasRole('store_person'))
                                        Store
                                    @else
                                        Staff
                                    @endif
                                </span>
                            </div>
                            <i class="ti ti-chevron-down fs-13 ms-1 d-none d-md-inline" style="color: var(--slate-400);"></i>
                        </a>

                        <div class="dropdown-menu dropdown-menu-end">
                            <div class="dropdown-header">
                                <div class="fw-bold text-dark">{{ $currentUser?->name ?? 'User' }}</div>
                                <small class="text-muted">{{ $currentUser?->email ?? '' }}</small>
                            </div>
                            <div class="dropdown-divider"></div>

                            <a href="{{ route('profile.show') }}" class="dropdown-item">
                                <i class="ti ti-user-circle fs-17"></i>
                                <span>My Profile</span>
                            </a>

                            @if($currentUser?->hasRole('admin') || $currentUser?->isSuperAdmin())
                                <a href="{{ route('company-settings.index') }}" class="dropdown-item">
                                    <i class="ti ti-settings fs-17"></i>
                                    <span>Company Settings</span>
                                </a>
                            @endif

                            <div class="dropdown-divider"></div>

                            <a href="#" class="dropdown-item dropdown-danger fw-semibold" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="ti ti-logout-2 fs-17"></i>
                                <span>Sign Out</span>
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </header>
        <!-- ═════════ END TOPBAR ═════════ -->


        <!-- ════════════════════════════════════════════════════
             MAIN CONTENT PAGE
             ════════════════════════════════════════════════════ -->
        <div class="content-page">
            <div class="container-fluid">
                @yield('content')
            </div>

            <!-- Footer -->
            <footer class="footer">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                            &copy; {{ date('Y') }} <strong>{{ $companyName }}</strong>. All rights reserved.
                        </div>
                        <div class="col-md-6 text-center text-md-end">
                            Powered by <a href="#" target="_blank" rel="noopener">Techgeeks</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
        <!-- ═════════ END CONTENT PAGE ═════════ -->

    </div>
    <!-- END wrapper -->

    <!-- Theme Vendor & App Scripts -->
    <script src="{{ asset('assets/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>

    <!-- App js (Vite) -->
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])

    @stack('scripts')

    {{-- Toast container --}}
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1100;">
        @foreach([
            'success' => ['bg-success', 'ti-circle-check'],
            'error'   => ['bg-danger',  'ti-alert-circle'],
            'warning' => ['bg-warning text-dark', 'ti-alert-triangle'],
            'info'    => ['bg-info',    'ti-info-circle'],
        ] as $type => [$classes, $icon])
            @if(session($type))
            <div class="toast align-items-center text-white border-0 {{ $classes }}" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="4000">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center gap-2">
                        <i class="ti {{ $icon }} fs-18"></i>
                        <span>{{ session($type) }}</span>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
            @endif
        @endforeach
    </div>

    <!-- Sidebar Mobile Toggle Script -->
    <script>
        (function() {
            const toggleBtn = document.getElementById('sidebarToggleBtn');
            const closeBtn = document.getElementById('closeSidebarBtn');
            const backdrop = document.getElementById('sidebarBackdrop');
            const body = document.body;

            function toggleSidebar() {
                body.classList.toggle('sidebar-open');
            }

            function closeSidebar() {
                body.classList.remove('sidebar-open');
            }

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleSidebar();
                });
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeSidebar();
                });
            }

            if (backdrop) {
                backdrop.addEventListener('click', function() {
                    closeSidebar();
                });
            }

            // Also support lucide icons re-render if loaded
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }
        })();
    </script>

    <!-- PWA Service Worker & Install Script -->
    <script>
        (function () {
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js').catch(function () {});
            }

            let deferredPrompt = null;
            const wrapper = document.getElementById('pwa-install-wrapper');
            const btn = document.getElementById('pwa-install-btn');

            window.addEventListener('beforeinstallprompt', function (e) {
                e.preventDefault();
                deferredPrompt = e;
                if (wrapper) wrapper.style.setProperty('display', 'flex', 'important');
            });

            if (btn) {
                btn.addEventListener('click', async function () {
                    if (!deferredPrompt) return;
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    deferredPrompt = null;
                    if (wrapper) wrapper.style.setProperty('display', 'none', 'important');
                });
            }

            window.addEventListener('appinstalled', function () {
                deferredPrompt = null;
                if (wrapper) wrapper.style.setProperty('display', 'none', 'important');
            });
        })();
    </script>

    <!-- Toast Notification Helper -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.toast-container .toast').forEach(function (el) {
                new bootstrap.Toast(el).show();
            });
        });

        function showToast(message, type) {
            const colors = { success: 'bg-success', error: 'bg-danger', warning: 'bg-warning text-dark', info: 'bg-info' };
            const icons  = { success: 'ti-circle-check', error: 'ti-alert-circle', warning: 'ti-alert-triangle', info: 'ti-info-circle' };
            let container = document.querySelector('.toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                container.style.zIndex = '1100';
                document.body.appendChild(container);
            }
            const el = document.createElement('div');
            el.className = 'toast align-items-center text-white border-0 ' + (colors[type] || 'bg-secondary');
            el.setAttribute('role', 'alert');
            el.innerHTML = '<div class="d-flex"><div class="toast-body d-flex align-items-center gap-2"><i class="ti ' + (icons[type] || 'ti-info-circle') + ' fs-18"></i><span>' + message + '</span></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
            container.appendChild(el);
            new bootstrap.Toast(el, { delay: 4000 }).show();
        }
    </script>

    <!-- Global Web Bluetooth / Android Thermal Printing Bridge Helper -->
    <script>
    (function() {
      var cachedBleDevice = null;
      var cachedBleChar = null;

      function base64ToUint8Array(base64) {
        var binaryString = atob(base64);
        var len = binaryString.length;
        var bytes = new Uint8Array(len);
        for (var i = 0; i < len; i++) {
          bytes[i] = binaryString.charCodeAt(i);
        }
        return bytes;
      }

      function bleConnectWithTimeout(device, ms) {
        return Promise.race([
          device.gatt.connect(),
          new Promise(function (_, rej) {
            setTimeout(function () {
              try { device.gatt.disconnect(); } catch (e) {}
              rej(new Error('connect timeout after ' + ms + 'ms'));
            }, ms);
          })
        ]);
      }

      async function connectBleDevice(forceChooser) {
        const serviceUuids = [
          '000018f0-0000-1000-8000-00805f9b34fb',
          '00004953-0000-1000-8000-00805f9b34fb',
          'e7810a71-73ae-499d-8c15-faa9aef0c3f2'
        ];

        try {
          let device = null;

          if (!forceChooser) {
            if (navigator.bluetooth && navigator.bluetooth.getDevices) {
              try {
                const devices = await navigator.bluetooth.getDevices();
                console.log('Remembered devices:', devices.length);
                if (devices.length > 0) {
                  var savedId = null;
                  try { savedId = localStorage.getItem('tp_ble_device_id'); } catch (e) {}
                  for (var i = 0; i < devices.length; i++) {
                    if (savedId && devices[i].id === savedId) { device = devices[i]; break; }
                  }
                  if (!device) device = devices[0];
                }
              } catch (errDevices) {
                device = null;
              }
            }

            if (device) {
              try {
                await bleConnectWithTimeout(device, 15000);
              } catch (eSilent) {
                return null;
              }
            } else {
              return null;
            }
          } else {
            if (!navigator.bluetooth) {
              throw new Error('Web Bluetooth is not supported on this browser or context.');
            }
            device = await navigator.bluetooth.requestDevice({
              acceptAllDevices: true,
              optionalServices: serviceUuids
            });
            await bleConnectWithTimeout(device, 10000);
          }

          if (!device) {
            return null;
          }

          try { localStorage.setItem('tp_ble_device_id', device.id); } catch (e) {}

          device.addEventListener('gattserverdisconnected', function() {
            cachedBleDevice = null;
            cachedBleChar = null;
            if (typeof showToast === 'function') showToast('📶 Printer disconnected', 'info');
          });

          let foundChar = null;
          for (const uuid of serviceUuids) {
            try {
              const service = await device.gatt.getPrimaryService(uuid);
              const chars = await service.getCharacteristics();
              for (const c of chars) {
                if (c.properties.write || c.properties.writeWithoutResponse) {
                  foundChar = c;
                  break;
                }
              }
            } catch (e) {}
            if (foundChar) break;
          }

          if (!foundChar) {
            throw new Error('Could not find a writable characteristic on the selected printer.');
          }

          cachedBleDevice = device;
          cachedBleChar = foundChar;
          return foundChar;
        } catch (err) {
          if (forceChooser && typeof showToast === 'function') {
            showToast('✗ BLE Connection failed: ' + err.message, 'error');
          }
          throw err;
        }
      }

      function launchAndroidPrintPreview(invoiceId, jobId, baseUrl, paperWidth) {
        var ua = navigator.userAgent || '';
        if (!/android/i.test(ua)) return false;

        var fallbackUrl = baseUrl + 'sales-invoices/' + invoiceId + '/thermal-print';
        var query = 'job_id=' + encodeURIComponent(jobId) +
          '&invoice_id=' + encodeURIComponent(invoiceId) +
          '&server_url=' + encodeURIComponent(baseUrl.replace(/\/$/, '')) +
          '&width=' + encodeURIComponent(paperWidth) +
          '&t=' + Date.now();

        window.location.href = 'intent://preview?' + query +
          '#Intent;scheme=wholetradeprint;package=com.techgeeks.wholetradeprint;' +
          'S.browser_fallback_url=' + encodeURIComponent(fallbackUrl) + ';end';
        return true;
      }

      window.printViaBle = async function(invoiceId, paperWidth, copies) {
        paperWidth = paperWidth || 80;
        copies = copies || 1;
        var baseUrl = window.APP_URL || '/';
        if (!baseUrl.endsWith('/')) baseUrl += '/';
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        var csrf = csrfMeta ? csrfMeta.content : '{{ csrf_token() }}';

        try {
          if (typeof showToast === 'function') showToast('🖨️ Sending to print service…', 'info');

          const response = await fetch(baseUrl + 'sales-invoices/' + invoiceId + '/direct-print', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ format: 'escpos', station: 'main', width: paperWidth, copies: copies })
          });
          const d = await response.json();
          if (!d.success) throw new Error(d.error || 'Could not queue print job');

          if (launchAndroidPrintPreview(invoiceId, d.job_id, baseUrl, paperWidth)) {
            if (typeof showToast === 'function') showToast('Opening Print App preview...', 'info');
          }

          /* Poll until the app completes it (max ~30 s) */
          for (let n = 0; n < 15; n++) {
            await new Promise(function (r) { setTimeout(r, 2000); });
            let s = null;
            try {
              const sr = await fetch(baseUrl + 'print-jobs/' + d.job_id + '/status', {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
              });
              s = await sr.json();
            } catch (netErr) { continue; }

            if (s.status === 'done') {
              if (typeof showToast === 'function') showToast('✓ Print job completed!', 'success');
              return;
            }
            if (s.status === 'failed') {
              throw new Error(s.error || 'printer reported failure');
            }
          }
          throw new Error('Timed out — is the Print App open and connected?');
        } catch (err) {
          if (typeof showToast === 'function') {
            if (/cancelled|dismissed/i.test(err.message || '')) {
              showToast('Print cancelled on the device', 'warning');
            } else {
              showToast('✗ Print failed: ' + err.message, 'error');
            }
          }
        }
      };
    })();
    </script>

    {{-- ══════════════════════════════════════════════════════ Global Search ══ --}}
    @auth
    <script>
    (function () {
        var pages = [
            { label: 'Dashboard',              url: '/dashboard',                  icon: 'ti-layout-dashboard',   cat: 'Main' },
            { label: 'Sales Invoices',         url: '/sales-invoices',             icon: 'ti-file-invoice',       cat: 'Sales' },
            { label: 'Create Sales Invoice',   url: '/sales-invoices/create',      icon: 'ti-file-plus',          cat: 'Sales' },
            { label: 'Sales Receipts',         url: '/sales-receipts',             icon: 'ti-receipt',            cat: 'Sales' },
            { label: 'Quotations',             url: '/quotations',                 icon: 'ti-file-text',          cat: 'Sales' },
            { label: 'Proforma Invoices',      url: '/proforma-invoices',          icon: 'ti-file-description',   cat: 'Sales' },
            { label: 'Product List (Order)',   url: '/product-list',               icon: 'ti-list-check',         cat: 'Sales' },
            { label: 'Products',               url: '/products',                   icon: 'ti-box',                cat: 'Inventory' },
            { label: 'Stock List',             url: '/stock-list',                 icon: 'ti-stack-2',            cat: 'Inventory' },
            { label: 'Purchase Orders',        url: '/purchase-orders',            icon: 'ti-shopping-cart',      cat: 'Purchasing' },
            { label: 'Supplier Invoices',      url: '/supplier-invoices',          icon: 'ti-file-invoice',       cat: 'Purchasing' },
            { label: 'Customers & Suppliers',  url: '/associates',                 icon: 'ti-users',              cat: 'Contacts' },
            { label: 'Reports',                url: '/reports',                    icon: 'ti-chart-bar',          cat: 'Reports' },
            { label: 'Customer Reports',       url: '/reports/customers',          icon: 'ti-report',             cat: 'Reports' },
            { label: 'Price Settings',         url: '/price-settings',             icon: 'ti-tag',                cat: 'Settings' },
            { label: 'Price Updates',          url: '/price-updates',              icon: 'ti-refresh',            cat: 'Settings' },
            { label: 'Cost Price Updates',     url: '/cost-price-updates',         icon: 'ti-coin',               cat: 'Settings' },
            { label: 'My Profile',             url: '/profile',                    icon: 'ti-user-circle',        cat: 'Account' },
        ];

        var input    = document.getElementById('global-search-input');
        var dropdown = document.getElementById('global-search-dropdown');
        var clearBtn = document.getElementById('global-search-clear');
        if (!input) return;

        var activeIdx = -1;

        function getItems() { return dropdown.querySelectorAll('.gs-item'); }

        function render(q) {
            activeIdx = -1;
            var q2 = q.trim().toLowerCase();
            if (!q2) { dropdown.style.display = 'none'; return; }

            var matches = pages.filter(function (p) {
                return p.label.toLowerCase().includes(q2) || p.cat.toLowerCase().includes(q2);
            });

            if (!matches.length) {
                dropdown.innerHTML = '<div class="gs-no-result">No pages found for "' + escHtml(q) + '"</div>';
                dropdown.style.display = 'block';
                return;
            }

            dropdown.innerHTML = matches.map(function (p) {
                return '<a class="gs-item" href="' + p.url + '" role="option">'
                    + '<span class="gs-icon"><i class="ti ' + p.icon + '"></i></span>'
                    + '<span class="gs-label">' + highlight(p.label, q2) + '</span>'
                    + '<span class="gs-category">' + escHtml(p.cat) + '</span>'
                    + '</a>';
            }).join('');
            dropdown.style.display = 'block';
        }

        function highlight(text, q) {
            var idx = text.toLowerCase().indexOf(q);
            if (idx < 0) return escHtml(text);
            return escHtml(text.slice(0, idx))
                + '<strong>' + escHtml(text.slice(idx, idx + q.length)) + '</strong>'
                + escHtml(text.slice(idx + q.length));
        }

        function escHtml(s) {
            return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }

        function setActive(idx) {
            var items = getItems();
            items.forEach(function (el, i) { el.classList.toggle('gs-active', i === idx); });
            if (items[idx]) items[idx].scrollIntoView({ block: 'nearest' });
        }

        function close() {
            dropdown.style.display = 'none';
            activeIdx = -1;
        }

        input.addEventListener('input', function () {
            clearBtn.style.display = this.value ? 'block' : 'none';
            render(this.value);
        });

        input.addEventListener('keydown', function (e) {
            var items = getItems();
            if (e.key === 'ArrowDown')  { e.preventDefault(); activeIdx = Math.min(activeIdx + 1, items.length - 1); setActive(activeIdx); }
            if (e.key === 'ArrowUp')    { e.preventDefault(); activeIdx = Math.max(activeIdx - 1, 0); setActive(activeIdx); }
            if (e.key === 'Enter')      { if (items[activeIdx]) { e.preventDefault(); window.location.href = items[activeIdx].href; } }
            if (e.key === 'Escape')     { close(); input.blur(); }
        });

        clearBtn.addEventListener('click', function () {
            input.value = '';
            clearBtn.style.display = 'none';
            close();
            input.focus();
        });

        document.addEventListener('click', function (e) {
            if (!document.getElementById('global-search-wrap').contains(e.target)) close();
        });

        input.addEventListener('focus', function () {
            if (this.value.trim()) render(this.value);
        });
    })();
    </script>
    @endauth

    {{-- ═══════════════════════════════════════════════════ AI Chatbot Widget ══ --}}
    @auth
    <style>
        #ai-chat-btn {
            position: fixed; bottom: 28px; right: 28px; z-index: 1200;
            width: 52px; height: 52px; border-radius: 50%;
            background: #15803d; border: none; cursor: pointer;
            box-shadow: 0 4px 16px rgba(21,128,61,.45);
            display: flex; align-items: center; justify-content: center;
            transition: transform .2s, box-shadow .2s;
        }
        #ai-chat-btn:hover { transform: scale(1.08); box-shadow: 0 6px 22px rgba(21,128,61,.55); }
        #ai-chat-btn svg { width: 26px; height: 26px; fill: #fff; }
        #ai-chat-panel {
            position: fixed; bottom: 92px; right: 28px; z-index: 1200;
            width: 380px;
            height: min(540px, calc(100dvh - 110px));
            background: #fff; border-radius: 14px;
            box-shadow: 0 8px 40px rgba(0,0,0,.18);
            display: flex; flex-direction: column; overflow: hidden;
            transition: opacity .2s, transform .2s;
        }
        #ai-chat-panel.ai-hidden { opacity: 0; transform: translateY(12px) scale(.97); pointer-events: none; }
        #ai-chat-header {
            background: #15803d; color: #fff; padding: 12px 16px;
            display: flex; align-items: center; justify-content: space-between;
            font-weight: 600; font-size: .95rem; flex-shrink: 0;
        }
        #ai-chat-header .ai-close { background: none; border: none; color: rgba(255,255,255,.8); cursor: pointer; font-size: 1.2rem; line-height: 1; padding: 0; }
        #ai-chat-header .ai-close:hover { color: #fff; }
        #ai-chat-msgs {
            flex: 1; min-height: 0; overflow-y: auto; padding: 12px 12px 6px;
            display: flex; flex-direction: column; gap: 10px;
        }
        .ai-msg { max-width: 88%; font-size: .875rem; line-height: 1.5; word-break: break-word; }
        .ai-msg.user { align-self: flex-end; background: #15803d; color: #fff; border-radius: 14px 14px 4px 14px; padding: 9px 13px; }
        .ai-msg.bot  { align-self: flex-start; background: #f3f4f6; color: #111; border-radius: 14px 14px 14px 4px; padding: 9px 13px; }
        .ai-msg.bot a { color: #15803d; }
        .ai-msg.bot strong { font-weight: 700; }
        .ai-msg.bot ul, .ai-msg.bot ol { padding-left: 1.2em; margin: .25em 0; }
        .ai-msg.bot code { background: #e5e7eb; border-radius: 3px; padding: 1px 4px; font-size: .82rem; }
        .ai-typing { align-self: flex-start; display: flex; gap: 5px; padding: 10px 14px; background: #f3f4f6; border-radius: 14px 14px 14px 4px; }
        .ai-typing span { width: 7px; height: 7px; border-radius: 50%; background: #9ca3af; animation: ai-dot 1.2s infinite; }
        .ai-typing span:nth-child(2) { animation-delay: .2s; }
        .ai-typing span:nth-child(3) { animation-delay: .4s; }
        @keyframes ai-dot { 0%,80%,100%{transform:scale(1);opacity:.5} 40%{transform:scale(1.3);opacity:1} }
        #ai-chat-chips {
            padding: 5px 10px 6px; display: flex; flex-wrap: nowrap; gap: 5px; flex-shrink: 0;
            overflow-x: auto; border-top: 1px solid #e5e7eb; background: #fafafa;
            scrollbar-width: none;
        }
        #ai-chat-chips::-webkit-scrollbar { display: none; }
        .ai-chip {
            background: #f0fdf4; border: 1px solid #86efac; color: #15803d;
            border-radius: 999px; padding: 3px 10px; font-size: .74rem; cursor: pointer;
            white-space: nowrap; flex-shrink: 0; transition: background .15s; line-height: 1.6;
        }
        .ai-chip:hover { background: #dcfce7; }
        #ai-chat-footer { padding: 7px 12px; border-top: 1px solid #e5e7eb; flex-shrink: 0; display: flex; gap: 8px; }
        #ai-chat-input {
            flex: 1; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 12px;
            font-size: .875rem; outline: none; resize: none; height: 38px; max-height: 100px;
            overflow-y: auto; line-height: 1.4; font-family: inherit;
        }
        #ai-chat-input:focus { border-color: #15803d; box-shadow: 0 0 0 2px rgba(21,128,61,.15); }
        #ai-send-btn {
            background: #15803d; border: none; border-radius: 8px; cursor: pointer;
            color: #fff; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; transition: background .15s;
        }
        #ai-send-btn:hover { background: #166534; }
        #ai-send-btn:disabled { background: #d1d5db; cursor: default; }
        #ai-send-btn svg { width: 18px; height: 18px; fill: #fff; }
    </style>

    <button id="ai-chat-btn" title="AI Assistant" aria-label="Open AI Assistant">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2a10 10 0 0 1 10 10c0 5.52-4.48 10-10 10S2 17.52 2 12 6.48 2 12 2zm0 2a8 8 0 1 0 0 16A8 8 0 0 0 12 4zm-1 4h2v2h-2V8zm0 4h2v5h-2v-5z"/>
        </svg>
    </button>

    <div id="ai-chat-panel" class="ai-hidden" role="dialog" aria-label="AI Assistant">
        <div id="ai-chat-header">
            <span>
                <svg style="width:16px;height:16px;fill:#fff;vertical-align:middle;margin-right:6px" viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 1 10 10c0 5.52-4.48 10-10 10S2 17.52 2 12 6.48 2 12 2zm0 2a8 8 0 1 0 0 16A8 8 0 0 0 12 4zm-1 4h2v2h-2V8zm0 4h2v5h-2v-5z"/></svg>
                AI Assistant
            </span>
            <button class="ai-close" id="ai-chat-close" aria-label="Close">&times;</button>
        </div>
        <div id="ai-chat-msgs">
            <div class="ai-msg bot">Hi! I'm your ERP assistant. Ask me about invoices, products, customers, or stock — or just type <strong>help</strong> to see what I can do.</div>
        </div>
        <div id="ai-chat-footer">
            <textarea id="ai-chat-input" placeholder="Ask anything…" rows="1"></textarea>
            <button id="ai-send-btn" aria-label="Send">
                <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
            </button>
        </div>
        <div id="ai-chat-chips">
            <button class="ai-chip" data-q="today invoice count">Today's Invoices</button>
            <button class="ai-chip" data-q="go to sales invoices">Sales Invoice</button>
            <button class="ai-chip" data-q="products">Products</button>
            <button class="ai-chip" data-q="go to reports">Reports</button>
            <button class="ai-chip" data-q="go to customers">Customers</button>
            <button class="ai-chip" data-q="help">Help</button>
        </div>
    </div>

    <script>
    (function () {
        'use strict';
        var panel   = document.getElementById('ai-chat-panel');
        var btn     = document.getElementById('ai-chat-btn');
        var closeBtn= document.getElementById('ai-chat-close');
        var msgs    = document.getElementById('ai-chat-msgs');
        var input   = document.getElementById('ai-chat-input');
        var sendBtn = document.getElementById('ai-send-btn');
        var history = [];
        var busy    = false;
        var CSRF    = document.querySelector('meta[name="csrf-token"]')?.content || '';

        function togglePanel() {
            panel.classList.toggle('ai-hidden');
            if (!panel.classList.contains('ai-hidden')) { input.focus(); }
        }

        btn.addEventListener('click', togglePanel);
        closeBtn.addEventListener('click', togglePanel);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !panel.classList.contains('ai-hidden')) { panel.classList.add('ai-hidden'); }
        });

        chips.addEventListener('click', function (e) {
            var chip = e.target.closest('.ai-chip');
            if (chip) { send(chip.dataset.q); }
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(input.value); }
        });
        sendBtn.addEventListener('click', function () { send(input.value); });

        input.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });

        function append(role, html) {
            var div = document.createElement('div');
            div.className = 'ai-msg ' + role;
            div.innerHTML = html;
            msgs.appendChild(div);
            msgs.scrollTop = msgs.scrollHeight;
            return div;
        }

        function showTyping() {
            var t = document.createElement('div');
            t.className = 'ai-typing';
            t.id = 'ai-typing-indicator';
            t.innerHTML = '<span></span><span></span><span></span>';
            msgs.appendChild(t);
            msgs.scrollTop = msgs.scrollHeight;
        }

        function hideTyping() {
            var t = document.getElementById('ai-typing-indicator');
            if (t) t.remove();
        }

        function mdToHtml(text) {
            return text
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.+?)\*/g, '<em>$1</em>')
                .replace(/`(.+?)`/g, '<code>$1</code>')
                .replace(/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>')
                .replace(/^[-*] (.+)$/gm, '<li>$1</li>')
                .replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>')
                .replace(/\n{2,}/g, '<br><br>')
                .replace(/\n/g, '<br>');
        }

        function send(text) {
            text = (text || '').trim();
            if (!text || busy) return;
            busy = true;
            input.value = '';
            input.style.height = '';
            sendBtn.disabled = true;
            append('user', mdToHtml(text));
            showTyping();

            var payload = { message: text, history: history, _token: CSRF };

            fetch('/api/chatbot/message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                hideTyping();
                var reply = data.reply || 'Sorry, I could not get a response.';
                append('bot', mdToHtml(reply));
                history.push({ role: 'user', content: text });
                history.push({ role: 'assistant', content: reply });
                if (history.length > 20) { history = history.slice(-20); }
            })
            .catch(function () {
                hideTyping();
                append('bot', 'Network error — please try again.');
            })
            .finally(function () {
                busy = false;
                sendBtn.disabled = false;
            });
        }
    })();
    </script>
    @endauth
    {{-- ══════════════════════════════════════════════════════════════════════════ --}}
</body>
</html>
