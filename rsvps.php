<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/config/Database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT
        e.*,
        r.status as rsvp_status,
        r.response_date,
        r.plus_ones,
        u.first_name as host_first_name,
        u.last_name as host_last_name
    FROM rsvps r
    INNER JOIN events e ON r.event_id = e.event_id
    INNER JOIN users u ON e.host_id = u.user_id
    WHERE r.guest_id = :user_id
    ORDER BY e.event_date DESC
");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$rsvps = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My RSVPs - Gatherly</title>
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
                    <li class="nav-item">
                        <a class="nav-link active" href="<?= BASE_URL ?>/rsvps.php">
                            <i class="bi bi-envelope-check me-1"></i>My RSVPs
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
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
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1>My RSVPs</h1>
                    <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <?php if (count($rsvps) > 0): ?>
            <div class="row">
                <?php foreach ($rsvps as $rsvp): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><?= htmlspecialchars($rsvp['title']) ?></h5>
                                <span class="badge bg-<?php
                                    echo match($rsvp['rsvp_status']) {
                                        'yes' => 'success',
                                        'maybe' => 'warning',
                                        'no' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php
                                        echo match($rsvp['rsvp_status']) {
                                            'yes' => 'Attending',
                                            'maybe' => 'Maybe',
                                            'no' => "Can't Go",
                                            default => ucfirst($rsvp['rsvp_status'])
                                        };
                                    ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-3">
                                    <li class="mb-2">
                                        <i class="bi bi-calendar-date text-primary me-2"></i>
                                        <strong>Date:</strong>
                                        <?php
                                            echo date('M d, Y', strtotime($rsvp['event_date']));
                                            if (!empty($rsvp['event_time']) && $rsvp['event_time'] !== '00:00:00') {
                                                echo ' at ' . date('g:i A', strtotime($rsvp['event_time']));
                                            }
                                        ?>
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-geo-alt text-danger me-2"></i>
                                        <strong>Location:</strong> <?= htmlspecialchars($rsvp['location']) ?>
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-person text-info me-2"></i>
                                        <strong>Host:</strong> <?= htmlspecialchars($rsvp['host_first_name'] . ' ' . $rsvp['host_last_name']) ?>
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-clock text-success me-2"></i>
                                        <strong>RSVP'd:</strong> <?= date('M d, Y', strtotime($rsvp['response_date'])) ?>
                                    </li>
                                </ul>

                                <?php if (!empty($rsvp['description'])): ?>
                                    <p class="text-muted small mb-3">
                                        <?= htmlspecialchars(mb_substr($rsvp['description'], 0, 150)) ?>
                                        <?= strlen($rsvp['description']) > 150 ? '...' : '' ?>
                                    </p>
                                <?php endif; ?>

                                <div class="d-flex gap-2">
                                    <a href="<?= BASE_URL ?>/view_event.php?id=<?= $rsvp['event_id'] ?>" class="btn btn-sm btn-primary flex-fill">
                                        <i class="bi bi-eye me-1"></i>View Event
                                    </a>
                                    <button class="btn btn-sm btn-outline-secondary flex-fill"
                                            onclick="changeRSVP(<?= $rsvp['event_id'] ?>)">
                                        <i class="bi bi-pencil me-1"></i>Change RSVP
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-envelope-x text-muted" style="font-size: 4rem;"></i>
                            <h4 class="mt-3">No RSVPs Yet</h4>
                            <p class="text-muted">You haven't RSVP'd to any events yet.</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeRSVP(eventId) {
            window.location.href = '<?= BASE_URL ?>/view_event.php?id=' + eventId;
        }
    </script>
</body>
</html>
