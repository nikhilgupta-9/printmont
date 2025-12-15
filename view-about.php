<?php
// frontend/about-us.php
require_once '../config/constants.php';
require_once '../controllers/AboutUsController.php';

$aboutUsController = new AboutUsController();
$sections = $aboutUsController->getAllSections();
$teamMembers = $aboutUsController->getAllTeamMembers();

// Filter active content only
$activeSections = array_filter($sections, fn($s) => $s['is_active']);
$activeTeamMembers = array_filter($teamMembers, fn($t) => $t['is_active']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | Printmont</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation would be included here -->

    <div class="container my-5">
        <?php foreach ($activeSections as $section): ?>
            <div class="row mb-5 align-items-center">
                <?php if ($section['section_type'] === 'hero'): ?>
                    <!-- Hero Section -->
                    <div class="col-12 text-center">
                        <?php if ($section['image_path']): ?>
                            <img src="<?php echo $section['image_path']; ?>" alt="<?php echo htmlspecialchars($section['section_title']); ?>" class="img-fluid mb-4" style="max-height: 400px;">
                        <?php endif; ?>
                        <h1 class="display-4"><?php echo htmlspecialchars($section['section_title']); ?></h1>
                        <p class="lead"><?php echo nl2br(htmlspecialchars($section['section_content'])); ?></p>
                    </div>
                <?php else: ?>
                    <!-- Regular Sections -->
                    <div class="col-md-6">
                        <h2><?php echo htmlspecialchars($section['section_title']); ?></h2>
                        <p><?php echo nl2br(htmlspecialchars($section['section_content'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <?php if ($section['image_path']): ?>
                            <img src="<?php echo $section['image_path']; ?>" alt="<?php echo htmlspecialchars($section['section_title']); ?>" class="img-fluid rounded">
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- Team Section -->
        <?php if (!empty($activeTeamMembers)): ?>
            <div class="row mb-5">
                <div class="col-12 text-center mb-4">
                    <h2>Our Team</h2>
                    <p class="lead">Meet the talented people behind Printmont</p>
                </div>
                <?php foreach ($activeTeamMembers as $member): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 text-center">
                            <?php if ($member['image_path']): ?>
                                <img src="<?php echo $member['image_path']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($member['name']); ?>" style="height: 300px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($member['name']); ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($member['position']); ?></h6>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($member['bio'])); ?></p>
                                <?php if ($member['email']): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($member['email']); ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-envelope"></i> Email
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>