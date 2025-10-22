<?php

class Database
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        if (!isset($_ENV['DB_HOST']) && file_exists(__DIR__ . '/../../.env')) {
            require_once __DIR__ . '/../../vendor/autoload.php';
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
            $dotenv->load();
        }

        $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost';
        $dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'gatherly';
        $username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'root';
        $password = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? '';

        try {
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            if ($host === 'localhost' && file_exists('/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock')) {
                $dsn = "mysql:unix_socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock;dbname=$dbname;charset=utf8mb4";
            }

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->connection = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
