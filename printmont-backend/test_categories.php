<?php
// test_categories.php - Place this in your project root folder
require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/models/CategoryModel.php');

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Create category model
$categoryModel = new Category($db);

echo "<h2>Testing Categories</h2>";

try {
    // Check all categories
    $categories = $categoryModel->getAllCategoriesFlat();
    
    if (empty($categories)) {
        echo "<p style='color: red;'>No categories found in database!</p>";
    } else {
        echo "<h3>Available Categories (" . count($categories) . "):</h3>";
        echo "<table border='1' cellpadding='8'>";
        echo "<tr><th>ID</th><th>Name</th><th>Slug</th><th>Parent ID</th><th>Status</th></tr>";
        
        foreach ($categories as $category) {
            echo "<tr>";
            echo "<td>{$category['id']}</td>";
            echo "<td>{$category['name']}</td>";
            echo "<td>{$category['slug']}</td>";
            echo "<td>{$category['parent_id']}</td>";
            echo "<td>{$category['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Also test products
echo "<h2>Testing Products by Category Slug</h2>";

require_once(__DIR__ . '/models/ProductModel.php');
$productModel = new ProductModel();

// Test a specific category
$testSlug = 'men-clothing';
$products = $productModel->getProductsByCategorySlug($testSlug);

if (empty($products)) {
    echo "<p>No products found for slug: '{$testSlug}'</p>";
} else {
    echo "<p>Found " . count($products) . " products for slug: '{$testSlug}'</p>";
}

// Test all category slugs used in API
echo "<h2>Testing All API Category Slugs</h2>";
$apiSlugs = [
    'men-clothing', 'women-clothing', 'kids', 'mobile', 'laptop', 
    'buds', 'home-decor', 'table-dinnerware', 'women-outfit', 'men', 'women'
];

foreach ($apiSlugs as $slug) {
    $products = $productModel->getProductsByCategorySlug($slug);
    $count = count($products);
    $status = $count > 0 ? "✅" : "❌";
    echo "<p>{$status} Slug '{$slug}': {$count} products</p>";
}
?>