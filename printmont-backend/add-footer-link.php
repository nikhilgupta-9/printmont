<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/FooterLinkController.php';
require_once 'controllers/FooterSectionController.php';

$linkController = new FooterLinkController();
$sectionController = new FooterSectionController();
$sections = $sectionController->getAllSections();

// Get section_id from URL if provided
$preselected_section = isset($_GET['section_id']) ? (int)$_GET['section_id'] : null;

// Handle form submission
if ($_POST) {
    try {
        $data = [
            'section_id' => (int)$_POST['section_id'],
            'title' => trim($_POST['title']),
            'url' => trim($_POST['url']),
            'link_order' => (int)$_POST['link_order'],
            'status' => $_POST['status']
        ];

        if ($linkController->createLink($data)) {
            $_SESSION['success_message'] = "Footer link created successfully!";
            header("Location: footer-links.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Failed to create footer link.";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Add Footer Link | Printmont</title>
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
                            <h3><strong>Add</strong> Footer Link</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="footer-links.php" class="btn btn-secondary">Back to Links</a>
                        </div>
                    </div>

                    <!-- Messages -->
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12 col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Link Information</h5>
                                    <h6 class="card-subtitle text-muted">Add a new link to your footer.</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="linkForm">
                                        <div class="mb-3">
                                            <label for="section_id" class="form-label">Section *</label>
                                            <select name="section_id" id="section_id" class="form-control" required>
                                                <option value="">Select a section...</option>
                                                <?php foreach ($sections as $section): ?>
                                                    <option value="<?php echo $section['id']; ?>" 
                                                        <?php echo ($preselected_section == $section['id'] || (isset($_POST['section_id']) && $_POST['section_id'] == $section['id'])) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($section['title']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="title" class="form-label">Link Title *</label>
                                            <input type="text" class="form-control" id="title" name="title" 
                                                   value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                                                   required maxlength="255">
                                            <small class="form-text text-muted">e.g., Contact Us, Privacy Policy, etc.</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="url" class="form-label">URL *</label>
                                            <input type="url" class="form-control" id="url" name="url" 
                                                   value="<?php echo isset($_POST['url']) ? htmlspecialchars($_POST['url']) : ''; ?>" 
                                                   placeholder="https://example.com/page" required>
                                            <small class="form-text text-muted">Full URL including http:// or https://</small>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="link_order" class="form-label">Link Order *</label>
                                                    <input type="number" class="form-control" id="link_order" name="link_order" 
                                                           value="<?php echo isset($_POST['link_order']) ? (int)$_POST['link_order'] : 0; ?>" 
                                                           min="0" required>
                                                    <small class="form-text text-muted">Lower numbers appear first in the section</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="status" class="form-label">Status *</label>
                                                    <select name="status" id="status" class="form-control" required>
                                                        <option value="active" selected>Active</option>
                                                        <option value="inactive">Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">Create Link</button>
                                            <a href="footer-links.php" class="btn btn-secondary">Cancel</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12 col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Quick Tips</h5>
                                </div>
                                <div class="card-body">

                                    <!-- Section Selection -->
                                    <div class="alert alert-info p-2">
                                        <h6 class="mb-2">Section Selection</h6>
                                        <ul class="mb-0 ps-3">
                                            <li>Choose the appropriate section</li>
                                            <li>Links are grouped by section</li>
                                            <li>Each section has its own column</li>
                                        </ul>
                                    </div>

                                    <!-- URL Guidelines -->
                                    <div class="alert alert-warning p-2">
                                        <h6 class="mb-2">URL Guidelines</h6>
                                        <ul class="mb-0 ps-3">
                                            <li>Use absolute URLs for external links</li>
                                            <li>Use relative URLs for internal pages</li>
                                            <li>Always include http:// or https://</li>
                                        </ul>
                                    </div>

                                    <!-- Link Order -->
                                    <div class="alert alert-success p-2">
                                        <h6 class="mb-2">Link Order</h6>
                                        <ul class="mb-0 ps-3">
                                            <li>Order 0: Top of the section</li>
                                            <li>Order 1: Second link, etc.</li>
                                            <li>Links sort automatically by order</li>
                                        </ul>
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

    <script src="js/app.js"></script>
</body>
</html>