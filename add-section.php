<?php
// add-section.php
session_start();
require_once 'config/database.php';
require_once 'controllers/AboutUsController.php';

$aboutUsController = new AboutUsController();
$pageTitle = "Add Section";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'section_title' => $_POST['section_title'],
            'section_content' => $_POST['section_content'],
            'section_type' => $_POST['section_type'],
            'display_order' => $_POST['display_order'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'image_path' => null
        ];

        // Handle file upload
        if (!empty($_FILES['image']['name'])) {
            $uploadedPath = $aboutUsController->uploadImage($_FILES['image']);
            if ($uploadedPath) {
                $data['image_path'] = $uploadedPath;
            }
        }

        // Create section
        if ($aboutUsController->createSection($data)) {
            $_SESSION['success_message'] = "Section added successfully!";
            header("Location: about-page.php");
            exit;
        } else {
            throw new Exception("Failed to create section in database.");
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
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
    <style>
        .image-placeholder {
            width: 100%;
            height: 200px;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
        }
        .image-placeholder:hover {
            border-color: #007bff;
            background: #e7f3ff;
        }
        .image-preview {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            display: none;
        }
        .section-type-info {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            margin-top: 10px;
        }
        .section-type-item {
            margin-bottom: 8px;
            padding-left: 10px;
            border-left: 3px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <!-- Display Messages -->
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['error_message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><?php echo $pageTitle; ?></h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <!-- Left Column - Content -->
                                            <div class="col-md-8">
                                                <div class="mb-3">
                                                    <label for="section_title" class="form-label">Section Title *</label>
                                                    <input type="text" class="form-control" id="section_title" name="section_title" 
                                                           value="<?php echo $_POST['section_title'] ?? ''; ?>" required
                                                           placeholder="Enter a compelling title for this section">
                                                    <div class="form-text">This will be the main heading of your section.</div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="section_content" class="form-label">Section Content *</label>
                                                    <textarea class="form-control" id="section_content" name="section_content" 
                                                              rows="8" required placeholder="Write the content for this section..."><?php echo $_POST['section_content'] ?? ''; ?></textarea>
                                                    <div class="form-text">You can use multiple paragraphs. Line breaks will be preserved.</div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="section_type" class="form-label">Section Type *</label>
                                                            <select class="form-select" id="section_type" name="section_type" required>
                                                                <option value="">Select a section type...</option>
                                                                <option value="hero" <?php echo ($_POST['section_type'] ?? '') == 'hero' ? 'selected' : ''; ?>>Hero Section</option>
                                                                <option value="mission" <?php echo ($_POST['section_type'] ?? '') == 'mission' ? 'selected' : ''; ?>>Mission Section</option>
                                                                <option value="team" <?php echo ($_POST['section_type'] ?? '') == 'team' ? 'selected' : ''; ?>>Team Section</option>
                                                                <option value="values" <?php echo ($_POST['section_type'] ?? '') == 'values' ? 'selected' : ''; ?>>Values Section</option>
                                                                <option value="history" <?php echo ($_POST['section_type'] ?? '') == 'history' ? 'selected' : ''; ?>>History Section</option>
                                                            </select>
                                                            <div class="form-text">Choose the purpose of this section.</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="display_order" class="form-label">Display Order</label>
                                                            <input type="number" class="form-control" id="display_order" name="display_order" 
                                                                   value="<?php echo $_POST['display_order'] ?? 0; ?>" min="0">
                                                            <div class="form-text">Lower numbers appear first. Start with 0.</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                                        <label class="form-check-label" for="is_active">
                                                            Active Section
                                                        </label>
                                                    </div>
                                                    <div class="form-text">Uncheck to hide this section from the public page.</div>
                                                </div>
                                            </div>

                                            <!-- Right Column - Image & Info -->
                                            <div class="col-md-4">
                                                <!-- Section Image -->
                                                <div class="mb-4">
                                                    <label class="form-label">Section Image</label>
                                                    <div class="mb-2">
                                                        <div class="image-placeholder" onclick="document.getElementById('image').click()">
                                                            <div class="text-center">
                                                                <i class="fas fa-image fa-2x text-muted mb-2"></i>
                                                                <div class="text-muted">Click to upload image</div>
                                                            </div>
                                                        </div>
                                                        <img id="imagePreview" class="image-preview" alt="Preview">
                                                    </div>
                                                    <input type="file" class="form-control d-none" id="image" name="image" accept="image/*">
                                                    <div class="form-text">Optional. Recommended: 16:9 ratio, JPG, PNG, or WebP. Max 5MB.</div>
                                                </div>

                                                <!-- Section Type Information -->
                                                <div class="section-type-info">
                                                    <h6 class="mb-3">Section Type Guide:</h6>
                                                    
                                                    <div class="section-type-item">
                                                        <strong>Hero Section</strong>
                                                        <div class="text-muted small">Main banner section, usually at the top</div>
                                                    </div>
                                                    
                                                    <div class="section-type-item">
                                                        <strong>Mission Section</strong>
                                                        <div class="text-muted small">Company mission, vision, and values</div>
                                                    </div>
                                                    
                                                    <div class="section-type-item">
                                                        <strong>Team Section</strong>
                                                        <div class="text-muted small">Introduction to team members</div>
                                                    </div>
                                                    
                                                    <div class="section-type-item">
                                                        <strong>Values Section</strong>
                                                        <div class="text-muted small">Company values and principles</div>
                                                    </div>
                                                    
                                                    <div class="section-type-item">
                                                        <strong>History Section</strong>
                                                        <div class="text-muted small">Company story and milestones</div>
                                                    </div>
                                                </div>

                                                <!-- Quick Tips -->
                                                <div class="mt-3 p-3 bg-light rounded">
                                                    <h6 class="mb-2">Quick Tips:</h6>
                                                    <ul class="small text-muted mb-0">
                                                        <li>Keep titles clear and engaging</li>
                                                        <li>Use high-quality images</li>
                                                        <li>Organize sections logically</li>
                                                        <li>Preview on mobile devices</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-4 pt-3 border-top">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Add Section
                                            </button>
                                            <a href="about-page.php" class="btn btn-outline-secondary">
                                                <i class="fas fa-arrow-left"></i> Cancel
                                            </a>
                                            
                                            <button type="button" class="btn btn-outline-info" onclick="previewContent()">
                                                <i class="fas fa-eye"></i> Preview Content
                                            </button>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    
    <script>
        // Image preview functionality
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const placeholder = document.querySelector('.image-placeholder');
            const preview = document.getElementById('imagePreview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    placeholder.style.display = 'none';
                }
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
                placeholder.style.display = 'flex';
            }
        });

        // Click on placeholder to trigger file input
        document.querySelector('.image-placeholder').addEventListener('click', function() {
            document.getElementById('image').click();
        });

        // Preview content functionality
        function previewContent() {
            const title = document.getElementById('section_title').value || '[No Title]';
            const content = document.getElementById('section_content').value || '[No Content]';
            const type = document.getElementById('section_type').value || 'Not specified';
            
            const preview = `
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Content Preview</h6>
                    </div>
                    <div class="card-body">
                        <h4>${title}</h4>
                        <p class="text-muted">Section Type: <span class="badge bg-primary">${type}</span></p>
                        <div style="white-space: pre-line; line-height: 1.6;">${content}</div>
                    </div>
                </div>
            `;
            
            // Create modal for preview
            const modal = document.createElement('div');
            modal.className = 'modal fade show';
            modal.style.display = 'block';
            modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Content Preview</h5>
                            <button type="button" class="btn-close" onclick="this.closest('.modal').remove()"></button>
                        </div>
                        <div class="modal-body">
                            ${preview}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()">Close</button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
        }

        // Auto-resize textarea
        document.getElementById('section_content').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    </script>
</body>
</html>