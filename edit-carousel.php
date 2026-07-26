<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/CarouselController.php';
require_once 'includes/carousel-upload.php';

$carouselController = new CarouselController();
$categories = $carouselController->getCategoriesForSelect();
$banners    = $carouselController->getBannersForSelect();

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
if (!$id) {
    $_SESSION['error_message'] = "No carousel specified.";
    header("Location: carousels.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (trim($_POST['title'] ?? '') === '') {
            throw new Exception("Carousel title is required.");
        }
        $slug = trim($_POST['slug'] ?? '');
        if ($slug === '') {
            $slug = strtolower(preg_replace(['/[^a-z0-9 -]/i', '/\s+/', '/-+/'], ['', '-', '-'], trim($_POST['title'])));
        }

        $data = [
            'title'                     => trim($_POST['title']),
            'slug'                      => $slug,
            'desktop_other_pages'       => $_POST['desktop_other_pages'] ?? 'no',
            'desktop_other_category_id' => $_POST['desktop_other_category_id'] ?? '',
            'desktop_other_banner_id'   => $_POST['desktop_other_banner_id'] ?? '',
            'desktop_home_category_id'  => $_POST['desktop_home_category_id'] ?? '',
            'desktop_home_design'       => $_POST['desktop_home_design'] ?? 'design1',
            'desktop_bg_color'          => $_POST['desktop_bg_color'] ?? '',
            'desktop_sort_order'        => $_POST['desktop_sort_order'] ?? 0,
            'desktop_status'            => $_POST['desktop_status'] ?? 'active',
            'mobile_other_pages'        => $_POST['mobile_other_pages'] ?? 'no',
            'mobile_category_id'        => $_POST['mobile_category_id'] ?? '',
            'mobile_banner_id'          => $_POST['mobile_banner_id'] ?? '',
            'mobile_home_show'          => $_POST['mobile_home_show'] ?? 'no',
            'mobile_home_category_id'   => $_POST['mobile_home_category_id'] ?? '',
            'mobile_home_design'        => $_POST['mobile_home_design'] ?? 'design1',
            'mobile_other_design'       => $_POST['mobile_other_design'] ?? 'design1',
            'mobile_bg_color'           => $_POST['mobile_bg_color'] ?? '',
            'mobile_sort_order'         => $_POST['mobile_sort_order'] ?? 0,
            'mobile_status'             => $_POST['mobile_status'] ?? 'active',
            'meta_title'                => trim($_POST['meta_title'] ?? ''),
            'meta_keywords'             => trim($_POST['meta_keywords'] ?? ''),
            'meta_description'          => trim($_POST['meta_description'] ?? ''),
            'status'                    => $_POST['status'] ?? 'active',
        ];

        // Only overwrite images when a new file is uploaded (keep existing otherwise).
        $desktopImg = carousel_upload_image('desktop_bg_image');
        $mobileImg  = carousel_upload_image('mobile_bg_image');
        if ($desktopImg !== '') $data['desktop_bg_image'] = $desktopImg;
        if ($mobileImg !== '')  $data['mobile_bg_image']  = $mobileImg;

        if ($carouselController->updateCarousel($id, $data)) {
            $_SESSION['success_message'] = "Carousel updated successfully!";
            header("Location: carousels.php");
            exit();
        }
        throw new Exception("Failed to update carousel.");
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

$carousel = $carouselController->getById($id);
if (!$carousel) {
    $_SESSION['error_message'] = "Carousel not found.";
    header("Location: carousels.php");
    exit();
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message   = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$formAction  = 'edit-carousel.php?id=' . $id;
$submitLabel = 'Update Carousel';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Edit Carousel | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .form-label { font-weight: 500; }
        .required:after { content: " *"; color: red; }
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
                            <h3><strong>Edit</strong> Carousel</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="carousels.php" class="btn btn-secondary">All Carousels</a>
                        </div>
                    </div>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($success_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($error_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Edit Carousel</h5>
                                    <h6 class="card-subtitle text-muted">Update carousel #<?php echo (int)$id; ?>.</h6>
                                </div>
                                <div class="card-body">
                                    <?php include "includes/carousel-form.php"; ?>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
