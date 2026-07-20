<?php
require_once(__DIR__ . '/../models/CouponModel.php');

class CouponController {
    private $model;

    public function __construct() {
        $this->model = new CouponModel();
    }

    // ----------------------------------------------------------------
    // Reads (pass-through to the model)
    // ----------------------------------------------------------------

    public function getCouponsWithPagination(int $page, int $perPage, string $search = '', string $scope = 'all'): array {
        return $this->model->getCouponsWithPagination($page, $perPage, $search, $scope);
    }

    public function getCouponsCount(string $search = '', string $scope = 'all'): int {
        return $this->model->getCouponsCount($search, $scope);
    }

    public function getCouponStats(): array {
        return $this->model->getCouponStats();
    }

    public function getCouponById(int $id): ?array {
        return $this->model->getCouponById($id);
    }

    public function getAllCategories(): array {
        return $this->model->getAllCategories();
    }

    public function getAllCarousels(): array {
        return $this->model->getAllCarousels();
    }

    // ----------------------------------------------------------------
    // Validation + normalisation
    // ----------------------------------------------------------------

    /**
     * Turns raw $_POST into DB-ready values.
     * Returns ['error' => string] on the first validation failure.
     */
    private function prepare(array $post, ?int $ignoreId = null): array {
        $code = strtoupper(trim($post['coupon_code'] ?? ''));

        if ($code === '') {
            return ['error' => 'Coupon code is required.'];
        }
        if (!preg_match('/^[A-Z0-9_-]+$/', $code)) {
            return ['error' => 'Coupon code may only contain letters, numbers, hyphens and underscores.'];
        }
        if ($this->model->codeExists($code, $ignoreId)) {
            return ['error' => "Coupon code \"{$code}\" already exists. Please choose a different code."];
        }

        $format = ($post['discount_format'] ?? 'percentage') === 'fixed' ? 'fixed' : 'percentage';
        $value  = $post['discount_value'] ?? '';

        if ($value === '' || !is_numeric($value) || (float)$value <= 0) {
            return ['error' => 'Discount value must be a number greater than zero.'];
        }
        if ($format === 'percentage' && (float)$value > 100) {
            return ['error' => 'A percentage discount cannot be greater than 100.'];
        }

        $start = trim($post['start_date'] ?? '');
        $end   = trim($post['end_date'] ?? '');

        if ($start !== '' && $end !== '' && $end < $start) {
            return ['error' => 'End date cannot be earlier than the start date.'];
        }

        // Empty selects are stored as NULL rather than 0 so the LEFT JOINs stay clean.
        $toIntOrNull = function ($v) {
            return ($v === '' || $v === null) ? null : (int)$v;
        };

        $status = $post['status'] ?? 'active';
        if (!in_array($status, ['active', 'inactive', 'expired'], true)) {
            $status = 'active';
        }

        return [
            'coupon_code'         => $code,
            'category_id'         => $toIntOrNull($post['category_id']         ?? ''),
            'sub_category_id'     => $toIntOrNull($post['sub_category_id']     ?? ''),
            'sub_sub_category_id' => $toIntOrNull($post['sub_sub_category_id'] ?? ''),
            'carousel_type'       => trim($post['carousel_type'] ?? '') ?: null,
            'discount_format'     => $format,
            'discount_value'      => (float)$value,
            'min_order_value'     => (float)($post['min_order_value'] ?? 0),
            'quantity'            => (int)($post['quantity'] ?? 0),
            'start_date'          => $start !== '' ? $start : null,
            'end_date'            => $end   !== '' ? $end   : null,
            'status'              => $status,
        ];
    }

    // ----------------------------------------------------------------
    // Writes
    // ----------------------------------------------------------------

    public function createCoupon(array $post): array {
        $data = $this->prepare($post);
        if (isset($data['error'])) {
            return ['success' => false, 'error' => $data['error']];
        }

        try {
            $id = $this->model->createCoupon($data);
            return [
                'success' => true,
                'message' => "Coupon \"{$data['coupon_code']}\" created successfully.",
                'id'      => $id,
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Could not create coupon: ' . $e->getMessage()];
        }
    }

    public function updateCoupon(int $id, array $post): array {
        if (!$this->model->getCouponById($id)) {
            return ['success' => false, 'error' => 'Coupon not found.'];
        }

        $data = $this->prepare($post, $id);
        if (isset($data['error'])) {
            return ['success' => false, 'error' => $data['error']];
        }

        try {
            $this->model->updateCoupon($id, $data);
            return [
                'success' => true,
                'message' => "Coupon \"{$data['coupon_code']}\" updated successfully.",
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Could not update coupon: ' . $e->getMessage()];
        }
    }

    public function deleteCoupon(int $id): array {
        $coupon = $this->model->getCouponById($id);
        if (!$coupon) {
            return ['success' => false, 'error' => 'Coupon not found.'];
        }

        try {
            $this->model->deleteCoupon($id);
            return [
                'success' => true,
                'message' => "Coupon \"{$coupon['coupon_code']}\" deleted successfully.",
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Could not delete coupon: ' . $e->getMessage()];
        }
    }
}
