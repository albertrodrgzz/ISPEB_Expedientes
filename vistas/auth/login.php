<?php
/**
 * vistas/auth/login.php
 * 
 * CORRECCIÓN APLICADA: Todas las rutas de assets ahora usan APP_URL
 * como prefijo absoluto. Esto garantiza que sin importar desde qué
 * controlador se incluya esta vista, los assets siempre apuntarán
 * a la raíz correcta del dominio con el protocolo correcto (https://).
 *
 * ANTES (roto en Render):
 *   href="publico/css/responsive.css"          ← relativa, se rompe según contexto
 *   href="../publico/css/responsive.css"        ← relativa, se rompe en subdirectorios
 *   href="http://tuapp.onrender.com/..."        ← hardcodeado, Mixed Content en HTTPS
 *
 * DESPUÉS (correcto):
 *   href="<?= APP_URL ?>/publico/css/responsive.css"
 *   → Renderiza: https://tuapp.onrender.com/publico/css/responsive.css
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión — <?php echo e(APP_NAME); ?></title>

    <!-- ✅ CORRECTO: Favicon con APP_URL absoluto -->
    <link rel="icon"           type="image/png" href="<?= APP_URL ?>/publico/imagenes/isotipo.png">
    <link rel="shortcut icon"                   href="<?= APP_URL ?>/publico/imagenes/isotipo.png">

    <!-- ✅ CORRECTO: CSS con APP_URL + cache busting con APP_BUILD -->
    <link rel="stylesheet" href="<?= APP_URL ?>/publico/css/responsive.css?v=<?= APP_BUILD ?>">

    <style>
        /* Estilos inline de la vista login — estos sí son seguros inline */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --color-primary:      #0F4C81;
            --color-primary-dark: #0a3560;
            --color-secondary:    #0288D1;
            --color-success:      #10b981;
            --color-warning:      #f59e0b;
            --color-danger:       #ef4444;
            --color-text:         #1e293b;
            --color-text-light:   #64748b;
            --color-bg:           #f1f5f9;
            --color-white:        #ffffff;
            --color-border:       #e2e8f0;
            --shadow-sm:  0 1px 3px rgba(0,0,0,0.12);
            --shadow-md:  0 4px 6px rgba(0,0,0,0.14);
            --shadow-lg:  0 10px 30px rgba(15,76,129,0.22);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #1e3a52;
            min-height: 100vh;
            margin: 0; padding: 0;
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
        }

        /* ✅ CORRECTO: Imagen de fondo en CSS inline usando APP_URL via PHP */
        /* NOTA: No se puede usar APP_URL dentro de <style> directamente.
           La solución es inyectarlo como variable CSS o usarlo en style="" inline. */
    </style>

    <!--
        PATRÓN RECOMENDADO para imágenes de fondo en CSS que necesiten APP_URL:
        En lugar de:  background-image: url('publico/imagenes/edificio.jpg')
        Usar una variable CSS inyectada desde PHP:
    -->
    <style>
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            /* ✅ Variable CSS inyectada con PHP para usar APP_URL en CSS */
            background-image: var(--bg-edificio);
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.45;
            z-index: 0;
        }
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background: linear-gradient(135deg, rgba(15,76,129,0.75) 0%, rgba(2,136,209,0.55) 100%);
            z-index: 1;
        }
    </style>

    <!-- ✅ Inyección de la variable CSS con APP_URL resuelto por PHP -->
    <style>
        :root {
            --bg-edificio: url('<?= APP_URL ?>/publico/imagenes/edificio-ispeb.jpg');
        }
    </style>
</head>

<body>
    <div class="login-wrapper" style="position:relative; z-index:10; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:2rem;">

        <!-- Logo -->
        <div class="login-container" style="background:white; border-radius:1rem; padding:2.5rem; width:100%; max-width:420px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">

            <div style="text-align:center; margin-bottom:2rem;">
                <!-- ✅ CORRECTO: <img> con APP_URL absoluto -->
                <img src="<?= APP_URL ?>/publico/imagenes/logotipo.png"
                     alt="<?= e(APP_NAME) ?>"
                     style="max-width:200px; height:auto;">
            </div>

            <h1 style="text-align:center; color:var(--color-primary); margin-bottom:0.5rem; font-size:1.5rem;">
                Iniciar Sesión
            </h1>
            <p style="text-align:center; color:var(--color-text-light); margin-bottom:2rem; font-size:0.9rem;">
                <?= e(APP_NAME) ?> — Sistema de Gestión de Expedientes
            </p>

            <?php if (!empty($error)): ?>
                <div style="background:#fef2f2; border:1px solid #fecaca; color:#dc2626; padding:0.75rem 1rem; border-radius:0.5rem; margin-bottom:1rem; font-size:0.875rem;">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($mensaje)): ?>
                <div style="background:#f0fdf4; border:1px solid #bbf7d0; color:#16a34a; padding:0.75rem 1rem; border-radius:0.5rem; margin-bottom:1rem; font-size:0.875rem;">
                    <?= e($mensaje) ?>
                </div>
            <?php endif; ?>

            <!-- ✅ CORRECTO: action del form con APP_URL absoluto -->
            <form method="POST" action="<?= APP_URL ?>/">
                <div style="margin-bottom:1.25rem;">
                    <label for="username" style="display:block; margin-bottom:0.4rem; font-weight:500; color:var(--color-text); font-size:0.875rem;">
                        Usuario
                    </label>
                    <input type="text"
                           id="username"
                           name="username"
                           required
                           autocomplete="username"
                           value="<?= eAttr($_POST['username'] ?? '') ?>"
                           style="width:100%; padding:0.75rem 1rem; border:1px solid var(--color-border); border-radius:0.5rem; font-size:1rem; outline:none; transition:border-color 0.2s;">
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label for="password" style="display:block; margin-bottom:0.4rem; font-weight:500; color:var(--color-text); font-size:0.875rem;">
                        Contraseña
                    </label>
                    <input type="password"
                           id="password"
                           name="password"
                           required
                           autocomplete="current-password"
                           style="width:100%; padding:0.75rem 1rem; border:1px solid var(--color-border); border-radius:0.5rem; font-size:1rem; outline:none; transition:border-color 0.2s;">
                </div>

                <button type="submit"
                        name="login"
                        value="1"
                        style="width:100%; padding:0.875rem; background:var(--color-primary); color:white; border:none; border-radius:0.5rem; font-size:1rem; font-weight:600; cursor:pointer; transition:background 0.2s;">
                    Ingresar al Sistema
                </button>
            </form>

        </div>
    </div>

    <!-- ✅ CORRECTO: Scripts JS con APP_URL absoluto + cache busting -->
    <script src="<?= APP_URL ?>/publico/vendor/sweetalert2/sweetalert2.all.min.js?v=<?= APP_BUILD ?>"></script>
    <script src="<?= APP_URL ?>/publico/js/app.js?v=<?= APP_BUILD ?>"></script>

</body>
</html>
