<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/models/Event.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$user_role = $_SESSION['role'];

$eventModel = new Event();
$events = $eventModel->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Events - Gatherly</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f0f0f0;
        }
        .card {
            border: 2px solid #ddd;
            border-radius: 0;
            box-shadow: none;
        }
        .btn {
            border-radius: 0;
        }
        .navbar {
            border-radius: 0;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= BASE_URL ?>/dashboard.php">
                <i class="bi bi-calendar-event me-2"></i>Gatherly
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/dashboard.php">
                            <i class="bi bi-house me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?= BASE_URL ?>/events.php">
                            <i class="bi bi-calendar-check me-1"></i>Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($user_name) ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/handlers/logout_handler.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="bi bi-calendar-event me-2"></i>All Events</h1>
                    <div class="d-flex gap-2">
                        <a href="<?= BASE_URL ?>/create_event.php" class="btn btn-success">
                            <i class="bi bi-plus-circle me-1"></i>Create Event
                        </a>
                        <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                    </div>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?= htmlspecialchars($_SESSION['success']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (!empty($events) && is_array($events)): ?>
                    <div class="row g-3">
                        <?php foreach ($events as $event): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title mb-0"><?= htmlspecialchars($event['title']) ?></h5>
                                            <span class="badge bg-<?php
                                                echo match($event['status']) {
                                                    'published' => 'success',
                                                    'draft' => 'secondary',
                                                    'completed' => 'primary',
                                                    'cancelled' => 'danger',
                                                    default => 'secondary'
                                                };
                                            ?>">
                                                <?= ucfirst(htmlspecialchars($event['status'])) ?>
                                            </span>
                                        </div>

                                        <?php if (!empty($event['description'])): ?>
                                            <p class="card-text text-muted small">
                                                <?= htmlspecialchars(mb_substr($event['description'], 0, 100)) ?>
                                                <?= strlen($event['description']) > 100 ? '...' : '' ?>
                                            </p>
                                        <?php endif; ?>

                                        <ul class="list-unstyled small mb-3">
                                            <li class="mb-1">
                                                <i class="bi bi-calendar-date text-primary me-2"></i>
                                                <?php
                                                    echo date('M d, Y', strtotime($event['event_date']));
                                                    if (!empty($event['event_time']) && $event['event_time'] !== '00:00:00') {
                                                        echo ' at ' . date('g:i A', strtotime($event['event_time']));
                                                    }
                                                ?>
                                            </li>
                                            <li class="mb-1">
                                                <i class="bi bi-geo-alt text-danger me-2"></i>
                                                <?= htmlspecialchars($event['location']) ?>
                                            </li>
                                            <li class="mb-1">
                                                <i class="bi bi-people text-success me-2"></i>
                                                Max Guests: <?= (int)$event['max_guests'] ?: 'Unlimited' ?>
                                            </li>
                                            <li class="mb-1">
                                                <i class="bi bi-person text-info me-2"></i>
                                                Host ID: <?= $event['host_id'] ?>
                                            </li>
                                        </ul>

                                        <div class="d-flex gap-2">
                                            <a href="<?= BASE_URL ?>/view_event.php?id=<?= $event['event_id'] ?>" class="btn btn-sm btn-primary flex-fill">
                                                <i class="bi bi-eye me-1"></i>View
                                            </a>
                                            <a href="<?= BASE_URL ?>/edit_event.php?id=<?= $event['event_id'] ?>" class="btn btn-sm btn-outline-primary flex-fill">
                                                <i class="bi bi-pencil me-1"></i>Edit
                                            </a>
                                            <a href="<?= BASE_URL ?>/handlers/delete_event_handler.php?id=<?= $event['event_id'] ?>"
                                               class="btn btn-sm btn-outline-danger flex-fill"
                                               onclick="return confirm('Are you sure you want to delete this event?');">
                                                <i class="bi bi-trash me-1"></i>Delete
                                            </a>
                                        </div>
                                    </div>
                                    <div class="card-footer text-muted small">
                                        <i class="bi bi-clock me-1"></i>
                                        Created: <?= date('M d, Y', strtotime($event['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>No events found!</strong> There are currently no events in the database.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
