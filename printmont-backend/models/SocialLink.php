<?php
class SocialLink {
    private $conn;
    private $table = "social_links";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all social links
    public function getAll($filters = []) {
        $where_conditions = ["1=1"];
        $params = [];
        $types = "";
        
        if (!empty($filters['status'])) {
            $where_conditions[] = "status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }
        
        $where_clause = implode(" AND ", $where_conditions);
        
        $query = "SELECT * FROM {$this->table} 
                 WHERE {$where_clause}
                 ORDER BY display_order ASC, platform ASC";
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $links = [];
        while ($row = $result->fetch_assoc()) {
            $links[] = $row;
        }
        
        return $links;
    }

    // Get social link by ID
    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    // Get social link by platform
    public function getByPlatform($platform) {
        $query = "SELECT * FROM {$this->table} WHERE platform = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $platform);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    // Create social link
    public function create($data) {
        $query = "INSERT INTO {$this->table} 
                 (platform, url, icon, display_order, status) 
                 VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            "sssis",
            $data['platform'],
            $data['url'],
            $data['icon'],
            $data['display_order'],
            $data['status']
        );
        
        return $stmt->execute();
    }

    // Update social link
    public function update($id, $data) {
        $setClause = [];
        $types = "";
        $values = [];
        
        foreach ($data as $key => $value) {
            $setClause[] = "{$key} = ?";
            
            if (is_int($value)) {
                $types .= "i";
            } else {
                $types .= "s";
            }
            
            $values[] = $value;
        }
        
        $values[] = $id;
        $types .= "i";
        
        $query = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param($types, ...$values);
            return $stmt->execute();
        }
        
        return false;
    }

    // Delete social link
    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Update status
    public function updateStatus($id, $status) {
        $query = "UPDATE {$this->table} SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }

    // Get active social links for frontend
    public function getActiveLinks() {
        $query = "SELECT * FROM {$this->table} 
                 WHERE status = 'active' 
                 ORDER BY display_order ASC, platform ASC";
        
        $result = $this->conn->query($query);
        
        $links = [];
        while ($row = $result->fetch_assoc()) {
            $links[] = $row;
        }
        
        return $links;
    }

    // Check if platform already exists (for validation)
    public function platformExists($platform, $exclude_id = null) {
        $query = "SELECT id FROM {$this->table} WHERE platform = ?";
        $params = [$platform];
        $types = "s";
        
        if ($exclude_id) {
            $query .= " AND id != ?";
            $params[] = $exclude_id;
            $types .= "i";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }

    // Get platform options with icons
    public function getPlatformOptions() {
        return [
            'facebook' => [
                'name' => 'Facebook',
                'icon' => 'fab fa-facebook-f',
                'color' => '#1877f2',
                'url_prefix' => 'https://facebook.com/'
            ],
            'twitter' => [
                'name' => 'Twitter',
                'icon' => 'fab fa-twitter',
                'color' => '#1da1f2',
                'url_prefix' => 'https://twitter.com/'
            ],
            'instagram' => [
                'name' => 'Instagram',
                'icon' => 'fab fa-instagram',
                'color' => '#e4405f',
                'url_prefix' => 'https://instagram.com/'
            ],
            'linkedin' => [
                'name' => 'LinkedIn',
                'icon' => 'fab fa-linkedin-in',
                'color' => '#0a66c2',
                'url_prefix' => 'https://linkedin.com/in/'
            ],
            'youtube' => [
                'name' => 'YouTube',
                'icon' => 'fab fa-youtube',
                'color' => '#ff0000',
                'url_prefix' => 'https://youtube.com/'
            ],
            'pinterest' => [
                'name' => 'Pinterest',
                'icon' => 'fab fa-pinterest-p',
                'color' => '#bd081c',
                'url_prefix' => 'https://pinterest.com/'
            ],
            'tiktok' => [
                'name' => 'TikTok',
                'icon' => 'fab fa-tiktok',
                'color' => '#000000',
                'url_prefix' => 'https://tiktok.com/@'
            ],
            'whatsapp' => [
                'name' => 'WhatsApp',
                'icon' => 'fab fa-whatsapp',
                'color' => '#25d366',
                'url_prefix' => 'https://wa.me/'
            ],
            'telegram' => [
                'name' => 'Telegram',
                'icon' => 'fab fa-telegram',
                'color' => '#0088cc',
                'url_prefix' => 'https://t.me/'
            ],
            'github' => [
                'name' => 'GitHub',
                'icon' => 'fab fa-github',
                'color' => '#333333',
                'url_prefix' => 'https://github.com/'
            ],
            'discord' => [
                'name' => 'Discord',
                'icon' => 'fab fa-discord',
                'color' => '#5865f2',
                'url_prefix' => 'https://discord.gg/'
            ],
            'reddit' => [
                'name' => 'Reddit',
                'icon' => 'fab fa-reddit',
                'color' => '#ff4500',
                'url_prefix' => 'https://reddit.com/r/'
            ],
            'spotify' => [
                'name' => 'Spotify',
                'icon' => 'fab fa-spotify',
                'color' => '#1db954',
                'url_prefix' => 'https://open.spotify.com/'
            ]
        ];
    }
}
?>