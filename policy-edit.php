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
        .policy-tabs .nav-link.active {
            background-color: #0d6efd;
            color: #fff !important;
        }

        .policy-box {
            border: 1px solid #ddd;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
        }

        .remove-point {
            cursor: pointer;
            color: red;
            font-size: 20px;
            padding-left: 10px;
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
                    </div>

                    <!-- SUCCESS MESSAGE -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?= $success_message ?></div>
                    <?php endif; ?>

                    <!-- ERROR MESSAGE -->
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?= $error_message ?></div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">All Policies</h5>
                        </div>

                        <!-- Tabs -->
                        <ul class="nav nav-tabs policy-tabs mb-4">
                            <?php $first = true;
                            foreach ($policies as $p): ?>
                                <li class="nav-item">
                                    <button class="nav-link <?= $first ? 'active' : '' ?>" data-bs-toggle="tab"
                                        data-bs-target="#<?= $p['policy_key'] ?>">
                                        <?= $p['heading'] ?>
                                    </button>
                                </li>
                                <?php $first = false; endforeach; ?>
                        </ul>

                        <div class="tab-content">

                            <?php $first = true;
                            foreach ($policies as $p):

                                $points = json_decode($p['points'], true);
                                if (!$points)
                                    $points = [];
                                ?>

                                <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" id="<?= $p['policy_key'] ?>">

                                    <div class="policy-box">
                                        <h4 class="fw-bold mb-3">Edit <?= $p['heading'] ?></h4>

                                        <form method="POST" action="update-policy.php">

                                            <input type="hidden" name="policy_key" value="<?= $p['policy_key'] ?>">

                                            <!-- Heading -->
                                            <div class="mb-3">
                                                <label class="form-label">Heading</label>
                                                <input type="text" class="form-control" name="heading"
                                                    value="<?= $p['heading'] ?>">
                                            </div>

                                            <!-- Description -->
                                            <div class="mb-3">
                                                <label class="form-label">Description</label>
                                                <textarea class="form-control policy-richtext"
                                                    id="description-<?= htmlspecialchars($p['policy_key']) ?>"
                                                    name="description"
                                                    rows="8"><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
                                            </div>

                                            <!-- Points -->
                                            <div class="mb-3">
                                                <label class="form-label d-flex justify-content-between">
                                                    Points
                                                    <button type="button" class="btn btn-sm btn-success"
                                                        onclick="addPoint('<?= $p['policy_key'] ?>')">+ Add Point</button>
                                                </label>

                                                <div id="points-<?= $p['policy_key'] ?>">
                                                    <?php foreach ($points as $point): ?>
                                                        <div class="d-flex mb-2 point-item">
                                                            <input type="text" class="form-control" name="points[]"
                                                                value="<?= $point ?>">
                                                            <span class="remove-point"
                                                                onclick="this.parentElement.remove()">×</span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>

                                            <!-- Status (INSIDE the form, BEFORE Save btn) -->
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select name="status" class="form-control">
                                                    <option value="active" <?= $p['status'] == 'active' ? 'selected' : '' ?>>
                                                        Active</option>
                                                    <option value="inactive" <?= $p['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                                </select>
                                            </div>

                                            <!-- Save button must be last inside the form -->
                                            <button class="btn btn-primary">Save Changes</button>

                                        </form>

                                    </div>

                                </div>

                                <?php $first = false; endforeach; ?>

                        </div>
                    </div>

                </div>
            </main>

            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <script src="js/app.js"></script>
    <!-- Same CKEditor build the rest of the admin uses (blog posts, products). -->
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
    <script>
        // Each policy is its own tab pane, so there is one editor per policy.
        // CKEditor measures the textarea when it attaches, and a pane that is
        // still hidden measures zero — so only the visible tab is initialised
        // up front and the rest are created the first time their tab is shown.
        (function () {
            if (typeof CKEDITOR === 'undefined') return;

            const CONFIG = {
                height: 260,
                removePlugins: 'elementspath',
                resize_enabled: true,
                toolbar: [
                    { name: 'styles', items: ['Format'] },
                    { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', '-', 'RemoveFormat'] },
                    { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote'] },
                    { name: 'links', items: ['Link', 'Unlink'] },
                    { name: 'insert', items: ['Table', 'HorizontalRule'] },
                    { name: 'tools', items: ['Maximize'] },
                    { name: 'document', items: ['Source'] }
                ]
            };

            function initEditor(textarea) {
                if (!textarea || CKEDITOR.instances[textarea.id]) return;
                CKEDITOR.replace(textarea.id, CONFIG);
            }

            function initVisible() {
                document.querySelectorAll('.tab-pane.active .policy-richtext').forEach(initEditor);
            }

            document.addEventListener('DOMContentLoaded', function () {
                initVisible();

                // Bootstrap fires this after the pane is visible and measurable.
                document.querySelectorAll('[data-bs-toggle="tab"], [data-bs-toggle="pill"]').forEach(function (trigger) {
                    trigger.addEventListener('shown.bs.tab', function (e) {
                        const paneId = (e.target.getAttribute('href') || e.target.dataset.bsTarget || '').replace('#', '');
                        const pane = paneId && document.getElementById(paneId);
                        if (pane) pane.querySelectorAll('.policy-richtext').forEach(initEditor);
                    });
                });
            });

            // CKEditor writes back to its textarea on submit, but only for the
            // form it belongs to — force it so a stale value is never posted.
            document.addEventListener('submit', function (e) {
                if (!e.target.matches('form')) return;
                e.target.querySelectorAll('.policy-richtext').forEach(function (ta) {
                    const instance = CKEDITOR.instances[ta.id];
                    if (instance) ta.value = instance.getData();
                });
            }, true);
        })();

        function addPoint(id) {
            const container = document.getElementById("points-" + id);
            const div = document.createElement("div");
            div.classList.add("d-flex", "mb-2", "point-item");
            div.innerHTML = `
        <input type="text" class="form-control" name="points[]" placeholder="Enter point" />
        <span class="remove-point" onclick="this.parentElement.remove()">×</span>
    `;
            container.appendChild(div);
        }
    </script>

</body>

</html>
