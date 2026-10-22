<?php
// Editing uses the same 12-section form as "Add Product" (product.php), opened in edit mode.
// This file stays so existing links (view-products.php, bookmarks) keep working.
$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
header('Location: ' . ($productId > 0 ? 'product.php?id=' . $productId : 'view-products.php'));
exit;
