<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/vendor/autoload.php'; // go up from config/ to project root

use Dotenv\Dotenv;

// Load .env from project root
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Define BASE_URL outside the class
// define("BASE_URL", "https://mediumvioletred-pelican-783174.hostingersite.com/");
if (!defined('BASE_URL')) define("BASE_URL", $_ENV['SITE'] ?? 'https://printmont.com/');

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
        $this->conn = null;
        $attempts = [
            ['host' => $this->host, 'username' => $this->username, 'password' => $this->password, 'db_name' => $this->db_name],
            ['host' => 'localhost', 'username' => $this->username, 'password' => $this->password, 'db_name' => $this->db_name],
            ['host' => '127.0.0.1', 'username' => 'root', 'password' => '', 'db_name' => $this->db_name],
            ['host' => 'localhost', 'username' => 'root', 'password' => '', 'db_name' => $this->db_name],
        ];

        $last_error = null;

        foreach ($attempts as $attempt) {
            try {
                $conn = new mysqli($attempt['host'], $attempt['username'], $attempt['password'], $attempt['db_name']);
                if ($conn->connect_error) {
                    $last_error = "Connection failed: " . $conn->connect_error;
                    continue;
                }

                $conn->set_charset('utf8');
                $this->conn = $conn;
                return $this->conn;
            } catch (Exception $e) {
                $last_error = $e->getMessage();
            }
        }

        throw new Exception("Database connection failed. Please verify MySQL is running and the database credentials are correct. Last error: " . $last_error);
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