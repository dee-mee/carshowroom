<!-- Top Navbar -->
        <div class="top-navbar">
            <button class="mobile-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                <div class="user-dropdown" onclick="toggleDropdown()">
                    <div class="user-avatar"><?php echo strtoupper(substr(($_SESSION['user_name'] ?? 'A'), 0, 1)); ?></div>
                    <div class="dropdown-menu" id="userDropdown">
                        <a href="/carshowroom/admin/profile.php" class="dropdown-item">
                            <i class="fas fa-user" style="margin-right: 8px;"></i>Profile
                        </a>
                        <a href="/carshowroom/admin/settings.php" class="dropdown-item">
                            <i class="fas fa-cog" style="margin-right: 8px;"></i>Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="/carshowroom/logout.php" class="dropdown-item text-danger">
                            <i class="fas fa-sign-out-alt" style="margin-right: 8px;"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>