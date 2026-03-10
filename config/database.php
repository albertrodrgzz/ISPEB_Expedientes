<?php
/**
 * config/database.php — Conexión PDO Universal
 * ============================================================
 * XAMPP local:   Sin SSL, credenciales root/vacío por defecto
 * Render/Aiven:  Con SSL (ca.pem), credenciales via variables de entorno
 *
 * Variables de entorno para Render (Dashboard → Environment):
 *   DB_HOST  = mysql-xxxx.aivencloud.com
 *   DB_PORT  = 12345
 *   DB_NAME  = ispeb_expedientes
 *   DB_USER  = avnadmin
 *   DB_PASS  = tu_password_aiven
 *   APP_URL  = https://siged.onrender.com
 * ============================================================
 */

require_once __DIR__ . '/config.php';

// ── Credenciales ──────────────────────────────────────────────────────────────
// XAMPP: ninguna variable de entorno definida → usa valores por defecto locales
// Render: variables de entorno sobrescriben los valores por defecto
define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_PORT',    getenv('DB_PORT')    ?: '3306');
define('DB_NAME',    getenv('DB_NAME')    ?: 'ispeb_expedientes');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: '');
define('DB_CHARSET', 'utf8mb4');

// ── ¿Necesita SSL? Solo si el host NO es local ────────────────────────────────
// XAMPP siempre usa localhost/127.0.0.1 → SSL = false (no intenta cargar ca.pem)
// Aiven siempre usa host externo          → SSL = true  (carga ca.pem)
function necesitaSSL(): bool
{
    $localHosts = ['localhost', '127.0.0.1', '::1'];
    return !in_array(strtolower(DB_HOST), $localHosts, true);
}

/**
 * Clase Database — Singleton PDO
 */
class Database
{
    private static $instance = null;
    private $conn;

    private function __construct()
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            // SSL solo para Aiven (hosts externos — nunca para localhost)
            if (necesitaSSL()) {
                $caPem = __DIR__ . '/ca.pem';
                if (file_exists($caPem)) {
                    $options[PDO::MYSQL_ATTR_SSL_CA]                 = $caPem;
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                }
            }

            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);

        } catch (PDOException $e) {
            error_log('SIGED DB Error: ' . $e->getMessage());
            http_response_code(500);
            exit('Error de conexión a la base de datos. Contacte al administrador.');
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->conn;
    }

    private function __clone() {}

    public function __wakeup(): void
    {
        throw new Exception('No se puede deserializar un singleton.');
    }
}

/**
 * Helper global para obtener la conexión PDO
 */
function getDB(): PDO
{
    return Database::getInstance()->getConnection();
}

/**
 * Obtener IP del cliente (compatible con proxies y Render)
 */
function obtenerIP(): string
{
    if (!empty($_SERVER['HTTP_CLIENT_IP']))       return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    return $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
}
