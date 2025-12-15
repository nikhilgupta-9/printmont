<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/BlogController.php';

$blogController = new BlogController();
// You would fetch tags from database

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Blog Tags | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <div class="row mb-2 mb-xl-3">
                        <div class="col-auto d-none d-sm-block">
                            <h3><strong>Blog</strong> Tags</h3>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <p>Tags management page - to be implemented</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>