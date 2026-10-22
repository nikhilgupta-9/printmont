<?php
class EmailTemplate {
    private $conn;
    private $table = "email_templates";

    public function __construct($db) {
        $this->conn = $db;
        $this->ensureDefaultTemplates();
    }

    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        $result = $this->conn->query($query);
        
        $templates = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $templates[] = $row;
            }
        }
        
        return $templates;
    }

    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result ? $result->fetch_assoc() : null;
    }

    public function getByType($type) {
        $query = "SELECT * FROM {$this->table} WHERE template_type = ? AND status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $type);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $templates = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $templates[] = $row;
            }
        }
        
        return $templates;
    }

    public function getByKey($key) {
        $query = "SELECT * FROM {$this->table} WHERE template_key = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }

    private function generateKey($name) {
        $key = strtolower(trim($name));
        $key = preg_replace('/[^a-z0-9_]+/i', '_', $key);
        return trim($key, '_') ?: 'template_' . time();
    }

    public function create($data) {
        $key = !empty($data['template_key']) ? $data['template_key'] : $this->generateKey($data['template_name']);
        $name = $data['template_name'];
        $subject = $data['template_subject'];
        $body = $data['template_body'];
        $type = in_array($data['template_type'] ?? '', ['system', 'custom']) ? $data['template_type'] : 'system';
        $status = ($data['status'] ?? '') === 'active' ? 'active' : 'inactive';
        $variables = $data['variables'] ?? '{customer_name}, {order_number}, {order_total}, {tracking_url}, {site_name}';

        $query = "INSERT INTO {$this->table} (template_key, template_name, template_subject, template_body, template_type, status, subject, body_html, variables, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssssssss", $key, $name, $subject, $body, $type, $status, $subject, $body, $variables);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $existing = $this->getById($id);
        if (!$existing) return false;

        $name = $data['template_name'] ?? $existing['template_name'];
        $subject = $data['template_subject'] ?? $existing['template_subject'];
        $body = $data['template_body'] ?? $existing['template_body'];
        $type = $data['template_type'] ?? $existing['template_type'];
        $status = $data['status'] ?? $existing['status'];
        $key = $data['template_key'] ?? $existing['template_key'];

        $query = "UPDATE {$this->table} SET template_key = ?, template_name = ?, template_subject = ?, template_body = ?, template_type = ?, status = ?, subject = ?, body_html = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssssssssi", $key, $name, $subject, $body, $type, $status, $subject, $body, $id);
        return $stmt->execute();
    }

    public function toggleStatus($id) {
        $tpl = $this->getById($id);
        if (!$tpl) return false;
        $newStatus = ($tpl['status'] === 'active') ? 'inactive' : 'active';
        $query = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $newStatus, $id);
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /**
     * Auto-seed default transactional templates if table is empty
     */
    public function ensureDefaultTemplates() {
        $check = $this->conn->query("SELECT COUNT(*) as cnt FROM {$this->table}");
        if ($check) {
            $row = $check->fetch_assoc();
            if ((int)$row['cnt'] > 0) {
                return; // Already populated
            }
        }

        $defaults = [
            [
                'key' => 'order_confirmation',
                'name' => 'Order Confirmation & Payment Receipt',
                'type' => 'system',
                'status' => 'active',
                'subject' => '🎉 Order Confirmed: #{order_number} at Printmont',
                'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
    <div style="background: #0B53A1; padding: 24px; text-align: center; color: #ffffff;">
        <h2 style="margin: 0; font-size: 24px; font-weight: bold;">PRINTMONT</h2>
        <p style="margin: 6px 0 0; font-size: 14px; opacity: 0.9;">Printing Ideas Into Reality</p>
    </div>
    <div style="padding: 28px;">
        <h3 style="color: #0f172a; margin-top: 0;">Hi {customer_name},</h3>
        <p style="color: #475569; font-size: 15px; line-height: 1.6;">
            Thank you for your order! We have received your purchase and our printing specialists are currently preparing your customized merchandise with precision.
        </p>
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 18px; margin: 20px 0;">
            <p style="margin: 0 0 8px; font-size: 14px; color: #334155;"><strong>Order Number:</strong> #{order_number}</p>
            <p style="margin: 0 0 8px; font-size: 14px; color: #334155;"><strong>Total Payable:</strong> ₹{order_total}</p>
            <p style="margin: 0 0 8px; font-size: 14px; color: #334155;"><strong>Payment Method:</strong> {payment_method}</p>
            <p style="margin: 0; font-size: 14px; color: #334155;"><strong>Delivery Address:</strong> {shipping_address}</p>
        </div>
        <div style="text-align: center; margin: 28px 0;">
            <a href="{tracking_url}" style="background: #0B53A1; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: bold; font-size: 14px; display: inline-block;">Track Your Order</a>
        </div>
        <p style="color: #64748b; font-size: 13px; margin-bottom: 0;">Need help? Reply directly to this email or reach us at <a href="mailto:{support_email}" style="color: #0B53A1;">{support_email}</a>.</p>
    </div>
    <div style="background: #f1f5f9; padding: 16px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0;">
        © {year} Printmont. All rights reserved. • {site_url}
    </div>
</div>'
            ],
            [
                'key' => 'order_dispatched',
                'name' => 'Order Dispatched & Tracking Link',
                'type' => 'system',
                'status' => 'active',
                'subject' => '🚚 Your Printmont Order #{order_number} is on the way!',
                'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
    <div style="background: #0B53A1; padding: 24px; text-align: center; color: #ffffff;">
        <h2 style="margin: 0; font-size: 24px; font-weight: bold;">PRINTMONT</h2>
        <p style="margin: 6px 0 0; font-size: 14px; opacity: 0.9;">Order Shipment Notification</p>
    </div>
    <div style="padding: 28px;">
        <h3 style="color: #0f172a; margin-top: 0;">Great news, {customer_name}!</h3>
        <p style="color: #475569; font-size: 15px; line-height: 1.6;">
            Your package for Order <strong>#{order_number}</strong> has been printed, quality-inspected, and handed over to our courier partner.
        </p>
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 18px; margin: 20px 0;">
            <p style="margin: 0 0 8px; font-size: 14px; color: #166534;"><strong>Courier Partner:</strong> {courier_name}</p>
            <p style="margin: 0 0 8px; font-size: 14px; color: #166534;"><strong>Tracking / AWB Number:</strong> {tracking_number}</p>
            <p style="margin: 0; font-size: 14px; color: #166534;"><strong>Estimated Delivery:</strong> 3 - 5 Business Days</p>
        </div>
        <div style="text-align: center; margin: 28px 0;">
            <a href="{tracking_url}" style="background: #16a34a; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: bold; font-size: 14px; display: inline-block;">Live Tracking Link</a>
        </div>
        <p style="color: #64748b; font-size: 13px; margin-bottom: 0;">Questions regarding delivery? Contact us anytime at {support_email}.</p>
    </div>
    <div style="background: #f1f5f9; padding: 16px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0;">
        © {year} Printmont. All rights reserved.
    </div>
</div>'
            ],
            [
                'key' => 'password_reset_otp',
                'name' => 'Password Reset Verification OTP',
                'type' => 'system',
                'status' => 'active',
                'subject' => '🔐 Your Printmont Password Reset Code: {otp_code}',
                'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
    <div style="background: #0B53A1; padding: 24px; text-align: center; color: #ffffff;">
        <h2 style="margin: 0; font-size: 24px; font-weight: bold;">PRINTMONT</h2>
        <p style="margin: 6px 0 0; font-size: 14px; opacity: 0.9;">Security & Authentication</p>
    </div>
    <div style="padding: 28px; text-align: center;">
        <h3 style="color: #0f172a; margin-top: 0;">Password Reset Request</h3>
        <p style="color: #475569; font-size: 15px; line-height: 1.6;">
            We received a request to reset the password for your Printmont account. Use the one-time verification code below:
        </p>
        <div style="background: #f8fafc; border: 2px dashed #0B53A1; border-radius: 8px; padding: 16px 24px; display: inline-block; margin: 20px 0;">
            <span style="font-size: 32px; font-weight: 800; letter-spacing: 6px; color: #0B53A1; font-family: monospace;">{otp_code}</span>
        </div>
        <p style="color: #64748b; font-size: 13px; line-height: 1.5;">
            This OTP is valid for 10 minutes. If you did not request a password reset, please ignore this email or reach out to security support immediately.
        </p>
    </div>
    <div style="background: #f1f5f9; padding: 16px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0;">
        © {year} Printmont Security Team • {site_url}
    </div>
</div>'
            ],
            [
                'key' => 'welcome_family',
                'name' => 'Welcome to Printmont Family',
                'type' => 'custom',
                'status' => 'active',
                'subject' => '✨ Welcome to Printmont, {customer_name}! Here is a gift for you',
                'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
    <div style="background: #0B53A1; padding: 28px; text-align: center; color: #ffffff;">
        <h2 style="margin: 0; font-size: 26px; font-weight: bold;">Welcome to Printmont! 🎨</h2>
        <p style="margin: 8px 0 0; font-size: 15px; opacity: 0.9;">Your Premium Custom Printing Partner</p>
    </div>
    <div style="padding: 28px;">
        <h3 style="color: #0f172a; margin-top: 0;">Hello {customer_name},</h3>
        <p style="color: #475569; font-size: 15px; line-height: 1.6;">
            We are excited to have you with us! Whether you are customizing corporate gifts, bespoke apparel, drinkware, or personalized stationery, Printmont delivers high-definition quality with fast delivery.
        </p>
        <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 20px; text-align: center; margin: 24px 0;">
            <div style="font-size: 13px; color: #92400e; font-weight: bold; text-transform: uppercase;">Your First Order Special Offer</div>
            <div style="font-size: 24px; font-weight: 800; color: #b45309; margin: 8px 0;">FLAT 10% OFF</div>
            <p style="font-size: 13px; color: #78350f; margin: 0;">Use coupon code: <strong>WELCOME10</strong> at checkout</p>
        </div>
        <div style="text-align: center; margin: 24px 0;">
            <a href="{site_url}" style="background: #0B53A1; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: bold; font-size: 14px; display: inline-block;">Start Exploring Products</a>
        </div>
    </div>
    <div style="background: #f1f5f9; padding: 16px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0;">
        © {year} Printmont. All rights reserved.
    </div>
</div>'
            ],
            [
                'key' => 'contact_inquiry_ack',
                'name' => 'Contact Inquiry Acknowledgment',
                'type' => 'system',
                'status' => 'active',
                'subject' => '📩 We received your inquiry, {customer_name} [Ticket #{ticket_id}]',
                'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
    <div style="background: #0B53A1; padding: 24px; text-align: center; color: #ffffff;">
        <h2 style="margin: 0; font-size: 24px; font-weight: bold;">PRINTMONT</h2>
        <p style="margin: 6px 0 0; font-size: 14px; opacity: 0.9;">Customer Support & Helpdesk</p>
    </div>
    <div style="padding: 28px;">
        <h3 style="color: #0f172a; margin-top: 0;">Hello {customer_name},</h3>
        <p style="color: #475569; font-size: 15px; line-height: 1.6;">
            Thank you for getting in touch with Printmont. We have received your inquiry and our support team has been assigned Ticket <strong>#{ticket_id}</strong>.
        </p>
        <div style="background: #f8fafc; border-left: 4px solid #0B53A1; padding: 16px; margin: 20px 0;">
            <p style="margin: 0; font-size: 14px; color: #334155; font-style: italic;">"{inquiry_summary}"</p>
        </div>
        <p style="color: #475569; font-size: 14px; line-height: 1.5;">
            Our support representative will review your message and reply within <strong>24 business hours</strong>.
        </p>
        <p style="color: #64748b; font-size: 13px; margin-top: 24px; margin-bottom: 0;">Best regards,<br><strong>Printmont Support Team</strong></p>
    </div>
    <div style="background: #f1f5f9; padding: 16px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0;">
        © {year} Printmont. All rights reserved. • {site_url}
    </div>
</div>'
            ]
        ];

        foreach ($defaults as $item) {
            $k = $item['key'];
            $n = $item['name'];
            $s = $item['subject'];
            $b = $item['body'];
            $t = $item['type'];
            $st = $item['status'];
            $s_col = $item['subject'];
            $b_col = $item['body'];
            $vars = '{customer_name}, {order_number}, {order_total}, {site_name}, {support_email}';

            $stmt = $this->conn->prepare("INSERT INTO {$this->table} (template_key, template_name, template_subject, template_body, template_type, status, subject, body_html, variables, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            if ($stmt) {
                $stmt->bind_param("sssssssss", $k, $n, $s, $b, $t, $st, $s_col, $b_col, $vars);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}
?>