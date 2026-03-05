<?php
/**
 * topbar.php — Alias global del topbar unificado SIGED
 *
 * INSTRUCCIÓN DE USO:
 * En cualquier vista interna, coloca este include DENTRO del div.main-content:
 *
 *   <div class="main-content">
 *       <?php include __DIR__ . '/../layout/topbar.php'; ?>
 *       ...contenido...
 *   </div>
 *
 * Si estás en un subdirectorio diferente, ajusta la ruta relativa:
 *   include __DIR__ . '/../../vistas/layout/topbar.php';
 *
 * Este archivo NO contiene lógica propia, solo delega a header.php
 * para mantener un único punto de verdad en el codebase.
 */

include __DIR__ . '/header.php';
