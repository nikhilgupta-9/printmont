<?php
class BaseModel {
    protected $db;
    protected $table;

    public function __construct($db, $table) {
        $this->db = $db;
        $this->table = $table;
    }

    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY created_at DESC";
        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function create($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $query = "INSERT INTO $this->table ($columns) VALUES ($placeholders)";
        $stmt = $this->db->prepare($query);
        
        $types = str_repeat('s', count($data));
        $stmt->bind_param($types, ...array_values($data));
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function update($id, $data) {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "$column = ?";
        }
        $setClause = implode(', ', $setParts);
        
        $query = "UPDATE $this->table SET $setClause WHERE id = ?";
        $stmt = $this->db->prepare($query);
        
        $types = str_repeat('s', count($data)) . 'i';
        $params = array_values($data);
        $params[] = $id;
        
        $stmt->bind_param($types, ...$params);
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM $this->table WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
?>