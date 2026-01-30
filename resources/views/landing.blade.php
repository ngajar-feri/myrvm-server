<!doctype html>
<html lang="en" class="layout-navbar-fixed layout-wide" data-skin="default" data-template="front-pages-no-customizer" data-bs-theme="light">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>MyRVM Landing</title>
    <link rel="icon" type="image/x-icon" href="/vendor/assets/img/favicon/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/vendor/assets/vendor/fonts/iconify-icons.css" />
    <link rel="stylesheet" href="/vendor/assets/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="/vendor/assets/vendor/css/core.css" />
    <link rel="stylesheet" href="/vendor/assets/css/demo.css" />
    <link rel="stylesheet" href="/vendor/assets/vendor/css/pages/front-page.css" />
    <link rel="stylesheet" href="/vendor/assets/vendor/libs/nouislider/nouislider.css" />
    <link rel="stylesheet" href="/vendor/assets/vendor/libs/swiper/swiper.css" />
    <link rel="stylesheet" href="/vendor/assets/vendor/css/pages/front-page-landing.css" />
    <script src="/vendor/assets/vendor/js/helpers.js"></script>
    <script src="/vendor/assets/js/front-config.js"></script>
    <style>
      /* Fix for overlapping navbar and hero section */
      .layout-navbar {
        position: fixed;
        top: 0;
        right: 0;
        left: 0;
        z-index: 1030;
        background-color: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(5px);
      }
      
      /* Add spacing to prevent content from being hidden behind fixed navbar */
      body {
        padding-top: 5rem;
      }
      
      @media (max-width: 991.98px) {
        body {
          padding-top: 4rem;
        }
      }
    </style>
  </head>
  <body>
    <script src="/vendor/assets/vendor/js/dropdown-hover.js"></script>
    <script src="/vendor/assets/vendor/js/mega-dropdown.js"></script>
    <nav class="layout-navbar shadow-none py-0">
      <div class="container">
        <div class="navbar navbar-expand-lg landing-navbar px-3 px-md-8">
          <div class="navbar-brand app-brand demo d-flex py-0 me-4 me-xl-8 ms-0">
            <button class="navbar-toggler border-0 px-0 me-4" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
              <i class="icon-base ti tabler-menu-2 icon-lg align-middle text-heading fw-medium"></i>
            </button>
            <a href="{{ route('landing') }}" class="app-brand-link">
              <span class="app-brand-text demo menu-text fw-bold ms-2 ps-1">MyRVM</span>
            </a>
          </div>
          <div class="collapse navbar-collapse landing-nav-menu" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto">
              <li class="nav-item"><a class="nav-link fw-medium" href="#landingHero">Home</a></li>
              <li class="nav-item"><a class="nav-link fw-medium" href="#landingFeatures">Features</a></li>
              <li class="nav-item"><a class="nav-link fw-medium" href="#landingContact">Contact</a></li>
            </ul>
            <div class="d-flex align-items-center">
              <a class="btn btn-primary" href="{{ route('login') }}">Login</a>
            </div>
          </div>
        </div>
      </div>
    </nav>
    <section id="landingHero" class="py-6">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-md-6">
            <h1 class="display-5">Reverse Vending Machine Platform</h1>
            <p class="lead">Edge AI + Cloud Orchestration untuk pengelolaan daur ulang yang modern.</p>
            <a href="{{ route('login') }}" class="btn btn-primary btn-lg">Mulai</a>
          </div>
          <div class="col-md-6">
            <img src="/vendor/assets/img/front-pages/landing/hero-dashboard.png" class="img-fluid" alt="Hero" />
          </div>
        </div>
      </div>
    </section>
    <section id="landingFeatures" class="py-6 bg-light">
      <div class="container">
        <div class="row">
          <div class="col-md-4"><h5>Edge Vision</h5><p>Pemrosesan AI di Jetson Orin Nano.</p></div>
          <div class="col-md-4"><h5>GPU Compute</h5><p>Komputasi berat di vm102 tanpa storage.</p></div>
          <div class="col-md-4"><h5>Object Storage</h5><p>MinIO sebagai storage utama di vm100.</p></div>
        </div>
      </div>
    </section>
    <footer class="py-6">
      <div class="container text-center">
        <p class="mb-0">© {{ date('Y') }} MyRVM Platform</p>
      </div>
    </footer>
    <script src="/vendor/assets/vendor/libs/jquery/jquery.js"></script>
    <script src="/vendor/assets/vendor/libs/popper/popper.js"></script>
    <script src="/vendor/assets/vendor/js/bootstrap.js"></script>
    <script src="/vendor/assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="/vendor/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="/vendor/assets/vendor/libs/hammer/hammer.js"></script>
    <script src="/vendor/assets/vendor/libs/i18n/i18n.js"></script>
    <script src="/vendor/assets/vendor/js/menu.js"></script>
    <script src="/vendor/assets/js/main.js"></script>
  </body>
</html>

