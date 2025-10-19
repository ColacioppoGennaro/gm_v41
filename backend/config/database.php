<?php
/**
 * Database Configuration
 * Connessione MySQL per Netsons
 */

class Database {
    private $connection = null;
    private static $instance = null;

    private function __construct() {
        // Carica configurazione da .env
        $this->loadEnv();
        
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $dbname = getenv('DB_NAME') ?: 'ywrloefq_gm_v41';
        $username = getenv('DB_USER') ?: 'ywrloefq_gm_user';
        $password = getenv('DB_PASS') ?: '77453209**--Gm';
        $charset = getenv('DB_CHARSET') ?: 'utf8mb4';

        try {
            $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->connection = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'DB_CONNECTION_ERROR',
                    'message' => 'Database connection failed'
                ]
            ]);
            exit;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    private function loadEnv() {
        $envPath = dirname(__DIR__, 1) . '/.env';
        
        if (!file_exists($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Rimuovi virgolette
            $value = trim($value, '"\'');

            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage());
            throw $e;
        }
    }

    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}
