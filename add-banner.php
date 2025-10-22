<?php
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

    <title>AdminKit Demo - Bootstrap 5 Admin Template</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">

    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <!-- <script src="js/settings.js"></script> -->
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
                            <h3><strong>Banner</strong>  Management</h3>
                        </div>

                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="#" class="btn btn-light bg-white me-2">Invite a Friend</a>
                            <a href="view-banner.php" class="btn btn-primary">View Banners</a>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <!-- <h5 class="card-title">Banner Management</h5> -->
                                    <h6 class="card-subtitle text-muted">Add or edit banner information.</h6>
                                </div>
                                <div class="card-body">
                                    <form id="bannerForm"enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="title">Title</label>
                                                <input type="text" class="form-control" id="title" name="title"
                                                    placeholder="Enter banner title">
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="display_order">Display Order</label>
                                                <input type="number" class="form-control" id="display_order"
                                                    name="display_order" placeholder="Enter display order">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="description">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="3"
                                                placeholder="Enter banner description"></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="image_url_desktop">Desktop Image
                                                    URL</label>
                                                <input type="file" class="form-control" id="image_url_desktop"
                                                    name="image_url_desktop" placeholder="Enter desktop image URL">
                                            </div>
                                            <div class="mb-3 col-md-6">
                                                <label class="form-label" for="image_url_mobile">Mobile Image
                                                    URL</label>
                                                <input type="file" class="form-control" id="image_url_mobile"
                                                    name="image_url_mobile" placeholder="Enter mobile image URL">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="target_url">Target URL</label>
                                            <input type="text" class="form-control" id="target_url" name="target_url"
                                                placeholder="Enter target URL">
                                        </div>

                                        <div class="row">
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="status">Status</label>
                                                <select id="status" name="status" class="form-control">
                                                    <option value="active">Active</option>
                                                    <option value="inactive">Inactive</option>
                                                    <option value="draft">Draft</option>
                                                </select>
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="start_date">Start Date</label>
                                                <input type="date" class="form-control" id="start_date"
                                                    name="start_date">
                                            </div>
                                            <div class="mb-3 col-md-4">
                                                <label class="form-label" for="end_date">End Date</label>
                                                <input type="date" class="form-control" id="end_date" name="end_date">
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary">Submit</button>
                                        <button type="reset" class="btn btn-secondary">Reset</button>
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
    <script src="js/main.js"></script>

    <script>
        document.getElementById("bannerForm").addEventListener("submit", async function(e){
          e.preventDefault();

          const formData = new FormData(this);

          try{
            const response = await fetch("api/banner_api.php", {
                method: "POST",
                body: formData
            });

            const result = await response.json();
            alert(result.message || "Banner Submitted Successfully");
            this.reset;
          }catch(error){
            console.error("Error:", error);
            alert(error);
          }
        })
    </script>

</body>

</html>