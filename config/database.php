<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/vendor/autoload.php'; // go up from config/ to project root

use Dotenv\Dotenv;

// Load .env from project root
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define("BASE_URL", $_ENV['SITE'] ?? ($protocol . $host . '/printmont/printmont-backend/'));
}

if (!class_exists('Database')) {
class Database {
    private $host;
        private $db_name;
        private $username;
        private $password;

        public $conn;

        public function __construct()
        {
            $this->host     = $_ENV['DB_HOST'] ?? 'localhost';
            $this->db_name  = $_ENV['DB_NAME'] ?? '';
            $this->username = $_ENV['DB_USERNAME'] ?? 'root';
            $this->password = $_ENV['DB_PASSWORD'] ?? '';
        }

    public function getConnection() {
        $rawPass = $this->password;
        $cleanPass = trim($rawPass, "\"' \t\n\r\0\x0B");
        $passwords = array_unique([$cleanPass, $rawPass]);
        $hosts = array_unique([$this->host, '127.0.0.1', 'localhost']);

        $attempts = [];
        foreach ($hosts as $h) {
            foreach ($passwords as $p) {
                $attempts[] = ['host' => $h, 'username' => $this->username, 'password' => $p, 'db_name' => $this->db_name];
            }
        }
        $attempts[] = ['host' => '127.0.0.1', 'username' => 'root', 'password' => '', 'db_name' => $this->db_name];
        $attempts[] = ['host' => 'localhost', 'username' => 'root', 'password' => '', 'db_name' => $this->db_name];

        $last_error = [];

        try {
            $pdo = new PDO("mysql:host=localhost;dbname={$this->db_name};charset=utf8", $this->username, $cleanPass);
            $last_error[] = "PDO(localhost) SUCCESS";
        } catch (Throwable $pe) {
            $last_error[] = "PDO(localhost): " . $pe->getMessage();
        }

        try {
            $pdo2 = new PDO("mysql:host=127.0.0.1;dbname={$this->db_name};charset=utf8", $this->username, $cleanPass);
            $last_error[] = "PDO(127.0.0.1) SUCCESS";
        } catch (Throwable $pe) {
            $last_error[] = "PDO(127.0.0.1): " . $pe->getMessage();
        }

        mysqli_report(MYSQLI_REPORT_OFF);

        foreach ($attempts as $index => $attempt) {
            $conn = @new mysqli($attempt['host'], $attempt['username'], $attempt['password'], $attempt['db_name']);
            if ($conn && !$conn->connect_error) {
                $conn->set_charset('utf8');
                $this->conn = $conn;
                return $this->conn;
            }
            $err = ($conn && $conn->connect_error) ? $conn->connect_error : mysqli_connect_error();
            $last_error[] = "Attempt {$index} ({$attempt['host']}@{$attempt['username']}): " . ($err ?: 'Unknown error');
        }

        throw new Exception("Database connection failed. Details: " . implode(" | ", $last_error));
    }

    public function execute($query, $params = []) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            $stmt->bind_param($types, ...$params);
        }
        
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function fetch($query, $params = []) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    }

    public function fetchAll($query, $params = []) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
        return $data;
    }

    public function insert($query, $params = []) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            $stmt->bind_param($types, ...$params);
        }
        
        $success = $stmt->execute();
        $insert_id = $stmt->insert_id;
        $stmt->close();
        return $insert_id;
    }

    public function beginTransaction() {
        $conn = $this->getConnection();
        return $conn->begin_transaction();
    }

    public function commit() {
        $conn = $this->getConnection();
        return $conn->commit();
    }

    public function rollback() {
        $conn = $this->getConnection();
        return $conn->rollback();
    }

    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
}
?>