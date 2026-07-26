<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

if (class_exists('Dotenv\Dotenv')) {
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
        $dotenv->safeLoad();
    } catch (\Throwable $t) {
        // Safe load if .env is missing on production
    }
}

// Define BASE_URL outside the class
if (!defined('BASE_URL')) define("BASE_URL", $_ENV['SITE'] ?? ($_SERVER['SITE'] ?? 'https://printmont.com/'));

if (!class_exists('Database')) {
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;

    public $conn;

    public function __construct()
    {
        $this->host     = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? (defined('DB_HOST') ? DB_HOST : 'localhost'));
        $this->db_name  = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? (defined('DB_NAME') ? DB_NAME : 'printmont_db'));
        $this->username = getenv('DB_USER') ?: (getenv('DB_USERNAME') ?: ($_ENV['DB_USERNAME'] ?? ($_ENV['DB_USER'] ?? (defined('DB_USER') ? DB_USER : 'root'))));
        $this->password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : ($_ENV['DB_PASSWORD'] ?? ($_ENV['DB_PASS'] ?? (defined('DB_PASS') ? DB_PASS : ''))));
    }

    public function getConnection() {
        if ($this->conn && !$this->conn->connect_error) {
            return $this->conn;
        }

        $attempts = [
            ['host' => $this->host, 'username' => $this->username, 'password' => $this->password, 'db_name' => $this->db_name],
            ['host' => 'localhost', 'username' => $this->username, 'password' => $this->password, 'db_name' => $this->db_name],
            ['host' => '127.0.0.1', 'username' => 'root', 'password' => '', 'db_name' => 'printmont_db'],
            ['host' => 'localhost', 'username' => 'root', 'password' => '', 'db_name' => 'printmont_db'],
        ];

        $last_error = null;

        foreach ($attempts as $attempt) {
            if (empty($attempt['host']) || empty($attempt['db_name'])) continue;
            try {
                $conn = @new mysqli($attempt['host'], $attempt['username'], $attempt['password'], $attempt['db_name']);
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

        throw new Exception("Database connection failed. Please verify MySQL is running and database credentials are correct. Last error: " . $last_error);
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