<!doctype html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed" dir="ltr" data-theme="theme-default"
    data-assets-path="/vendor/assets/" data-template="vertical-menu-template">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - MyRVM</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/vendor/assets/img/favicon/favicon.ico" />
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <!-- Icons -->
    <link rel="stylesheet" href="/vendor/assets/vendor/fonts/iconify-icons.css" />
    <!-- Core CSS -->
    <link rel="stylesheet" href="/vendor/assets/vendor/css/core.css" />
    <!-- <link rel="stylesheet" href="/vendor/assets/vendor/css/theme-default.css" /> -->
    <link rel="stylesheet" href="/vendor/assets/css/demo.css" />
    <!-- Vendors CSS -->
    <link rel="stylesheet" href="/vendor/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="/vendor/assets/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="/vendor/assets/vendor/libs/apex-charts/apex-charts.css" />
    <!-- Page CSS -->
    @yield('page-style')
    <!-- SPA Navigation CSS -->
    <link rel="stylesheet" href="{{ asset('css/spa-navigation.css') }}" />
    <!-- Leaflet.js CSS (OpenStreetMap) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <!-- Tag Autocomplete CSS -->
    <link rel="stylesheet" href="{{ asset('css/tag-autocomplete.css') }}" />
    <!-- Assignments CSS -->
    <link rel="stylesheet" href="{{ asset('css/assignments.css') }}" />
    <!-- Helpers -->
    <script src="/vendor/assets/vendor/js/helpers.js"></script>
    <script src="/vendor/assets/js/config.js"></script>

    <!-- API Token for Dashboard -->
    <script>
        window.API_TOKEN = "{{ session('api_token', '') }}";
    </script>
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->
            <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
                <div class="app-brand demo">
                    <a href="{{ route('dashboard') }}" class="app-brand-link">
                        <span class="app-brand-text demo menu-text fw-bold">MyRVM</span>
                    </a>
                </div>
                <div class="menu-inner-shadow"></div>
                <ul class="menu-inner py-1">
                    <!-- Dashboard -->
                    <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <a href="{{ route('dashboard') }}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-smart-home"></i>
                            <div>Dashboard</div>
                        </a>
                    </li>

                    <!-- Management Header -->
                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text">Management</span>
                    </li>

                    <!-- 1. User & Tenants (Expandable) -->
                    @if(in_array(auth()->user()->role, ['super_admin', 'admin', 'operator']))
                        <li class="menu-item {{ request()->routeIs('dashboard.users*') ? 'open active' : '' }}">
                            <a href="javascript:void(0);" class="menu-link menu-toggle">
                                <i class="menu-icon icon-base ti tabler-users"></i>
                                <div>User & Tenants</div>
                            </a>
                            <ul class="menu-sub">
                                <li class="menu-item {{ request()->routeIs('dashboard.users') ? 'active' : '' }}">
                                    <a href="{{ route('dashboard.users') }}" class="menu-link" data-page="users">
                                        <div>Master Data</div>
                                    </a>
                                </li>
                                <li class="menu-item {{ request()->routeIs('dashboard.assignments') ? 'active' : '' }}">
                                    <a href="{{ route('dashboard.assignments') }}" class="menu-link"
                                        data-page="assignments">
                                        <div>Assignments</div>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    <!-- 2. RVM Machines (Expandable) -->
                    @if(in_array(auth()->user()->role, ['super_admin', 'admin', 'operator', 'teknisi']))
                        <li class="menu-item {{ request()->routeIs('dashboard.machines*') ? 'open active' : '' }}">
                            <a href="javascript:void(0);" class="menu-link menu-toggle">
                                <i class="menu-icon icon-base ti tabler-device-desktop-analytics"></i>
                                <div>RVM Machines</div>
                            </a>
                            <ul class="menu-sub">
                                <li class="menu-item {{ request()->routeIs('dashboard.machines') ? 'active' : '' }}">
                                    <a href="{{ route('dashboard.machines') }}" class="menu-link" data-page="machines">
                                        <div>Master Data</div>
                                    </a>
                                </li>
                                <li class="menu-item {{ request()->routeIs('dashboard.tickets') ? 'active' : '' }}">
                                    <a href="{{ route('dashboard.tickets') }}" class="menu-link" data-page="tickets">
                                        <div>Maintenance Tickets</div>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif


                    <!-- Monitoring Header -->
                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text">Monitoring</span>
                    </li>
                    <li class="menu-item">
                        <a href="#" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-activity"></i>
                            <div>System Health</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="#" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-chart-bar"></i>
                            <div>Transactions</div>
                        </a>
                    </li>
                    @if(in_array(auth()->user()->role, ['super_admin', 'admin']))
                        <li class="menu-item {{ request()->routeIs('dashboard.logs') ? 'active' : '' }}">
                            <a href="{{ route('dashboard.logs') }}" class="menu-link" data-page="logs">
                                <i class="menu-icon icon-base ti tabler-file-analytics"></i>
                                <div>Logs</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="{{ route('dashboard.api-docs') }}" class="menu-link">
                                <i class="menu-icon icon-base ti tabler-api"></i>
                                <div>API Documentation</div>
                            </a>
                        </li>
                    @endif
                </ul>
            </aside>
            <!-- / Menu -->

            <!-- Layout container -->
            <div class="layout-page">
                <!-- Navbar -->
                <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
                    id="layout-navbar">
                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                            <i class="icon-base ti tabler-menu-2 icon-sm"></i>
                        </a>
                    </div>
                    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
                        <!-- Search -->
                        <div class="navbar-nav align-items-center">
                            <div class="nav-item d-flex align-items-center">
                                <i class="icon-base ti tabler-search fs-4 lh-0"></i>
                                <input type="text" class="form-control border-0 shadow-none" placeholder="Search..."
                                    aria-label="Search..." />
                            </div>
                        </div>
                        <!-- /Search -->
                        <ul class="navbar-nav flex-row align-items-center ms-auto">
                            <!-- User -->
                            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);"
                                    data-bs-toggle="dropdown">
                                    <div class="avatar avatar-online">
                                        <img src="/vendor/assets/img/avatars/1.png" alt class="h-auto rounded-circle" />
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="#">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="avatar avatar-online">
                                                        <img src="/vendor/assets/img/avatars/1.png" alt
                                                            class="h-auto rounded-circle" />
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <span
                                                        class="fw-semibold d-block">{{ auth()->user()->name ?? 'User' }}</span>
                                                    <small
                                                        class="text-muted">{{ auth()->user()->role ?? 'Role' }}</small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                    </li>
                                    <li>
                                        <form action="{{ route('logout') }}" method="POST">
                                            @csrf
                                            <button class="dropdown-item" type="submit">
                                                <i class="icon-base ti tabler-power me-2"></i>
                                                <span class="align-middle">Log Out</span>
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                            <!--/ User -->
                        </ul>
                    </div>
                </nav>
                <!-- / Navbar -->

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Content -->
                    <div class="container-xxl flex-grow-1 container-p-y">
                        @yield('content')
                    </div>
                    <!-- / Content -->

                    <!-- Footer -->
                    <footer class="content-footer footer bg-footer-theme">
                        <div class="container-xxl">
                            <div
                                class="footer-container d-flex align-items-center justify-content-between py-2 flex-md-row flex-column">
                                <div>
                                    © {{ date('Y') }} MyRVM Platform
                                </div>
                            </div>
                        </div>
                    </footer>
                    <!-- / Footer -->
                    <div class="content-backdrop fade"></div>
                </div>
                <!-- Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>
        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>
        <!-- Drag Target Area To SlideIn Menu On Small Screens -->
        <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->

    <!-- Core JS -->
    <script src="/vendor/assets/vendor/libs/jquery/jquery.js"></script>
    <script src="/vendor/assets/vendor/libs/popper/popper.js"></script>
    <script src="/vendor/assets/vendor/js/bootstrap.js"></script>
    <script src="/vendor/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="/vendor/assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="/vendor/assets/vendor/libs/hammer/hammer.js"></script>
    <script src="/vendor/assets/vendor/js/menu.js"></script>
    <!-- Vendors JS -->
    <script src="/vendor/assets/vendor/libs/apex-charts/apexcharts.js"></script>
    <!-- Main JS -->
    <script src="/vendor/assets/js/main.js"></script>
    <!-- API Helper for Dashboard API calls -->
    <script src="{{ asset('js/api-helper.js') }}"></script>
    <!-- SPA Navigator -->
    <script src="{{ asset('js/spa-navigator.js') }}?v={{ time() }}"></script>
    <!-- Leaflet.js (OpenStreetMap) -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <!-- Assignment Components -->
    <script src="{{ asset('js/components/tag-autocomplete.js') }}"></script>
    <script src="{{ asset('js/components/enhanced-map.js') }}"></script>
    <!-- Page JS -->
    @yield('page-script')
</body>

</html>