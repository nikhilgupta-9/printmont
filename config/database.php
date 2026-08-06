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

if (!class_exists('PdoResultWrapper')) {
class PdoResultWrapper {
    private $rows;
    private $currentIndex = 0;
    public $num_rows;

    public function __construct(array $rows) {
        $this->rows = array_values($rows);
        $this->num_rows = count($this->rows);
    }

    public function fetch_assoc() {
        if ($this->currentIndex < $this->num_rows) {
            return $this->rows[$this->currentIndex++];
        }
        return null;
    }

    public function fetch_all($mode = MYSQLI_ASSOC) {
        return $this->rows;
    }
}
}

if (!class_exists('PdoStmtWrapper')) {
class PdoStmtWrapper {
    private $pdo;
    private $sql;
    private $params = [];
    private $resultRows = [];
    public $insert_id = 0;
    public $error = '';

    public function __construct($pdo, $sql) {
        $this->pdo = $pdo;
        $this->sql = $sql;
    }

    public function bind_param($types, ...$params) {
        $this->params = $params;
        return true;
    }

    public function execute() {
        try {
            $stmt = $this->pdo->prepare($this->sql);
            $success = $stmt->execute($this->params);
            $this->insert_id = (int)$this->pdo->lastInsertId();
            if ($stmt->columnCount() > 0) {
                $this->resultRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            return $success;
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function get_result() {
        return new PdoResultWrapper($this->resultRows);
    }

    public function close() {
        return true;
    }
}
}

if (!class_exists('PdoMysqliWrapper')) {
class PdoMysqliWrapper {
    public $pdo;
    public $connect_error = null;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function query($sql) {
        try {
            $stmt = $this->pdo->query($sql);
            if (!$stmt) return false;
            if ($stmt->columnCount() > 0) {
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                return new PdoResultWrapper($rows);
            }
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function prepare($sql) {
        return new PdoStmtWrapper($this->pdo, $sql);
    }

    public function real_escape_string($str) {
        if ($str === null) return '';
        $quoted = $this->pdo->quote($str);
        if (strlen($quoted) >= 2 && $quoted[0] === "'" && substr($quoted, -1) === "'") {
            return substr($quoted, 1, -1);
        }
        return addslashes($str);
    }

    public function set_charset($charset) {
        try {
            $this->pdo->exec("SET NAMES " . $charset);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function begin_transaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollback() {
        return $this->pdo->rollBack();
    }

    public function close() {
        $this->pdo = null;
    }
}
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
        mysqli_report(MYSQLI_REPORT_OFF);

        // 1. Try standard mysqli first
        foreach ($attempts as $index => $attempt) {
            $conn = @new mysqli($attempt['host'], $attempt['username'], $attempt['password'], $attempt['db_name']);
            if ($conn && !$conn->connect_error) {
                $conn->set_charset('utf8');
                $this->conn = $conn;
                return $this->conn;
            }
        }

        // 2. Try PDO fallback (which works on Hostinger)
        $dsns = [
            "mysql:host={$this->host};dbname={$this->db_name};charset=utf8",
            "mysql:host=127.0.0.1;dbname={$this->db_name};charset=utf8",
            "mysql:host=localhost;dbname={$this->db_name};charset=utf8",
            "mysql:unix_socket=/var/lib/mysql/mysql.sock;dbname={$this->db_name};charset=utf8",
            "mysql:unix_socket=/tmp/mysql.sock;dbname={$this->db_name};charset=utf8"
        ];

        foreach ($dsns as $dsn) {
            foreach ($passwords as $p) {
                try {
                    $pdo = new PDO($dsn, $this->username, $p, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);
                    $wrapper = new PdoMysqliWrapper($pdo);
                    $this->conn = $wrapper;
                    return $this->conn;
                } catch (Throwable $e) {
                    $last_error[] = "PDO ({$dsn}): " . $e->getMessage();
                }
            }
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