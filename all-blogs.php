<?php
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/LogoController.php';

// Initialize Logo Controller
$database = new Database();
$db = $database->getConnection();
$logoController = new LogoController($db);

// Handle form submissions
if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == 'create') {
        $result = $logoController->createLogo($_POST, $_FILES['logo_file']);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'danger';
    } elseif ($action == 'update') {
        $result = $logoController->updateLogo($_POST['id'], $_POST, $_FILES['logo_file'] ?? null);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'danger';
    }
}

// Get all logos for display
$logos_result = $logoController->getAllLogos();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Responsive Admin &amp; Dashboard Template based on Bootstrap 5">
    <meta name="author" content="AdminKit">
    <meta name="keywords"
        content="adminkit, bootstrap, bootstrap 5, admin, dashboard, template, responsive, css, sass, html, theme, front-end, ui kit, web">

    <link rel="preconnect" href="https://fonts.gstatic.com/">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />

    <link rel="canonical" href="index.html" />

    <title>Logo Management - Admin Panel</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">

    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .logo-preview {
            max-width: 200px;
            max-height: 100px;
            border: 1px solid #ddd;
            padding: 5px;
            margin: 5px 0;
        }

        .asset-type-badge {
            font-size: 0.75em;
        }

        .active-logo {
            border-left: 4px solid #28a745;
        }
    </style>
</head>

<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>

        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>

            <main class="content">
                <div class="container-fluid p-0">

                    <div class="row mb-2 mb-xl-3">
                        <div class="col-auto d-none d-sm-block">
                            <h3><strong>Blogs</strong></h3>
                        </div>

                        <div class="col-auto ms-auto text-end mt-n1">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#addLogoModal">
                                <i class="fas fa-plus"></i> Add New Blog
                            </button>
                        </div>
                    </div>

                    <?php if (isset($message)): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="col-lg-4 col-md-6 col-sm-12">
                                <div class="card blog-card h-100">
                                    <img src="https://via.placeholder.com/400x250" alt="Blog Image" />
                                    <div class="card-body">
                                        <h5 class="card-title">Children’s Day Celebration Ideas</h5>
                                        <p class="blog-meta mb-1">
                                            <i class="bi bi-person"></i> Priya Lamba &nbsp;
                                            <i class="bi bi-calendar"></i> Nov 5, 2025
                                        </p>
                                        <p class="blog-summary">
                                            Teachers and students look forward to celebrating Children’s Day
                                            with creative and fun classroom activities...
                                        </p>
                                    </div>
                                    <div class="card-footer d-flex justify-content-between">
                                        <button class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-pencil-square me-1"></i> Edit
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-trash3 me-1"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </main>

            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>




    <script src="js/app.js"></script>
    <script src="js/main.js"></script>


</body>

</html>