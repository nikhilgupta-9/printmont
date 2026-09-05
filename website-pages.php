<?php
/**
 * Website Pages — one screen listing every page on the storefront.
 *
 * The sidebar used to carry a dozen flat links here, half of them modern
 * editors and half old forms, with no way to see at a glance which page
 * held what. This is the way in: every page, how much is on it, whether
 * any of it is hidden, and one click to edit or to view it live.
 *
 * Pages backed by config/page-editor-schema.php get their counts read
 * live. The rest keep their own screens and are listed alongside so the
 * hub stays the complete picture rather than a partial one.
 */

require_once 'config/constants.php';
require_once 'services/PageEditor.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Website Pages | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="css/page-editor.css">
</head>

<body>
<div class="wrapper">
    <?php include_once 'includes/side-navbar.php'; ?>

    <div class="main">
        <?php include_once 'includes/top-navbar.php'; ?>

        <main class="content">
            <div class="container-fluid p-0">
                <?php
                $schemas = require 'config/page-editor-schema.php';
                $editor = new PageEditor($db, $schemas);

                /**
                 * Pages that keep their own screen. Counting rows here means the
                 * hub reports the same kind of information for every page rather
                 * than leaving half the cards blank.
                 */
                $others = [
                    [
                        'label' => 'Policies',
                        'note'  => 'Terms, privacy, shipping and refund text.',
                        'edit'  => 'policy-edit.php',
                        'view'  => '/privacy-policy',
                        'table' => 'policies',
                        'why'   => 'Keeps its rich-text editor — the policy text is formatted HTML.',
                    ],
                    [
                        'label' => 'FAQ Categories',
                        'note'  => 'The groups questions are filed under.',
                        'edit'  => 'faq-view-category.php',
                        'view'  => '/faq',
                        'table' => 'faq_categories',
                    ],
                    [
                        'label' => 'Contact Us',
                        'note'  => 'Phone numbers, addresses and the enquiries people send.',
                        'edit'  => 'contact-view.php',
                        'view'  => '/contact',
                        'table' => 'contact_inquiries',
                        'unit'  => 'enquiries',
                    ],
                    [
                        'label' => 'Careers',
                        'note'  => 'Open roles and the applications they receive.',
                        'edit'  => 'careers.php',
                        'view'  => '/careers',
                        'table' => 'careers',
                        'unit'  => 'roles',
                    ],
                ];

                /** How many rows a page holds, and how many are hidden. */
                function pe_hub_counts(PageEditor $editor, string $key, array $schema): array
                {
                    $total = 0;
                    $hidden = 0;
                    foreach ($editor->rows($schema, $key) as $rows) {
                        foreach ($rows as $row) {
                            $total++;
                            if ((int) $row['is_active'] !== 1) {
                                $hidden++;
                            }
                        }
                    }
                    return [$total, $hidden];
                }

                function pe_hub_rows($db, string $table): ?int
                {
                    $result = @$db->query('SELECT COUNT(*) AS n FROM `' . $table . '`');
                    return $result ? (int) $result->fetch_assoc()['n'] : null;
                }

                $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
                ?>

                <div class="pe">
                    <header class="pe-head">
                        <div class="pe-head-text">
                            <span class="pe-eyebrow">Content</span>
                            <h1 class="pe-title">Website Pages</h1>
                            <p class="pe-note">
                                Every page on the storefront. Open one to edit its sections, or view it
                                live to see the change.
                            </p>
                        </div>
                        <div class="pe-head-actions">
                            <label class="pe-search">
                                <input type="search" id="hubSearch" placeholder="Find a page">
                            </label>
                        </div>
                    </header>

                    <h2 class="pe-group-title">Edited here</h2>
                    <div class="pe-hub" id="hubEditor">
                        <?php foreach ($schemas as $key => $schema): ?>
                            <?php
                            [$total, $hidden] = pe_hub_counts($editor, $key, $schema);
                            $bands = count($schema['bands']);
                            ?>
                            <article class="pe-tile" data-search="<?php echo htmlspecialchars(strtolower($schema['label'] . ' ' . ($schema['note'] ?? ''))); ?>">
                                <div class="pe-tile-top">
                                    <h3><?php echo htmlspecialchars($schema['label']); ?></h3>
                                    <code class="pe-path"><?php echo htmlspecialchars($schema['preview'] ?? '/'); ?></code>
                                </div>

                                <p class="pe-tile-note"><?php echo htmlspecialchars($schema['note'] ?? ''); ?></p>

                                <ul class="pe-facts">
                                    <li><strong><?php echo $bands; ?></strong> sections</li>
                                    <li><strong><?php echo $total; ?></strong> items</li>
                                    <?php if ($hidden): ?>
                                        <li class="is-warn"><strong><?php echo $hidden; ?></strong> hidden</li>
                                    <?php endif; ?>
                                </ul>

                                <div class="pe-tile-actions">
                                    <a class="pe-btn pe-btn-primary pe-btn-sm"
                                       href="<?php echo htmlspecialchars($schema['admin'] ?? ($key . '-page.php')); ?>">Edit page</a>
                                    <a class="pe-btn pe-btn-sm pe-btn-ghost" target="_blank" rel="noopener"
                                       href="<?php echo htmlspecialchars($base . ($schema['preview'] ?? '/')); ?>">View live</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <h2 class="pe-group-title">Edited on their own screens</h2>
                    <div class="pe-hub">
                        <?php foreach ($others as $page): ?>
                            <?php $count = pe_hub_rows($db, $page['table']); ?>
                            <article class="pe-tile is-plain" data-search="<?php echo htmlspecialchars(strtolower($page['label'] . ' ' . $page['note'])); ?>">
                                <div class="pe-tile-top">
                                    <h3><?php echo htmlspecialchars($page['label']); ?></h3>
                                    <code class="pe-path"><?php echo htmlspecialchars($page['view']); ?></code>
                                </div>

                                <p class="pe-tile-note"><?php echo htmlspecialchars($page['note']); ?></p>

                                <ul class="pe-facts">
                                    <?php if ($count !== null): ?>
                                        <li><strong><?php echo $count; ?></strong> <?php echo htmlspecialchars($page['unit'] ?? 'items'); ?></li>
                                    <?php endif; ?>
                                </ul>

                                <?php if (!empty($page['why'])): ?>
                                    <p class="pe-tile-why"><?php echo htmlspecialchars($page['why']); ?></p>
                                <?php endif; ?>

                                <div class="pe-tile-actions">
                                    <a class="pe-btn pe-btn-sm" href="<?php echo htmlspecialchars($page['edit']); ?>">Open</a>
                                    <a class="pe-btn pe-btn-sm pe-btn-ghost" target="_blank" rel="noopener"
                                       href="<?php echo htmlspecialchars($base . $page['view']); ?>">View live</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>

        <?php include_once 'includes/footer.php'; ?>
    </div>
</div>

<script src="js/app.js"></script>
<script>
    // Filtering a dozen tiles needs no more than this.
    (function () {
        var box = document.getElementById('hubSearch');
        if (!box) return;

        box.addEventListener('input', function () {
            var term = box.value.trim().toLowerCase();
            document.querySelectorAll('.pe-tile').forEach(function (tile) {
                tile.hidden = term !== '' && tile.dataset.search.indexOf(term) === -1;
            });
        });
    })();
</script>
</body>

</html>
