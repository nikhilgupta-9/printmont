<?php
require_once(__DIR__ . '/../models/PolicyModel.php');

class PolicyController
{

    private $policyModel;

    public function __construct()
    {
        $this->policyModel = new PolicyModel();
    }
    public function getAllPolicies()
{
    return $this->policyModel->getAllPolicies();
}

    /* ================================
       API: Get All Policies
       ================================ */
    public function getAllPoliciesApi()
    {
        $policies = $this->policyModel->getAllPolicies();

        $formatted = [];
        foreach ($policies as $p) {
            $formatted[] = $this->formatPolicyForApi($p);
        }

        return $formatted;
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
            'policy_key' => $policy['policy_key'],     // terms, privacy, shipping, refund
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

            return ['success' => true, 'policy_id' => $policyId];

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
                'points' => json_encode($points),
            ];

            $res = $this->policyModel->updatePolicy($key, $policyData);

            return ['success' => $res];

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
        return $this->policyModel->deletePolicy($key);
    }
}
?>