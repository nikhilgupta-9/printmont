<?php
// add-section.php / edit-section.php
session_start();
require_once 'config/constants.php';
require_once 'controllers/AboutUsController.php';

$aboutUsController = new AboutUsController();
$section = null;
$pageTitle = "Add Section";

// For edit mode
if (isset($_GET['id'])) {
    $section = $aboutUsController->getSectionById($_GET['id']);
    if (!$section) {
        $_SESSION['error_message'] = "Section not found!";
        header("Location: about-page.php");
        exit;
    }
    $pageTitle = "Edit Section";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'section_title' => $_POST['section_title'],
        'section_content' => $_POST['section_content'],
        'section_type' => $_POST['section_type'],
        'display_order' => $_POST['display_order'],
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'image_path' => $section['image_path'] ?? null
    ];

    // Handle file upload
    if (!empty($_FILES['image']['name'])) {
        $uploadedPath = $aboutUsController->uploadImage($_FILES['image']);
        if ($uploadedPath) {
            $data['image_path'] = $uploadedPath;
        }
    }

    if (isset($_POST['id'])) {
        // Update existing section
        if ($aboutUsController->updateSection($_POST['id'], $data)) {
            $_SESSION['success_message'] = "Section updated successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to update section!";
        }
    } else {
        // Create new section
        if ($aboutUsController->createSection($data)) {
            $_SESSION['success_message'] = "Section created successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to create section!";
        }
    }
    
    header("Location: about-page.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo $pageTitle; ?> | Printmont</title>
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
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><?php echo $pageTitle; ?></h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" enctype="multipart/form-data">
                                        <?php if ($section): ?>
                                            <input type="hidden" name="id" value="<?php echo $section['id']; ?>">
                                        <?php endif; ?>
                                        
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="mb-3">
                                                    <label for="section_title" class="form-label">Section Title</label>
                                                    <input type="text" class="form-control" id="section_title" name="section_title" 
                                                           value="<?php echo $section['section_title'] ?? ''; ?>" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="section_content" class="form-label">Section Content</label>
                                                    <textarea class="form-control" id="section_content" name="section_content" 
                                                              rows="6" required><?php echo $section['section_content'] ?? ''; ?></textarea>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="section_type" class="form-label">Section Type</label>
                                                            <select class="form-select" id="section_type" name="section_type" required>
                                                                <option value="hero" <?php echo ($section['section_type'] ?? '') == 'hero' ? 'selected' : ''; ?>>Hero</option>
                                                                <option value="mission" <?php echo ($section['section_type'] ?? '') == 'mission' ? 'selected' : ''; ?>>Mission</option>
                                                                <option value="team" <?php echo ($section['section_type'] ?? '') == 'team' ? 'selected' : ''; ?>>Team</option>
                                                                <option value="values" <?php echo ($section['section_type'] ?? '') == 'values' ? 'selected' : ''; ?>>Values</option>
                                                                <option value="history" <?php echo ($section['section_type'] ?? '') == 'history' ? 'selected' : ''; ?>>History</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="display_order" class="form-label">Display Order</label>
                                                            <input type="number" class="form-control" id="display_order" name="display_order" 
                                                                   value="<?php echo $section['display_order'] ?? 0; ?>" min="0">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                                               <?php echo ($section['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="is_active">
                                                            Active Section
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="image" class="form-label">Section Image</label>
                                                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                                    <?php if ($section && $section['image_path']): ?>
                                                        <div class="mt-2">
                                                            <img src="<?php echo $section['image_path']; ?>" alt="Section Image" class="img-thumbnail" style="max-height: 200px;">
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-4">
                                            <button type="submit" class="btn btn-primary"><?php echo $section ? 'Update' : 'Create'; ?> Section</button>
                                            <a href="about-page.php" class="btn btn-outline-secondary">Cancel</a>
                                        </div>
                                    </form>
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
</body>
</html>