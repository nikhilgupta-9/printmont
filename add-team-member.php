<?php
// add-team-member.php
session_start();
require_once 'config/database.php';
require_once 'controllers/AboutUsController.php';

$aboutUsController = new AboutUsController();
$pageTitle = "Add Team Member";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'name' => $_POST['name'],
            'position' => $_POST['position'],
            'bio' => $_POST['bio'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'display_order' => $_POST['display_order'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'social_links' => [
                'linkedin' => $_POST['linkedin'] ?? '',
                'twitter' => $_POST['twitter'] ?? '',
                'facebook' => $_POST['facebook'] ?? '',
                'instagram' => $_POST['instagram'] ?? '',
                'github' => $_POST['github'] ?? '',
                'behance' => $_POST['behance'] ?? '',
                'dribbble' => $_POST['dribbble'] ?? ''
            ],
            'image_path' => null
        ];

        // Handle file upload
        if (!empty($_FILES['image']['name'])) {
            $uploadedPath = $aboutUsController->uploadImage($_FILES['image']);
            if ($uploadedPath) {
                $data['image_path'] = $uploadedPath;
            }
        }

        // Create team member
        if ($aboutUsController->createTeamMember($data)) {
            $_SESSION['success_message'] = "Team member added successfully!";
            header("Location: about-page.php");
            exit;
        } else {
            throw new Exception("Failed to create team member in database.");
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
        .profile-image-container {
            position: relative;
            display: inline-block;
        }
        .profile-image {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        .image-placeholder {
            width: 200px;
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
        .social-input-group {
            margin-bottom: 10px;
        }
        .social-icon {
            width: 30px;
            text-align: center;
            color: #666;
        }
        .image-preview {
            display: none;
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
                                            <!-- Left Column - Basic Info -->
                                            <div class="col-md-8">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="name" class="form-label">Full Name *</label>
                                                            <input type="text" class="form-control" id="name" name="name" 
                                                                   value="<?php echo $_POST['name'] ?? ''; ?>" required>
                                                            <div class="form-text">Enter the full name of the team member.</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="position" class="form-label">Position *</label>
                                                            <input type="text" class="form-control" id="position" name="position" 
                                                                   value="<?php echo $_POST['position'] ?? ''; ?>" required>
                                                            <div class="form-text">e.g., CEO, Creative Director, etc.</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="bio" class="form-label">Bio *</label>
                                                    <textarea class="form-control" id="bio" name="bio" rows="4" 
                                                              required placeholder="Write a brief description about this team member..."><?php echo $_POST['bio'] ?? ''; ?></textarea>
                                                    <div class="form-text">Describe their role, experience, and contributions.</div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="email" class="form-label">Email Address</label>
                                                            <input type="email" class="form-control" id="email" name="email" 
                                                                   value="<?php echo $_POST['email'] ?? ''; ?>" 
                                                                   placeholder="team.member@company.com">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="phone" class="form-label">Phone Number</label>
                                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                                   value="<?php echo $_POST['phone'] ?? ''; ?>" 
                                                                   placeholder="+1 (555) 123-4567">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="display_order" class="form-label">Display Order</label>
                                                            <input type="number" class="form-control" id="display_order" name="display_order" 
                                                                   value="<?php echo $_POST['display_order'] ?? 0; ?>" min="0">
                                                            <div class="form-text">Lower numbers appear first. Start with 0.</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Status</label>
                                                            <div class="form-check mt-2">
                                                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                                                <label class="form-check-label" for="is_active">
                                                                    Active Team Member
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Right Column - Image & Social -->
                                            <div class="col-md-4">
                                                <!-- Profile Image -->
                                                <div class="mb-4">
                                                    <label class="form-label">Profile Image</label>
                                                    <div class="profile-image-container mb-2">
                                                        <div class="image-placeholder" onclick="document.getElementById('image').click()">
                                                            <div class="text-center">
                                                                <i class="fas fa-camera fa-2x text-muted mb-2"></i>
                                                                <div class="text-muted">Click to upload image</div>
                                                            </div>
                                                        </div>
                                                        <img id="imagePreview" class="profile-image image-preview" alt="Preview">
                                                    </div>
                                                    <input type="file" class="form-control d-none" id="image" name="image" accept="image/*">
                                                    <div class="form-text">Recommended: 400x400px, JPG, PNG, or WebP. Max 5MB.</div>
                                                </div>

                                                <!-- Social Media Links -->
                                                <div class="mb-3">
                                                    <label class="form-label">Social Media Links</label>
                                                    
                                                    <div class="social-input-group">
                                                        <div class="input-group">
                                                            <span class="input-group-text social-icon">
                                                                <i class="fab fa-linkedin"></i>
                                                            </span>
                                                            <input type="url" class="form-control" name="linkedin" 
                                                                   placeholder="LinkedIn URL" value="<?php echo $_POST['linkedin'] ?? ''; ?>">
                                                        </div>
                                                    </div>

                                                    <div class="social-input-group">
                                                        <div class="input-group">
                                                            <span class="input-group-text social-icon">
                                                                <i class="fab fa-twitter"></i>
                                                            </span>
                                                            <input type="url" class="form-control" name="twitter" 
                                                                   placeholder="Twitter URL" value="<?php echo $_POST['twitter'] ?? ''; ?>">
                                                        </div>
                                                    </div>

                                                    <div class="social-input-group">
                                                        <div class="input-group">
                                                            <span class="input-group-text social-icon">
                                                                <i class="fab fa-facebook"></i>
                                                            </span>
                                                            <input type="url" class="form-control" name="facebook" 
                                                                   placeholder="Facebook URL" value="<?php echo $_POST['facebook'] ?? ''; ?>">
                                                        </div>
                                                    </div>

                                                    <div class="social-input-group">
                                                        <div class="input-group">
                                                            <span class="input-group-text social-icon">
                                                                <i class="fab fa-instagram"></i>
                                                            </span>
                                                            <input type="url" class="form-control" name="instagram" 
                                                                   placeholder="Instagram URL" value="<?php echo $_POST['instagram'] ?? ''; ?>">
                                                        </div>
                                                    </div>

                                                    <div class="social-input-group">
                                                        <div class="input-group">
                                                            <span class="input-group-text social-icon">
                                                                <i class="fab fa-github"></i>
                                                            </span>
                                                            <input type="url" class="form-control" name="github" 
                                                                   placeholder="GitHub URL" value="<?php echo $_POST['github'] ?? ''; ?>">
                                                        </div>
                                                    </div>

                                                    <div class="social-input-group">
                                                        <div class="input-group">
                                                            <span class="input-group-text social-icon">
                                                                <i class="fab fa-behance"></i>
                                                            </span>
                                                            <input type="url" class="form-control" name="behance" 
                                                                   placeholder="Behance URL" value="<?php echo $_POST['behance'] ?? ''; ?>">
                                                        </div>
                                                    </div>

                                                    <div class="social-input-group">
                                                        <div class="input-group">
                                                            <span class="input-group-text social-icon">
                                                                <i class="fab fa-dribbble"></i>
                                                            </span>
                                                            <input type="url" class="form-control" name="dribbble" 
                                                                   placeholder="Dribbble URL" value="<?php echo $_POST['dribbble'] ?? ''; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-4 pt-3 border-top">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Add Team Member
                                            </button>
                                            <a href="about-page.php" class="btn btn-outline-secondary">
                                                <i class="fas fa-arrow-left"></i> Cancel
                                            </a>
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
    </script>
</body>
</html>