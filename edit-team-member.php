<?php
// edit-team-member.php
session_start();
require_once 'config/database.php';
require_once 'controllers/AboutUsController.php';

$aboutUsController = new AboutUsController();
$teamMember = null;
$pageTitle = "Edit Team Member";

// Get team member data
if (isset($_GET['id'])) {
    try {
        $teamMember = $aboutUsController->getTeamMemberById($_GET['id']);
        if (!$teamMember) {
            $_SESSION['error_message'] = "Team member not found!";
            header("Location: about-page.php");
            exit;
        }
        
        // Decode social links if they exist
        if ($teamMember['social_links']) {
            $teamMember['social_links'] = json_decode($teamMember['social_links'], true);
        } else {
            $teamMember['social_links'] = [
                'linkedin' => '',
                'twitter' => '',
                'facebook' => '',
                'instagram' => '',
                'github' => '',
                'behance' => '',
                'dribbble' => ''
            ];
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error loading team member: " . $e->getMessage();
        header("Location: about-page.php");
        exit;
    }
} else {
    $_SESSION['error_message'] = "No team member ID specified!";
    header("Location: about-page.php");
    exit;
}

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
            'image_path' => $teamMember['image_path'] // Keep existing image by default
        ];

        // Handle file upload
        if (!empty($_FILES['image']['name'])) {
            $uploadedPath = $aboutUsController->uploadImage($_FILES['image']);
            if ($uploadedPath) {
                $data['image_path'] = $uploadedPath;
                // Optionally delete old image file
                if ($teamMember['image_path'] && file_exists($teamMember['image_path'])) {
                    unlink($teamMember['image_path']);
                }
            }
        }

        // Update team member
        if ($aboutUsController->updateTeamMember($_POST['id'], $data)) {
            $_SESSION['success_message'] = "Team member updated successfully!";
            header("Location: about-page.php");
            exit;
        } else {
            throw new Exception("Failed to update team member in database.");
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
        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
            border-radius: 8px;
        }
        .profile-image-container:hover .image-overlay {
            opacity: 1;
        }
        .social-input-group {
            margin-bottom: 10px;
        }
        .social-icon {
            width: 30px;
            text-align: center;
            color: #666;
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
                                        <input type="hidden" name="id" value="<?php echo $teamMember['id']; ?>">
                                        
                                        <div class="row">
                                            <!-- Left Column - Basic Info -->
                                            <div class="col-md-8">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="name" class="form-label">Full Name *</label>
                                                            <input type="text" class="form-control" id="name" name="name" 
                                                                   value="<?php echo htmlspecialchars($teamMember['name']); ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="position" class="form-label">Position *</label>
                                                            <input type="text" class="form-control" id="position" name="position" 
                                                                   value="<?php echo htmlspecialchars($teamMember['position']); ?>" required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="bio" class="form-label">Bio *</label>
                                                    <textarea class="form-control" id="bio" name="bio" rows="4" 
                                                              required><?php echo htmlspecialchars($teamMember['bio']); ?></textarea>
                                                    <div class="form-text">Write a brief description about this team member.</div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="email" class="form-label">Email Address</label>
                                                            <input type="email" class="form-control" id="email" name="email" 
                                                                   value="<?php echo htmlspecialchars($teamMember['email']); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="phone" class="form-label">Phone Number</label>
                                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                                   value="<?php echo htmlspecialchars($teamMember['phone']); ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="display_order" class="form-label">Display Order</label>
                                                            <input type="number" class="form-control" id="display_order" name="display_order" 
                                                                   value="<?php echo $teamMember['display_order']; ?>" min="0">
                                                            <div class="form-text">Lower numbers appear first.</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Status</label>
                                                            <div class="form-check mt-2">
                                                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                                                       <?php echo $teamMember['is_active'] ? 'checked' : ''; ?>>
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
                                                        <?php if ($teamMember['image_path']): ?>
                                                            <img src="<?php echo $teamMember['image_path']; ?>" 
                                                                 alt="<?php echo htmlspecialchars($teamMember['name']); ?>" 
                                                                 class="profile-image">
                                                            <div class="image-overlay">
                                                                <span class="text-white">Change Image</span>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="border rounded d-flex align-items-center justify-content-center" 
                                                                 style="width: 200px; height: 200px; background: #f8f9fa;">
                                                                <span class="text-muted">No Image</span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                                    <div class="form-text">Recommended: 400x400px, JPG, PNG, or WebP</div>
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
                                                                   placeholder="LinkedIn URL" 
                                                                   value="<?php echo htmlspecialchars($teamMember['social_links']['linkedin']); ?>">
                                                        </div>
                                                    </div>

                                                    <div class="social-input-group">
                                                        <div class="input-group">
                                                            <span class="input-group-text social-icon">
                                                                <i class="fab fa-twitter"></i>
                                                            </span>
                                                            <input type="url" class="form-control" name="twitter" 
                                                                   placeholder="Twitter URL" 
                                                                   value="<?php echo htmlspecialchars($teamMember['social_links']['twitter']); ?>">
                                                        </div>
                                                    </div>

                                                    <div class="social-input-group">
                                                        <div class="input-group">
                                                            <span class="input-group-text social-icon">
                                                                <i class="fab fa-facebook"></i>
                                                            </span>
                                                            <input type="url" class="form-control" name="facebook" 
                                                                   placeholder="Facebook URL" 
                                                                   value="<?php echo htmlspecialchars($teamMember['social_links']['facebook']); ?>">
                                                        </div>
                                                    </div>

                                                    <div class="social-input-group">
                                                        <div class="input-group">
                                                            <span class="input-group-text social-icon">
                                                                <i class="fab fa-instagram"></i>
                                                            </span>
                                                            <input type="url" class="form-control" name="instagram" 
                                                                   placeholder="Instagram URL" 
                                                                   value="<?php echo htmlspecialchars($teamMember['social_links']['instagram']); ?>">
                                                        </div>
                                                    </div>

                                                    <div class="social-input-group">
                                                        <div class="input-group">
                                                            <span class="input-group-text social-icon">
                                                                <i class="fab fa-github"></i>
                                                            </span>
                                                            <input type="url" class="form-control" name="github" 
                                                                   placeholder="GitHub URL" 
                                                                   value="<?php echo htmlspecialchars($teamMember['social_links']['github']); ?>">
                                                        </div>
                                                    </div>

                                                    <div class="social-input-group">
                                                        <div class="input-group">
                                                            <span class="input-group-text social-icon">
                                                                <i class="fab fa-behance"></i>
                                                            </span>
                                                            <input type="url" class="form-control" name="behance" 
                                                                   placeholder="Behance URL" 
                                                                   value="<?php echo htmlspecialchars($teamMember['social_links']['behance']); ?>">
                                                        </div>
                                                    </div>

                                                    <div class="social-input-group">
                                                        <div class="input-group">
                                                            <span class="input-group-text social-icon">
                                                                <i class="fab fa-dribbble"></i>
                                                            </span>
                                                            <input type="url" class="form-control" name="dribbble" 
                                                                   placeholder="Dribbble URL" 
                                                                   value="<?php echo htmlspecialchars($teamMember['social_links']['dribbble']); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-4 pt-3 border-top">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Update Team Member
                                            </button>
                                            <a href="about-page.php" class="btn btn-outline-secondary">
                                                <i class="fas fa-arrow-left"></i> Cancel
                                            </a>
                                            
                                            <button type="button" class="btn btn-outline-danger float-end" 
                                                    onclick="confirmDelete(<?php echo $teamMember['id']; ?>)">
                                                <i class="fas fa-trash"></i> Delete Team Member
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
        function confirmDelete(teamMemberId) {
            if (confirm('Are you sure you want to delete this team member? This action cannot be undone.')) {
                // Create a form and submit it to delete the team member
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'about-page.php';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = teamMemberId;
                
                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'delete_team_member';
                deleteInput.value = '1';
                
                form.appendChild(idInput);
                form.appendChild(deleteInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Image preview functionality
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.querySelector('.profile-image') || document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'profile-image';
                    img.alt = 'Preview';
                    
                    const container = document.querySelector('.profile-image-container');
                    const existingImg = container.querySelector('img');
                    const existingDiv = container.querySelector('div');
                    
                    if (existingImg) {
                        container.replaceChild(img, existingImg);
                    } else if (existingDiv) {
                        container.innerHTML = '';
                        container.appendChild(img);
                        
                        // Add overlay back
                        const overlay = document.createElement('div');
                        overlay.className = 'image-overlay';
                        overlay.innerHTML = '<span class="text-white">Change Image</span>';
                        container.appendChild(overlay);
                    }
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>