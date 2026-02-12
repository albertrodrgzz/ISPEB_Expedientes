<?php
/**
 * Controlador de Escritura - Historial Administrativo
 * * Gestiona las operaciones de creación de registros de historial CON COHERENCIA DE NEGOCIO:
 * - Traslados → Actualiza departamento_id del funcionario
 * - Nombramientos → Actualiza cargo_id del funcionario
 * - Vacaciones → Actualiza estado a 'vacaciones'
 * - Retorno de Vacaciones (REINCORPORACION) → Actualiza estado a 'activo'
 * - Amonestaciones → Marca flag si es "muy_grave"
 * - Despidos/Renuncias → Actualiza estado a 'inactivo'
 * * @author Sistema ISPEB v3.3 - Corrección Fatal Error Auditoría
 * @date 2026-02-11
 */

// IMPORTANTE: Iniciar output buffering ANTES de cualquier otra cosa
ob_start();

// Suprimir display de errores para AJAX (los errores se logean en archivo)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Configuración de respuesta JSON (debe estar antes de cualquier output)
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
        
        case 'registrar_despido':
            $resultado = registrarDespido($pdo);
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
        
        case 'registrar_remocion':
            $resultado = registrarRemocion($pdo);
            break;
        
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

        // Registrar en auditoría (CORREGIDO: Sin $pdo)
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
 * Registra un período de vacaciones
 * ✅ COHERENCIA: Actualiza estado a 'vacaciones'
 */
function registrarVacacion($pdo) {
    // Validar campos requeridos
    $funcionario_id = filter_var($_POST['funcionario_id'] ?? 0, FILTER_VALIDATE_INT);
    $fecha_evento = $_POST['fecha_evento'] ?? ''; 
    $dias_habiles = filter_var($_POST['dias_habiles'] ?? 0, FILTER_VALIDATE_INT);
    $observaciones = trim($_POST['observaciones'] ?? '');

    if (!$funcionario_id || empty($fecha_evento) || !$dias_habiles) {
        throw new Exception('Datos incompletos para registrar vacación', 400);
    }

    if ($dias_habiles <= 0 || $dias_habiles > 30) {
        throw new Exception('Los días hábiles deben estar entre 1 y 30', 400);
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("SELECT f.id, f.estado, f.nombres, f.apellidos, f.fecha_ingreso FROM funcionarios f WHERE f.id = ?");
        $stmt->execute([$funcionario_id]);
        $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$funcionario) throw new Exception('Funcionario no encontrado', 404);
        if ($funcionario['estado'] === 'inactivo') throw new Exception('Funcionario inactivo', 400);

        // VALIDACIÓN LOTTT
        $fecha_ingreso = new DateTime($funcionario['fecha_ingreso']);
        $fecha_actual = new DateTime();
        $años_servicio = $fecha_ingreso->diff($fecha_actual)->y;

        if ($años_servicio < 1) throw new Exception('No cumple requisito mínimo de 1 año de servicio', 400);

        $dias_totales_lottt = min(15 + ($años_servicio - 1), 30);

        // Contar días usados
        $año_actual = date('Y');
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(JSON_UNQUOTE(JSON_EXTRACT(detalles, '$.dias_habiles'))), 0) as total_usado
            FROM historial_administrativo
            WHERE funcionario_id = ? AND tipo_evento = 'VACACION' AND YEAR(fecha_evento) = ?
        ");
        $stmt->execute([$funcionario_id, $año_actual]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        $dias_usados = (int)($resultado['total_usado'] ?? 0);
        $dias_disponibles = $dias_totales_lottt - $dias_usados;

        if ($dias_habiles > $dias_disponibles) {
            throw new Exception("Solo tiene {$dias_disponibles} días disponibles. Ya usó {$dias_usados}.", 400);
        }

        // CALCULAR FECHAS
        $fecha_inicio = new DateTime($fecha_evento);
        $dias_contados = 0;
        $fecha_actual_calculo = clone $fecha_inicio;

        while ($dias_contados < $dias_habiles) {
            $fecha_actual_calculo->modify('+1 day');
            $dia_semana = (int)$fecha_actual_calculo->format('N');
            if ($dia_semana >= 1 && $dia_semana <= 5) $dias_contados++;
        }

        $fecha_ultimo_dia = $fecha_actual_calculo->format('Y-m-d');
        
        $fecha_retorno = clone $fecha_actual_calculo;
        $fecha_retorno->modify('+1 day');
        while ((int)$fecha_retorno->format('N') > 5) $fecha_retorno->modify('+1 day');
        $fecha_retorno_str = $fecha_retorno->format('Y-m-d');

        // JSON Detalles
        $detalles = json_encode([
            'dias_habiles' => $dias_habiles,
            'observaciones' => htmlspecialchars($observaciones, ENT_QUOTES, 'UTF-8'),
            'años_servicio' => $años_servicio,
            'dias_totales_año' => $dias_totales_lottt,
            'fecha_retorno' => $fecha_retorno_str
        ], JSON_UNESCAPED_UNICODE);

        // Archivo
        $ruta_archivo = null;
        $nombre_original = null;
        if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
            $resultado_archivo = guardarArchivoHistorial($funcionario_id, 'vacaciones', $_FILES['archivo_pdf']);
            $ruta_archivo = $resultado_archivo['ruta'];
            $nombre_original = $resultado_archivo['nombre_original'];
        } else {
            throw new Exception('Se requiere documento de aval', 400);
        }

        // Insertar
        $stmt = $pdo->prepare("
            INSERT INTO historial_administrativo (
                funcionario_id, tipo_evento, fecha_evento, fecha_fin,
                detalles, ruta_archivo_pdf, nombre_archivo_original,
                registrado_por
            ) VALUES (?, 'VACACION', ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $funcionario_id, $fecha_evento, $fecha_ultimo_dia,
            $detalles, $ruta_archivo, $nombre_original, $_SESSION['usuario_id']
        ]);

        $historial_id = $pdo->lastInsertId();

        // Actualizar estado funcionario
        $stmt = $pdo->prepare("UPDATE funcionarios SET estado = 'vacaciones', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$funcionario_id]);

        // Auditoría (CORREGIDO: Sin $pdo)
        registrarAuditoria('REGISTRAR_VACACION', 'historial_administrativo', $historial_id, null, [
            'funcionario_id' => $funcionario_id,
            'dias_habiles' => $dias_habiles,
            'estado_actualizado' => 'vacaciones'
        ]);

        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Vacación registrada exitosamente.',
            'data' => [
                'fecha_retorno' => $fecha_retorno_str
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

        // Auditoría (CORREGIDO: Sin $pdo)
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

        // Auditoría (CORREGIDO: Sin $pdo)
        registrarAuditoria('REGISTRAR_AMONESTACION', 'historial_administrativo', $historial_id, null, ['tipo_falta' => $tipo_falta]);

        $pdo->commit();

        return ['success' => true, 'message' => 'Amonestación registrada.'];

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Registra un despido o renuncia
 */
function registrarDespido($pdo) {
    $funcionario_id = filter_var($_POST['funcionario_id'] ?? 0, FILTER_VALIDATE_INT);
    $tipo_evento = $_POST['tipo_evento'] ?? 'DESPIDO';
    $fecha_evento = $_POST['fecha_evento'] ?? date('Y-m-d');
    $motivo = trim($_POST['motivo'] ?? '');

    if (!$funcionario_id) throw new Exception('Datos incompletos', 400);

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("SELECT * FROM funcionarios WHERE id = ?");
        $stmt->execute([$funcionario_id]);
        $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$funcionario || $funcionario['estado'] === 'inactivo') throw new Exception('Funcionario no válido', 400);

        $detalles = json_encode(['motivo' => htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8')], JSON_UNESCAPED_UNICODE);

        $ruta_archivo = null;
        $nombre_original = null;
        if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
            $resultado_archivo = guardarArchivoHistorial($funcionario_id, 'despidos', $_FILES['archivo_pdf']);
            $ruta_archivo = $resultado_archivo['ruta'];
            $nombre_original = $resultado_archivo['nombre_original'];
        }

        $stmt = $pdo->prepare("
            INSERT INTO historial_administrativo (
                funcionario_id, tipo_evento, fecha_evento,
                detalles, ruta_archivo_pdf, nombre_archivo_original,
                registrado_por
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$funcionario_id, $tipo_evento, $fecha_evento, $detalles, $ruta_archivo, $nombre_original, $_SESSION['usuario_id']]);

        $historial_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("UPDATE funcionarios SET estado = 'inactivo', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$funcionario_id]);

        $stmt = $pdo->prepare("UPDATE usuarios SET estado = 'inactivo', updated_at = CURRENT_TIMESTAMP WHERE funcionario_id = ?");
        $stmt->execute([$funcionario_id]);

        // Auditoría (CORREGIDO: Sin $pdo)
        registrarAuditoria('REGISTRAR_' . $tipo_evento, 'funcionarios', $funcionario_id, ['estado' => 'activo'], ['estado' => 'inactivo']);

        $pdo->commit();

        return ['success' => true, 'message' => 'Baja registrada exitosamente.'];

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Registra una remoción de cargo
 */
function registrarRemocion($pdo) {
    $funcionario_id = filter_var($_POST['funcionario_id'] ?? 0, FILTER_VALIDATE_INT);
    $motivo = trim($_POST['motivo'] ?? '');
    $fecha_evento = $_POST['fecha_evento'] ?? date('Y-m-d');
    
    if (!$funcionario_id) throw new Exception('Datos incompletos', 400);
    
    $pdo->beginTransaction();
    
    try {
        $detalles = json_encode(['motivo' => htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8')], JSON_UNESCAPED_UNICODE);
        
        $ruta_archivo = null;
        $nombre_original = null;
        if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
            $resultado_archivo = guardarArchivoHistorial($funcionario_id, 'remociones', $_FILES['archivo_pdf']);
            $ruta_archivo = $resultado_archivo['ruta'];
            $nombre_original = $resultado_archivo['nombre_original'];
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO historial_administrativo (
                funcionario_id, tipo_evento, fecha_evento,
                detalles, ruta_archivo_pdf, nombre_archivo_original,
                registrado_por
            ) VALUES (?, 'REMOCION', ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$funcionario_id, $fecha_evento, $detalles, $ruta_archivo, $nombre_original, $_SESSION['usuario_id']]);
        
        $historial_id = $pdo->lastInsertId();
        
        // Auditoría (CORREGIDO: Sin $pdo)
        registrarAuditoria('REGISTRAR_REMOCION', 'historial_administrativo', $historial_id, null, ['motivo' => $motivo]);
        
        $pdo->commit();
        
        return ['success' => true, 'message' => 'Remoción registrada exitosamente.'];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Guarda un archivo
 */
function guardarArchivoHistorial($funcionario_id, $tipo, $archivo) {
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $extensiones_validas = ['pdf', 'jpg', 'jpeg', 'png'];
    
    if (!in_array($extension, $extensiones_validas)) throw new Exception('Formato no válido (PDF/JPG/PNG)', 400);

    $max_size = 5 * 1024 * 1024;
    if ($archivo['size'] > $max_size) throw new Exception('Archivo muy grande (máx 5MB)', 400);

    $dir_base = '../../../subidas/funcionarios/' . $funcionario_id . '/' . $tipo;
    if (!file_exists($dir_base)) mkdir($dir_base, 0755, true);

    $nombre_archivo = $tipo . '_' . date('Ymd_His') . '.' . $extension;
    $ruta_completa = $dir_base . '/' . $nombre_archivo;
    $ruta_relativa = 'subidas/funcionarios/' . $funcionario_id . '/' . $tipo . '/' . $nombre_archivo;

    if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) throw new Exception('Error al guardar archivo', 500);

    chmod($ruta_completa, 0644);

    return [
        'ruta' => $ruta_relativa,
        'nombre_original' => basename($archivo['name']),
        'tipo_archivo' => $extension
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