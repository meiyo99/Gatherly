<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/config/Database.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$user_role = $_SESSION['role'];

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT COUNT(*) as total FROM events WHERE host_id = :user_id");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$total_events = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) as total FROM events WHERE host_id = :user_id AND event_date >= CURDATE() AND status = 'published'");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$upcoming_events = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) as total FROM rsvps r INNER JOIN events e ON r.event_id = e.event_id WHERE e.host_id = :user_id");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$total_rsvps = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT * FROM events WHERE host_id = :user_id ORDER BY created_at DESC LIMIT 5");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$recent_events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gatherly</title>
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
        .alert {
            border-radius: 0;
            border-width: 2px;
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
                <h1 class="mb-4">Dashboard</h1>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?= htmlspecialchars($_SESSION['success']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <div class="alert alert-info">
                    <h4 class="alert-heading">Welcome, <?= htmlspecialchars($_SESSION['first_name']) ?>!</h4>
                    <p>You are logged in as: <strong><?= htmlspecialchars($user_role) ?></strong></p>
                    <hr>
                    <p class="mb-0">This is your main dashboard. Use the buttons below to manage your events!</p>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h6 class="card-subtitle mb-2 text-white-50">Total Events</h6>
                        <h2 class="card-title mb-0"><?= $total_events ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h6 class="card-subtitle mb-2 text-white-50">Upcoming Events</h6>
                        <h2 class="card-title mb-0"><?= $upcoming_events ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h6 class="card-subtitle mb-2 text-white-50">Total RSVPs</h6>
                        <h2 class="card-title mb-0"><?= $total_rsvps ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-3">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-plus text-primary" style="font-size: 3rem;"></i>
                        <h5 class="card-title mt-3">Create Event</h5>
                        <p class="card-text">Plan your next celebration</p>
                        <a href="<?= BASE_URL ?>/create_event.php" class="btn btn-primary">Create Event</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-check text-success" style="font-size: 3rem;"></i>
                        <h5 class="card-title mt-3">My Events</h5>
                        <p class="card-text">View and manage events</p>
                        <a href="<?= BASE_URL ?>/events.php" class="btn btn-success">View Events</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-envelope-check text-warning" style="font-size: 3rem;"></i>
                        <h5 class="card-title mt-3">My RSVPs</h5>
                        <p class="card-text">View and manage RSVPs</p>
                        <a href="<?= BASE_URL ?>/rsvps.php" class="btn btn-warning">View RSVPs</a>
                    </div>
                </div>
            </div>
        </div>

        <?php if (count($recent_events) > 0): ?>
        <div class="row mt-5">
            <div class="col-12">
                <h3 class="mb-4">Recent Events</h3>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Date</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_events as $event): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($event['title']) ?></strong>
                                        </td>
                                        <td>
                                            <i class="bi bi-calendar-date me-1"></i>
                                            <?= date('M d, Y', strtotime($event['event_date'])) ?>
                                        </td>
                                        <td>
                                            <i class="bi bi-geo-alt me-1"></i>
                                            <?= htmlspecialchars($event['location']) ?>
                                        </td>
                                        <td>
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
                                        </td>
                                        <td>
                                            <a href="<?= BASE_URL ?>/view_event.php?id=<?= $event['event_id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye me-1"></i>View
                                            </a>
                                            <a href="<?= BASE_URL ?>/edit_event.php?id=<?= $event['event_id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil me-1"></i>Edit
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
