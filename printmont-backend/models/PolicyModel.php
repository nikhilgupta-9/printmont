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

    /* ================================
       Initialize Default Policies
       ================================ */
    public function initializeDefaultPolicies() {
        $defaultPolicies = [
            [
                'policy_key' => 'terms',
                'heading' => 'Terms & Conditions',
                'description' => 'Please read these terms and conditions carefully before using our service.',
                'points' => json_encode([
                    'By accessing and using this website, you accept and agree to be bound by the terms and provision of this agreement.',
                    'All content provided on this website is for informational purposes only.',
                    'We reserve the right to modify these terms at any time without prior notice.',
                    'You must be at least 18 years old to use our services.'
                ]),
                'status' => 'active'
            ],
            [
                'policy_key' => 'privacy',
                'heading' => 'Privacy Policy',
                'description' => 'We are committed to protecting your privacy and personal information.',
                'points' => json_encode([
                    'We collect personal information that you voluntarily provide to us.',
                    'Your information is used to provide and improve our services.',
                    'We implement security measures to protect your personal information.',
                    'We do not sell or share your personal information with third parties for marketing purposes.'
                ]),
                'status' => 'active'
            ],
            [
                'policy_key' => 'shipping',
                'heading' => 'Shipping Policy',
                'description' => 'Information about our shipping methods, delivery times, and shipping costs.',
                'points' => json_encode([
                    'We offer standard and express shipping options.',
                    'Orders are processed within 1-2 business days.',
                    'Shipping costs are calculated based on weight and destination.',
                    'Free shipping available on orders over $50.'
                ]),
                'status' => 'active'
            ],
            [
                'policy_key' => 'refund',
                'heading' => 'Refund & Return Policy',
                'description' => 'Our policy regarding returns, refunds, and exchanges.',
                'points' => json_encode([
                    'You have 30 days to return items from the purchase date.',
                    'Items must be in original condition with tags attached.',
                    'Refunds will be processed to the original payment method.',
                    'Sale items are final and cannot be returned.'
                ]),
                'status' => 'active'
            ]
        ];

        foreach ($defaultPolicies as $policy) {
            // Check if policy already exists
            $existing = $this->getPolicyByKey($policy['policy_key']);
            if (!$existing) {
                $this->addPolicy($policy);
            }
        }
    }
}
?>