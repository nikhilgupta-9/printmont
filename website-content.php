<?php
/**
 * Website Content — the content block builder.
 *
 * Not one fixed page, one fixed layout. The admin names any page here
 * (home, a category page, about-us, anything the frontend will ask
 * content-blocks-api.php for by that same key), picks a design pattern
 * for it, fills that pattern's own fields, and can add as many sections
 * as needed, on as many pages as needed, in any order.
 *
 * The patterns themselves live in config/content-block-patterns.php.
 * The browser never invents a field — content-blocks-action.php checks
 * every value against the chosen pattern's own field list.
 */

session_start();
require_once 'config/constants.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Website Content | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="css/content-blocks.css">
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
                            <h3><strong>Website</strong> Content</h3>
                            <p class="text-muted mb-0">
                                Sections for any page, in any design pattern. Add one, pick the page it
                                belongs to, choose a pattern, and fill it in.
                            </p>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <button type="button" class="btn btn-primary" id="cbAddBtn">
                                <i data-feather="plus" class="align-middle"></i> Add section
                            </button>
                        </div>
                    </div>

                    <div id="cbStatus"></div>
                    <div id="cbGroups"></div>
                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Add / edit section dialog -->
    <div class="modal fade" id="cbModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cbModalTitle">Add section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="cbForm" novalidate>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Page</label>
                                <input type="text" class="form-control" id="cbPageKey" list="cbPageKeyList"
                                       placeholder="e.g. home, about-us, /category/apparel">
                                <datalist id="cbPageKeyList"></datalist>
                                <div class="form-text">The key your frontend page asks for. Any text you like — just use the same one on both sides.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Internal title</label>
                                <input type="text" class="form-control" id="cbTitle" placeholder="For your reference in this list only">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Design pattern</label>
                            <select class="form-select" id="cbPattern"></select>
                            <div class="form-text" id="cbPatternNote"></div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="cbActive" checked>
                            <label class="form-check-label" for="cbActive">Visible on the site</label>
                        </div>

                        <hr>
                        <div id="cbFields"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="cbSaveBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <!-- Same CKEditor build the rest of the admin uses (blog posts, products, policies). -->
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
    <script src="js/content-blocks.js"></script>
</body>
</html>
