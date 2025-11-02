<?php
require_once __DIR__ . '/config/config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$step = $_GET['step'] ?? 1;
$form_data = $_SESSION['form_data'] ?? [];
$guest_list = $_SESSION['guest_list'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_step1') {
        $_SESSION['form_data'] = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'event_date' => trim($_POST['event_date'] ?? ''),
            'event_time' => trim($_POST['event_time'] ?? ''),
            'location' => trim($_POST['location'] ?? ''),
            'max_guests' => trim($_POST['max_guests'] ?? ''),
            'status' => trim($_POST['status'] ?? 'published')
        ];
        header('Location: ' . BASE_URL . '/create_event.php?step=2');
        exit;
    } elseif ($_POST['action'] === 'add_guest') {
        $email = trim($_POST['guest_email'] ?? '');
        $name = trim($_POST['guest_name'] ?? '');

        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $guest_list[] = [
                'email' => $email,
                'name' => $name ?: '-',
                'status' => 'pending'
            ];
            $_SESSION['guest_list'] = $guest_list;
        }
        header('Location: ' . BASE_URL . '/create_event.php?step=2');
        exit;
    } elseif ($_POST['action'] === 'remove_guest') {
        $index = (int)$_POST['guest_index'];
        if (isset($guest_list[$index])) {
            unset($guest_list[$index]);
            $_SESSION['guest_list'] = array_values($guest_list);
        }
        header('Location: ' . BASE_URL . '/create_event.php?step=2');
        exit;
    }
}

$max_guests = (int)($form_data['max_guests'] ?? 100);
$total_guests = count($guest_list);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create New Event - Gatherly</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            padding: 40px 20px;
        }
        .create-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .page-subtitle {
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 30px;
        }
        .step-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-bottom: 40px;
        }
        .step {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
        }
        .step-number.active {
            background: #000;
            color: white;
        }
        .step-number.completed {
            background: #28a745;
            color: white;
        }
        .step-number.inactive {
            background: #e9ecef;
            color: #6c757d;
        }
        .step-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .step.active .step-label {
            color: #000;
            font-weight: 600;
        }
        .step-connector {
            width: 60px;
            height: 2px;
            background: #e9ecef;
        }
        .step-connector.completed {
            background: #28a745;
        }
        .form-card {
            background: white;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .form-section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 25px;
        }
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }
        .form-control, .form-select {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 10px 14px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #000;
            box-shadow: 0 0 0 0.2rem rgba(0,0,0,0.1);
        }
        .image-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
        }
        .image-upload-area:hover {
            border-color: #adb5bd;
            background: #e9ecef;
        }
        .image-upload-icon {
            font-size: 3rem;
            color: #adb5bd;
            margin-bottom: 10px;
        }
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e9ecef;
        }
        .btn-back {
            background: white;
            color: #000;
            border: 2px solid #e9ecef;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-back:hover {
            background: #f8f9fa;
            color: #000;
        }
        .btn-next {
            background: #000;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-next:hover {
            background: #333;
        }
        .btn-save-draft {
            background: white;
            color: #000;
            border: 2px solid #e9ecef;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
        }
        .btn-save-draft:hover {
            background: #f8f9fa;
        }
        .guest-input-section {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .guest-input-section input {
            flex: 1;
        }
        .guest-input-section .btn {
            flex-shrink: 0;
        }
        .guest-info-box {
            background: #f0f4ff;
            border-radius: 6px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .guest-info-box i {
            font-size: 1.5rem;
            color: #0d6efd;
        }
        .guest-info-box .guest-count {
            font-size: 1.5rem;
            font-weight: 700;
            color: #000;
        }
        .guest-table {
            width: 100%;
            margin-top: 20px;
        }
        .guest-table thead {
            background: #f8f9fa;
        }
        .guest-table th {
            padding: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            color: #495057;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }
        .guest-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #e9ecef;
        }
        .guest-status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .guest-status-badge.invited {
            background: #d4edda;
            color: #155724;
        }
        .guest-status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }
        .btn-guest-action {
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            padding: 5px 8px;
            font-size: 1rem;
        }
        .btn-guest-action:hover {
            color: #dc3545;
        }
        .help-text {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }
        .review-section {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #e9ecef;
        }
        .review-section:last-of-type {
            border-bottom: none;
        }
        .review-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .review-section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }
        .btn-edit {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .btn-edit:hover {
            text-decoration: underline;
        }
        .review-event-card {
            display: flex;
            gap: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }
        .review-event-image {
            width: 250px;
            height: 140px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            flex-shrink: 0;
        }
        .review-event-content {
            flex: 1;
        }
        .review-event-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .review-event-description {
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .review-event-meta {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .review-meta-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .review-meta-item i {
            font-size: 1.1rem;
            color: #6c757d;
            margin-top: 2px;
        }
        .review-meta-label {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 2px;
        }
        .review-meta-value {
            font-size: 0.9rem;
            font-weight: 500;
            color: #000;
        }
        .review-guest-summary {
            text-align: center;
            margin-bottom: 25px;
        }
        .review-guest-count-box {
            display: inline-block;
            background: #f0f4ff;
            border-radius: 8px;
            padding: 20px 40px;
        }
        .review-guest-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #000;
            line-height: 1;
        }
        .review-guest-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 5px;
        }
        .review-guest-list {
            margin-bottom: 25px;
        }
        .review-guest-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .review-guest-item:last-child {
            border-bottom: none;
        }
        .review-guest-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #2c3e50;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
        }
        .review-guest-info {
            flex: 1;
        }
        .review-guest-name {
            font-weight: 500;
            font-size: 0.95rem;
            color: #000;
        }
        .review-guest-email {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .review-more-guests {
            text-align: center;
            padding: 15px;
            color: #2c3e50;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .review-rsvp-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        .review-rsvp-item {
            text-align: center;
        }
        .review-rsvp-count {
            font-size: 1.75rem;
            font-weight: 700;
            color: #000;
            line-height: 1;
        }
        .review-rsvp-label {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="create-container">
        <h1 class="page-title">Create New Event</h1>
        <p class="page-subtitle">Set up your event details and manage your guest list</p>

        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step <?= $step == 1 ? 'active' : ($step > 1 ? 'completed' : '') ?>">
                <div class="step-number <?= $step == 1 ? 'active' : ($step > 1 ? 'completed' : 'inactive') ?>">
                    <?= $step > 1 ? '✓' : '1' ?>
                </div>
                <span class="step-label">Event Details</span>
            </div>
            <div class="step-connector <?= $step > 1 ? 'completed' : '' ?>"></div>
            <div class="step <?= $step == 2 ? 'active' : ($step > 2 ? 'completed' : '') ?>">
                <div class="step-number <?= $step == 2 ? 'active' : ($step > 2 ? 'completed' : 'inactive') ?>">
                    <?= $step > 2 ? '✓' : '2' ?>
                </div>
                <span class="step-label">Guest List</span>
            </div>
            <div class="step-connector <?= $step > 2 ? 'completed' : '' ?>"></div>
            <div class="step <?= $step == 3 ? 'active' : '' ?>">
                <div class="step-number <?= $step == 3 ? 'active' : 'inactive' ?>">3</div>
                <span class="step-label">Review</span>
            </div>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Step 1: Event Details -->
        <?php if ($step == 1): ?>
        <div class="form-card">
            <h2 class="form-section-title">Event Details</h2>
            <form action="<?= BASE_URL ?>/create_event.php?step=1" method="POST" id="eventForm">
                <input type="hidden" name="action" value="save_step1">

                <div class="mb-4">
                    <label for="title" class="form-label">Event Title</label>
                    <input type="text" class="form-control" id="title" name="title"
                           placeholder="Enter event title"
                           value="<?= htmlspecialchars($form_data['title'] ?? '') ?>" required>
                </div>

                <div class="mb-4">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4"
                              placeholder="Describe your event..."><?= htmlspecialchars($form_data['description'] ?? '') ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label for="event_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="event_date" name="event_date"
                               value="<?= htmlspecialchars($form_data['event_date'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label for="event_time" class="form-label">Start Time</label>
                        <input type="time" class="form-control" id="event_time" name="event_time"
                               value="<?= htmlspecialchars($form_data['event_time'] ?? '14:00') ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" class="form-control" id="location" name="location"
                           placeholder="Enter venue or online meeting link"
                           value="<?= htmlspecialchars($form_data['location'] ?? '') ?>" required>
                </div>

                <div class="mb-4">
                    <label for="max_guests" class="form-label">Max Attendees</label>
                    <input type="number" class="form-control" id="max_guests" name="max_guests"
                           placeholder="100"
                           value="<?= htmlspecialchars($form_data['max_guests'] ?? '') ?>" min="1">
                </div>

                <div class="mb-4">
                    <label class="form-label">Event Image</label>
                    <div class="image-upload-area">
                        <div class="image-upload-icon">
                            <i class="bi bi-cloud-upload"></i>
                        </div>
                        <p class="mb-2">Drag and drop an image, or click to browse</p>
                        <button type="button" class="btn btn-sm btn-outline-secondary">Choose File</button>
                        <p class="text-muted small mt-2 mb-0">Image upload coming soon</p>
                    </div>
                </div>

                <input type="hidden" name="status" value="published">

                <div class="form-actions">
                    <a href="<?= BASE_URL ?>/events.php" class="btn-back">
                        <i class="bi bi-arrow-left"></i>
                        Back
                    </a>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn-next">
                            Next Step
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Step 2: Guest List -->
        <?php if ($step == 2): ?>
        <div class="form-card">
            <h2 class="form-section-title">Guest List</h2>

            <form method="POST" action="<?= BASE_URL ?>/create_event.php?step=2">
                <input type="hidden" name="action" value="add_guest">
                <div class="guest-input-section">
                    <input type="email"
                           class="form-control"
                           name="guest_email"
                           placeholder="Enter guest email address"
                           required>
                    <input type="text"
                           class="form-control"
                           name="guest_name"
                           placeholder="Guest name (optional)">
                    <button type="submit" class="btn btn-dark">Add Guest</button>
                </div>
                <p class="help-text">You can also paste multiple emails separated by commas</p>
            </form>

            <div class="guest-info-box">
                <i class="bi bi-people-fill"></i>
                <div>
                    <div class="guest-count"><?= $total_guests ?></div>
                    <div class="small text-muted">Total Guests</div>
                </div>
                <div class="ms-auto text-end">
                    <div class="small text-muted">Maximum capacity: <?= $max_guests ?></div>
                </div>
            </div>

            <?php if (!empty($guest_list)): ?>
            <table class="guest-table">
                <thead>
                    <tr>
                        <th>Guest Name</th>
                        <th>Email Address</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($guest_list as $index => $guest): ?>
                    <tr>
                        <td><?= htmlspecialchars($guest['name']) ?></td>
                        <td><?= htmlspecialchars($guest['email']) ?></td>
                        <td>
                            <span class="guest-status-badge <?= $guest['status'] ?>">
                                <?= ucfirst($guest['status']) ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;" action="<?= BASE_URL ?>/create_event.php?step=2">
                                <input type="hidden" name="action" value="remove_guest">
                                <input type="hidden" name="guest_index" value="<?= $index ?>">
                                <button type="submit" class="btn-guest-action" title="Remove guest">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-person-plus" style="font-size: 3rem; color: #dee2e6;"></i>
                <p class="text-muted mt-3">No guests added yet. Start by adding guest emails above.</p>
            </div>
            <?php endif; ?>

            <div class="form-actions">
                <a href="<?= BASE_URL ?>/create_event.php?step=1" class="btn-back">
                    <i class="bi bi-arrow-left"></i>
                    Back
                </a>
                <a href="<?= BASE_URL ?>/create_event.php?step=3" class="btn-next">
                    Next Step
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Step 3: Review -->
        <?php if ($step == 3): ?>
        <div class="form-card">
            <h2 class="form-section-title">Review & Submit</h2>

            <!-- Event Details Section -->
            <div class="review-section">
                <div class="review-section-header">
                    <h3 class="review-section-title">Event Details</h3>
                    <a href="<?= BASE_URL ?>/create_event.php?step=1" class="btn-edit">Edit</a>
                </div>

                <div class="review-event-card">
                    <div class="review-event-image">
                        <i class="bi bi-image"></i>
                    </div>
                    <div class="review-event-content">
                        <h4 class="review-event-title"><?= htmlspecialchars($form_data['title'] ?? 'Untitled Event') ?></h4>
                        <p class="review-event-description"><?= htmlspecialchars($form_data['description'] ?? 'No description provided.') ?></p>

                        <div class="review-event-meta">
                            <div class="review-meta-item">
                                <i class="bi bi-tag"></i>
                                <div>
                                    <div class="review-meta-label">Event Type</div>
                                    <div class="review-meta-value">Conference • Business</div>
                                </div>
                            </div>
                            <div class="review-meta-item">
                                <i class="bi bi-calendar3"></i>
                                <div>
                                    <div class="review-meta-label">Date</div>
                                    <div class="review-meta-value"><?= !empty($form_data['event_date']) ? date('F d, Y', strtotime($form_data['event_date'])) : 'Not set' ?></div>
                                </div>
                            </div>
                            <div class="review-meta-item">
                                <i class="bi bi-clock"></i>
                                <div>
                                    <div class="review-meta-label">Time</div>
                                    <div class="review-meta-value"><?= !empty($form_data['event_time']) ? date('g:i A', strtotime($form_data['event_time'])) : 'Not set' ?></div>
                            </div>
                            </div>
                            <div class="review-meta-item">
                                <i class="bi bi-geo-alt"></i>
                                <div>
                                    <div class="review-meta-label">Location</div>
                                    <div class="review-meta-value"><?= htmlspecialchars($form_data['location'] ?? 'Not set') ?></div>
                                </div>
                            </div>
                            <div class="review-meta-item">
                                <i class="bi bi-people"></i>
                                <div>
                                    <div class="review-meta-label">Max Attendees</div>
                                    <div class="review-meta-value"><?= htmlspecialchars($form_data['max_guests'] ?? 'Unlimited') ?> people</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Guest List Section -->
            <div class="review-section">
                <div class="review-section-header">
                    <h3 class="review-section-title">Guest List</h3>
                    <a href="<?= BASE_URL ?>/create_event.php?step=2" class="btn-edit">Edit</a>
                </div>

                <div class="review-guest-summary">
                    <div class="review-guest-count-box">
                        <div class="review-guest-number"><?= count($guest_list) ?></div>
                        <div class="review-guest-label">guests invited</div>
                    </div>
                </div>

                <?php if (!empty($guest_list)): ?>
                <div class="review-guest-list">
                    <?php
                    $display_limit = 5;
                    $displayed_guests = array_slice($guest_list, 0, $display_limit);
                    $remaining = count($guest_list) - $display_limit;
                    ?>
                    <?php foreach ($displayed_guests as $guest): ?>
                    <div class="review-guest-item">
                        <div class="review-guest-avatar">
                            <?= strtoupper(substr($guest['name'], 0, 1)) ?>
                        </div>
                        <div class="review-guest-info">
                            <div class="review-guest-name"><?= htmlspecialchars($guest['name']) ?></div>
                            <div class="review-guest-email"><?= htmlspecialchars($guest['email']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if ($remaining > 0): ?>
                    <div class="review-more-guests">
                        + <?= $remaining ?> more guest<?= $remaining > 1 ? 's' : '' ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="review-rsvp-stats">
                    <div class="review-rsvp-item">
                        <div class="review-rsvp-count">0</div>
                        <div class="review-rsvp-label">Confirmed</div>
                    </div>
                    <div class="review-rsvp-item">
                        <div class="review-rsvp-count">0</div>
                        <div class="review-rsvp-label">Declined</div>
                    </div>
                    <div class="review-rsvp-item">
                        <div class="review-rsvp-count"><?= count($guest_list) ?></div>
                        <div class="review-rsvp-label">Pending</div>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-person-plus" style="font-size: 2rem; opacity: 0.3;"></i>
                    <p class="mb-0 mt-2">No guests added yet</p>
                </div>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <a href="<?= BASE_URL ?>/create_event.php?step=2" class="btn-back">
                    <i class="bi bi-arrow-left"></i>
                    Back
                </a>
                <form action="<?= BASE_URL ?>/handlers/create_event_handler.php" method="POST" style="display: inline;">
                    <input type="hidden" name="title" value="<?= htmlspecialchars($form_data['title'] ?? '') ?>">
                    <input type="hidden" name="description" value="<?= htmlspecialchars($form_data['description'] ?? '') ?>">
                    <input type="hidden" name="event_date" value="<?= htmlspecialchars($form_data['event_date'] ?? '') ?>">
                    <input type="hidden" name="event_time" value="<?= htmlspecialchars($form_data['event_time'] ?? '') ?>">
                    <input type="hidden" name="location" value="<?= htmlspecialchars($form_data['location'] ?? '') ?>">
                    <input type="hidden" name="max_guests" value="<?= htmlspecialchars($form_data['max_guests'] ?? '') ?>">
                    <input type="hidden" name="status" value="published">
                    <button type="submit" class="btn-next">Publish Event</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
