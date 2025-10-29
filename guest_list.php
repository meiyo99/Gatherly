<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/config/Database.php';
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
    $_SESSION['error'] = 'You do not have permission to view this guest list.';
    header('Location: ' . BASE_URL . '/events.php');
    exit;
}

$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT
        r.*,
        u.email as user_email,
        u.first_name,
        u.last_name
    FROM rsvps r
    LEFT JOIN users u ON r.guest_id = u.user_id
    WHERE r.event_id = :event_id
    ORDER BY r.response_date DESC
");
$stmt->execute([':event_id' => $event_id]);
$guests = $stmt->fetchAll();

$stmt = $db->prepare("SELECT status, COUNT(*) as count FROM rsvps WHERE event_id = :event_id GROUP BY status");
$stmt->execute([':event_id' => $event_id]);
$rsvp_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$attending = $rsvp_counts['yes'] ?? 0;
$maybe = $rsvp_counts['maybe'] ?? 0;
$not_attending = $rsvp_counts['no'] ?? 0;
$total_rsvps = $attending + $maybe + $not_attending;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Guest List - <?= htmlspecialchars($event['title']) ?></title>
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
        .table {
            border: 2px solid #ddd;
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
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/dashboard.php">
                            <i class="bi bi-house-door me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/events.php">
                            <i class="bi bi-calendar-check me-1"></i>My Events
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/profile.php">
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
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1>Guest List</h1>
                        <h5 class="text-muted"><?= htmlspecialchars($event['title']) ?></h5>
                    </div>
                    <div>
                        <a href="<?= BASE_URL ?>/handlers/download_guest_list.php?id=<?= $event_id ?>" class="btn btn-success me-2">
                            <i class="bi bi-download me-2"></i>Download CSV
                        </a>
                        <a href="<?= BASE_URL ?>/view_event.php?id=<?= $event_id ?>" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left me-2"></i>Back to Event
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h2 class="mb-0"><?= $attending ?></h2>
                        <small>Attending</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h2 class="mb-0"><?= $maybe ?></h2>
                        <small>Maybe</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h2 class="mb-0"><?= $not_attending ?></h2>
                        <small>Can't Go</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h2 class="mb-0"><?= $total_rsvps ?></h2>
                        <small>Total Responses</small>
                    </div>
                </div>
            </div>
        </div>

        <?php if (count($guests) > 0): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Guests</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Plus Ones</th>
                                        <th>Dietary Restrictions</th>
                                        <th>RSVP Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($guests as $guest): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($guest['guest_name']) ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($guest['guest_email']) ?></td>
                                        <td>
                                            <span class="badge bg-<?php
                                                echo match($guest['status']) {
                                                    'yes' => 'success',
                                                    'maybe' => 'warning',
                                                    'no' => 'danger',
                                                    default => 'secondary'
                                                };
                                            ?>">
                                                <?php
                                                    echo match($guest['status']) {
                                                        'yes' => 'Attending',
                                                        'maybe' => 'Maybe',
                                                        'no' => "Can't Go",
                                                        default => ucfirst($guest['status'])
                                                    };
                                                ?>
                                            </span>
                                        </td>
                                        <td><?= (int)$guest['plus_ones'] ?></td>
                                        <td>
                                            <?php if (!empty($guest['dietary_restrictions'])): ?>
                                                <?= htmlspecialchars($guest['dietary_restrictions']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($guest['response_date'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-people text-muted" style="font-size: 4rem;"></i>
                        <h4 class="mt-3">No RSVPs Yet</h4>
                        <p class="text-muted">No one has RSVP'd to this event yet.</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
