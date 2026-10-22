<?php
session_start();
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/AuthController.php';

$database = new Database();
$db = $database->getConnection();
$auth = new AuthController($db);

if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Fetch all locations
$stmt = $db->prepare("SELECT * FROM delivery_locations ORDER BY display_order ASC, city_name ASC");
$stmt->execute();
$result = $stmt->get_result();
$locations = [];
while ($row = $result->fetch_assoc()) {
    $locations[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Delivery Locations & Pincodes | Printmont Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .badge-status { padding: 5px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 600; cursor: pointer; }
        .badge-active { background-color: #d1fae5; color: #065f46; }
        .badge-inactive { background-color: #fee2e2; color: #991b1b; }
        .badge-sameday { background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .badge-nextday { background-color: #fef3c7; color: #92400e; }
        .table th { background-color: #f8fafc; font-size: 12.5px; text-transform: uppercase; letter-spacing: 0.5px; }
        .table td { vertical-align: middle; font-size: 13.5px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once "includes/side-navbar.php"; ?>
        <div class="main">
            <?php include_once "includes/top-navbar.php"; ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <div class="row mb-3 align-items-center">
                        <div class="col">
                            <h1 class="h3 mb-1"><strong>Delivery Locations</strong> & Popular Cities</h1>
                            <p class="text-muted mb-0">Manage deliverable cities, default pincodes, same-day delivery flags, and delivery speeds (FNP-style location selector).</p>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-primary" onclick="openAddModal()">
                                <i class="fas fa-plus me-1"></i> Add Location
                            </button>
                        </div>
                    </div>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-1"></i> <?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 fw-bold"><i class="fas fa-map-marker-alt text-danger me-2"></i>Active Delivery Hubs (<?php echo count($locations); ?>)</h5>
                            <input type="text" id="tableFilter" class="form-control form-control-sm w-auto" placeholder="Search city or pincode..." onkeyup="filterTable()">
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0" id="locationsTable">
                                    <thead>
                                        <tr>
                                            <th>Order</th>
                                            <th>City / Region</th>
                                            <th>State</th>
                                            <th>Default Pincode</th>
                                            <th>Popular City</th>
                                            <th>Same Day</th>
                                            <th>Next Day</th>
                                            <th>Speed</th>
                                            <th>Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($locations)): ?>
                                            <tr>
                                                <td colspan="10" class="text-center py-4 text-muted">No delivery locations found.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($locations as $loc): ?>
                                                <tr id="row-<?php echo $loc['id']; ?>">
                                                    <td class="fw-bold text-muted"><?php echo $loc['display_order']; ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-city text-primary me-2"></i>
                                                            <strong class="text-dark"><?php echo htmlspecialchars($loc['city_name']); ?></strong>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($loc['state_name']); ?></td>
                                                    <td><span class="badge bg-secondary font-monospace"><?php echo htmlspecialchars($loc['pincode'] ?: 'N/A'); ?></span></td>
                                                    <td>
                                                        <span class="badge-status <?php echo $loc['is_popular'] ? 'badge-active' : 'badge-inactive'; ?>" onclick="toggleFlag(<?php echo $loc['id']; ?>, 'is_popular')">
                                                            <?php echo $loc['is_popular'] ? '<i class="fas fa-star text-warning me-1"></i> Yes' : 'No'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge-status <?php echo $loc['is_same_day'] ? 'badge-sameday' : 'badge-inactive'; ?>" onclick="toggleFlag(<?php echo $loc['id']; ?>, 'is_same_day')">
                                                            <?php echo $loc['is_same_day'] ? '<i class="fas fa-bolt me-1"></i> Yes' : 'No'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge-status <?php echo $loc['is_next_day'] ? 'badge-nextday' : 'badge-inactive'; ?>" onclick="toggleFlag(<?php echo $loc['id']; ?>, 'is_next_day')">
                                                            <?php echo $loc['is_next_day'] ? '<i class="fas fa-check me-1"></i> Yes' : 'No'; ?>
                                                        </span>
                                                    </td>
                                                    <td><small class="text-muted fw-semibold"><?php echo htmlspecialchars($loc['standard_delivery_days']); ?></small></td>
                                                    <td>
                                                        <span class="badge-status <?php echo $loc['status'] === 'active' ? 'badge-active' : 'badge-inactive'; ?>" onclick="toggleFlag(<?php echo $loc['id']; ?>, 'status')">
                                                            <?php echo ucfirst($loc['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        <button class="btn btn-sm btn-outline-primary me-1" onclick='openEditModal(<?php echo json_encode($loc); ?>)' title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteLocation(<?php echo $loc['id']; ?>, '<?php echo htmlspecialchars(addslashes($loc['city_name'])); ?>')" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <!-- Location Add/Edit Modal -->
    <div class="modal fade" id="locationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white" id="modalTitle"><i class="fas fa-map-marker-alt me-2"></i> Add Delivery Location</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="locationForm" onsubmit="saveLocation(event)">
                    <div class="modal-body p-4">
                        <input type="hidden" id="loc_id" name="id" value="0">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">City / Region Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="loc_city" name="city_name" placeholder="e.g. Delhi NCR, Mumbai, Bengaluru" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">State Name</label>
                                <input type="text" class="form-control" id="loc_state" name="state_name" placeholder="e.g. Delhi, Maharashtra">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Default Pincode</label>
                                <input type="text" class="form-control font-monospace" id="loc_pincode" name="pincode" placeholder="e.g. 110001" maxlength="6">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Standard Delivery Speed</label>
                            <input type="text" class="form-control" id="loc_speed" name="standard_delivery_days" placeholder="e.g. Same Day / 1-2 Days, 2-3 Days" value="1-2 Days">
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="loc_popular" name="is_popular" value="1" checked>
                                    <label class="form-check-label fw-semibold" for="loc_popular">Popular City</label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="loc_sameday" name="is_same_day" value="1">
                                    <label class="form-check-label fw-semibold" for="loc_sameday">Same Day</label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="loc_nextday" name="is_next_day" value="1" checked>
                                    <label class="form-check-label fw-semibold" for="loc_nextday">Next Day</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Display Order</label>
                                <input type="number" class="form-control" id="loc_order" name="display_order" value="1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Status</label>
                                <select class="form-select" id="loc_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveBtn"><i class="fas fa-save me-1"></i> Save Location</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        const modal = new bootstrap.Modal(document.getElementById('locationModal'));

        function openAddModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-map-marker-alt me-2"></i> Add Delivery Location';
            document.getElementById('loc_id').value = '0';
            document.getElementById('loc_city').value = '';
            document.getElementById('loc_state').value = '';
            document.getElementById('loc_pincode').value = '';
            document.getElementById('loc_speed').value = '1-2 Days';
            document.getElementById('loc_popular').checked = true;
            document.getElementById('loc_sameday').checked = false;
            document.getElementById('loc_nextday').checked = true;
            document.getElementById('loc_order').value = '1';
            document.getElementById('loc_status').value = 'active';
            modal.show();
        }

        function openEditModal(loc) {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i> Edit Delivery Location';
            document.getElementById('loc_id').value = loc.id;
            document.getElementById('loc_city').value = loc.city_name || '';
            document.getElementById('loc_state').value = loc.state_name || '';
            document.getElementById('loc_pincode').value = loc.pincode || '';
            document.getElementById('loc_speed').value = loc.standard_delivery_days || '1-2 Days';
            document.getElementById('loc_popular').checked = Number(loc.is_popular) === 1;
            document.getElementById('loc_sameday').checked = Number(loc.is_same_day) === 1;
            document.getElementById('loc_nextday').checked = Number(loc.is_next_day) === 1;
            document.getElementById('loc_order').value = loc.display_order || '1';
            document.getElementById('loc_status').value = loc.status || 'active';
            modal.show();
        }

        async function saveLocation(e) {
            e.preventDefault();
            const form = document.getElementById('locationForm');
            const formData = new FormData(form);
            formData.append('action', 'admin_save');
            if (!document.getElementById('loc_popular').checked) formData.set('is_popular', '0');
            if (!document.getElementById('loc_sameday').checked) formData.set('is_same_day', '0');
            if (!document.getElementById('loc_nextday').checked) formData.set('is_next_day', '0');

            document.getElementById('saveBtn').disabled = true;
            try {
                const res = await fetch('api/locations-api.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Failed to save location');
                }
            } catch (err) {
                alert('Network error while saving location');
            } finally {
                document.getElementById('saveBtn').disabled = false;
            }
        }

        async function toggleFlag(id, field) {
            const formData = new FormData();
            formData.append('action', 'admin_toggle');
            formData.append('id', id);
            formData.append('field', field);
            try {
                const res = await fetch('api/locations-api.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                }
            } catch (err) {
                console.error(err);
            }
        }

        async function deleteLocation(id, cityName) {
            if (!confirm(`Are you sure you want to delete "${cityName}"?`)) return;
            const formData = new FormData();
            formData.append('action', 'admin_delete');
            formData.append('id', id);
            try {
                const res = await fetch('api/locations-api.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    const row = document.getElementById('row-' + id);
                    if (row) row.remove();
                }
            } catch (err) {
                alert('Error deleting location');
            }
        }

        function filterTable() {
            const q = document.getElementById('tableFilter').value.toLowerCase();
            const rows = document.querySelectorAll('#locationsTable tbody tr');
            rows.forEach(r => {
                const text = r.innerText.toLowerCase();
                r.style.display = text.includes(q) ? '' : 'none';
            });
        }
    </script>
</body>
</html>
