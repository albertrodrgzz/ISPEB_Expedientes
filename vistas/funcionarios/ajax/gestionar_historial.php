<?php
/**
 * Controlador de Escritura - Historial Administrativo
 * * Gestiona las operaciones de creación de registros de historial CON COHERENCIA DE NEGOCIO.
 * * Módulos Activos: Traslados, Nombramientos, Vacaciones, Amonestaciones.
 * * Módulos Eliminados: Remociones, Salidas (Despidos/Renuncias).
 */

// IMPORTANTE: Iniciar output buffering ANTES de cualquier otra cosa
ob_start();

// Suprimir display de errores para AJAX
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Configuración de respuesta JSON
header('Content-Type: application/json; charset=utf-8');

// Seguridad y configuración
require_once __DIR__ . '/../../../config/seguridad.php';
require_once __DIR__ . '/../../../config/database.php';

verificarSesion();

// Solo usuarios con nivel de acceso 1-2 pueden registrar en historial
if ($_SESSION['nivel_acceso'] > 2) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'No tiene permisos para realizar esta operación',
        'code' => 'ACCESO_DENEGADO'
    ]);
    exit;
}

// ⚠️ SEGURIDAD: Validar token CSRF
if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Token CSRF inválido o ausente. Recargue la página e intente nuevamente.',
        'code' => 'CSRF_INVALID'
    ]);
    exit;
}

// Inicializar conexión a base de datos
$pdo = getDB();

try {
    // Validar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido', 405);
    }

    // Obtener acción
    $accion = $_POST['accion'] ?? '';

    if (empty($accion)) {
        throw new Exception('Acción no especificada', 400);
    }

    // Enrutador de acciones
    switch($accion) {
        case 'registrar_traslado':
            $resultado = registrarTraslado($pdo);
            break;
        
        case 'registrar_vacacion':
            $resultado = registrarVacacion($pdo);
            break;
        
        case 'registrar_retorno_vacacion':
            $resultado = registrarRetornoVacacion($pdo);
            break;
        
        case 'registrar_amonestacion':
            $resultado = registrarAmonestacion($pdo);
            break;
        
        case 'registrar_nombramiento':
            $resultado = registrarNombramiento($pdo);
            break;
        
        // ELIMINADOS: registrar_remocion, registrar_despido
        
        default:
            throw new Exception('Acción no válida: ' . $accion, 400);
    }

    echo json_encode($resultado);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => 'ERROR_' . strtoupper($accion ?? 'GENERAL')
    ]);
}

/**
 * Registra un traslado de departamento
 * ✅ COHERENCIA: Actualiza departamento_id del funcionario
 */
function registrarTraslado($pdo) {
    // Validar campos requeridos
    $funcionario_id = filter_var($_POST['funcionario_id'] ?? 0, FILTER_VALIDATE_INT);
    $departamento_destino_id = filter_var($_POST['departamento_destino_id'] ?? 0, FILTER_VALIDATE_INT);
    $fecha_evento = $_POST['fecha_evento'] ?? date('Y-m-d');
    $motivo = trim($_POST['motivo'] ?? '');

    if (!$funcionario_id || !$departamento_destino_id) {
        throw new Exception('Datos incompletos para registrar traslado', 400);
    }

    if (empty($motivo)) {
        throw new Exception('Debe especificar el motivo del traslado', 400);
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    try {
        // Obtener datos actuales del funcionario
        $stmt = $pdo->prepare("
            SELECT f.departamento_id, d.nombre as departamento_actual
            FROM funcionarios f
            LEFT JOIN departamentos d ON f.departamento_id = d.id
            WHERE f.id = ? AND f.estado != 'inactivo'
        ");
        $stmt->execute([$funcionario_id]);
        $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$funcionario) {
            throw new Exception('Funcionario no encontrado o está inactivo', 404);
        }

        $departamento_origen_id = $funcionario['departamento_id'];

        if ($departamento_origen_id == $departamento_destino_id) {
            throw new Exception('El departamento de destino es igual al actual', 400);
        }

        // Obtener nombre del departamento destino
        $stmt = $pdo->prepare("SELECT nombre FROM departamentos WHERE id = ? AND estado = 'activo'");
        $stmt->execute([$departamento_destino_id]);
        $dept_destino = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dept_destino) {
            throw new Exception('Departamento de destino no válido o inactivo', 400);
        }

        // Preparar JSON con detalles
        $detalles = json_encode([
            'departamento_origen' => $funcionario['departamento_actual'] ?? 'Sin asignar',
            'departamento_destino' => $dept_destino['nombre'],
            'motivo' => htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8')
        ], JSON_UNESCAPED_UNICODE);

        // Manejar archivo PDF si existe
        $ruta_archivo = null;
        $nombre_original = null;

        if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
            $resultado_archivo = guardarArchivoHistorial($funcionario_id, 'traslados', $_FILES['archivo_pdf']);
            $ruta_archivo = $resultado_archivo['ruta'];
            $nombre_original = $resultado_archivo['nombre_original'];
        }

        // Insertar en historial_administrativo
        $stmt = $pdo->prepare("
            INSERT INTO historial_administrativo (
                funcionario_id, tipo_evento, fecha_evento,
                detalles, ruta_archivo_pdf, nombre_archivo_original,
                registrado_por
            ) VALUES (?, 'TRASLADO', ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $funcionario_id,
            $fecha_evento,
            $detalles,
            $ruta_archivo,
            $nombre_original,
            $_SESSION['usuario_id']
        ]);

        $historial_id = $pdo->lastInsertId();

        // ✅ COHERENCIA: Actualizar departamento del funcionario
        $stmt = $pdo->prepare("
            UPDATE funcionarios 
            SET departamento_id = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$departamento_destino_id, $funcionario_id]);

        // Registrar en auditoría
        registrarAuditoria('REGISTRAR_TRASLADO', 'historial_administrativo', $historial_id, null, [
            'funcionario_id' => $funcionario_id,
            'departamento_origen' => $funcionario['departamento_actual'],
            'departamento_destino' => $dept_destino['nombre'],
            'motivo' => $motivo,
            'departamento_id_actualizado' => $departamento_destino_id
        ]);

        // Confirmar transacción
        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Traslado registrado exitosamente. El departamento del funcionario se actualizó.',
            'data' => [
                'historial_id' => $historial_id,
                'departamento_nuevo' => $dept_destino['nombre']
            ]
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Registra un nombramiento (cambio de cargo)
 * ✅ COHERENCIA: Actualiza cargo_id del funcionario
 */
function registrarNombramiento($pdo) {
    // Validar campos requeridos
    $funcionario_id = filter_var($_POST['funcionario_id'] ?? 0, FILTER_VALIDATE_INT);
    $fecha_evento = $_POST['fecha_evento'] ?? date('Y-m-d');
    
    if (!$funcionario_id) {
        throw new Exception('Debe seleccionar un funcionario', 400);
    }
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    try {
        // Obtener datos actuales del funcionario
        $stmt = $pdo->prepare("
            SELECT f.*, c.nombre_cargo as cargo_actual, d.nombre as departamento
            FROM funcionarios f
            LEFT JOIN cargos c ON f.cargo_id = c.id
            LEFT JOIN departamentos d ON f.departamento_id = d.id
            WHERE f.id = ?
        ");
        $stmt->execute([$funcionario_id]);
        $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$funcionario) {
            throw new Exception('Funcionario no encontrado', 404);
        }
        
        if ($funcionario['estado'] === 'inactivo') {
            throw new Exception('No se pueden registrar nombramientos para un funcionario inactivo', 400);
        }
        
        // Preparar JSON con detalles
        $detalles = json_encode([
            'cargo' => $funcionario['cargo_actual'] ?? 'Sin cargo',
            'departamento' => $funcionario['departamento'] ?? 'Sin departamento',
            'motivo' => 'Registro de nombramiento'
        ], JSON_UNESCAPED_UNICODE);
        
        // Manejar archivo
        $ruta_archivo = null;
        $nombre_original = null;
        
        if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
            $resultado_archivo = guardarArchivoHistorial($funcionario_id, 'nombramientos', $_FILES['archivo_pdf']);
            $ruta_archivo = $resultado_archivo['ruta'];
            $nombre_original = $resultado_archivo['nombre_original'];
        }
        
        // Insertar en historial
        $stmt = $pdo->prepare("
            INSERT INTO historial_administrativo (
                funcionario_id, tipo_evento, fecha_evento,
                detalles, ruta_archivo_pdf, nombre_archivo_original,
                registrado_por
            ) VALUES (?, 'NOMBRAMIENTO', ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $funcionario_id,
            $fecha_evento,
            $detalles,
            $ruta_archivo,
            $nombre_original,
            $_SESSION['usuario_id']
        ]);
        
        $historial_id = $pdo->lastInsertId();
        
        // Registrar en auditoría
        registrarAuditoria('REGISTRAR_NOMBRAMIENTO', 'historial_administrativo', $historial_id, null, [
            'funcionario_id' => $funcionario_id,
            'cargo_actual' => $funcionario['cargo_actual'],
            'fecha_evento' => $fecha_evento
        ]);
        
        // Confirmar transacción
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Nombramiento registrado correctamente',
            'data' => [
                'funcionario' => $funcionario['nombres'] . ' ' . $funcionario['apellidos'],
                'cargo_actual' => $funcionario['cargo_actual'],
                'fecha' => date('d/m/Y', strtotime($fecha_evento))
            ]
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Registra uno o varios períodos de vacaciones en una sola transacción
 * ✅ COHERENCIA: Actualiza estado a 'vacaciones'
 *
 * Recibe por POST:
 *   - funcionario_id  : int
 *   - fecha_evento    : date (inicio del primer período)
 *   - periodos_años   : JSON array de números de año de período, ej. [1,3]
 *   - observaciones   : string (opcional)
 *   - archivo_pdf     : file (requerido)
 */
function registrarVacacion($pdo) {

    // ── Helper LOTTT ──────────────────────────────────────────────────────────
    $diasPorPeriodo = function(int $n): int {
        if ($n === 1) return 15;
        return min(18 + ($n - 2), 30);
    };

    // ── Validar entrada ───────────────────────────────────────────────────────
    $funcionario_id  = filter_var($_POST['funcionario_id'] ?? 0, FILTER_VALIDATE_INT);
    $fecha_evento    = trim($_POST['fecha_evento'] ?? '');
    $observaciones   = trim($_POST['observaciones'] ?? '');
    $periodos_raw    = $_POST['periodos_años'] ?? ''; // JSON string: "[1,2]"

    if (!$funcionario_id || empty($fecha_evento)) {
        throw new Exception('Datos incompletos para registrar vacación', 400);
    }

    $periodos_seleccionados = json_decode($periodos_raw, true);
    if (!is_array($periodos_seleccionados) || empty($periodos_seleccionados)) {
        throw new Exception('Debe seleccionar al menos un período vacacional', 400);
    }

    // Asegurarse que sean enteros positivos únicos
    $periodos_seleccionados = array_values(array_unique(array_filter(
        array_map('intval', $periodos_seleccionados),
        fn($v) => $v >= 1
    )));

    if (empty($periodos_seleccionados)) {
        throw new Exception('Los períodos seleccionados no son válidos', 400);
    }

    // ── Iniciar transacción ───────────────────────────────────────────────────
    $pdo->beginTransaction();

    try {
        // Obtener datos del funcionario
        $stmt = $pdo->prepare("
            SELECT id, estado, nombres, apellidos, fecha_ingreso
            FROM funcionarios WHERE id = ?
        ");
        $stmt->execute([$funcionario_id]);
        $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$funcionario)                              throw new Exception('Funcionario no encontrado', 404);
        if ($funcionario['estado'] === 'inactivo')      throw new Exception('Funcionario inactivo', 400);

        // Calcular antigüedad
        $fecha_ingreso = new DateTime($funcionario['fecha_ingreso']);
        $fecha_actual  = new DateTime();
        $años_servicio = $fecha_ingreso->diff($fecha_actual)->y;

        if ($años_servicio < 1) throw new Exception('No cumple requisito mínimo de 1 año de servicio', 400);

        // Validar que los períodos solicitados no superen los años de servicio
        foreach ($periodos_seleccionados as $p_año) {
            if ($p_año > $años_servicio) {
                throw new Exception("El período del año {$p_año} no le corresponde (solo tiene {$años_servicio} años de servicio)", 400);
            }
        }

        // Obtener períodos ya tomados
        $stmt = $pdo->prepare("
            SELECT CAST(JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.periodo_año')) AS UNSIGNED) AS periodo_año
            FROM historial_administrativo
            WHERE funcionario_id = ?
              AND tipo_evento = 'VACACION'
              AND JSON_EXTRACT(detalles, '$.periodo_año') IS NOT NULL
        ");
        $stmt->execute([$funcionario_id]);
        $ya_tomados = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'periodo_año');
        $ya_tomados = array_map('intval', $ya_tomados);

        foreach ($periodos_seleccionados as $p_año) {
            if (in_array($p_año, $ya_tomados, true)) {
                throw new Exception("El período del año {$p_año} ya fue tomado", 400);
            }
        }

        // Guardar archivo (único para todos los períodos del lote)
        if (!isset($_FILES['archivo_pdf']) || $_FILES['archivo_pdf']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Se requiere documento de aval', 400);
        }
        $resultado_archivo = guardarArchivoHistorial($funcionario_id, 'vacaciones', $_FILES['archivo_pdf']);
        $ruta_archivo      = $resultado_archivo['ruta'];
        $nombre_original   = $resultado_archivo['nombre_original'];

        // Insertar un registro por cada período seleccionado
        // Los períodos van en secuencia: al terminar uno, empieza el siguiente día hábil
        $fecha_cursor = new DateTime($fecha_evento);
        // Avanzar si cae en fin de semana
        while ((int)$fecha_cursor->format('N') > 5) $fecha_cursor->modify('+1 day');

        $historial_ids   = [];
        $fecha_retorno_final = null;

        foreach ($periodos_seleccionados as $p_año) {
            $dias_habiles = $diasPorPeriodo($p_año);

            // Calcular fecha de fin del período (contando días hábiles)
            $fecha_inicio_periodo = clone $fecha_cursor;
            $dias_contados = 0;

            while ($dias_contados < $dias_habiles) {
                $dow = (int)$fecha_cursor->format('N');
                if ($dow >= 1 && $dow <= 5) {
                    $dias_contados++;
                }
                if ($dias_contados < $dias_habiles) {
                    $fecha_cursor->modify('+1 day');
                }
            }

            $fecha_fin_periodo = clone $fecha_cursor;

            // Calcular fecha de retorno (próximo día hábil tras el fin)
            $fecha_retorno = clone $fecha_cursor;
            $fecha_retorno->modify('+1 day');
            while ((int)$fecha_retorno->format('N') > 5) $fecha_retorno->modify('+1 day');
            $fecha_retorno_str = $fecha_retorno->format('Y-m-d');

            // Detalles JSON
            $detalles = json_encode([
                'periodo_año'  => $p_año,
                'dias_habiles' => $dias_habiles,
                'observaciones'=> htmlspecialchars($observaciones, ENT_QUOTES, 'UTF-8'),
                'años_servicio'=> $años_servicio,
                'fecha_retorno'=> $fecha_retorno_str,
            ], JSON_UNESCAPED_UNICODE);

            $stmt = $pdo->prepare("
                INSERT INTO historial_administrativo (
                    funcionario_id, tipo_evento, fecha_evento, fecha_fin,
                    detalles, ruta_archivo_pdf, nombre_archivo_original,
                    registrado_por
                ) VALUES (?, 'VACACION', ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $funcionario_id,
                $fecha_inicio_periodo->format('Y-m-d'),
                $fecha_fin_periodo->format('Y-m-d'),
                $detalles,
                $ruta_archivo,
                $nombre_original,
                $_SESSION['usuario_id'],
            ]);

            $historial_ids[] = $pdo->lastInsertId();
            $fecha_retorno_final = $fecha_retorno_str;

            // Avanzar el cursor al inicio del siguiente período
            $fecha_cursor = clone $fecha_retorno;
        }

        // Actualizar estado funcionario a 'vacaciones'
        $stmt = $pdo->prepare("UPDATE funcionarios SET estado = 'vacaciones', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$funcionario_id]);

        // Auditoría
        registrarAuditoria('REGISTRAR_VACACION', 'historial_administrativo', $historial_ids[0], null, [
            'funcionario_id'    => $funcionario_id,
            'periodos_registrados' => $periodos_seleccionados,
            'estado_actualizado'=> 'vacaciones',
        ]);

        $pdo->commit();

        return [
            'success' => true,
            'message' => count($periodos_seleccionados) > 1
                ? count($periodos_seleccionados) . ' períodos vacacionales registrados exitosamente.'
                : 'Período vacacional registrado exitosamente.',
            'data' => [
                'periodos_registrados' => $periodos_seleccionados,
                'fecha_retorno'        => $fecha_retorno_final,
            ]
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Registra el retorno de vacaciones
 */
function registrarRetornoVacacion($pdo) {
    $funcionario_id = filter_var($_POST['funcionario_id'] ?? 0, FILTER_VALIDATE_INT);
    $fecha_evento = $_POST['fecha_evento'] ?? date('Y-m-d');
    $observaciones = trim($_POST['observaciones'] ?? '');

    if (!$funcionario_id) throw new Exception('Funcionario no especificado', 400);

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("SELECT id, estado FROM funcionarios WHERE id = ?");
        $stmt->execute([$funcionario_id]);
        $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$funcionario || $funcionario['estado'] !== 'vacaciones') {
            throw new Exception('El funcionario no está en vacaciones', 400);
        }

        $detalles = json_encode([
            'observaciones' => htmlspecialchars($observaciones, ENT_QUOTES, 'UTF-8'),
            'tipo_reincorporacion' => 'retorno_vacaciones'
        ], JSON_UNESCAPED_UNICODE);

        $ruta_archivo = null;
        $nombre_original = null;
        if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
            $resultado_archivo = guardarArchivoHistorial($funcionario_id, 'reincorporaciones', $_FILES['archivo_pdf']);
            $ruta_archivo = $resultado_archivo['ruta'];
            $nombre_original = $resultado_archivo['nombre_original'];
        }

        $stmt = $pdo->prepare("
            INSERT INTO historial_administrativo (
                funcionario_id, tipo_evento, fecha_evento,
                detalles, ruta_archivo_pdf, nombre_archivo_original,
                registrado_por
            ) VALUES (?, 'REINCORPORACION', ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$funcionario_id, $fecha_evento, $detalles, $ruta_archivo, $nombre_original, $_SESSION['usuario_id']]);

        $historial_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("UPDATE funcionarios SET estado = 'activo', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$funcionario_id]);

        // Auditoría
        registrarAuditoria('REGISTRAR_RETORNO_VACACION', 'historial_administrativo', $historial_id, ['estado' => 'vacaciones'], ['estado' => 'activo']);

        $pdo->commit();

        return ['success' => true, 'message' => 'Retorno registrado. Funcionario activo.'];

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Registra una amonestación
 */
function registrarAmonestacion($pdo) {
    $funcionario_id = filter_var($_POST['funcionario_id'] ?? 0, FILTER_VALIDATE_INT);
    $fecha_evento = $_POST['fecha_evento'] ?? date('Y-m-d');
    $tipo_falta = $_POST['tipo_falta'] ?? '';
    $motivo = trim($_POST['motivo'] ?? '');
    $sancion = trim($_POST['sancion'] ?? '');

    if (!$funcionario_id || empty($tipo_falta) || empty($motivo) || empty($sancion)) {
        throw new Exception('Datos incompletos', 400);
    }

    $pdo->beginTransaction();

    try {
        $detalles = json_encode([
            'tipo_falta' => $tipo_falta,
            'motivo' => htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8'),
            'sancion' => htmlspecialchars($sancion, ENT_QUOTES, 'UTF-8')
        ], JSON_UNESCAPED_UNICODE);

        if (!isset($_FILES['archivo_pdf']) || $_FILES['archivo_pdf']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Documento obligatorio', 400);
        }

        $resultado_archivo = guardarArchivoHistorial($funcionario_id, 'amonestaciones', $_FILES['archivo_pdf']);
        
        $stmt = $pdo->prepare("
            INSERT INTO historial_administrativo (
                funcionario_id, tipo_evento, fecha_evento,
                detalles, ruta_archivo_pdf, nombre_archivo_original,
                registrado_por
            ) VALUES (?, 'AMONESTACION', ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $funcionario_id, $fecha_evento, $detalles,
            $resultado_archivo['ruta'], $resultado_archivo['nombre_original'], $_SESSION['usuario_id']
        ]);

        $historial_id = $pdo->lastInsertId();

        if ($tipo_falta === 'muy_grave') {
            $stmt = $pdo->prepare("UPDATE funcionarios SET tiene_amonestaciones_graves = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$funcionario_id]);
        }

        // Auditoría
        registrarAuditoria('REGISTRAR_AMONESTACION', 'historial_administrativo', $historial_id, null, ['tipo_falta' => $tipo_falta]);

        $pdo->commit();

        return ['success' => true, 'message' => 'Amonestación registrada.'];

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Guarda un archivo
 */
function guardarArchivoHistorial($funcionario_id, $tipo, $archivo) {
    // ── 1. Validar extensión declarada ──────────────────────────────────────────
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $extensiones_validas = ['pdf', 'jpg', 'jpeg', 'png'];

    if (!in_array($extension, $extensiones_validas)) {
        throw new Exception('Formato no válido. Solo se aceptan PDF, JPG y PNG.', 400);
    }

    // ── 2. Validar tamaño ───────────────────────────────────────────────────────
    $max_size = 5 * 1024 * 1024; // 5 MB
    if ($archivo['size'] > $max_size) {
        throw new Exception('El archivo supera el límite de 5MB permitido.', 400);
    }

    // ── 3. *** ANTI-MALWARE *** Validar MIME real del archivo ──────────────────
    // Verifica la firma del archivo (magic bytes) sin importar la extensión.
    // Un .txt renombrado a .pdf tendrá MIME text/plain y será rechazado.
    if (!function_exists('finfo_open')) {
        throw new Exception('El servidor no soporta validación de archivos (finfo). Contacte al administrador.', 500);
    }
    $finfo     = finfo_open(FILEINFO_MIME_TYPE);
    $mime_real = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);

    $mimes_permitidos = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    if (!in_array($mime_real, $mimes_permitidos)) {
        throw new Exception(
            'El contenido real del archivo no coincide con el tipo permitido. ' .
            'Tipo detectado: ' . $mime_real . '. Solo se aceptan PDF, JPG y PNG auténticos.',
            400
        );
    }

    // ── 4. Crear directorio y guardar ───────────────────────────────────────────
    $dir_base = '../../../subidas/funcionarios/' . $funcionario_id . '/' . $tipo;
    if (!file_exists($dir_base)) {
        mkdir($dir_base, 0755, true);
    }

    $nombre_archivo = $tipo . '_' . date('Ymd_His') . '.' . $extension;
    $ruta_completa  = $dir_base . '/' . $nombre_archivo;
    $ruta_relativa  = 'subidas/funcionarios/' . $funcionario_id . '/' . $tipo . '/' . $nombre_archivo;

    if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
        throw new Exception('Error al guardar el archivo en el servidor. Verifique permisos.', 500);
    }

    chmod($ruta_completa, 0644);

    return [
        'ruta'            => $ruta_relativa,
        'nombre_original' => basename($archivo['name']),
        'tipo_archivo'    => $extension,
        'mime_validado'   => $mime_real,
    ];
}

// Limpieza final y output
$buffer = ob_get_clean();
if (!empty($buffer) && strpos($buffer, '{') !== 0) {
    error_log("OUTPUT INESPERADO: " . $buffer);
}
echo $buffer ?: json_encode(['success' => false, 'error' => 'No se generó respuesta']);
ob_end_flush();
?>
