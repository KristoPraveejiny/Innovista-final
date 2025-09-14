            </main>
        </div>
    </div>

    <script>
    // Dashboard menu toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.getElementById('dashboard-menu-toggle');
        const sidebar = document.getElementById('sidebar');
        const container = document.querySelector('.user-dashboard-container');
        
        if (menuToggle && sidebar) {
            menuToggle.addEventListener('click', function() {
                container.classList.toggle('sidebar-active');
            });
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                    container.classList.remove('sidebar-active');
                }
            }
        });
    });
    </script>
</body>
</html>