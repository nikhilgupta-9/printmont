<?php
session_start();
require_once 'config/constants.php';
require_once 'controllers/ContactController.php';

$contactController = new ContactController();

// Status / notes update from the row form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inquiry_id'])) {
    $ok = $contactController->updateInquiryStatus(
        $_POST['inquiry_id'],
        $_POST['status'] ?? 'new',
        trim($_POST['admin_notes'] ?? '')
    );

    $_SESSION[$ok ? 'success_message' : 'error_message'] =
        $ok ? 'Inquiry updated.' : 'Could not update the inquiry.';

    header('Location: contact-inquiries.php' . (!empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    exit();
}

$statusFilter = $_GET['status'] ?? '';
$inquiries = $contactController->getInquiries($statusFilter);

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$STATUSES = ['new' => 'New', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Contact Inquiries | Printmont</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">
    <style>
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-new { background-color: #cce7ff; color: #004085; }
        .status-in_progress { background-color: #fff3cd; color: #856404; }
        .status-resolved { background-color: #d4edda; color: #155724; }
        .status-closed { background-color: #e2e3e5; color: #383d41; }
        .msg-cell { max-width: 380px; white-space: pre-line; }
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
                            <h3><strong>Contact</strong> Inquiries</h3>
                        </div>
                        <div class="col-auto ms-auto text-end mt-n1">
                            <a href="general-settings.php" class="btn btn-light">Contact Details</a>
                        </div>
                    </div>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($success_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="alert-message"><?php echo htmlspecialchars($error_message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Messages from the storefront Contact page</h5>
                            <h6 class="card-subtitle text-muted">
                                Submitted via <code>api/contact-api.php</code>.
                            </h6>
                            <div class="mt-3">
                                <a href="contact-inquiries.php"
                                   class="btn btn-sm <?php echo $statusFilter === '' ? 'btn-primary' : 'btn-light'; ?>">All</a>
                                <?php foreach ($STATUSES as $key => $label): ?>
                                    <a href="contact-inquiries.php?status=<?php echo urlencode($key); ?>"
                                       class="btn btn-sm <?php echo $statusFilter === $key ? 'btn-primary' : 'btn-light'; ?>">
                                        <?php echo $label; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="card-body">
                            <?php if (empty($inquiries)): ?>
                                <div class="text-center py-4">
                                    <h5>No inquiries yet</h5>
                                    <p class="text-muted mb-0">
                                        Messages sent from the storefront Contact page will appear here.
                                    </p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Received</th>
                                                <th>From</th>
                                                <th>Subject</th>
                                                <th>Message</th>
                                                <th style="min-width:260px;">Status &amp; Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($inquiries as $row): ?>
                                            <tr>
                                                <td><?php echo (int) $row['id']; ?></td>
                                                <td class="text-muted" style="white-space:nowrap;">
                                                    <?php echo htmlspecialchars(date('d M Y, H:i', strtotime($row['created_at']))); ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                                                    <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>">
                                                        <?php echo htmlspecialchars($row['email']); ?>
                                                    </a>
                                                    <?php if (!empty($row['phone'])): ?>
                                                        <br><span class="text-muted"><?php echo htmlspecialchars($row['phone']); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['subject'] ?: '—'); ?></td>
                                                <td class="msg-cell"><?php echo htmlspecialchars($row['message']); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo htmlspecialchars($row['status']); ?>">
                                                        <?php echo htmlspecialchars($STATUSES[$row['status']] ?? ucfirst($row['status'])); ?>
                                                    </span>
                                                    <form method="POST"
                                                          action="contact-inquiries.php<?php echo $statusFilter !== '' ? '?status=' . urlencode($statusFilter) : ''; ?>"
                                                          class="mt-2">
                                                        <input type="hidden" name="inquiry_id" value="<?php echo (int) $row['id']; ?>">
                                                        <select name="status" class="form-select form-select-sm mb-2">
                                                            <?php foreach ($STATUSES as $key => $label): ?>
                                                                <option value="<?php echo $key; ?>" <?php echo $row['status'] === $key ? 'selected' : ''; ?>>
                                                                    <?php echo $label; ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <textarea name="admin_notes" rows="2" class="form-control form-control-sm mb-2"
                                                                  placeholder="Internal notes"><?php echo htmlspecialchars($row['admin_notes'] ?? ''); ?></textarea>
                                                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
            <?php include_once "includes/footer.php"; ?>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
