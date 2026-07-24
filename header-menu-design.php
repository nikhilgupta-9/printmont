<?php
session_start();
require_once(__DIR__ . '/config/database.php');

$configFile = __DIR__ . '/config/header_menu_design.json';
$designOptions = [
    'design1' => ['📱', 'Design 1'],
    'design2' => ['🖼️', 'Design 2'],
    'design3' => ['🗂️', 'Design 3'],
    'design4' => ['🎨', 'Design 4'],
];

function readHeaderDesign($configFile) {
    if (!file_exists($configFile)) return 'design1';
    $data = json_decode(file_get_contents($configFile), true);
    return $data['design'] ?? 'design1';
}

$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $design = $_POST['design'] ?? 'design1';
    if (!array_key_exists($design, $designOptions)) {
        $design = 'design1';
    }
    file_put_contents($configFile, json_encode(['design' => $design], JSON_PRETTY_PRINT));
    $_SESSION['success_message'] = 'Header menu design updated.';
    header('Location: header-menu-design.php');
    exit;
}

$currentDesign = readHeaderDesign($configFile);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Header Menu — Select Design | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
    <style>
        body { opacity: 0; }
        .design-option { border: 2px solid #dee2e6; border-radius: 8px; padding: 16px; text-align: center; transition: all .2s; cursor: pointer; }
        .design-option:hover { border-color: #0d6efd; }
        input[type=radio]:checked + .design-option { border-color: #0d6efd; background: #e8f4fd; }
    </style>
</head>
<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
<div class="wrapper">
    <?php include_once "includes/side-navbar.php"; ?>
    <div class="main">
        <?php include_once "includes/top-navbar.php"; ?>
        <main class="content">
            <div class="container-fluid p-0">

                <div class="row mb-3">
                    <div class="col-auto d-none d-sm-block">
                        <h3><strong>Header Menu</strong> — Select Design</h3>
                    </div>
                    <div class="col-auto ms-auto text-end mt-n1">
                        <a href="header-menu-list.php" class="btn btn-success">All Menus</a>
                    </div>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible p-2"><button class="btn-close" data-bs-dismiss="alert"></button><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST">    
                    <div class="card mb-4">
                        <div class="card-header"><h5 class="mb-0">Overall Header Menu Layout</h5></div>
                        <div class="card-body">
                            <p class="text-muted">This is the site-wide layout template used to render the header category menu (top icons + mega-menu panels). It applies globally, independent of each category's own settings.</p>
                            <div class="row g-3">
                                <?php foreach ($designOptions as $val => [$icon, $lbl]): ?>
                                <div class="col-6 col-md-3">
                                    <input type="radio" name="design" value="<?php echo $val; ?>" id="hd_<?php echo $val; ?>" class="d-none"
                                           <?php echo $currentDesign === $val ? 'checked' : ''; ?>>
                                    <label for="hd_<?php echo $val; ?>" class="design-option d-block">
                                        <div class="mb-2" style="font-size:36px"><?php echo $icon; ?></div>
                                        <div><?php echo $lbl; ?></div>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <button type="submit" class="btn btn-primary btn-lg">Save Design</button>
                    </div>
                </form>

            </div>
        </main>
        <?php include_once "includes/footer.php"; ?>
    </div>
</div>
<script src="js/app.js"></script>
</body>
</html>
