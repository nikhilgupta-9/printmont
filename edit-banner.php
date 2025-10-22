<?php
// filepath: c:\xampp\htdocs\printmont-admin\edit-banner.php
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/ProductController.php';
include_once "config/Database.php";

// Check if an ID is provided in the query string
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid banner ID.";
    exit;
}

$banner_id = intval($_GET['id']);

// Fetch banner data from the database
$database = new Database();
$conn = $database->getConnection();

$sql = "SELECT * FROM banners WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $banner_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $banner = $result->fetch_assoc();
} else {
    echo "Banner not found.";
    exit;
}
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

    <title>AdminKit Demo - Edit Banner</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">

    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        body {
            opacity: 0;
        }
    </style>
    <!-- END SETTINGS -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-120946860-10"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());

        gtag('config', 'UA-120946860-10', { 'anonymize_ip': true });
    </script>
</head>

<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <div class="wrapper">
        <?php
        include_once "includes/side-navbar.php";
        ?>

        <div class="main">
            <?php
            include_once "includes/top-navbar.php";
            ?>

            <main class="content">
                <div class="container-fluid p-0">

                    <div class="row mb-2 mb-xl-3">
                        <div class="col-auto d-none d-sm-block">
                            <h3><strong>Edit</strong> Banner</h3>
                        </div>
                    </div>

                    <!-- Banner Form Section -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-subtitle text-muted">Edit banner information.</h6>
                                </div>
                                <div class="card-body">
                                    <form id="editBannerForm" enctype="multipart/form-data">
                                        <input type="hidden" id="banner_id" name="id" value="<?php echo $banner_id; ?>">
                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="title">Title</label>
                                                <input type="text" class="form-control" id="title" name="title"
                                                    placeholder="Enter banner title" value="<?php echo htmlspecialchars($banner['title'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="display_order">Display Order</label>
                                                <input type="number" class="form-control" id="display_order"
                                                    name="display_order" placeholder="Enter display order" value="<?php echo htmlspecialchars($banner['display_order'] ?? ''); ?>">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="description">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="3"
                                                placeholder="Enter banner description"><?php echo htmlspecialchars($banner['description'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="image_url_desktop">Desktop Image</label>
                                                <input type="file" class="form-control" id="image_url_desktop"
                                                    name="image_url_desktop">
                                                <img id="current_image_desktop" src="uploads/banners/<?php echo isset($banner['image_url_desktop']) ? htmlspecialchars($banner['image_url_desktop']) : ''; ?>" alt="Current Desktop Image"
                                                    style="max-width: 100px; margin-top: 5px;">
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="image_url_mobile">Mobile Image</label>
                                                <input type="file" class="form-control" id="image_url_mobile"
                                                    name="image_url_mobile">
                                                <img id="current_image_mobile" src="uploads/banners/<?php echo isset($banner['image_url_mobile']) ? htmlspecialchars($banner['image_url_mobile']) : ''; ?>" alt="Current Mobile Image"
                                                    style="max-width: 100px; margin-top: 5px;">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="target_url">Target URL</label>
                                            <input type="text" class="form-control" id="target_url" name="target_url"
                                                placeholder="Enter target URL" value="<?php echo htmlspecialchars($banner['target_url'] ?? ''); ?>">
                                        </div>

                                        <div class="row">
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="status">Status</label>
                                                <select id="status" name="status" class="form-control">
                                                    <option value="active" <?php echo ($banner['status'] ?? '') == 'active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?php echo ($banner['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                    <option value="draft" <?php echo ($banner['status'] ?? '') == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                </select>
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="start_date">Start Date</label>
                                                <input type="date" class="form-control" id="start_date"
                                                    name="start_date" value="<?php echo ($banner['start_date'] != '0000-00-00 00:00:00') ? date('Y-m-d', strtotime($banner['start_date'])) : ''; ?>">
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="end_date">End Date</label>
                                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo ($banner['end_date'] != '0000-00-00 00:00:00') ? date('Y-m-d', strtotime($banner['end_date'])) : ''; ?>">
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary">Update</button>
                                        <button type="button" class="btn btn-secondary"
                                            onclick="window.location.href='view-banner.php'">Cancel</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </main>

            <?php
            include_once "includes/footer.php";
            ?>
        </div>
    </div>

    <script src="js/app.js"></script>
    <!-- <script src="js/main.js"></script> -->

    <script>
        const bannerId = document.getElementById('banner_id').value;
        console.log(bannerId);

        async function fetchBannerData(id) {
            try {
                const response = await fetch(`api/banner_api.php?id=${id}`);
                const banner = await response.json();

                if (banner) {
                    document.getElementById('title').value = banner.title;
                    document.getElementById('description').value = banner.description;
                    document.getElementById('display_order').value = banner.display_order;
                    document.getElementById('target_url').value = banner.target_url;
                    document.getElementById('status').value = banner.status;
                    document.getElementById('start_date').value = banner.start_date;
                    document.getElementById('end_date').value = banner.end_date;

                    // Remove these lines:
                    // document.getElementById('current_image_desktop').src = `uploads/banners/${banner.image_url_desktop}`;
                    // document.getElementById('current_image_mobile').src = `uploads/banners/${banner.image_url_mobile}`;
                } else {
                    alert("Banner not found");
                    window.location.href = 'view-banner.php';
                }
            } catch (error) {
                console.error("Error fetching banner:", error);
                alert("Error fetching banner data");
            }
        }

        document.getElementById("editBannerForm").addEventListener("submit", async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('id', bannerId); // Ensure ID is sent

            try {
                const response = await fetch("api/banner_api.php", {
                    method: "PUT",
                    body: formData
                });

                const result = await response.json();
                alert(result.message || "Banner updated successfully");
                window.location.href = 'view-banner.php'; // Redirect back to banner list
            } catch (error) {
                console.error("Error:", error);
                alert("Error updating banner");
            }
        });

        // Fetch banner data on page load
        window.onload = () => {
            //fetchBannerData(bannerId);
        };
    </script>

</body>

</html>