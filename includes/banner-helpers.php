<?php
if (!function_exists('resolveAdminThumb')) {
    function resolveAdminThumb($path) {
        if (empty($path)) return '';
        $trimmed = trim($path);
        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://') || str_starts_with($trimmed, '//') || str_starts_with($trimmed, 'data:')) {
            return $trimmed;
        }
        if (str_starts_with($trimmed, '/printmont/')) {
            return $trimmed;
        }
        return ltrim($trimmed, '/');
    }
}
