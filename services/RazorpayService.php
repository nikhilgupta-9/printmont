<?php
// Razorpay helpers shared by order checkout (OrderController) and wallet top-ups (wallet-api.php).
// Keys come from admin Payment Settings (site_settings, group payment_settings) for the active mode.

require_once __DIR__ . '/../controllers/PaymentSettingsController.php';

class RazorpayService
{
    private $settings;

    public function __construct($db = null)
    {
        $this->settings = (new PaymentSettingsController($db))->getSettings(true);
    }

    public function settings(): array
    {
        return $this->settings;
    }

    public function enabled(): bool
    {
        return ($this->settings['razorpay_enabled'] ?? '0') === '1';
    }

    // A real secret is needed to create orders and verify payments
    public function configured(): bool
    {
        $key = (string) ($this->settings['active_key_id'] ?? '');
        $secret = (string) ($this->settings['active_secret'] ?? '');
        return $key !== '' && $secret !== '' && $secret !== 'rzp_test_secret_sample_key';
    }

    public function keyId(): string
    {
        return (string) ($this->settings['active_key_id'] ?? '');
    }

    /** Creates a Razorpay order for $amount rupees. Returns ['success'=>true,'id'=>...] or ['success'=>false,'error'=>...] */
    public function createOrder(float $amount, string $receipt, array $notes = []): array
    {
        $payload = [
            'amount'   => (int) round($amount * 100),
            'currency' => 'INR',
            'receipt'  => substr($receipt, 0, 40),
        ];
        if ($notes) $payload['notes'] = $notes;

        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_USERPWD        => $this->settings['active_key_id'] . ':' . $this->settings['active_secret'],
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload),
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $json = $body ? json_decode($body, true) : null;
        if ($status === 200 && !empty($json['id'])) {
            return ['success' => true, 'id' => $json['id']];
        }
        $msg = $json['error']['description'] ?? ($err ?: ('HTTP ' . $status));
        error_log('Razorpay order create failed: ' . $msg);
        return ['success' => false, 'error' => $msg];
    }

    /** Checks the signature Razorpay returns to the browser after a successful payment */
    public function verifySignature(string $razorpayOrderId, string $paymentId, string $signature): bool
    {
        $secret = (string) ($this->settings['active_secret'] ?? '');
        if ($secret === '' || $razorpayOrderId === '' || $paymentId === '' || $signature === '') return false;
        $expected = hash_hmac('sha256', $razorpayOrderId . '|' . $paymentId, $secret);
        return hash_equals($expected, $signature);
    }
}
