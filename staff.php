<?php
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/LogoController.php';

// Initialize Logo Controller
$database = new Database();
$db = $database->getConnection();

// Dummy static record (You will replace with DB loop later)
$customers = [
    [
        "image" => "uploads/users/user1.png",
        "name" => "Amit Kumar",
        "email" => "test123@gmail.com",
        "number" => "1234565432",
        "joined" => "2024-01-10",
        "orders" => 14,
        "address" => "Delhi, India",
        "active_order" => "Yes"
    ]
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Staff</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        .User-image {
            width: 85px;
            height: 85px;
            object-fit: cover;
            border-radius: 4px;
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
                            <h3><strong>Staff</strong> Management</h3>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">

                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">All Staff</h5>
                                    <h6 class="card-subtitle text-muted">Manage your customers.</h6>
                                </div>

                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Image</th>
                                                    <th>Customer Name</th>
                                                    <th>Email</th>
                                                    <th>Number</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>

                                                <?php foreach ($customers as $c): ?>
                                                    <tr>
                                                        <td>
                                                            <img class="User-image" src="<?= $c['image'] ?>" alt="">
                                                        </td>

                                                        <td><?= $c['name'] ?></td>
                                                        <td><?= $c['email'] ?></td>
                                                        <td><?= $c['number'] ?></td>

                                                        <td>
                                                            <button 
                                                                class="btn btn-primary viewDetailsBtn"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#customerDetailsModal"
                                                                data-name="<?= $c['name'] ?>"
                                                                data-email="<?= $c['email'] ?>"
                                                                data-number="<?= $c['number'] ?>"
                                                                data-image="<?= $c['image'] ?>"
                                                                data-joined="<?= $c['joined'] ?>"
                                                                data-orders="<?= $c['orders'] ?>"
                                                                data-address="<?= $c['address'] ?>"
                                                                data-active-order="<?= $c['active_order'] ?>"
                                                            >
                                                                View Details
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>

                                            </tbody>
                                        </table>
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

    <!-- CUSTOMER DETAILS MODAL -->
    <div class="modal fade" id="customerDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Staff Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="card p-3">

                        <div class="row">
                            <div class="col-md-4 text-center">
                                <img id="cd-image"
                                    class="img-fluid rounded"
                                    style="width:160px;height:160px;object-fit:cover;">
                            </div>

                            <div class="col-md-8">
                                <h4><strong>Name:</strong> <span id="cd-name"></span></h4>
                                <p><strong>Email:</strong> <span id="cd-email"></span></p>
                                <p><strong>Mobile:</strong> <span id="cd-number"></span></p>
                                <p><strong>Joined On:</strong> <span id="cd-joined"></span></p>
                            </div>
                        </div>

                    </div>

                </div>

            </div>
        </div>
    </div>

    <script src="js/app.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {

            const buttons = document.querySelectorAll(".viewDetailsBtn");

            buttons.forEach(btn => {
                btn.addEventListener("click", function () {

                    document.getElementById("cd-name").innerText = this.dataset.name;
                    document.getElementById("cd-email").innerText = this.dataset.email;
                    document.getElementById("cd-number").innerText = this.dataset.number;
                    document.getElementById("cd-joined").innerText = this.dataset.joined;
                    document.getElementById("cd-orders").innerText = this.dataset.orders;
                    document.getElementById("cd-address").innerText = this.dataset.address;
                    document.getElementById("cd-active").innerText = this.dataset.activeOrder;

                    document.getElementById("cd-image").src = this.dataset.image;
                });
            });

        });
    </script>

</body>
</html>
