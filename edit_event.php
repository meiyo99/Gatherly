<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/models/Event.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$event_id = $_GET['id'] ?? null;

if (!$event_id) {
    $_SESSION['error'] = 'Event ID is required.';
    header('Location: ' . BASE_URL . '/events.php');
    exit;
}

$eventModel = new Event();
$event = $eventModel->findById($event_id);

if (!$event) {
    $_SESSION['error'] = 'Event not found.';
    header('Location: ' . BASE_URL . '/events.php');
    exit;
}

if ($event['host_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = 'You do not have permission to edit this event.';
    header('Location: ' . BASE_URL . '/events.php');
    exit;
}

$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$form_data = $_SESSION['form_data'] ?? $event;
unset($_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Event - Gatherly</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        .sidebar {
            background-color: #2c3e50;
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 200px;
            padding: 0;
            display: flex;
            flex-direction: column;
        }
        .sidebar-brand {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-nav {
            list-style: none;
            padding: 20px 0;
            margin: 0;
            flex: 1;
        }
        .sidebar-footer {
            padding: 20px 0;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-nav-item {
            margin: 5px 0;
        }
        .sidebar-nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s;
        }
        .sidebar-nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        .sidebar-nav-link.active {
            background-color: #000;
            color: white;
        }
        .sidebar-nav-link i {
            margin-right: 12px;
            font-size: 1.1rem;
        }
        .main-content {
            margin-left: 200px;
            padding: 40px;
        }
        .page-header {
            margin-bottom: 30px;
        }
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .page-subtitle {
            color: #6c757d;
            font-size: 0.95rem;
        }
        .edit-card {
            background: white;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            max-width: 800px;
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
            border-color: #2c3e50;
            box-shadow: 0 0 0 0.2rem rgba(44,62,80,0.1);
        }
        .btn-save {
            background: #2c3e50;
            color: white;
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 600;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-save:hover {
            background: #1a252f;
            color: white;
        }
        .btn-cancel {
            background: white;
            color: #495057;
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 600;
            border: 2px solid #dee2e6;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-cancel:hover {
            background: #f8f9fa;
            color: #495057;
        }
        .alert {
            border-radius: 6px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand">Gatherly</div>
        <ul class="sidebar-nav">
            <li class="sidebar-nav-item">
                <a href="<?= BASE_URL ?>/dashboard.php" class="sidebar-nav-link">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="<?= BASE_URL ?>/events.php" class="sidebar-nav-link active">
                    <i class="bi bi-calendar-event"></i>
                    <span>Events</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="<?= BASE_URL ?>/invitations.php" class="sidebar-nav-link">
                    <i class="bi bi-envelope"></i>
                    <span>Invitations</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="<?= BASE_URL ?>/rsvps.php" class="sidebar-nav-link">
                    <i class="bi bi-check-circle"></i>
                    <span>RSVPs</span>
                </a>
            </li>
        </ul>
        <div class="sidebar-footer">
            <a href="<?= BASE_URL ?>/profile.php" class="sidebar-nav-link">
                <i class="bi bi-person-circle"></i>
                <span>Profile</span>
            </a>
            <a href="<?= BASE_URL ?>/handlers/logout_handler.php" class="sidebar-nav-link">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Edit Event</h1>
            <p class="page-subtitle">Update event details</p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="edit-card">
            <form action="<?= BASE_URL ?>/handlers/edit_event_handler.php" method="POST">
                <input type="hidden" name="event_id" value="<?= htmlspecialchars($event['event_id']) ?>">

                <div class="mb-4">
                    <label for="title" class="form-label">Event Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title"
                           value="<?= htmlspecialchars($form_data['title'] ?? '') ?>" required>
                </div>

                <div class="mb-4">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($form_data['description'] ?? '') ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label for="event_date" class="form-label">Event Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="event_date" name="event_date"
                               value="<?= htmlspecialchars($form_data['event_date'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6 mb-4">
                        <label for="event_time" class="form-label">Event Time</label>
                        <input type="time" class="form-control" id="event_time" name="event_time"
                               value="<?= htmlspecialchars($form_data['event_time'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="location" name="location"
                           value="<?= htmlspecialchars($form_data['location'] ?? '') ?>" required>
                </div>

                <div class="mb-4">
                    <label for="max_guests" class="form-label">Maximum Guests</label>
                    <input type="number" class="form-control" id="max_guests" name="max_guests"
                           value="<?= htmlspecialchars($form_data['max_guests'] ?? '') ?>" min="1">
                </div>

                <div class="mb-4">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="draft" <?= ($form_data['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= ($form_data['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="completed" <?= ($form_data['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= ($form_data['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn-save">
                        <i class="bi bi-check-circle"></i>
                        Update Event
                    </button>
                    <a href="<?= BASE_URL ?>/events.php" class="btn-cancel">
                        <i class="bi bi-x-circle"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
