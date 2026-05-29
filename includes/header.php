<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="view-transition" content="same-origin">
    
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | TUPV RMS' : 'TUPV Room Management System'; ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="/room-management-system/assets/css/main/variables.css">
    <link rel="stylesheet" href="/room-management-system/assets/css/main/reset.css">
    <link rel="stylesheet" href="/room-management-system/assets/css/main/layout.css">
    <link rel="stylesheet" href="/room-management-system/assets/css/main/components.css">
    <link rel="stylesheet" href="/room-management-system/assets/css/style.css">
    
    <?php if (isset($extraCSS)): ?>
        <?php foreach ($extraCSS as $css): ?>
            <link rel="stylesheet" href="/room-management-system/assets/css/<?= htmlspecialchars($css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>

<header class="main-header" id="main-header">
    <div class="nav-container container">

        <a href="/room-management-system/index.php" class="brand">
            <svg class="logo-svg" viewBox="0 0 56 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="TUPV RMS Logo">
                <defs>
                    <linearGradient id="fbldA" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#FF5555"/>
                        <stop offset="100%" stop-color="#9A1010"/>
                    </linearGradient>
                    <linearGradient id="fbldB" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#EE3333"/>
                        <stop offset="100%" stop-color="#AA1818"/>
                    </linearGradient>
                </defs>
                <rect x="1"  y="3"  width="13" height="37" rx="1.2" fill="url(#fbldA)"/>
                <rect x="10" y="3"  width="4"  height="37" rx="0"   fill="rgba(0,0,0,0.2)"/>
                <rect x="16" y="10" width="11" height="30" rx="1.2" fill="url(#fbldB)"/>
                <rect x="29" y="18" width="9"  height="22" rx="1.2" fill="url(#fbldA)"/>
                <path d="M0 42 Q20 47 46 39" stroke="rgba(255,255,255,0.35)" stroke-width="2.4" stroke-linecap="round" fill="none"/>
                <circle cx="41" cy="37" r="7"   fill="rgba(255,255,255,0.15)"/>
                <circle cx="41" cy="37" r="5.2" fill="rgba(255,255,255,0.22)"/>
                <circle cx="41" cy="37" r="2.4" fill="rgba(255,255,255,0.40)"/>
                <g fill="rgba(255,255,255,0.55)">
                    <rect x="40.2" y="28.5" width="1.6" height="2.8" rx="0.6" transform="rotate(0   41 37)"/>
                    <rect x="40.2" y="28.5" width="1.6" height="2.8" rx="0.6" transform="rotate(45  41 37)"/>
                    <rect x="40.2" y="28.5" width="1.6" height="2.8" rx="0.6" transform="rotate(90  41 37)"/>
                    <rect x="40.2" y="28.5" width="1.6" height="2.8" rx="0.6" transform="rotate(135 41 37)"/>
                    <rect x="40.2" y="28.5" width="1.6" height="2.8" rx="0.6" transform="rotate(180 41 37)"/>
                    <rect x="40.2" y="28.5" width="1.6" height="2.8" rx="0.6" transform="rotate(225 41 37)"/>
                    <rect x="40.2" y="28.5" width="1.6" height="2.8" rx="0.6" transform="rotate(270 41 37)"/>
                    <rect x="40.2" y="28.5" width="1.6" height="2.8" rx="0.6" transform="rotate(315 41 37)"/>
                </g>
            </svg>

            <div class="brand-text">
                <span class="brand-name">TUPV</span>
                <span class="brand-sub">Room Management System</span>
            </div>
        </a>

        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>

        <nav class="main-nav" id="mainNav">
            <ul class="nav-links">
                <li><a href="/room-management-system/index.php" class="nav-link">Home</a></li>

                <?php if (isset($_SESSION['student_id'])): ?>
                    <li><a href="/room-management-system/user/dashboard.php"   class="nav-link">Dashboard</a></li>
                    <li><a href="/room-management-system/user/my_bookings.php" class="nav-link">My Bookings</a></li>
                    <li><a href="/room-management-system/user/logout.php"      class="nav-link nav-btn-outline">Logout</a></li>

                <?php elseif (isset($_SESSION['admin_id'])): ?>
                    <li><a href="/room-management-system/admin/dashboard.php" class="nav-link">Admin Portal</a></li>
                    <li><a href="/room-management-system/admin/booking_history.php" class="nav-link">History Log</a></li>
                    <li><a href="/room-management-system/admin/logout.php"    class="nav-link nav-btn-outline">Logout</a></li>

                <?php else: ?>
                    <li><a href="/room-management-system/login.php"       class="nav-link">Student Login</a></li>
                    <li><a href="/room-management-system/admin/login.php" class="nav-link nav-btn-solid">Admin</a></li>
                <?php endif; ?>
            </ul>
        </nav>

    </div>
</header>

<main class="main-content">