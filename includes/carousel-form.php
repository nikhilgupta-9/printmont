<?php
/**
 * Shared Carousel form (DOCX §8 full spec).
 * Expects in scope:
 *   $carousel   array|null  existing row when editing, null when adding
 *   $categories array       [{id,name}]
 *   $banners    array       [{id,title}]
 *   $formAction string      target URL for the form POST
 *   $submitLabel string     button label
 */
if (!function_exists('cf_val')) {
    function cf_val($row, $key, $default = '') {
        return htmlspecialchars((string)($row[$key] ?? $default));
    }
    function cf_sel($row, $key, $value, $default = '') {
        return (($row[$key] ?? $default) == $value) ? 'selected' : '';
    }
}
$c = $carousel ?? [];
$designOptions = ['design1' => 'Design 1', 'design2' => 'Design 2', 'design3' => 'Design 3', 'design4' => 'Design 4'];
?>
<form method="POST" action="<?php echo htmlspecialchars($formAction); ?>" enctype="multipart/form-data">
    <?php if (!empty($c['id'])): ?>
        <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
    <?php endif; ?>

    <!-- Basic -->
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label required">Carousel Title</label>
            <input type="text" class="form-control" id="title" name="title" required maxlength="255"
                   value="<?php echo cf_val($c, 'title'); ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Slug</label>
            <input type="text" class="form-control" id="slug" name="slug" maxlength="255"
                   value="<?php echo cf_val($c, 'slug'); ?>">
            <small class="form-text text-muted">Auto-generated from title if left blank.</small>
        </div>
    </div>

    <!-- Desktop -->
    <hr>
    <h5 class="mb-3"><i class="fas fa-desktop"></i> Desktop Settings</h5>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Show on Other Pages</label>
            <select class="form-control" name="desktop_other_pages">
                <option value="no"  <?php echo cf_sel($c, 'desktop_other_pages', 'no', 'no'); ?>>No</option>
                <option value="yes" <?php echo cf_sel($c, 'desktop_other_pages', 'yes'); ?>>Yes</option>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Other Page Category</label>
            <select class="form-control" name="desktop_other_category_id">
                <option value="">— Select Category —</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo (int)$cat['id']; ?>" <?php echo cf_sel($c, 'desktop_other_category_id', $cat['id']); ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Other Page Banner</label>
            <select class="form-control" name="desktop_other_banner_id">
                <option value="">— Select Banner —</option>
                <?php foreach ($banners as $b): ?>
                    <option value="<?php echo (int)$b['id']; ?>" <?php echo cf_sel($c, 'desktop_other_banner_id', $b['id']); ?>>
                        <?php echo htmlspecialchars($b['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Home Page Category</label>
            <select class="form-control" name="desktop_home_category_id">
                <option value="">— Select Category —</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo (int)$cat['id']; ?>" <?php echo cf_sel($c, 'desktop_home_category_id', $cat['id']); ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Home Page Design</label>
            <select class="form-control" name="desktop_home_design">
                <?php foreach ($designOptions as $val => $lbl): ?>
                    <option value="<?php echo $val; ?>" <?php echo cf_sel($c, 'desktop_home_design', $val, 'design1'); ?>><?php echo $lbl; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 mb-3">
            <label class="form-label">Sort Order</label>
            <input type="number" class="form-control" name="desktop_sort_order" min="0"
                   value="<?php echo (int)($c['desktop_sort_order'] ?? 0); ?>">
        </div>
        <div class="col-md-2 mb-3">
            <label class="form-label">Status</label>
            <select class="form-control" name="desktop_status">
                <option value="active"   <?php echo cf_sel($c, 'desktop_status', 'active', 'active'); ?>>Active</option>
                <option value="inactive" <?php echo cf_sel($c, 'desktop_status', 'inactive'); ?>>Inactive</option>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Background Color</label>
            <input type="color" class="form-control form-control-color" name="desktop_bg_color"
                   value="<?php echo cf_val($c, 'desktop_bg_color', '#ffffff'); ?>">
        </div>
        <div class="col-md-8 mb-3">
            <label class="form-label">Background Image (optional)</label>
            <input type="file" class="form-control" name="desktop_bg_image" accept=".jpg,.jpeg,.png,.gif,.webp">
            <?php if (!empty($c['desktop_bg_image'])): ?>
                <small class="d-block mt-1">Current:
                    <a href="<?php echo htmlspecialchars($c['desktop_bg_image']); ?>" target="_blank"><?php echo htmlspecialchars(basename($c['desktop_bg_image'])); ?></a>
                </small>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mobile -->
    <hr>
    <h5 class="mb-3"><i class="fas fa-mobile-alt"></i> Mobile Settings</h5>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Show on Other Pages</label>
            <select class="form-control" name="mobile_other_pages">
                <option value="no"  <?php echo cf_sel($c, 'mobile_other_pages', 'no', 'no'); ?>>No</option>
                <option value="yes" <?php echo cf_sel($c, 'mobile_other_pages', 'yes'); ?>>Yes</option>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Category</label>
            <select class="form-control" name="mobile_category_id">
                <option value="">— Select Category —</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo (int)$cat['id']; ?>" <?php echo cf_sel($c, 'mobile_category_id', $cat['id']); ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Banner</label>
            <select class="form-control" name="mobile_banner_id">
                <option value="">— Select Banner —</option>
                <?php foreach ($banners as $b): ?>
                    <option value="<?php echo (int)$b['id']; ?>" <?php echo cf_sel($c, 'mobile_banner_id', $b['id']); ?>>
                        <?php echo htmlspecialchars($b['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3 mb-3">
            <label class="form-label">Show on Home Page</label>
            <select class="form-control" name="mobile_home_show">
                <option value="no"  <?php echo cf_sel($c, 'mobile_home_show', 'no', 'no'); ?>>No</option>
                <option value="yes" <?php echo cf_sel($c, 'mobile_home_show', 'yes'); ?>>Yes</option>
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Home Page Category</label>
            <select class="form-control" name="mobile_home_category_id">
                <option value="">— Select Category —</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo (int)$cat['id']; ?>" <?php echo cf_sel($c, 'mobile_home_category_id', $cat['id']); ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Home Page Design</label>
            <select class="form-control" name="mobile_home_design">
                <?php foreach ($designOptions as $val => $lbl): ?>
                    <option value="<?php echo $val; ?>" <?php echo cf_sel($c, 'mobile_home_design', $val, 'design1'); ?>><?php echo $lbl; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Other Page Design</label>
            <select class="form-control" name="mobile_other_design">
                <?php foreach ($designOptions as $val => $lbl): ?>
                    <option value="<?php echo $val; ?>" <?php echo cf_sel($c, 'mobile_other_design', $val, 'design1'); ?>><?php echo $lbl; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Background Color</label>
            <input type="color" class="form-control form-control-color" name="mobile_bg_color"
                   value="<?php echo cf_val($c, 'mobile_bg_color', '#ffffff'); ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Background Image (optional)</label>
            <input type="file" class="form-control" name="mobile_bg_image" accept=".jpg,.jpeg,.png,.gif,.webp">
            <?php if (!empty($c['mobile_bg_image'])): ?>
                <small class="d-block mt-1">Current:
                    <a href="<?php echo htmlspecialchars($c['mobile_bg_image']); ?>" target="_blank"><?php echo htmlspecialchars(basename($c['mobile_bg_image'])); ?></a>
                </small>
            <?php endif; ?>
        </div>
        <div class="col-md-2 mb-3">
            <label class="form-label">Sort Order</label>
            <input type="number" class="form-control" name="mobile_sort_order" min="0"
                   value="<?php echo (int)($c['mobile_sort_order'] ?? 0); ?>">
        </div>
        <div class="col-md-2 mb-3">
            <label class="form-label">Status</label>
            <select class="form-control" name="mobile_status">
                <option value="active"   <?php echo cf_sel($c, 'mobile_status', 'active', 'active'); ?>>Active</option>
                <option value="inactive" <?php echo cf_sel($c, 'mobile_status', 'inactive'); ?>>Inactive</option>
            </select>
        </div>
    </div>

    <!-- SEO -->
    <hr>
    <h5 class="mb-3"><i class="fas fa-search"></i> SEO</h5>
    <div class="mb-3">
        <label class="form-label">Meta Title</label>
        <input type="text" class="form-control" name="meta_title" maxlength="255" value="<?php echo cf_val($c, 'meta_title'); ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Meta Keywords</label>
        <input type="text" class="form-control" name="meta_keywords" value="<?php echo cf_val($c, 'meta_keywords'); ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Meta Description</label>
        <textarea class="form-control" name="meta_description" rows="3"><?php echo cf_val($c, 'meta_description'); ?></textarea>
    </div>

    <!-- Overall status -->
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Overall Status</label>
            <select class="form-control" name="status">
                <option value="active"   <?php echo cf_sel($c, 'status', 'active', 'active'); ?>>Active</option>
                <option value="inactive" <?php echo cf_sel($c, 'status', 'inactive'); ?>>Inactive</option>
            </select>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars($submitLabel); ?></button>
        <a href="carousels.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<script>
    // Auto-generate slug from title (only when slug is empty)
    (function () {
        const title = document.getElementById('title');
        const slug = document.getElementById('slug');
        if (!title || !slug) return;
        title.addEventListener('input', function () {
            if (slug.dataset.touched) return;
            slug.value = this.value.toLowerCase().trim()
                .replace(/[^a-z0-9 -]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-');
        });
        slug.addEventListener('input', function () { slug.dataset.touched = '1'; });
    })();
</script>
