<div class="col-md-12">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Add Product</h5>
            <h6 class="card-subtitle text-muted">Add new product to the store.</h6>
        </div>
        <div class="card-body">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <div class="alert-message"><?php echo htmlspecialchars($error_message); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="mb-3 col-md-6">
                        <label class="form-label" for="name">Product Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required 
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                               placeholder="Enter product name">
                    </div>
                    <div class="mb-3 col-md-6">
                        <label class="form-label" for="sku">SKU *</label>
                        <input type="text" class="form-control" id="sku" name="sku" required 
                               value="<?php echo isset($_POST['sku']) ? htmlspecialchars($_POST['sku']) : ''; ?>"
                               placeholder="Enter SKU">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" 
                              placeholder="Enter product description"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <div class="row">
                    <div class="mb-3 col-md-4">
                        <label class="form-label" for="category_id">Category *</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                    <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3 col-md-4">
                        <label class="form-label" for="brand">Brand</label>
                        <input type="text" class="form-control" id="brand" name="brand" 
                               value="<?php echo isset($_POST['brand']) ? htmlspecialchars($_POST['brand']) : ''; ?>"
                               placeholder="Enter brand name">
                    </div>
                    <div class="mb-3 col-md-4">
                        <label class="form-label" for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="draft" <?php echo (isset($_POST['status']) && $_POST['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="mb-3 col-md-4">
                        <label class="form-label" for="price">Price *</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" required 
                               value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>"
                               placeholder="0.00">
                    </div>
                    <div class="mb-3 col-md-4">
                        <label class="form-label" for="discount_price">Discount Price</label>
                        <input type="number" step="0.01" class="form-control" id="discount_price" name="discount_price" 
                               value="<?php echo isset($_POST['discount_price']) ? htmlspecialchars($_POST['discount_price']) : ''; ?>"
                               placeholder="0.00">
                    </div>
                    <div class="mb-3 col-md-4">
                        <label class="form-label" for="stock_quantity">Stock Quantity *</label>
                        <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" required 
                               value="<?php echo isset($_POST['stock_quantity']) ? htmlspecialchars($_POST['stock_quantity']) : ''; ?>"
                               placeholder="0">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="images">Product Images</label>
                    <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                    <small class="form-text text-muted">First image will be set as primary. You can select multiple images (JPEG, PNG, GIF, WebP).</small>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="featured" name="featured" value="1" 
                               <?php echo (isset($_POST['featured']) && $_POST['featured'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="featured">Featured Product</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Add Product</button>
                <a href="products.php?action=list" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>