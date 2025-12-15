<?php
require_once(__DIR__ . '/../config/database.php');

class PolicyModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /* ================================
       Get All Policies
       ================================ */
    public function getAllPolicies() {
        $query = "SELECT * FROM policies ORDER BY updated_at DESC";
        return $this->db->fetchAll($query);
    }

    /* ================================
       Get Policy By Key
       ================================ */
    public function getPolicyByKey($key) {
        $query = "SELECT * FROM policies WHERE policy_key = ?";
        return $this->db->fetch($query, [$key]);
    }

    /* ================================
       Create Policy
       ================================ */
    public function addPolicy($data) {
        $query = "INSERT INTO policies 
                  (policy_key, heading, description, points, status, updated_at)
                  VALUES (?, ?, ?, ?, ?, NOW())";

        $params = [
            $data['policy_key'],
            $data['heading'],
            $data['description'],
            $data['points'],
            $data['status']
        ];

        return $this->db->insert($query, $params);
    }

    /* ================================
       Update Policy by Key
       ================================ */
    public function updatePolicy($key, $data) {
        $query = "UPDATE policies SET 
                  heading = ?, 
                  description = ?, 
                  points = ?, 
                  status = ?, 
                  updated_at = NOW() 
                  WHERE policy_key = ?";

        $params = [
            $data['heading'],
            $data['description'],
            $data['points'],
            $data['status'],
            $key
        ];

        return $this->db->execute($query, $params);
    }

    /* ================================
       Delete Policy
       ================================ */
    public function deletePolicy($key) {
        $query = "DELETE FROM policies WHERE policy_key = ?";
        return $this->db->execute($query, [$key]);
    }
}

