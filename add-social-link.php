<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/SocialLinkController.php';

$socialLinkController = new SocialLinkController();
$platform_options = $socialLinkController->getPlatformOptions();

// Handle form submission
if ($_POST) {
    try {
        $data = [
            'platform' => $_POST['platform'],
            'url' => trim($_POST['url']),
            'icon' => trim($_POST['icon']),
            'display_order' => (int) $_POST['display_order'],
            'status' => $_POST['status']
        ];

        // Validate platform uniqueness
        if ($socialLinkController->platformExists($data['platform'])) {
            throw new Exception("Social link for this platform already exists.");
        }

        // Validate URL
        if (!$socialLinkController->validateUrl($data['platform'], $data['url'])) {
            throw new Exception("Invalid URL format for the selected platform.");
        }

        if ($socialLinkController->createSocialLink($data)) {
            $_SESSION['success_message'] = "Social link created successfully!";
            header("Location: social-links.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Failed to create social link.";
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
    <title>Add Social Link | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .platform-option {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .platform-option:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }

        .platform-option.selected {
            border-color: #007bff;
            background-color: #e7f3ff;
        }

        .platform-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            margin-right: 15px;
        }

        .form-label {
            font-weight: 500;
        }

        .required:after {
            content: " *";
            color: red;
        }

        .url-preview {
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 8px 12px;
            font-family: monospace;
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
                    <div class="row mb-2 mb-xl-3">
                        <div class="col-auto d-none d-sm-block">
                            <h3><strong>Add</strong> Social Link</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="social-links.php" class="btn btn-secondary">Back to Social Links</a>
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
                                    <h5 class="card-title">Social Link Information</h5>
                                    <h6 class="card-subtitle text-muted">Add new social media link to display on your
                                        website.</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="socialLinkForm">
                                        <!-- Platform Selection -->
                                        <div class="mb-4">
                                            <label class="form-label required">Select Platform</label>
                                            <div id="platformOptions">
                                                <?php foreach ($platform_options as $platform_key => $platform): ?>
                                                    <div class="platform-option"
                                                        data-platform="<?php echo $platform_key; ?>"
                                                        data-icon="<?php echo $platform['icon']; ?>"
                                                        data-prefix="<?php echo $platform['url_prefix']; ?>">
                                                        <div class="d-flex align-items-center">
                                                            <div class="platform-icon"
                                                                style="background-color: <?php echo $platform['color']; ?>">
                                                                <i class="<?php echo $platform['icon']; ?>"></i>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-1"><?php echo $platform['name']; ?></h6>
                                                                <small
                                                                    class="text-muted"><?php echo $platform['url_prefix']; ?></small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <input type="hidden" name="platform" id="platformInput" required>
                                        </div>

                                        <!-- URL -->
                                        <div class="mb-3">
                                            <label for="url" class="form-label required">Profile URL</label>
                                            <div class="input-group">
                                                <span class="input-group-text" id="urlPrefix">https://</span>
                                                <input type="url" class="form-control" id="url" name="url"
                                                    placeholder="Enter your profile URL" required>
                                            </div>
                                            <div class="form-text">
                                                <span id="urlPreview" class="url-preview">Full URL will appear
                                                    here</span>
                                            </div>
                                        </div>

                                        <!-- Icon -->
                                        <div class="mb-3">
                                            <label for="icon" class="form-label required">Icon Class</label>
                                            <input type="text" class="form-control" id="icon" name="icon"
                                                placeholder="fab fa-facebook-f" required readonly>
                                            <small class="form-text text-muted">
                                                Font Awesome icon class (automatically selected based on platform)
                                            </small>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="display_order" class="form-label">Display Order</label>
                                                    <input type="number" class="form-control" id="display_order"
                                                        name="display_order" value="0" min="0">
                                                    <small class="form-text text-muted">
                                                        Lower numbers display first
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="status" class="form-label required">Status</label>
                                                    <select name="status" id="status" class="form-control" required>
                                                        <option value="active" selected>Active</option>
                                                        <option value="inactive">Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">Create Social Link</button>
                                            <a href="social-links.php" class="btn btn-secondary">Cancel</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Quick Tips</h5>
                                </div>

                                <div class="card-body">

                                    <!-- Platform Selection -->
                                    <div class="alert alert-info p-2">
                                        <h6 class="mb-2 fw-bold">Platform Selection</h6>
                                        <ul class="small ps-3 mb-0">
                                            <li>Click on a platform to select it</li>
                                            <li>Icon and URL prefix will auto-fill</li>
                                            <li>Each platform can only be added once</li>
                                        </ul>
                                    </div>

                                    <!-- URL Guidelines -->
                                    <div class="alert alert-warning p-2">
                                        <h6 class="mb-2 fw-bold">URL Guidelines</h6>
                                        <ul class="small ps-3 mb-0">
                                            <li>Use full profile URLs</li>
                                            <li>Ensure URLs are publicly accessible</li>
                                            <li>Test links before saving</li>
                                        </ul>
                                    </div>

                                    <!-- Display Order -->
                                    <div class="alert alert-success p-2">
                                        <h6 class="mb-2 fw-bold">Display Order</h6>
                                        <ul class="small ps-3 mb-0">
                                            <li>Use 0 for default ordering</li>
                                            <li>Lower numbers appear first</li>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Platform selection
        const platformOptions = document.querySelectorAll('.platform-option');
        const platformInput = document.getElementById('platformInput');
        const iconInput = document.getElementById('icon');
        const urlInput = document.getElementById('url');
        const urlPrefix = document.getElementById('urlPrefix');
        const urlPreview = document.getElementById('urlPreview');

        platformOptions.forEach(option => {
            option.addEventListener('click', function () {
                // Remove selected class from all options
                platformOptions.forEach(opt => opt.classList.remove('selected'));

                // Add selected class to clicked option
                this.classList.add('selected');

                // Set form values
                const platform = this.getAttribute('data-platform');
                const icon = this.getAttribute('data-icon');
                const prefix = this.getAttribute('data-prefix');

                platformInput.value = platform;
                iconInput.value = icon;
                urlPrefix.textContent = prefix;

                // Update URL preview
                updateUrlPreview();
            });
        });

        // URL input handling
        urlInput.addEventListener('input', updateUrlPreview);

        function updateUrlPreview() {
            const prefix = urlPrefix.textContent;
            const url = urlInput.value;

            if (url) {
                // If URL already starts with http, use it as is
                if (url.startsWith('http')) {
                    urlPreview.textContent = url;
                } else {
                    urlPreview.textContent = prefix + url;
                }
            } else {
                urlPreview.textContent = 'Full URL will appear here';
            }
        }

        // Form validation
        document.getElementById('socialLinkForm').addEventListener('submit', function (e) {
            const platform = platformInput.value;
            const url = urlInput.value.trim();

            if (!platform) {
                e.preventDefault();
                alert('Please select a social media platform');
                return;
            }

            if (!url) {
                e.preventDefault();
                alert('Please enter the profile URL');
                urlInput.focus();
                return;
            }

            // Basic URL validation
            if (!url.startsWith('http')) {
                if (!confirm('The URL should start with http:// or https://. Do you want to continue?')) {
                    e.preventDefault();
                    urlInput.focus();
                    return;
                }
            }
        });
    </script>
</body>

</html>