<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/PolicyController.php';

$policyController = new PolicyController();
$policies = $policyController->getAllPolicies();

// Check for messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Policy Management | Printmont</title>

    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">

    <style>
        .policy-tabs .nav-link {
            border: 1px solid #dee2e6;
            margin-right: 5px;
            margin-bottom: 5px;
            color: #495057;
            background: #f8f9fa;
        }

        .policy-tabs .nav-link.active {
            background-color: #0d6efd;
            color: #fff !important;
            border-color: #0d6efd;
        }

        .policy-box {
            border: 1px solid #dee2e6;
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .remove-point {
            cursor: pointer;
            color: #dc3545;
            font-size: 20px;
            padding-left: 10px;
            line-height: 38px;
        }

        .point-item {
            transition: all 0.3s ease;
        }

        .point-item:hover {
            background-color: #f8f9fa;
            padding: 5px;
            border-radius: 4px;
        }

        .status-badge {
            font-size: 0.75em;
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

                    <div class="row mb-3">
                        <div class="col-auto">
                            <h3><strong>Policy</strong> Management</h3>
                        </div>
                        <div class="col-auto ms-auto">
                            <span class="badge bg-primary"><?php echo count($policies); ?> Policies</span>
                        </div>
                    </div>

                    <!-- SUCCESS MESSAGE -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show p-3" role="alert">
                            <?= htmlspecialchars($success_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- ERROR MESSAGE -->
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($error_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Manage Policies</h5>
                            <p class="text-muted mb-0">Update your website policies and terms</p>
                        </div>

                        <!-- Tabs -->
                        <div class="card-header">
                            <ul class="nav nav-tabs policy-tabs mb-0" id="policyTabs" role="tablist">
                                <?php $first = true;
                                foreach ($policies as $p): ?>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link <?= $first ? 'active' : '' ?>"
                                            id="tab-<?= $p['policy_key'] ?>" data-bs-toggle="tab"
                                            data-bs-target="#<?= $p['policy_key'] ?>" type="button" role="tab">
                                            <?= htmlspecialchars($p['heading']) ?>
                                            <?php if ($p['status'] == 'active'): ?>
                                                <span class="badge bg-success status-badge ms-1">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary status-badge ms-1">Inactive</span>
                                            <?php endif; ?>
                                        </button>
                                    </li>
                                    <?php $first = false; endforeach; ?>
                            </ul>
                        </div>

                        <div class="card-body">
                            <div class="tab-content" id="policyTabsContent">

                                <?php $first = true;
                                foreach ($policies as $p):
                                    $points = json_decode($p['points'], true);
                                    if (!$points || !is_array($points))
                                        $points = [];
                                    ?>

                                    <div class="tab-pane fade <?= $first ? 'show active' : '' ?>"
                                        id="<?= $p['policy_key'] ?>" role="tabpanel"
                                        aria-labelledby="tab-<?= $p['policy_key'] ?>">

                                        <div class="policy-box">
                                            <div class="d-flex justify-content-between align-items-center mb-4">
                                                <h4 class="fw-bold mb-0">Edit <?= htmlspecialchars($p['heading']) ?></h4>
                                                <small class="text-muted">Last updated:
                                                    <?= date('M j, Y g:i A', strtotime($p['updated_at'])) ?></small>
                                            </div>

                                            <form method="POST" action="update-policy.php">

                                                <input type="hidden" name="policy_key" value="<?= $p['policy_key'] ?>">

                                                <!-- Heading -->
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Heading *</label>
                                                    <input type="text" class="form-control" name="heading"
                                                        value="<?= htmlspecialchars($p['heading']) ?>" required>
                                                </div>

                                                <!-- Description -->
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Description</label>
                                                    <textarea class="form-control" id="content-<?= $p['policy_key'] ?>"
                                                        name="description" name="description" rows="14"
                                                        placeholder="Enter policy description..."><?= htmlspecialchars($p['description']) ?></textarea>
                                                </div>

                                                <!-- Points -->
                                                <div class="mb-4">
                                                    <label
                                                        class="form-label fw-semibold d-flex justify-content-between align-items-center">
                                                        Policy Points
                                                        <button type="button" class="btn btn-sm btn-success"
                                                            onclick="addPoint('<?= $p['policy_key'] ?>')">
                                                            <i class="fas fa-plus"></i> Add Point
                                                        </button>
                                                    </label>

                                                    <div class="alert alert-info p-3">
                                                        <small><i class="fas fa-info-circle "></i> Add bullet points that
                                                            will be displayed as key features of this policy.</small>
                                                    </div>

                                                    <div id="points-<?= $p['policy_key'] ?>">
                                                        <?php foreach ($points as $index => $point): ?>
                                                            <div class="d-flex mb-2 point-item">
                                                                <input type="text" class="form-control" name="points[]"
                                                                    value="<?= htmlspecialchars($point) ?>"
                                                                    placeholder="Enter policy point...">
                                                                <span class="remove-point"
                                                                    onclick="this.parentElement.remove()">
                                                                    <i class="fas fa-times"></i>
                                                                </span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                        <?php if (empty($points)): ?>
                                                            <div class="d-flex mb-2 point-item">
                                                                <input type="text" class="form-control" name="points[]"
                                                                    placeholder="Enter policy point...">
                                                                <span class="remove-point"
                                                                    onclick="this.parentElement.remove()">
                                                                    <i class="fas fa-times"></i>
                                                                </span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <!-- Status -->
                                                <div class="mb-4">
                                                    <label class="form-label fw-semibold">Status</label>
                                                    <select name="status" class="form-control">
                                                        <option value="active" <?= $p['status'] == 'active' ? 'selected' : '' ?>>
                                                            Active
                                                        </option>
                                                        <option value="inactive" <?= $p['status'] == 'inactive' ? 'selected' : '' ?>>
                                                            Inactive
                                                        </option>
                                                    </select>
                                                    <div class="form-text">
                                                        Active policies will be visible on the website.
                                                    </div>
                                                </div>

                                                <!-- Save Button -->
                                                <div class="d-flex gap-2">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-save"></i> Save Changes
                                                    </button>
                                                    <button type="reset" class="btn btn-outline-secondary">
                                                        <i class="fas fa-undo"></i> Reset
                                                    </button>
                                                </div>

                                            </form>

                                        </div>

                                    </div>

                                    <?php $first = false; endforeach; ?>

                            </div>
                        </div>
                    </div>

                </div>
            </main>

            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script> -->
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>

    <script src="js/app.js"></script>

    <script>
        // Initialize CKEditor
        // Initialize CKEditor for all policy description fields
        document.querySelectorAll("textarea[id^='content-']").forEach(function (textarea) {
            CKEDITOR.replace(textarea.id, {
                toolbar: [
                    { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'RemoveFormat'] },
                    { name: 'paragraph', items: ['NumberedList', 'BulletedList', 'Blockquote'] },
                    { name: 'links', items: ['Link', 'Unlink'] },
                    { name: 'insert', items: ['Image', 'Table', 'HorizontalRule'] },
                    { name: 'styles', items: ['Styles', 'Format'] },
                    { name: 'tools', items: ['Maximize', 'Source'] }
                ],
                height: 400
            });
        });


        // Auto-generate slug from title
        document.getElementById('title').addEventListener('input', function () {
            const slugField = document.getElementById('slug');
            if (!slugField.value) {
                const slug = this.value
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9 -]/g, '-')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');
                slugField.value = slug;
            }
        });

        // Auto-generate meta description from content
        document.getElementById('content').addEventListener('input', function () {
            const metaDescField = document.getElementById('meta_description');
            if (!metaDescField.value) {
                // For CKEditor, we need to get the data differently
                const content = CKEDITOR.instances.content.getData().replace(/<[^>]*>/g, '').substring(0, 160);
                metaDescField.value = content + (content.length >= 160 ? '...' : '');
                updateMetaDescCount();
            }
        });
        function addPoint(policyKey) {
            const container = document.getElementById("points-" + policyKey);
            const div = document.createElement("div");
            div.classList.add("d-flex", "mb-2", "point-item");
            div.innerHTML = `
                <input type="text" class="form-control" name="points[]" placeholder="Enter policy point..." />
                <span class="remove-point" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </span>
            `;
            container.appendChild(div);

            // Focus on the new input
            div.querySelector('input').focus();
        }

        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>

</body>

</html>