<!-- Header -->
<header class="header">
    <div class="header-left">
        <h1 class="page-title">Vista General</h1>
    </div>
    
    <div class="header-right" style="display: flex; align-items: center; gap: 20px;">
        <!-- User Profile -->
        <div class="user-profile" style="display: flex; align-items: center; gap: 12px;">
            <div class="user-avatar-small" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">
                <?php echo strtoupper(substr($_SESSION['nombres'], 0, 2)); ?>
            </div>
            <div class="user-info">
                <div class="user-name-small" style="font-weight: 600; font-size: 14px; color: #1a202c;">
                    <?php echo htmlspecialchars($_SESSION['nombres']); ?>
                </div>
                <div class="user-role" style="font-size: 12px; color: #718096;">
                    <?php echo htmlspecialchars($_SESSION['cargo']); ?>
                </div>
            </div>
        </div>
        
        <!-- Logout Button -->
        <a href="<?php echo APP_URL; ?>/config/logout.php" class="logout-btn" title="Cerrar sesión" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #ef476f, #f78c6b); color: white; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; text-decoration: none;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
        </a>
    </div>
</header>

