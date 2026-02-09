<?php
/**
 * Controlador de Escritura - Historial Administrativo
 * 
 * Gestiona las operaciones de creación de registros de historial:
 * - Traslados
 * - Despidos/Renuncias
 * - Vacaciones
 * - Amonestaciones
 * 
 * @author Sistema ISPEB v3.1
 * @date 2026-02-03
 */

// Seguridad y configuración
require_once '../../../config/sesiones.php';
require_once '../../../config/database.php';

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

// Configuración de respuesta JSON
header('Content-Type: application/json; charset=utf-8');

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
            JOIN departamentos d ON f.departamento_id = d.id
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
            'departamento_origen' => $funcionario['departamento_actual'],
            'departamento_destino' => $dept_destino['nombre'],
            'motivo' => htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8')
        ], JSON_UNESCAPED_UNICODE);

        // Manejar archivo PDF si existe
        $ruta_archivo = null;
        $nombre_original = null;

        if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
            $resultado_archivo = guardarArchivoPDF($funcionario_id, 'traslados', $_FILES['archivo_pdf']);
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

        // Actualizar departamento del funcionario
        $stmt = $pdo->prepare("
            UPDATE funcionarios 
            SET departamento_id = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$departamento_destino_id, $funcionario_id]);

        // Registrar en auditoría
        registrarAuditoria($pdo, 'REGISTRAR_TRASLADO', 'historial_administrativo', $historial_id, null, [
            'funcionario_id' => $funcionario_id,
            'departamento_origen' => $funcionario['departamento_actual'],
            'departamento_destino' => $dept_destino['nombre'],
            'motivo' => $motivo
        ]);

        // Confirmar transacción
        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Traslado registrado exitosamente',
            'data' => [
                'historial_id' => $historial_id,
                'departamento_destino' => $dept_destino['nombre']
            ]
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Registra un despido o renuncia
 */
function registrarDespido($pdo) {
    // Validar campos requeridos
    $funcionario_id = filter_var($_POST['funcionario_id'] ?? 0, FILTER_VALIDATE_INT);
    $tipo_evento = $_POST['tipo_evento'] ?? 'DESPIDO';
    $fecha_evento = $_POST['fecha_evento'] ?? date('Y-m-d');
    $motivo = trim($_POST['motivo'] ?? '');

    if (!$funcionario_id) {
        throw new Exception('Funcionario no especificado', 400);
    }

    if (!in_array($tipo_evento, ['DESPIDO', 'RENUNCIA'])) {
        throw new Exception('Tipo de evento no válido. Debe ser DESPIDO o RENUNCIA', 400);
    }

    if (empty($motivo)) {
        throw new Exception('Debe especificar el motivo del ' . strtolower($tipo_evento), 400);
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    try {
        // Obtener datos actuales del funcionario
        $stmt = $pdo->prepare("
            SELECT f.*, c.nombre_cargo, d.nombre as departamento
            FROM funcionarios f
            JOIN cargos c ON f.cargo_id = c.id
            JOIN departamentos d ON f.departamento_id = d.id
            WHERE f.id = ?
        ");
        $stmt->execute([$funcionario_id]);
        $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$funcionario) {
            throw new Exception('Funcionario no encontrado', 404);
        }

        if ($funcionario['estado'] === 'inactivo') {
            throw new Exception('El funcionario ya está inactivo', 400);
        }

        // Preparar JSON con detalles
        $detalles = json_encode([
            'motivo' => htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8'),
            'cargo_al_retiro' => $funcionario['nombre_cargo'],
            'departamento_al_retiro' => $funcionario['departamento'],
            'antiguedad_anos' => (int) date_diff(
                date_create($funcionario['fecha_ingreso']), 
                date_create($fecha_evento)
            )->format('%y')
        ], JSON_UNESCAPED_UNICODE);

        // Manejar archivo PDF si existe
        $ruta_archivo = null;
        $nombre_original = null;

        if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
            $resultado_archivo = guardarArchivoPDF($funcionario_id, 'despidos', $_FILES['archivo_pdf']);
            $ruta_archivo = $resultado_archivo['ruta'];
            $nombre_original = $resultado_archivo['nombre_original'];
        }

        // Insertar en historial_administrativo
        $stmt = $pdo->prepare("
            INSERT INTO historial_administrativo (
                funcionario_id, tipo_evento, fecha_evento,
                detalles, ruta_archivo_pdf, nombre_archivo_original,
                registrado_por
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $funcionario_id,
            $tipo_evento,
            $fecha_evento,
            $detalles,
            $ruta_archivo,
            $nombre_original,
            $_SESSION['usuario_id']
        ]);

        $historial_id = $pdo->lastInsertId();

        // CRÍTICO: Actualizar estado del funcionario a inactivo
        $stmt = $pdo->prepare("
            UPDATE funcionarios 
            SET estado = 'inactivo', updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$funcionario_id]);

        // CRÍTICO: Desactivar usuario asociado
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET estado = 'inactivo', updated_at = CURRENT_TIMESTAMP
            WHERE funcionario_id = ?
        ");
        $stmt->execute([$funcionario_id]);

        // Registrar en auditoría
        registrarAuditoria($pdo, 'REGISTRAR_' . $tipo_evento, 'funcionarios', $funcionario_id, [
            'estado' => $funcionario['estado']
        ], [
            'estado' => 'inactivo',
            'motivo' => $motivo
        ]);

        // Confirmar transacción
        $pdo->commit();

        return [
            'success' => true,
            'message' => ucfirst(strtolower($tipo_evento)) . ' registrado exitosamente. El funcionario y su usuario han sido desactivados.',
            'data' => [
                'historial_id' => $historial_id,
                'funcionario_inactivo' => true,
                'usuario_inactivo' => true
            ]
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Registra un período de vacaciones
 */
function registrarVacacion($pdo) {
    // Validar campos requeridos
    $funcionario_id = filter_var($_POST['funcionario_id'] ?? 0, FILTER_VALIDATE_INT);
    $fecha_evento = $_POST['fecha_evento'] ?? ''; // Fecha de inicio
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    $observaciones = trim($_POST['observaciones'] ?? '');

    if (!$funcionario_id || empty($fecha_evento) || empty($fecha_fin)) {
        throw new Exception('Datos incompletos para registrar vacación', 400);
    }

    // Validar que fecha_fin sea posterior a fecha_evento
    if (strtotime($fecha_fin) <= strtotime($fecha_evento)) {
        throw new Exception('La fecha de finalización debe ser posterior a la fecha de inicio', 400);
    }

    // Calcular días hábiles (aproximado: días totales - domingos estimados)
    $dias_totales = (strtotime($fecha_fin) - strtotime($fecha_evento)) / 86400;
    $dias_habiles = floor($dias_totales * 5/7); // Estimado

    // Iniciar transacción
    $pdo->beginTransaction();

    try {
        // Verificar que el funcionario existe y está activo
        $stmt = $pdo->prepare("SELECT id, estado FROM funcionarios WHERE id = ?");
        $stmt->execute([$funcionario_id]);
        $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$funcionario) {
            throw new Exception('Funcionario no encontrado', 404);
        }

        if ($funcionario['estado'] === 'inactivo') {
            throw new Exception('No se pueden registrar vacaciones para un funcionario inactivo', 400);
        }

        // Preparar JSON con detalles
        $detalles = json_encode([
            'dias_habiles' => $dias_habiles,
            'observaciones' => htmlspecialchars($observaciones, ENT_QUOTES, 'UTF-8')
        ], JSON_UNESCAPED_UNICODE);

        // Manejar archivo PDF si existe
        $ruta_archivo = null;
        $nombre_original = null;

        if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
            $resultado_archivo = guardarArchivoPDF($funcionario_id, 'vacaciones', $_FILES['archivo_pdf']);
            $ruta_archivo = $resultado_archivo['ruta'];
            $nombre_original = $resultado_archivo['nombre_original'];
        }

        // Insertar en historial_administrativo
        $stmt = $pdo->prepare("
            INSERT INTO historial_administrativo (
                funcionario_id, tipo_evento, fecha_evento, fecha_fin,
                detalles, ruta_archivo_pdf, nombre_archivo_original,
                registrado_por
            ) VALUES (?, 'VACACION', ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $funcionario_id,
            $fecha_evento,
            $fecha_fin,
            $detalles,
            $ruta_archivo,
            $nombre_original,
            $_SESSION['usuario_id']
        ]);

        $historial_id = $pdo->lastInsertId();

        // CRÍTICO: Actualizar estado del funcionario a 'vacaciones'
        $stmt = $pdo->prepare("
            UPDATE funcionarios 
            SET estado = 'vacaciones', updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$funcionario_id]);

        // Registrar en auditoría
        registrarAuditoria($pdo, 'REGISTRAR_VACACION', 'historial_administrativo', $historial_id, null, [
            'funcionario_id' => $funcionario_id,
            'fecha_inicio' => $fecha_evento,
            'fecha_fin' => $fecha_fin,
            'dias_habiles' => $dias_habiles,
            'estado_actualizado' => 'vacaciones'
        ]);

        // Confirmar transacción
        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Vacación registrada exitosamente. El estado del funcionario se actualizó a "vacaciones".',
            'data' => [
                'historial_id' => $historial_id,
                'dias_habiles' => $dias_habiles,
                'fecha_inicio' => $fecha_evento,
                'fecha_fin' => $fecha_fin,
                'nuevo_estado' => 'vacaciones'
            ]
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Registra una amonestación
 */
function registrarAmonestacion($pdo) {
    // Validar campos requeridos
    $funcionario_id = filter_var($_POST['funcionario_id'] ?? 0, FILTER_VALIDATE_INT);
    $fecha_evento = $_POST['fecha_evento'] ?? date('Y-m-d');
    $tipo_falta = $_POST['tipo_falta'] ?? '';
    $motivo = trim($_POST['motivo'] ?? '');
    $sancion = trim($_POST['sancion'] ?? '');

    if (!$funcionario_id || empty($tipo_falta) || empty($motivo) || empty($sancion)) {
        throw new Exception('Datos incompletos para registrar amonestación', 400);
    }

    $tipos_validos = ['leve', 'grave', 'muy_grave'];
    if (!in_array($tipo_falta, $tipos_validos)) {
        throw new Exception('Tipo de falta no válido. Debe ser: leve, grave o muy_grave', 400);
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    try {
        // Verificar que el funcionario existe y está activo
        $stmt = $pdo->prepare("SELECT id, estado FROM funcionarios WHERE id = ?");
        $stmt->execute([$funcionario_id]);
        $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$funcionario) {
            throw new Exception('Funcionario no encontrado', 404);
        }

        if ($funcionario['estado'] === 'inactivo') {
            throw new Exception('No se pueden registrar amonestaciones para un funcionario inactivo', 400);
        }

        // Preparar JSON con detalles
        $detalles = json_encode([
            'tipo_falta' => $tipo_falta,
            'motivo' => htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8'),
            'sancion' => htmlspecialchars($sancion, ENT_QUOTES, 'UTF-8')
        ], JSON_UNESCAPED_UNICODE);

        // Manejar archivo PDF (obligatorio para amonestaciones)
        $ruta_archivo = null;
        $nombre_original = null;

        if (!isset($_FILES['archivo_pdf']) || $_FILES['archivo_pdf']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('El documento PDF es obligatorio para las amonestaciones', 400);
        }

        $resultado_archivo = guardarArchivoPDF($funcionario_id, 'amonestaciones', $_FILES['archivo_pdf']);
        $ruta_archivo = $resultado_archivo['ruta'];
        $nombre_original = $resultado_archivo['nombre_original'];

        // Insertar en historial_administrativo
        $stmt = $pdo->prepare("
            INSERT INTO historial_administrativo (
                funcionario_id, tipo_evento, fecha_evento,
                detalles, ruta_archivo_pdf, nombre_archivo_original,
                registrado_por
            ) VALUES (?, 'AMONESTACION', ?, ?, ?, ?, ?)
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
        registrarAuditoria($pdo, 'REGISTRAR_AMONESTACION', 'historial_administrativo', $historial_id, null, [
            'funcionario_id' => $funcionario_id,
            'tipo_falta' => $tipo_falta,
            'motivo' => $motivo
        ]);

        // Confirmar transacción
        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Amonestación registrada exitosamente',
            'data' => [
                'historial_id' => $historial_id,
                'tipo_falta' => $tipo_falta
            ]
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Guarda un archivo PDF en el directorio correspondiente
 */
function guardarArchivoPDF($funcionario_id, $tipo, $archivo) {
    // Validar que sea un PDF
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if ($extension !== 'pdf') {
        throw new Exception('Solo se permiten archivos PDF', 400);
    }

    // Validar MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);

    if ($mime !== 'application/pdf') {
        throw new Exception('El archivo no es un PDF válido', 400);
    }

    // Validar tamaño (máximo 5MB)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($archivo['size'] > $max_size) {
        throw new Exception('El archivo excede el tamaño máximo permitido de 5MB', 400);
    }

    // Crear directorios si no existen
    $dir_base = '../../../subidas/funcionarios/' . $funcionario_id . '/' . $tipo;
    if (!file_exists($dir_base)) {
        mkdir($dir_base, 0755, true);
    }

    // Generar nombre único para el archivo
    $nombre_archivo = $tipo . '_' . date('Ymd_His') . '.pdf';
    $ruta_completa = $dir_base . '/' . $nombre_archivo;
    $ruta_relativa = 'subidas/funcionarios/' . $funcionario_id . '/' . $tipo . '/' . $nombre_archivo;

    // Mover archivo
    if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
        throw new Exception('Error al guardar el archivo', 500);
    }

    // Establecer permisos
    chmod($ruta_completa, 0644);

    return [
        'ruta' => $ruta_relativa,
        'nombre_original' => basename($archivo['name'])
    ];
}

/**
 * Registra una acción en la tabla de auditoría
 */
function registrarAuditoria($pdo, $accion, $tabla, $registro_id, $datos_anteriores, $datos_nuevos) {
    $stmt = $pdo->prepare("
        INSERT INTO auditoria (
            usuario_id, accion, tabla_afectada, registro_id,
            datos_anteriores, datos_nuevos, ip_address, user_agent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $_SESSION['usuario_id'],
        $accion,
        $tabla,
        $registro_id,
        $datos_anteriores ? json_encode($datos_anteriores, JSON_UNESCAPED_UNICODE) : null,
        json_encode($datos_nuevos, JSON_UNESCAPED_UNICODE),
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

/**
 * Registra un nombramiento (cambio de cargo)
 */
function registrarNombramiento($pdo) {
    // Validar campos requeridos
    $funcionario_id = filter_var($_POST['funcionario_id'] ?? 0, FILTER_VALIDATE_INT);
    $nuevo_cargo_id = filter_var($_POST['nuevo_cargo_id'] ?? 0, FILTER_VALIDATE_INT);
    $fecha_evento = $_POST['fecha_evento'] ?? date('Y-m-d');
    
    if (!$funcionario_id || !$nuevo_cargo_id) {
        throw new Exception('Datos incompletos para registrar nombramiento', 400);
    }
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    try {
        // Obtener datos actuales del funcionario y cargo
        $stmt = $pdo->prepare("
            SELECT f.*, c.nombre_cargo as cargo_actual, d.nombre as departamento
            FROM funcionarios f
            JOIN cargos c ON f.cargo_id = c.id
            JOIN departamentos d ON f.departamento_id = d.id
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
        
        $cargo_anterior_id = $funcionario['cargo_id'];
        
        if ($cargo_anterior_id == $nuevo_cargo_id) {
            throw new Exception('El nuevo cargo es igual al cargo actual', 400);
        }
        
        // Obtener datos del nuevo cargo
        $stmt = $pdo->prepare("SELECT nombre_cargo, nivel_acceso FROM cargos WHERE id = ?");
        $stmt->execute([$nuevo_cargo_id]);
        $nuevo_cargo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$nuevo_cargo) {
            throw new Exception('Cargo no encontrado', 404);
        }
        
        // Preparar JSON con detalles
        $detalles = json_encode([
            'cargo' => $nuevo_cargo['nombre_cargo'],
            'cargo_anterior' => $funcionario['cargo_actual'],
            'departamento' => $funcionario['departamento'],
            'nivel_acceso' => $nuevo_cargo['nivel_acceso']
        ], JSON_UNESCAPED_UNICODE);
        
        // Manejar archivo PDF si existe
        $ruta_archivo = null;
        $nombre_original = null;
        
        if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
            $resultado_archivo = guardarArchivoPDF($funcionario_id, 'nombramientos', $_FILES['archivo_pdf']);
            $ruta_archivo = $resultado_archivo['ruta'];
            $nombre_original = $resultado_archivo['nombre_original'];
        }
        
        // Insertar en historial_administrativo
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
        
        // CRÍTICO: Actualizar cargo del funcionario
        $stmt = $pdo->prepare("
            UPDATE funcionarios 
            SET cargo_id = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$nuevo_cargo_id, $funcionario_id]);
        
        // Registrar en auditoría
        registrarAuditoria($pdo, 'REGISTRAR_NOMBRAMIENTO', 'historial_administrativo', $historial_id, [
            'cargo_anterior' => $funcionario['cargo_actual'],
            'cargo_id_anterior' => $cargo_anterior_id
        ], [
            'funcionario_id' => $funcionario_id,
            'nuevo_cargo' => $nuevo_cargo['nombre_cargo'],
            'nuevo_cargo_id' => $nuevo_cargo_id
        ]);
        
        // Confirmar transacción
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Nombramiento registrado exitosamente. El cargo del funcionario ha sido actualizado.',
            'data' => [
                'historial_id' => $historial_id,
                'cargo_anterior' => $funcionario['cargo_actual'],
                'cargo_nuevo' => $nuevo_cargo['nombre_cargo']
            ]
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Registra una remoción de cargo
 */
function registrarRemocion($pdo) {
    // Validar campos requeridos
    $funcionario_id = filter_var($_POST['funcionario_id'] ?? 0, FILTER_VALIDATE_INT);
    $motivo = trim($_POST['motivo'] ?? '');
    $fecha_evento = $_POST['fecha_evento'] ?? date('Y-m-d');
    $mantener_activo = filter_var($_POST['mantener_activo'] ?? true, FILTER_VALIDATE_BOOLEAN);
    
    if (!$funcionario_id || empty($motivo)) {
        throw new Exception('Datos incompletos para registrar remoción', 400);
    }
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    try {
        // Obtener datos actuales del funcionario
        $stmt = $pdo->prepare("
            SELECT f.*, c.nombre_cargo as cargo_actual, d.nombre as departamento
            FROM funcionarios f
            JOIN cargos c ON f.cargo_id = c.id
            JOIN departamentos d ON f.departamento_id = d.id
            WHERE f.id = ?
        ");
        $stmt->execute([$funcionario_id]);
        $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$funcionario) {
            throw new Exception('Funcionario no encontrado', 404);
        }
        
        if ($funcionario['estado'] === 'inactivo') {
            throw new Exception('El funcionario ya está inactivo', 400);
        }
        
        // Preparar JSON con detalles
        $detalles = json_encode([
            'motivo' => htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8'),
            'cargo_removido' => $funcionario['cargo_actual'],
            'departamento' => $funcionario['departamento'],
            'mantiene_activo' => $mantener_activo
        ], JSON_UNESCAPED_UNICODE);
        
        // Manejar archivo PDF si existe
        $ruta_archivo = null;
        $nombre_original = null;
        
        if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
            $resultado_archivo = guardarArchivoPDF($funcionario_id, 'remociones', $_FILES['archivo_pdf']);
            $ruta_archivo = $resultado_archivo['ruta'];
            $nombre_original = $resultado_archivo['nombre_original'];
        }
        
        // Insertar en historial_administrativo
        $stmt = $pdo->prepare("
            INSERT INTO historial_administrativo (
                funcionario_id, tipo_evento, fecha_evento,
                detalles, ruta_archivo_pdf, nombre_archivo_original,
                registrado_por
            ) VALUES (?, 'REMOCION', ?, ?, ?, ?, ?)
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
        registrarAuditoria($pdo, 'REGISTRAR_REMOCION', 'historial_administrativo', $historial_id, [
            'cargo_removido' => $funcionario['cargo_actual'],
            'estado_anterior' => $funcionario['estado']
        ], [
            'funcionario_id' => $funcionario_id,
            'motivo' => $motivo,
            'mantiene_activo' => $mantener_activo
        ]);
        
        // Confirmar transacción
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Remoción registrada exitosamente.',
            'data' => [
                'historial_id' => $historial_id,
                'cargo_removido' => $funcionario['cargo_actual'],
                'funcionario_mantiene_activo' => $mantener_activo
            ]
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
