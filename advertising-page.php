<?php
/**
 * Advertising page — content editor.
 *
 * Managed via includes/page-editor.php and config/page-editor-schema.php.
 */

require_once 'config/constants.php';

$pageKey = 'advertising';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <link rel="icon" type="image/png" href="favicon.png" />
    <title>Advertising Page Management | Printmont</title>
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
