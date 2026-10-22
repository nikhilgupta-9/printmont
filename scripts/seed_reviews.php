<?php
require_once dirname(__DIR__) . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed\n");
}

echo "Starting Review Seeder...\n";

// Get all products
$products_res = $db->query("SELECT id, name, category_id, sub_category_id FROM products");
$products = [];
while ($row = $products_res->fetch_assoc()) {
    $products[] = $row;
}

echo "Found " . count($products) . " products.\n";

$reviewers = [
    ['name' => 'Ankur Priyadarshi', 'email' => 'ankur.p@example.com'],
    ['name' => 'Harneet Kaur', 'email' => 'harneet.k@example.com'],
    ['name' => 'Zoom Mukherji', 'email' => 'zoom.m@example.com'],
    ['name' => 'KAILASA\'s Sudeep', 'email' => 'sudeep.k@example.com'],
    ['name' => 'Anita Patel', 'email' => 'anita.p@example.com'],
    ['name' => 'Rohit Malhotra', 'email' => 'rohit.m@example.com'],
    ['name' => 'Pooja Sharma', 'email' => 'pooja.s@example.com'],
    ['name' => 'Vikram Rathore', 'email' => 'vikram.r@example.com'],
    ['name' => 'Sunil Joshi', 'email' => 'sunil.j@example.com'],
    ['name' => 'Sneha Nair', 'email' => 'sneha.n@example.com'],
    ['name' => 'Aman Verma', 'email' => 'aman.v@example.com'],
    ['name' => 'Meera Kulkarni', 'email' => 'meera.k@example.com'],
    ['name' => 'Deepak Chopra', 'email' => 'deepak.c@example.com'],
    ['name' => 'Kavita Reddy', 'email' => 'kavita.r@example.com'],
    ['name' => 'Sanjay Singhal', 'email' => 'sanjay.s@example.com'],
    ['name' => 'Priyanka Roy', 'email' => 'priyanka.r@example.com'],
    ['name' => 'Aditya Deshmukh', 'email' => 'aditya.d@example.com'],
    ['name' => 'Nandini Sen', 'email' => 'nandini.s@example.com'],
    ['name' => 'Rakesh Bhatt', 'email' => 'rakesh.b@example.com'],
    ['name' => 'Divya Menon', 'email' => 'divya.m@example.com'],
    ['name' => 'Arun Vashishta', 'email' => 'arun.v@example.com'],
    ['name' => 'Tanvi Bhatia', 'email' => 'tanvi.b@example.com'],
    ['name' => 'Rajeshwari Iyer', 'email' => 'rajeshwari.i@example.com'],
    ['name' => 'Naveen Aggarwal', 'email' => 'naveen.a@example.com'],
    ['name' => 'Shreya Das', 'email' => 'shreya.d@example.com'],
    ['name' => 'Gaurav Mehta', 'email' => 'gaurav.m@example.com'],
    ['name' => 'Swati Saxena', 'email' => 'swati.s@example.com'],
    ['name' => 'Manish Pandey', 'email' => 'manish.p@example.com'],
    ['name' => 'Kritika Saini', 'email' => 'kritika.s@example.com'],
    ['name' => 'Alok Mishra', 'email' => 'alok.m@example.com'],
    ['name' => 'Siddharth Jain', 'email' => 'siddharth.j@example.com'],
    ['name' => 'Bhavna Sethi', 'email' => 'bhavna.s@example.com'],
    ['name' => 'Karan Kapoor', 'email' => 'karan.k@example.com'],
    ['name' => 'Simran Kaur', 'email' => 'simran.k@example.com'],
    ['name' => 'Mayank Mittal', 'email' => 'mayank.m@example.com'],
    ['name' => 'Ayesha Khan', 'email' => 'ayesha.k@example.com'],
    ['name' => 'Prateek Rawat', 'email' => 'prateek.r@example.com'],
    ['name' => 'Neha Bansal', 'email' => 'neha.b@example.com']
];

$occasions = [
    'Birthday',
    'Rakshabandhan',
    'Anniversary',
    'Corporate Event',
    'Promotion',
    'Festival',
    'Diwali',
    'Housewarming',
    'Office Welcome Kit',
    'Farewell',
    'Personal Gift',
    'Wedding'
];

$cities = [
    'Delhi',
    'Mumbai',
    'Kolkata',
    'Bengaluru',
    'Pune',
    'Hyderabad',
    'Chennai',
    'Jaipur',
    'Ahmedabad',
    'Chandigarh',
    'Noida',
    'Gurgaon'
];

$reviewTemplates = [
    ['title' => 'Prompt delivery', 'comment' => 'Prompt delivery and fantastic print clarity! Everyone in the office loved the merchandise.', 'rating' => 5],
    ['title' => 'Nice and clear picture', 'comment' => 'Nice and clear picture printing. Colors came out vibrant and sharp.', 'rating' => 5],
    ['title' => 'All the best', 'comment' => 'All the best to the team! The finish on the material is premium and durable.', 'rating' => 5],
    ['title' => 'Unique design idea', 'comment' => 'Unique design idea and fast execution. Delivered right on time for our event.', 'rating' => 5],
    ['title' => 'Top notch finish', 'comment' => 'Exceptional print quality! Exceeded our expectations. Will definitely order again.', 'rating' => 5],
    ['title' => 'Super fast turnaround', 'comment' => 'Super fast dispatch and immaculate packaging. Truly same-day experience!', 'rating' => 5],
    ['title' => 'Crisp resolution', 'comment' => 'The customized print was crisp and accurate to the artwork provided. 10/10 recommended.', 'rating' => 5],
    ['title' => 'Great office merch', 'comment' => 'Ordered for an office function on short notice. Received pristine quality on the same day!', 'rating' => 4],
    ['title' => 'Wonderful quality', 'comment' => 'Great texture and durable finish. Our clients appreciated the welcome gift.', 'rating' => 5],
    ['title' => 'True to colors', 'comment' => 'Brilliant work! The color reproduction is exact and the material feels high grade.', 'rating' => 5],
    ['title' => 'Delighted recipient', 'comment' => 'High quality personalized product. The recipient loved it so much!', 'rating' => 5],
    ['title' => 'Smooth ordering', 'comment' => 'Smooth ordering process and swift turnaround. Five stars for service and product quality!', 'rating' => 5],
    ['title' => 'Excellent work', 'comment' => 'Excellent craftsmanship and timely shipment. Highly satisfied with Printmont.', 'rating' => 5],
    ['title' => 'Received compliments', 'comment' => 'The print resolution is top-notch. Received lots of compliments at our corporate gathering.', 'rating' => 4],
    ['title' => 'Special memory', 'comment' => 'Perfect gift with personalized touch. Made our special occasion memorable!', 'rating' => 5]
];

// Clean existing reviews if needed or just insert
$db->query("TRUNCATE TABLE product_reviews");

$stmt = $db->prepare("INSERT INTO product_reviews 
    (product_id, customer_name, customer_email, rating, title, comment, status, is_verified, occasion, city, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, 'approved', 1, ?, ?, ?)");

$insertedCount = 0;
$now = time();

foreach ($products as $idx => $prod) {
    $prodId = (int)$prod['id'];
    // Insert 2 to 4 reviews per product
    $numReviews = ($idx < 30) ? 4 : 2; // more for front categories
    
    for ($i = 0; $i < $numReviews; $i++) {
        $reviewer = $reviewers[($idx * 3 + $i) % count($reviewers)];
        $occasion = $occasions[($idx * 2 + $i) % count($occasions)];
        $city = $cities[($idx * 4 + $i) % count($cities)];
        $tpl = $reviewTemplates[($idx * 5 + $i) % count($reviewTemplates)];
        
        // Vary creation timestamp (between 2 days and 300 days ago)
        $daysAgo = (($idx * 17 + $i * 23) % 200) + 2;
        $createdAt = date('Y-m-d H:i:s', $now - ($daysAgo * 86400) - ($i * 3600));
        
        $customerName = $reviewer['name'];
        $customerEmail = $reviewer['email'];
        $rating = $tpl['rating'];
        $title = $tpl['title'];
        $comment = $tpl['comment'];
        
        $stmt->bind_param(
            "ississsss",
            $prodId,
            $customerName,
            $customerEmail,
            $rating,
            $title,
            $comment,
            $occasion,
            $city,
            $createdAt
        );
        $stmt->execute();
        $insertedCount++;
    }
}

echo "Successfully seeded $insertedCount reviews for " . count($products) . " products!\n";
