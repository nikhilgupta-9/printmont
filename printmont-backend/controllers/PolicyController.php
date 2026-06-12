<?php
require_once(__DIR__ . '/../models/PolicyModel.php');

class PolicyController
{
    private $policyModel;

    public function __construct()
    {
        $this->policyModel = new PolicyModel();
        
        // Initialize default policies if they don't exist
        $this->policyModel->initializeDefaultPolicies();
    }

    public function handleOptions()
{
    header("HTTP/1.1 200 OK");
    exit();
}
    /* ================================
       Get All Policies
       ================================ */
    public function getAllPolicies()
    {
        return $this->policyModel->getAllPolicies();
    }

    /* ================================
       Get Policy By Key
       ================================ */
    public function getPolicyByKey($key)
    {
        return $this->policyModel->getPolicyByKey($key);
    }

    /* ================================
       API: Get All Policies
       ================================ */
   public function getAllPoliciesApi()
{
    try {
        $policies = $this->policyModel->getAllPolicies();
        
        // Check if query failed
        if ($policies === false) {
            throw new Exception('Database query failed');
        }
        
        // Check if no policies found
        if (empty($policies)) {
            return []; // Return empty array instead of false
        }

        $formatted = [];
        foreach ($policies as $p) {
            $formatted[] = $this->formatPolicyForApi($p);
        }

        return $formatted;
        
    } catch (Exception $e) {
        error_log("Policy API Error: " . $e->getMessage());
        return false;
    }
}

    /* ================================
       API: Get Policy By Key
       ================================ */
    public function getPolicyByKeyApi($key)
    {
        $policy = $this->policyModel->getPolicyByKey($key);
        if ($policy) {
            return $this->formatPolicyForApi($policy);
        }
        return null;
    }

    /* ================================
       Format Policy API Output
       ================================ */
    private function formatPolicyForApi($policy)
    {
        return [
            'id' => (int) $policy['id'],
            'policy_key' => $policy['policy_key'],
            'heading' => $policy['heading'],
            'description' => $policy['description'],
            'points' => json_decode($policy['points'], true),
            'status' => $policy['status'],
            'updated_at' => $policy['updated_at'],
        ];
    }

    /* ================================
       Add Policy
       ================================ */
    public function addPolicy($data)
    {
        try {
            $required = ['policy_key', 'heading'];
            foreach ($required as $r) {
                if (empty($data[$r])) {
                    throw new Exception("Field $r is required");
                }
            }

            $policyData = [
                'policy_key' => $data['policy_key'],
                'heading' => trim($data['heading']),
                'description' => trim($data['description'] ?? ''),
                'points' => json_encode($data['points'] ?? []),
                'status' => $data['status'] ?? 'active'
            ];

            $policyId = $this->policyModel->addPolicy($policyData);

            return ['success' => true, 'policy_id' => $policyId, 'message' => 'Policy added successfully!'];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /* ================================
       Update Policy
       ================================ */
    public function updatePolicy($key, $data)
    {
        try {
            if (empty($data['heading'])) {
                throw new Exception("Heading is required");
            }

            // Handle points safely
            $points = $data['points'] ?? [];
            if (!is_array($points)) {
                $points = [];
            }

            $policyData = [
                'heading' => trim($data['heading']),
                'description' => trim($data['description'] ?? ''),
                'status' => $data['status'] ?? 'active',
                'points' => json_encode(array_values($points)), // Reindex array
            ];

            $res = $this->policyModel->updatePolicy($key, $policyData);

            return [
                'success' => $res,
                'message' => $res ? 'Policy updated successfully!' : 'Failed to update policy'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /* ================================
       Delete Policy
       ================================ */
    public function deletePolicy($key)
    {
        $result = $this->policyModel->deletePolicy($key);
        return [
            'success' => $result,
            'message' => $result ? 'Policy deleted successfully!' : 'Failed to delete policy'
        ];
    }

    /* ================================
       Get Active Policies for Frontend
       ================================ */
    public function getActivePolicies()
    {
        $query = "SELECT * FROM policies WHERE status = 'active' ORDER BY updated_at DESC";
        // Assuming you have a method in your Database class to execute custom queries
        $database = new Database();
        return $database->fetchAll($query);
    }
}
?>