<?php
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/ProductController.php';

// header('Content-Type: application/json');

$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// Simple routing

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Responsive Admin &amp; Dashboard Template based on Bootstrap 5">
    <meta name="author" content="Nikhil">

    <link rel="preconnect" href="https://fonts.gstatic.com/">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Add Products | Printmont</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">

    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <script src="js/settings.js"></script>
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
                            <h3><strong>Analytics</strong> Dashboard</h3>
                        </div>

                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="#" class="btn btn-light bg-white me-2">Invite a Friend</a>
                            <a href="#" class="btn btn-primary">New Project</a>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xl-6 col-xxl-5 d-flex">
                            <div class="w-100">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col mt-0">
                                                        <h5 class="card-title">Sales</h5>
                                                    </div>

                                                    <div class="col-auto">
                                                        <div class="stat text-primary">
                                                            <i class="align-middle" data-feather="truck"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                                <h1 class="mt-1 mb-3">2.382</h1>
                                                <div class="mb-0">
                                                    <span class="badge badge-primary-light">-3.65%</span>
                                                    <span class="text-muted">Since last week</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col mt-0">
                                                        <h5 class="card-title">Visitors</h5>
                                                    </div>

                                                    <div class="col-auto">
                                                        <div class="stat text-primary">
                                                            <i class="align-middle" data-feather="users"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                                <h1 class="mt-1 mb-3">14.212</h1>
                                                <div class="mb-0">
                                                    <span class="badge badge-success-light">5.25%</span>
                                                    <span class="text-muted">Since last week</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col mt-0">
                                                        <h5 class="card-title">Earnings</h5>
                                                    </div>

                                                    <div class="col-auto">
                                                        <div class="stat text-primary">
                                                            <i class="align-middle" data-feather="dollar-sign"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                                <h1 class="mt-1 mb-3">$21.300</h1>
                                                <div class="mb-0">
                                                    <span class="badge badge-success-light">6.65%</span>
                                                    <span class="text-muted">Since last week</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col mt-0">
                                                        <h5 class="card-title">Orders</h5>
                                                    </div>

                                                    <div class="col-auto">
                                                        <div class="stat text-primary">
                                                            <i class="align-middle" data-feather="shopping-cart"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                                <h1 class="mt-1 mb-3">64</h1>
                                                <div class="mb-0">
                                                    <span class="badge badge-danger-light">-2.25%</span>
                                                    <span class="text-muted">Since last week</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-6 col-xxl-7">
                            <div class="card flex-fill w-100">
                                <div class="card-header">
                                    <div class="float-end">
                                        <form class="row g-2">
                                            <div class="col-auto">
                                                <select class="form-select form-select-sm bg-light border-0">
                                                    <option>Jan</option>
                                                    <option value="1">Feb</option>
                                                    <option value="2">Mar</option>
                                                    <option value="3">Apr</option>
                                                </select>
                                            </div>
                                            <div class="col-auto">
                                                <input type="text"
                                                    class="form-control form-control-sm bg-light rounded-2 border-0"
                                                    style="width: 100px;" placeholder="Search..">
                                            </div>
                                        </form>
                                    </div>
                                    <h5 class="card-title mb-0">Recent Movement</h5>
                                </div>
                                <div class="card-body pt-2 pb-3">
                                    <div class="chart chart-sm">
                                        <canvas id="chartjs-dashboard-line"></canvas>
                                    </div>
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

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var date = new Date(Date.now() - 5 * 24 * 60 * 60 * 1000);
            var defaultDate = date.getUTCFullYear() + "-" + (date.getUTCMonth() + 1) + "-" + date.getUTCDate();
            document.getElementById("datetimepicker-dashboard").flatpickr({
                inline: true,
                prevArrow: "<span class=\"fas fa-chevron-left\" title=\"Previous month\"></span>",
                nextArrow: "<span class=\"fas fa-chevron-right\" title=\"Next month\"></span>",
                defaultDate: defaultDate
            });
        });
    </script>

</body>

</html>