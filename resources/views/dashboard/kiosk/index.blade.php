<!DOCTYPE html>
<html lang="id" class="kiosk-app" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    
    <title>MyRVM Kiosk - {{ $machine->name ?? 'Reverse Vending Machine' }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/vendor/assets/img/favicon/favicon.ico">
    
    <!-- Fonts: Inter for Bio-Digital aesthetic -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Kiosk Styles -->
    @vite(['resources/css/kiosk.css', 'resources/js/kiosk/app.js'])
    
    <!-- Machine Configuration (Injected from Server) -->
    <script>
        window.KIOSK_CONFIG = @json($config);
    </script>
    
    <style>
        /* Critical CSS - Prevent flash of unstyled content */
        html, body {
            margin: 0;
            padding: 0;
            overflow: hidden;
            touch-action: manipulation;
            -webkit-user-select: none;
            user-select: none;
        }

        /* HOTFIX V2: Aggressive Compaction & Lift */
        .idle-screen { padding: 0 !important; }
        .kiosk-logo { margin-bottom: 8px !important; font-size: 42px !important; }
        .welcome-title { margin-bottom: 2px !important; font-size: 24px !important; }
        .welcome-subtitle { font-size: 16px !important; }
        .qr-container { margin: 16px 0 !important; }
        .qr-box { padding: 12px !important; min-width: 200px !important; min-height: 200px !important; }
        .guest-action { margin-top: 12px !important; }
        /* Lift Footer significantly (80px) to clear bottom bezel */
        .kiosk-footer { padding-bottom: 80px !important; }
        
        /* Loading state before Vue mounts */
        .kiosk-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: var(--bg-primary, #FDFDFD);
            font-family: 'Inter', sans-serif;
        }
        
        .kiosk-loading .loader {
            text-align: center;
        }
        
        .kiosk-loading .loader-icon {
            font-size: 48px;
            animation: pulse 2s ease-in-out infinite;
        }
        
        .kiosk-loading .loader-text {
            margin-top: 16px;
            font-size: 18px;
            color: var(--text-secondary, #9E9E9E);
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.05); }
        }
        
        /* Theme Variables */
        :root, [data-theme="light"] {
            --bg-primary: #FDFDFD;
            --bg-secondary: #EEEEEE;
            --bg-tertiary: #F5F5F5;
            --text-primary: #404040;
            --text-secondary: #9E9E9E;
            --accent-primary: #4CAF50;
            --accent-primary-hover: #43A047;
            --accent-warning: #FFB74D;
            --accent-danger: #EF5350;
            --shadow-soft: 0 4px 20px rgba(0, 0, 0, 0.08);
            --border-radius: 16px;
        }
        
        [data-theme="dark"] {
            --bg-primary: #1A1A1A;
            --bg-secondary: #2D2D2D;
            --bg-tertiary: #383838;
            --text-primary: #E0E0E0;
            --text-secondary: #9E9E9E;
            --accent-primary: #66BB6A;
            --accent-primary-hover: #5CB05F;
            --accent-warning: #FFB74D;
            --accent-danger: #EF5350;
            --shadow-soft: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>

<body>
    <!-- Vue.js Mount Point -->
    <div id="kiosk-app">
        <!-- Loading State (replaced by Vue when mounted) -->
        <div class="kiosk-loading">
            <div class="loader">
                <div class="loader-icon">🌿</div>
                <div class="loader-text">Memuat antarmuka...</div>
            </div>
        </div>
    </div>
    
    <!-- Prevent context menu on kiosk -->
    <script>
        document.addEventListener('contextmenu', e => e.preventDefault());
        
        // Apply theme based on config
        (function() {
            const config = window.KIOSK_CONFIG || {};
            const themeMode = config.theme_mode || 'auto';
            const suggestedTheme = config.suggested_theme || 'light';
            
            let appliedTheme = themeMode;
            if (themeMode === 'auto') {
                appliedTheme = suggestedTheme;
            }
            
            document.documentElement.setAttribute('data-theme', appliedTheme);
        })();
    </script>
</body>

</html>
