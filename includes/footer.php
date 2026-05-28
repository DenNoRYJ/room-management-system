</main> <footer class="main-footer">
    <div class="container footer-inner">

        <div class="footer-brand-col">
            <div class="footer-brand">
                <svg class="footer-logo-svg" viewBox="0 0 56 48" fill="none" xmlns="http://www.w3.org/2000/svg">
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
                <div>
                    <p class="footer-brand-name">TUPV</p>
                    <p class="footer-brand-sub">Room Management System</p>
                    <p class="footer-tagline">Reserve. Monitor. Manage.</p>
                </div>
            </div>
            <p class="footer-about">
                A web-based facility reservation platform for the Technological University
                of the Philippines Visayas, streamlining room bookings for students,
                faculty, and administrators.
            </p>
        </div>

        <div class="footer-links">

            <?php if (isset($_SESSION['student_id'])): ?>
                <div class="footer-col">
                    <h4>Student Portal</h4>
                    <a href="/room-management-system/user/dashboard.php">Dashboard</a>
                    <a href="/room-management-system/user/my_bookings.php">My Bookings</a>
                    <a href="/room-management-system/user/logout.php">Logout</a>
                </div>

            <?php elseif (isset($_SESSION['admin_id'])): ?>
                <div class="footer-col">
                    <h4>Admin Portal</h4>
                    <a href="/room-management-system/admin/dashboard.php">Dashboard</a>
                    <a href="/room-management-system/admin/manage_rooms.php">Manage Rooms</a>
                    <a href="/room-management-system/admin/pending_bookings.php">Pending Bookings</a>
                    <a href="/room-management-system/admin/walkin_booking.php">Walk-in Booking</a>
                    <a href="/room-management-system/admin/logout.php">Logout</a>
                </div>

            <?php else: ?>
                <div class="footer-col">
                    <h4>Student</h4>
                    <a href="/room-management-system/login.php">Student Login</a>
                </div>
                <div class="footer-col">
                    <h4>Administration</h4>
                    <a href="/room-management-system/admin/login.php">Admin Login</a>
                </div>
            <?php endif; ?>

        </div>

    </div>

    <div class="footer-bottom">
        <div class="container">
            <p>&copy; <?= date('Y'); ?> Technological University of the Philippines Visayas &mdash; Room Management System. All rights reserved.</p>
        </div>
    </div>
</footer>

<script src="/room-management-system/assets/js/main/main.js" defer></script>
</body>
</html>