<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/FooterSectionController.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid section ID!";
    header("Location: footer-sections.php");
    exit();
}

$sectionId = (int)$_GET['id'];
$sectionController = new FooterSectionController();
$section = $sectionController->getSectionById($sectionId);

if (!$section) {
    $_SESSION['error_message'] = "Section not found!";
    header("Location: footer-sections.php");
    exit();
}

// Handle form submission
if ($_POST) {
    try {
        $data = [
            'title' => trim($_POST['title']),
            'column_order' => (int)$_POST['column_order'],
            'status' => $_POST['status']
        ];

        if ($sectionController->updateSection($sectionId, $data)) {
            $_SESSION['success_message'] = "Footer section updated successfully!";
            header("Location: footer-sections.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Failed to update footer section.";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    // Refresh section data
    $section = $sectionController->getSectionById($sectionId);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Edit Footer Section | Printmont</title>
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
                            <h3><strong>Edit</strong> Footer Section</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="footer-sections.php" class="btn btn-secondary">Back to Sections</a>
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
                                    <h5 class="card-title">Edit Section</h5>
                                    <h6 class="card-subtitle text-muted">Update footer section information.</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="sectionForm">
                                        <div class="mb-3">
                                            <label for="title" class="form-label">Section Title *</label>
                                            <input type="text" class="form-control" id="title" name="title" 
                                                   value="<?php echo htmlspecialchars($section['title']); ?>" 
                                                   required maxlength="100">
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="column_order" class="form-label">Column Order *</label>
                                                    <input type="number" class="form-control" id="column_order" name="column_order" 
                                                           value="<?php echo $section['column_order']; ?>" 
                                                           min="0" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="status" class="form-label">Status *</label>
                                                    <select name="status" id="status" class="form-control" required>
                                                        <option value="active" <?php echo $section['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                        <option value="inactive" <?php echo $section['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Section Metadata -->
                                        <div class="card bg-light mb-4">
                                            <div class="card-body">
                                                <h6 class="card-title">Section Information</h6>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <small class="text-muted">Created:</small><br>
                                                        <strong><?php echo date('M j, Y g:i A', strtotime($section['created_at'])); ?></strong>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <small class="text-muted">Last Updated:</small><br>
                                                        <strong><?php echo date('M j, Y g:i A', strtotime($section['updated_at'])); ?></strong>
                                                    </div>
                                                </div>
                                                <div class="row mt-2">
                                                    <div class="col-12">
                                                        <small class="text-muted">Section ID:</small><br>
                                                        <strong>#<?php echo $section['id']; ?></strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">Update Section</button>
                                            <a href="footer-sections.php" class="btn btn-secondary">Cancel</a>
                                            <a href="delete-footer-section.php?id=<?php echo $sectionId; ?>" 
                                               class="btn btn-outline-danger ms-auto" 
                                               onclick="return confirm('Are you sure you want to delete this section? All links in this section will also be deleted.')">
                                                Delete Section
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12 col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Editing Tips</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info p-2">
                                        <h6>Column Order</h6>
                                        <p class="mb-2">• Changing order affects layout</p>
                                        <p class="mb-0">• Sections reorder automatically</p>
                                    </div>
                                    
                                    <div class="alert alert-warning p-2">
                                        <h6>Status Change</h6>
                                        <p class="mb-2">• Inactive sections are hidden</p>
                                        <p class="mb-0">• Links in inactive sections are also hidden</p>
                                    </div>

                                    <div class="alert alert-danger p-2">
                                        <h6>Deletion Warning</h6>
                                        <p class="mb-2">• Deleting a section removes all its links</p>
                                        <p class="mb-0">• This action cannot be undone</p>
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