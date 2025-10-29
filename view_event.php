<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/models/Event.php';
require_once __DIR__ . '/app/config/Database.php';
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

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT first_name, last_name FROM users WHERE user_id = :host_id");
$stmt->execute([':host_id' => $event['host_id']]);
$host = $stmt->fetch();

$isHost = ($event['host_id'] == $_SESSION['user_id']);
$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$host_name = $host ? $host['first_name'] . ' ' . $host['last_name'] : 'Unknown';

$current_rsvp = null;
if (!$isHost) {
    $stmt = $db->prepare("SELECT status FROM rsvps WHERE event_id = :event_id AND guest_id = :user_id");
    $stmt->execute([':event_id' => $event_id, ':user_id' => $_SESSION['user_id']]);
    $current_rsvp = $stmt->fetchColumn();
}

if ($isHost) {
    $stmt = $db->prepare("SELECT status, COUNT(*) as count FROM rsvps WHERE event_id = :event_id GROUP BY status");
    $stmt->execute([':event_id' => $event_id]);
    $rsvp_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $attending = $rsvp_counts['yes'] ?? 0;
    $maybe = $rsvp_counts['maybe'] ?? 0;
    $not_attending = $rsvp_counts['no'] ?? 0;
    $total_rsvps = $attending + $maybe + $not_attending;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($event['title']) ?> - Gatherly</title>
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
        .event-header {
            background-color: #0d6efd;
            color: white;
            padding: 30px;
            margin-bottom: 30px;
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
                        <a class="nav-link" href="<?= BASE_URL ?>/events.php">
                            <i class="bi bi-calendar-check me-1"></i>Events
                        </a>
                    </li>
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

    <div class="container mt-4">
        <a href="<?= BASE_URL ?>/events.php" class="btn btn-outline-secondary mb-3">
            <i class="bi bi-arrow-left me-1"></i>Back to Events
        </a>

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="event-header">
                        <h1 class="mb-0"><?= htmlspecialchars($event['title']) ?></h1>
                        <span class="badge bg-light text-dark mt-2">
                            <?= ucfirst(htmlspecialchars($event['status'])) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2"><i class="bi bi-calendar3 me-2"></i>Date & Time</h6>
                                <p class="mb-0">
                                    <?= date('F d, Y', strtotime($event['event_date'])) ?>
                                    <?php if (!empty($event['event_time']) && $event['event_time'] !== '00:00:00'): ?>
                                        <br><?= date('g:i A', strtotime($event['event_time'])) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2"><i class="bi bi-geo-alt me-2"></i>Location</h6>
                                <p class="mb-0"><?= htmlspecialchars($event['location']) ?></p>
                            </div>
                        </div>

                        <?php if (!empty($event['description'])): ?>
                            <div class="mb-4">
                                <h5>About This Event</h5>
                                <p><?= nl2br(htmlspecialchars($event['description'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <h6 class="text-muted mb-2"><i class="bi bi-people me-2"></i>Capacity</h6>
                            <p class="mb-0">
                                <?php if ($event['max_guests'] > 0): ?>
                                    Maximum <?= (int)$event['max_guests'] ?> guests
                                <?php else: ?>
                                    Unlimited
                                <?php endif; ?>
                            </p>
                        </div>

                        <div class="mb-4">
                            <h6 class="text-muted mb-2"><i class="bi bi-person me-2"></i>Organized by</h6>
                            <p class="mb-0"><?= htmlspecialchars($host_name) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <?php if ($isHost): ?>
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Event Management</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="<?= BASE_URL ?>/edit_event.php?id=<?= $event['event_id'] ?>" class="btn btn-outline-primary">
                                    <i class="bi bi-pencil me-2"></i>Edit Event
                                </a>
                                <a href="<?= BASE_URL ?>/handlers/delete_event_handler.php?id=<?= $event['event_id'] ?>"
                                   class="btn btn-outline-danger"
                                   onclick="return confirm('Are you sure you want to delete this event?');">
                                    <i class="bi bi-trash me-2"></i>Delete Event
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">Guest RSVPs</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span><i class="bi bi-check-circle text-success me-2"></i>Attending</span>
                                    <span class="badge bg-success"><?= $attending ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span><i class="bi bi-question-circle text-warning me-2"></i>Maybe</span>
                                    <span class="badge bg-warning"><?= $maybe ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span><i class="bi bi-x-circle text-danger me-2"></i>Can't Go</span>
                                    <span class="badge bg-danger"><?= $not_attending ?></span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center fw-bold">
                                    <span>Total Responses</span>
                                    <span class="badge bg-primary"><?= $total_rsvps ?></span>
                                </div>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="<?= BASE_URL ?>/guest_list.php?id=<?= $event['event_id'] ?>" class="btn btn-sm btn-primary w-100">
                                    <i class="bi bi-list-ul me-2"></i>View Full Guest List
                                </a>
                                <a href="<?= BASE_URL ?>/handlers/download_guest_list.php?id=<?= $event['event_id'] ?>" class="btn btn-sm btn-outline-primary w-100">
                                    <i class="bi bi-download me-2"></i>Download CSV
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">Invite Guests</h5>
                        </div>
                        <div class="card-body">
                            <form id="inviteForm">
                                <div class="mb-3">
                                    <label for="guest_name" class="form-label">Guest Name</label>
                                    <input type="text" class="form-control" id="guest_name" name="guest_name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="guest_email" class="form-label">Guest Email</label>
                                    <input type="email" class="form-control" id="guest_email" name="guest_email" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-envelope me-2"></i>Send Invitation
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">RSVP</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($current_rsvp): ?>
                                <p class="mb-3">
                                    <strong>Your RSVP:</strong>
                                    <span class="badge bg-<?php
                                        echo match($current_rsvp) {
                                            'yes' => 'success',
                                            'maybe' => 'warning',
                                            'no' => 'danger',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?php
                                            echo match($current_rsvp) {
                                                'yes' => 'Attending',
                                                'maybe' => 'Maybe',
                                                'no' => "Can't Go",
                                                default => ucfirst($current_rsvp)
                                            };
                                        ?>
                                    </span>
                                </p>
                                <p class="mb-3 small text-muted">Change your response:</p>
                            <?php else: ?>
                                <p class="mb-3">Will you attend this event?</p>
                            <?php endif; ?>
                            <div class="d-grid gap-2">
                                <button class="btn <?= $current_rsvp === 'yes' ? 'btn-success' : 'btn-outline-success' ?>" onclick="submitRSVP('attending')">
                                    <i class="bi bi-check-circle me-2"></i>Attending
                                </button>
                                <button class="btn <?= $current_rsvp === 'maybe' ? 'btn-warning' : 'btn-outline-warning' ?>" onclick="submitRSVP('maybe')">
                                    <i class="bi bi-question-circle me-2"></i>Maybe
                                </button>
                                <button class="btn <?= $current_rsvp === 'no' ? 'btn-danger' : 'btn-outline-danger' ?>" onclick="submitRSVP('not_attending')">
                                    <i class="bi bi-x-circle me-2"></i>Can't Go
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card mb-3">
                    <div class="card-body text-center">
                        <p class="text-muted mb-1">Event ID: #<?= $event['event_id'] ?></p>
                        <small class="text-muted">
                            Created: <?= date('M d, Y', strtotime($event['created_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if ($isHost): ?>
        document.getElementById('inviteForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('event_id', '<?= $event['event_id'] ?>');

            fetch('<?= BASE_URL ?>/handlers/invite_guest_handler.php', {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Invitation sent successfully!');
                    document.getElementById('inviteForm').reset();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
            });
        });
        <?php endif; ?>

        function submitRSVP(status) {
            fetch('<?= BASE_URL ?>/handlers/rsvp_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `event_id=<?= $event['event_id'] ?>&rsvp_status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('RSVP updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
            });
        }
    </script>
</body>
</html>
