<?php
/**
 * Business Solutions — page content.
 *
 * Split out of affiliate-page.php so each website page has its own admin
 * screen. See includes/page-editor.php.
 */

require_once 'config/constants.php';

$pageKey = 'business-solutions';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Business Solutions | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
</head>

<body>
<div class="wrapper">
    <?php include_once 'includes/side-navbar.php'; ?>

    <div class="main">
        <?php include_once 'includes/top-navbar.php'; ?>

        <main class="content">
            <div class="container-fluid p-0">
                <?php include 'includes/page-editor.php'; ?>
            </div>
        </main>

        <?php include_once 'includes/footer.php'; ?>
    </div>
</div>

<script src="js/app.js"></script>
</body>

</html>
