<?php
// filepath: c:\xampp\htdocs\printmont-admin\view-banner.php
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/ProductController.php';
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

    <title>AdminKit Demo - Banner Management</title>

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
                            <h3><strong>Banner</strong> Management</h3>
                        </div>

                        <div class="col-auto ms-auto text-end mt-n1">
                            <!-- <a href="#" class="btn btn-light bg-white me-2">Invite a Friend</a> -->
                            <a href="add-banner.php" class="btn btn-primary">Add Banners</a>
                        </div>
                    </div>

                    <!-- Banner Table Section -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Banner List</h5>
                                    <h6 class="card-subtitle text-muted">All banners are displayed in the table below.</h6>
                                </div>
                                <div class="table-responsive">
                                    <table class="table mb-0">
                                        <thead>
                                            <tr>
                                                <th scope="col">ID</th>
                                                <th scope="col">Title</th>
                                                <th scope="col">Display Order</th>
                                                <th scope="col">Desktop Image</th>
                                                <th scope="col">Mobile Image</th>
                                                <th scope="col">Target URL</th>
                                                <th scope="col">Status</th>
                                                <th scope="col">Start Date</th>
                                                <th scope="col">End Date</th>
                                                <th scope="col">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="bannerTableBody">
                                            <!-- Banners will be dynamically added here -->
                                        </tbody>
                                    </table>
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
    <script src="js/main.js"></script>

    <script>
        // Function to load banners from the API
        async function loadBanners() {
            try {
                const response = await fetch("api/banner_api.php");
                const banners = await response.json();

                let tableBody = document.getElementById("bannerTableBody");
                tableBody.innerHTML = ""; // Clear existing table rows

                banners.forEach(banner => {
                    let row = `
                        <tr>
                            <th scope="row">${banner.id}</th>
                            <td>${banner.title}</td>
                            <td>${banner.display_order}</td>
                            <td><img style='height:100px; width:100px' src="uploads/banners/${banner.image_url_desktop}"></td>
                            <td><img style='height:100px; width:100px' src="uploads/banners/${banner.image_url_mobile}"></td>
                            <td>${banner.target_url}</td>
                            <td>${banner.status}</td>
                            <td>${banner.created_at}</td>
                            <td>${banner.end_date}</td>
                            <td>
                                <a href="edit-banner.php?id=${banner.id}" class="btn btn-sm btn-primary">Edit</a>
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;
                });
            } catch (error) {
                console.error("Error fetching banners:", error);
                alert("Error fetching banners");
            }
        }

        // Load banners when the page loads
        window.onload = loadBanners;
    </script>

</body>

</html>