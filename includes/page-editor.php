<?php
/**
 * Renders the page editor.
 *
 * Callers set $pageKey and include this file inside the usual admin shell.
 * Everything else — which bands exist, what each field is called, which
 * table it is stored in — comes from config/page-editor-schema.php, so an
 * admin screen for a new website page is a two-line file plus a schema
 * entry.
 */

require_once __DIR__ . '/../services/PageEditor.php';

if (!isset($pageKey)) {
    echo '<div class="alert alert-danger">No page was given to the editor.</div>';
    return;
}

$pe_editor = new PageEditor($db, require __DIR__ . '/../config/page-editor-schema.php');
$pe_schema = $pe_editor->page($pageKey);

if (!$pe_schema) {
    echo '<div class="alert alert-danger">There is no editor layout for "'
        . htmlspecialchars($pageKey) . '" yet.</div>';
    return;
}

// One token per session, checked on every write in page-editor-action.php.
if (empty($_SESSION['pe_csrf'])) {
    $_SESSION['pe_csrf'] = bin2hex(random_bytes(16));
}

$pe_preview = defined('BASE_URL')
    ? rtrim(BASE_URL, '/') . ($pe_schema['preview'] ?? '/')
    : ($pe_schema['preview'] ?? '');

// Current content, so the first paint needs no round trip, and any select
// field's options, read fresh from whichever table supplies them.
$pe_payload = [
    'page'     => $pageKey,
    'label'    => $pe_schema['label'],
    'note'     => $pe_schema['note'] ?? '',
    'preview'  => $pe_preview,
    'bands'    => $pe_editor->withOptions($pe_schema)['bands'],
    'rows'     => $pe_editor->rows($pe_schema, $pageKey),
    'csrf'     => $_SESSION['pe_csrf'],
    'endpoint' => 'page-editor-action.php',
];
?>

<link rel="stylesheet" href="css/page-editor.css">

<div class="pe" id="pageEditor">
    <header class="pe-head">
        <div class="pe-head-text">
            <span class="pe-eyebrow"><a href="website-pages.php">Website pages</a></span>
            <h1 class="pe-title"><?php echo htmlspecialchars($pe_schema['label']); ?></h1>
            <?php if (!empty($pe_schema['note'])): ?>
                <p class="pe-note"><?php echo htmlspecialchars($pe_schema['note']); ?></p>
            <?php endif; ?>
        </div>
        <div class="pe-head-actions">
            <span class="pe-status" id="peStatus"></span>
            <label class="pe-search">
                <input type="search" id="peSearch" placeholder="Find text on this page">
            </label>
            <a class="pe-btn pe-btn-ghost" href="<?php echo htmlspecialchars($pe_preview); ?>" target="_blank" rel="noopener">
                View live page
            </a>
        </div>
    </header>

    <div class="pe-layout">
        <nav class="pe-rail" id="peRail" aria-label="Sections of this page"></nav>
        <div class="pe-bands" id="peBands"></div>
    </div>
</div>

<!-- Editor dialog for list rows -->
<div class="pe-overlay" id="peOverlay" hidden>
    <div class="pe-dialog" role="dialog" aria-modal="true" aria-labelledby="peDialogTitle">
        <header class="pe-dialog-head">
            <h2 id="peDialogTitle">Edit</h2>
            <button type="button" class="pe-close" id="peDialogClose" aria-label="Close">&times;</button>
        </header>
        <form id="peDialogForm" class="pe-dialog-body" novalidate></form>
        <footer class="pe-dialog-foot">
            <button type="button" class="pe-btn pe-btn-ghost" id="peDialogCancel">Cancel</button>
            <button type="submit" class="pe-btn pe-btn-primary" form="peDialogForm" id="peDialogSave">Save</button>
        </footer>
    </div>
</div>

<div class="pe-toasts" id="peToasts" aria-live="polite"></div>

<script>
    window.PAGE_EDITOR = <?php echo json_encode($pe_payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="js/page-editor.js"></script>
