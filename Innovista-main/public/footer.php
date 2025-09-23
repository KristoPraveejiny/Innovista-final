    <footer class="main-footer">
        <div class="container">
            <div class="footer-grid">
                <!-- Column 1: Brand -->
                <div class="footer-column">
                    <h4>INNOVISTA</h4>
                    <p>Connecting customers with trusted professionals for interior design, painting, and restoration projects in the Northern Province.</p>
                </div>

                <!-- Column 2: Quick Links -->
                <div class="footer-column">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="./index.php">Home</a></li>
                        <li><a href="./services.php">Services</a></li>
                        <li><a href="./product.php">Products</a></li>
                        <li><a href="./portfolio.php">Portfolio</a></li>
                        <li><a href="./about.php">About Us</a></li>
                        <li><a href="./contact.php">Contact</a></li>   
                    </ul>
                </div>

                <!-- =========================================
                     DYNAMIC "FOR USERS" SECTION
                     ========================================= -->
                <div class="footer-column">
                    <h4>For Users</h4>
                    <ul>
                        <?php if (isUserLoggedIn()): ?>
                            <!-- Show these links if the user IS logged in -->
                            <?php 
                            $userRole = getUserRole();
                            if ($userRole === 'customer'): ?>
                                <li><a href="../customer/customer_dashboard.php">My Dashboard</a></li>
                                <li><a href="../customer/my_profile.php">My Profile</a></li>
                                <li><a href="../customer/my_projects.php">My Projects</a></li>
                            <?php elseif ($userRole === 'provider'): ?>
                                <li><a href="../provider/provider_dashboard.php">My Dashboard</a></li>
                                <li><a href="../provider/my_profile.php">My Profile</a></li>
                                <li><a href="../provider/my_projects.php">My Projects</a></li>
                            <?php elseif ($userRole === 'admin'): ?>
                                <li><a href="../admin/admin_dashboard.php">Admin Dashboard</a></li>
                                <li><a href="../admin/manage_users.php">Manage Users</a></li>
                            <?php endif; ?>
                            <li><a href="./logout.php">Logout</a></li>
                        <?php else: ?>
                            <!-- Show these links if the user IS NOT logged in -->
                            <li><a href="./login.php">Login</a></li>
                            <li><a href="signup.php">Sign Up as a Customer</a></li>
                            <li><a href="signup.php">Become a Service Provider</a></li>
                            <li><a href="index.php#how-it-works">How It Works</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Column 4: Connect -->
                <div class="footer-column">
                    <h4>Connect With Us</h4>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p>© <?php echo date("Y"); ?> Innovista. All rights reserved. | <a href="#">Privacy Policy</a> | <a href="#">Terms of Service</a></p>
            </div>
        </div>
    </footer>

    <!-- All modal HTML (like the signup and login forms) should be placed right here, before the scripts -->

    <!-- =========================================
         GLOBAL JAVASCRIPT FILES
         ========================================= -->
         
    <!-- Script for the header (mobile menu, scrolled effect) -->
    <script src="assets/js/header-script.js"></script>


        <!-- Removed missing modals-script.js and main-script.js -->
    
    <!-- Page-specific scripts (only load if needed, but including them here is simple for now) -->
    <script src="assets/js/services.js"></script>
    <script src="assets/js/product-script.js"></script>
    <script src="assets/js/portfolio-script.js"></script>
    <script src="assets/js/contact-script.js"></script>
    <script src="assets/js/booking-script.js"></script>

</body>
</html>