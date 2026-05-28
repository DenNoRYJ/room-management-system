// index.php
<?php
include 'includes/db.php';

// Initialize total users count before template renders
$totalUsers = 0;

// Get total approved students
$studentCountQuery = $conn->query("SELECT COUNT(*) AS total FROM students WHERE account_status = 'Approved'");
if ($studentCountQuery) {
    $totalUsers += $studentCountQuery->fetch_assoc()['total'];
}

// Get total admins
$adminCountQuery = $conn->query("SELECT COUNT(*) AS total FROM admins");
if ($adminCountQuery) {
    $totalUsers += $adminCountQuery->fetch_assoc()['total'];
}

$pageTitle = "Home";
$extraCSS  = ['home.css'];
include 'includes/header.php';
?>

<!-- ═══════════════════════════════════════
     HERO SECTION
════════════════════════════════════════ -->
<section class="hero-section">
    <div class="hero-grid"></div>
    <div class="hero-orb hero-orb-1"></div>
    <div class="hero-orb hero-orb-2"></div>

    <div class="container">
        <div class="hero-content">

            <span class="hero-eyebrow">
                Technological University of the Philippines Visayas
            </span>

            <div class="hero-split-layout">
                
                <!-- Left: Headline -->
                <div class="hero-left">
                    <h1 class="hero-title">
                        Reserve.<br>
                        Monitor.<br>
                        <em>Manage.</em>
                    </h1>
                    <span class="hero-subtitle-line">TUPV Room Management System</span>
                </div>

                <!-- Right: Description & CTA -->
                <div class="hero-right">
                    <p class="hero-desc">
                        A centralized web-based platform for students, faculty, and administrators
                        to streamline classroom reservations, track real-time room availability,
                        and eliminate scheduling conflicts across TUPV campuses.
                    </p>

                    <div class="hero-actions">
                        <?php if (isset($_SESSION['student_id'])): ?>
                            <a href="/room-management-system/user/dashboard.php" class="hero-btn-primary">
                                Go to Dashboard
                                <svg class="btn-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                </svg>
                            </a>
                            <a href="/room-management-system/user/my_bookings.php" class="hero-btn-secondary">
                                My Bookings
                                <svg class="btn-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </a>

                        <?php elseif (isset($_SESSION['admin_id'])): ?>
                            <a href="/room-management-system/admin/dashboard.php" class="hero-btn-primary">
                                Admin Dashboard
                                <svg class="btn-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                </svg>
                            </a>
                            <a href="/room-management-system/admin/pending_bookings.php" class="hero-btn-secondary">
                                Pending Bookings
                                <svg class="btn-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </a>

                        <?php else: ?>
                            <a href="/room-management-system/login.php" class="hero-btn-primary">
                                Student Login
                                <svg class="btn-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                </svg>
                            </a>
                            <a href="/room-management-system/admin/login.php" class="hero-btn-secondary">
                                Admin Portal
                                <svg class="btn-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div> 
            
            <!-- Live System Statistics -->
            <div class="hero-stats">
                <div class="hero-stat-item">
                    <span class="hero-stat-number">100%</span>
                    <span class="hero-stat-label">Web-Based</span>
                </div>
                
                <div class="hero-stat-item">
                    <span class="hero-stat-number"><?= $totalUsers; ?></span>
                    <span class="hero-stat-label">Active Users</span>
                </div>
                
                <div class="hero-stat-item">
                    <span class="hero-stat-number">24/7</span>
                    <span class="hero-stat-label">Availability</span>
                </div>
            </div> 
        </div> 
    </div> 
</section>

<!-- ═══════════════════════════════════════
     ACCESS PORTALS (Hidden if logged in)
════════════════════════════════════════ -->
<?php if (!isset($_SESSION['student_id']) && !isset($_SESSION['admin_id'])): ?>
<section class="portals-section">
    <div class="container">

        <span class="section-label">Access Portals</span>
        <h2 class="section-heading">Who Are You?</h2>
        <p class="section-sub">
            Select the portal that matches your role to access the system.
            Student accounts are provided by the administration.
        </p>

        <div class="portals-grid">

            <div class="portal-card featured fade-up">
                <div class="portal-icon-wrap">
                    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="portal-card-title">Student Login</h3>
                    <p class="portal-card-desc">
                        Sign in with your assigned TUPV student credentials to browse course
                        room maps, check real-time room availability, submit booking requests,
                        and track the status of your reservations.
                    </p>
                </div>
                <a href="/room-management-system/login.php" class="portal-card-cta">
                    Sign In
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            </div>

            <div class="portal-card fade-up">
                <div class="portal-icon-wrap">
                    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="portal-card-title">Administrator</h3>
                    <p class="portal-card-desc">
                        Access the admin portal to manage courses, rooms, and student accounts,
                        review and approve booking requests, handle walk-in reservations,
                        and monitor overall facility usage from a centralized dashboard.
                    </p>
                </div>
                <a href="/room-management-system/admin/login.php" class="portal-card-cta">
                    Admin Login
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            </div>

        </div>
    </div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════
     HOW IT WORKS SECTION
════════════════════════════════════════ -->
<section class="hiw-section">
    <div class="container">

        <span class="section-label">Simple Process</span>
        <h2 class="section-heading">How It Works</h2>
        <p class="section-sub">Book a room in three simple steps, from anywhere on campus.</p>

        <div class="hiw-steps">

            <div class="hiw-step fade-up">
                <span class="hiw-step-num">01</span>
                <div class="hiw-step-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <h3 class="hiw-step-title">Login</h3>
                <p class="hiw-step-desc">
                    Sign in using your TUPV student credentials provided by the
                    department administration.
                </p>
            </div>

            <div class="hiw-step fade-up">
                <span class="hiw-step-num">02</span>
                <div class="hiw-step-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="hiw-step-title">Browse &amp; Book</h3>
                <p class="hiw-step-desc">
                    Select a course, view the room map, check real-time availability,
                    and submit your booking with preferred date and time.
                </p>
            </div>

            <div class="hiw-step fade-up">
                <span class="hiw-step-num">03</span>
                <div class="hiw-step-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="hiw-step-title">Get Confirmed</h3>
                <p class="hiw-step-desc">
                    The administrator reviews and approves your request. Track your
                    booking status and remarks from your dashboard.
                </p>
            </div>

        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════
     BOTTOM CTA BANNER (Hidden if logged in)
════════════════════════════════════════ -->
<?php if (!isset($_SESSION['student_id']) && !isset($_SESSION['admin_id'])): ?>
<section class="cta-section">
    <div class="container">
        <div class="cta-inner fade-up">
            <div class="cta-text">
                <h2 class="cta-title">Ready to Reserve Your Room?</h2>
                <p class="cta-desc">
                    Sign in to the TUPV Room Management System and submit your
                    room booking request in minutes.
                </p>
            </div>
            <div class="cta-actions">
                <a href="/room-management-system/login.php" class="cta-btn-primary">
                    Student Login
                </a>
                <a href="/room-management-system/admin/login.php" class="cta-btn-outline">
                    Admin Access
                </a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Standalone intersection observer for index.php specific animations -->
<script>
    (function () {
        const els = document.querySelectorAll('.fade-up');
        if (!els.length) return;
        const io = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const siblings = Array.from(entry.target.parentElement.children);
                    const idx = siblings.indexOf(entry.target);
                    entry.target.style.transitionDelay = (idx * 0.10) + 's';
                    entry.target.classList.add('visible');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        els.forEach(el => io.observe(el));
    })();
</script>

<?php include 'includes/footer.php'; ?>