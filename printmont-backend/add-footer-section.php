<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/FooterSectionController.php';

$sectionController = new FooterSectionController();

// Handle form submission
if ($_POST) {
    try {
        $data = [
            'title' => trim($_POST['title']),
            'column_order' => (int) $_POST['column_order'],
            'status' => $_POST['status']
        ];

        if ($sectionController->createSection($data)) {
            $_SESSION['success_message'] = "Footer section created successfully!";
            header("Location: footer-sections.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Failed to create footer section.";
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
    <title>Add Footer Section | Printmont</title>
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
                            <h3><strong>Add</strong> Footer Section</h3>
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
                                    <h5 class="card-title">Section Information</h5>
                                    <h6 class="card-subtitle text-muted">Add a new footer section.</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="sectionForm">
                                        <div class="mb-3">
                                            <label for="title" class="form-label">Section Title *</label>
                                            <input type="text" class="form-control" id="title" name="title"
                                                value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                                                required maxlength="100">
                                            <small class="form-text text-muted">e.g., OUR COMPANY, POLICY INFO,
                                                etc.</small>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="column_order" class="form-label">Column Order *</label>
                                                    <input type="number" class="form-control" id="column_order"
                                                        name="column_order"
                                                        value="<?php echo isset($_POST['column_order']) ? (int) $_POST['column_order'] : 0; ?>"
                                                        min="0" required>
                                                    <small class="form-text text-muted">Lower numbers appear first (left
                                                        to right)</small>
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
                                            <button type="submit" class="btn btn-primary">Create Section</button>
                                            <a href="footer-sections.php" class="btn btn-secondary">Cancel</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="card shadow-sm border-0 rounded-3">
                                <div class="card-header text-dark rounded-top">
                                    <h5 class="card-title mb-0"><i class="bi bi-lightbulb"></i> Quick Tips</h5>
                                </div>

                                <div class="card-body">

                                    <!-- Section Titles -->
                                    <div class="alert alert-info d-flex align-items-start p-2">
                                        <i class="bi bi-fonts fs-3 me-3"></i>
                                        <div>
                                            <h6 class="fw-bold mb-1">Section Titles</h6>
                                            <ul class="mb-0 ps-3">
                                                <li>Use clear, descriptive titles</li>
                                                <li>Keep titles short and concise</li>
                                                <li>Common sections: OUR COMPANY, POLICY INFO, etc.</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- Column Order -->
                                    <div class="alert alert-warning d-flex align-items-start p-2">
                                        <i class="bi bi-list-ol fs-3 me-3"></i>
                                        <div>
                                            <h6 class="fw-bold mb-1">Column Order</h6>
                                            <ul class="mb-0 ps-3">
                                                <li>Order 1: Leftmost column</li>
                                                <li>Order 2: Second column</li>
                                                <li>Order 3: Third column, etc.</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div class="alert alert-success d-flex align-items-start p-2">
                                        <i class="bi bi-check-circle fs-3 me-3"></i>
                                        <div>
                                            <h6 class="fw-bold mb-1">Status</h6>
                                            <ul class="mb-0 ps-3">
                                                <li>Active: Section is visible</li>
                                                <li>Inactive: Section is hidden</li>
                                            </ul>
                                        </div>
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