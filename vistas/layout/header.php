<!-- Header -->
<header class="header">
    <div class="header-left">
        <h1 class="page-title">Vista General</h1>
    </div>
    
    <div class="header-right">
        <div class="search-container">
            <input type="search" class="header-search" placeholder="Buscar...">
        </div>
        
        <button class="notification-btn">
            <span class="notification-icon">🔔</span>
            <?php if ($por_vencer > 0): ?>
                <span class="notification-badge"><?php echo $por_vencer; ?></span>
            <?php endif; ?>
        </button>
    </div>
</header>
