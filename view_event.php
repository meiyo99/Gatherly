<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/models/Event.php';
require_once __DIR__ . '/app/config/Database.php';
require_once __DIR__ . '/app/helpers/Weather.php';
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

    $stmt = $db->prepare("
        SELECT u.first_name, u.last_name, u.email, r.status
        FROM rsvps r
        INNER JOIN users u ON r.guest_id = u.user_id
        WHERE r.event_id = :event_id AND r.status = 'yes'
        LIMIT 4
    ");
    $stmt->execute([':event_id' => $event_id]);
    $attending_guests = $stmt->fetchAll();
}

// Fetch weather data for the event
$weatherHelper = new Weather();
$weather = $weatherHelper->getWeatherForEvent($event['location'], $event['event_date']);
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
            background-color: #f8f9fa;
        }
        .event-thumbnail-placeholder {
            width: 100%;
            height: 200px;
            background: #0d6efd;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .event-title-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .event-title-section h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .event-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            color: #6c757d;
            font-size: 0.95rem;
        }
        .event-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .about-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .organizer-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .organizer-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .organizer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #495057;
        }
        .sidebar-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .sidebar-card-header {
            padding: 15px 20px;
            font-weight: 600;
            font-size: 1.1rem;
            border-bottom: 1px solid #e9ecef;
        }
        .sidebar-card-body {
            padding: 20px;
        }
        .rsvp-stat {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .rsvp-stat.attending {
            background: #d4edda;
            color: #155724;
        }
        .rsvp-stat.maybe {
            background: #fff3cd;
            color: #856404;
        }
        .rsvp-stat.not-attending {
            background: #f8d7da;
            color: #721c24;
        }
        .rsvp-number {
            font-size: 1.5rem;
            font-weight: 700;
        }
        .guest-avatars {
            display: flex;
            gap: 8px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .guest-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #6c757d;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .weather-placeholder {
            background: #0d6efd;
            color: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
        }
        .weather-icon {
            font-size: 4rem;
            margin-bottom: 10px;
        }
        .weather-temp {
            font-size: 2.5rem;
            font-weight: 700;
        }
        .location-map-placeholder {
            width: 100%;
            height: 200px;
            background: #e9ecef;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 1rem;
            margin-top: 15px;
        }
        .btn-event-action {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .btn-event-action.primary {
            background: #000;
            color: white;
            border: none;
        }
        .btn-event-action.secondary {
            background: white;
            color: #000;
            border: 2px solid #e9ecef;
        }
        .btn-event-action.danger {
            background: white;
            color: #dc3545;
            border: 2px solid #e9ecef;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/dashboard.php">
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

    <div class="container mt-4 mb-5">
        <a href="<?= BASE_URL ?>/events.php" class="btn btn-link text-decoration-none mb-3 ps-0">
            <i class="bi bi-arrow-left me-1"></i>Back to Events
        </a>

        <div class="row">
            <div class="col-lg-8">
                <div class="event-thumbnail-placeholder">
                    <div>
                        <i class="bi bi-image" style="font-size: 3rem;"></i>
                        <div class="mt-2">Event Thumbnail Placeholder</div>
                    </div>
                </div>

                <div class="event-title-section">
                    <div class="d-flex justify-content-between align-items-start">
                        <h1><?= htmlspecialchars($event['title']) ?></h1>
                        <?php if ($isHost): ?>
                        <div class="d-flex gap-2">
                            <a href="<?= BASE_URL ?>/edit_event.php?id=<?= $event['event_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="<?= BASE_URL ?>/handlers/delete_event_handler.php?id=<?= $event['event_id'] ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Are you sure?');">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="event-meta mt-3">
                        <div class="event-meta-item">
                            <i class="bi bi-calendar3"></i>
                            <span><?= date('F d, Y', strtotime($event['event_date'])) ?></span>
                        </div>
                        <div class="event-meta-item">
                            <i class="bi bi-clock"></i>
                            <span>
                                <?php if (!empty($event['event_time']) && $event['event_time'] !== '00:00:00'): ?>
                                    <?= date('g:i A', strtotime($event['event_time'])) ?> - <?= date('g:i A', strtotime($event['event_time'] . ' +2 hours')) ?> PST
                                <?php else: ?>
                                    Time TBD
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="event-meta-item">
                            <i class="bi bi-geo-alt"></i>
                            <span><?= htmlspecialchars($event['location']) ?></span>
                        </div>
                    </div>
                </div>

                <div class="about-section">
                    <h5 class="mb-3">About This Event</h5>
                    <p class="text-muted" style="line-height: 1.7;">
                        <?php if (!empty($event['description'])): ?>
                            <?= nl2br(htmlspecialchars($event['description'])) ?>
                        <?php else: ?>
                            No description provided for this event.
                        <?php endif; ?>
                    </p>
                </div>

                <div class="organizer-section">
                    <h6 class="mb-3">Organized by</h6>
                    <div class="organizer-profile">
                        <div class="organizer-avatar">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <div>
                            <div class="fw-semibold"><?= htmlspecialchars($host_name) ?></div>
                            <small class="text-muted">127 events hosted</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <?php if ($isHost): ?>
                <div class="sidebar-card">
                    <div class="sidebar-card-header">Guest RSVPs</div>
                    <div class="sidebar-card-body">
                        <div class="rsvp-stat attending">
                            <div>
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <span class="rsvp-number"><?= $attending ?></span>
                            </div>
                            <div class="small">Attending</div>
                        </div>
                        <div class="rsvp-stat maybe">
                            <div>
                                <i class="bi bi-clock-fill me-2"></i>
                                <span class="rsvp-number"><?= $maybe ?></span>
                            </div>
                            <div class="small">Maybe</div>
                        </div>
                        <div class="rsvp-stat not-attending">
                            <div>
                                <i class="bi bi-x-circle-fill me-2"></i>
                                <span class="rsvp-number"><?= $not_attending ?></span>
                            </div>
                            <div class="small">Can't Attend</div>
                        </div>

                        <?php if (!empty($attending_guests)): ?>
                        <div class="guest-avatars">
                            <?php foreach ($attending_guests as $guest): ?>
                            <div class="guest-avatar" title="<?= htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']) ?>">
                                <?= strtoupper(substr($guest['first_name'], 0, 1)) ?>
                            </div>
                            <?php endforeach; ?>
                            <?php if ($attending > 4): ?>
                            <div class="guest-avatar" style="background: #495057;">
                                +<?= $attending - 4 ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <a href="<?= BASE_URL ?>/guest_list.php?id=<?= $event['event_id'] ?>" class="btn btn-outline-secondary w-100 mt-3">
                            View All Guests
                        </a>
                        <small class="text-muted d-block text-center mt-2">
                            <?= $total_rsvps ?> guests invited
                        </small>

                        <hr class="my-4">

                        <h6 class="mb-3">Invite Guests</h6>
                        <form id="inviteForm">
                            <div class="mb-3">
                                <label for="guest_name" class="form-label small">Guest Name</label>
                                <input type="text" class="form-control form-control-sm" id="guest_name" name="guest_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="guest_email" class="form-label small">Guest Email</label>
                                <input type="email" class="form-control form-control-sm" id="guest_email" name="guest_email" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 btn-sm">
                                <i class="bi bi-envelope me-2"></i>Send Invitation
                            </button>
                        </form>
                    </div>
                </div>

                <div class="sidebar-card">
                    <div class="sidebar-card-header">Weather Forecast</div>
                    <div class="sidebar-card-body">
                        <?php if ($weather): ?>
                            <!-- Display actual weather data from API -->
                            <div class="weather-placeholder">
                                <div class="weather-icon">
                                    <i class="<?= Weather::getIconClass($weather['icon']) ?>"></i>
                                </div>
                                <div class="weather-temp"><?= $weather['temp'] ?>°F</div>
                                <div class="mt-2"><?= htmlspecialchars($weather['description']) ?></div>
                                <div class="mt-3 small">
                                    <div>High: <?= $weather['temp_max'] ?>°F &nbsp;&nbsp; Low: <?= $weather['temp_min'] ?>°F</div>
                                    <div>Humidity: <?= $weather['humidity'] ?>% &nbsp;&nbsp; Wind: <?= $weather['wind_speed'] ?> mph</div>
                                </div>
                                <?php if (!$weather['is_forecast']): ?>
                                    <div class="mt-2 text-muted" style="font-size: 0.75rem;">
                                        <i class="bi bi-info-circle"></i> Showing current weather
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <!-- Fallback if weather data unavailable -->
                            <div class="weather-placeholder">
                                <div class="text-muted">
                                    <i class="bi bi-cloud" style="font-size: 2rem;"></i>
                                    <div class="mt-2 small">Weather data unavailable</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="sidebar-card">
                    <div class="sidebar-card-header">Location Details</div>
                    <div class="sidebar-card-body">
                        <?php if (GOOGLE_MAPS_KEY): ?>
                            <!-- Embed Google Maps iframe for the event location -->
                            <div class="location-map-container">
                                <iframe
                                    width="100%"
                                    height="200"
                                    style="border:0; border-radius: 6px;"
                                    loading="lazy"
                                    allowfullscreen
                                    referrerpolicy="no-referrer-when-downgrade"
                                    src="https://www.google.com/maps/embed/v1/place?key=<?= GOOGLE_MAPS_KEY ?>&q=<?= urlencode($event['location']) ?>">
                                </iframe>
                            </div>
                        <?php else: ?>
                            <!-- Fallback if API key not configured -->
                            <div class="location-map-placeholder">
                                <div class="text-center">
                                    <i class="bi bi-map" style="font-size: 2rem;"></i>
                                    <div class="mt-2">Map unavailable</div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="mt-3">
                            <strong><?= htmlspecialchars($event['location']) ?></strong>
                        </div>
                        <!-- Directions button opens Google Maps in new tab -->
                        <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($event['location']) ?>"
                           target="_blank"
                           class="btn btn-outline-secondary w-100 mt-3">
                            <i class="bi bi-geo-alt-fill me-2"></i>Get Directions
                        </a>
                    </div>
                </div>

                <div class="sidebar-card">
                    <div class="sidebar-card-header">Event Management</div>
                    <div class="sidebar-card-body">
                        <button class="btn btn-event-action primary">
                            Send Update to Guests
                        </button>
                        <a href="<?= BASE_URL ?>/edit_event.php?id=<?= $event['event_id'] ?>" class="btn btn-event-action secondary">
                            Edit Event Details
                        </a>
                        <a href="<?= BASE_URL ?>/handlers/download_guest_list.php?id=<?= $event['event_id'] ?>" class="btn btn-event-action secondary">
                            Download Guest List
                        </a>
                        <a href="<?= BASE_URL ?>/handlers/delete_event_handler.php?id=<?= $event['event_id'] ?>"
                           class="btn btn-event-action danger"
                           onclick="return confirm('Are you sure you want to cancel this event?');">
                            Cancel Event
                        </a>
                    </div>
                </div>

                <?php else: ?>
                <div class="sidebar-card">
                    <div class="sidebar-card-header">RSVP</div>
                    <div class="sidebar-card-body">
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

                <div class="sidebar-card">
                    <div class="sidebar-card-header">Weather Forecast</div>
                    <div class="sidebar-card-body">
                        <?php if ($weather): ?>
                            <!-- Display actual weather data from API -->
                            <div class="weather-placeholder">
                                <div class="weather-icon">
                                    <i class="<?= Weather::getIconClass($weather['icon']) ?>"></i>
                                </div>
                                <div class="weather-temp"><?= $weather['temp'] ?>°F</div>
                                <div class="mt-2"><?= htmlspecialchars($weather['description']) ?></div>
                                <div class="mt-3 small">
                                    <div>High: <?= $weather['temp_max'] ?>°F &nbsp;&nbsp; Low: <?= $weather['temp_min'] ?>°F</div>
                                    <div>Humidity: <?= $weather['humidity'] ?>% &nbsp;&nbsp; Wind: <?= $weather['wind_speed'] ?> mph</div>
                                </div>
                                <?php if (!$weather['is_forecast']): ?>
                                    <div class="mt-2 text-muted" style="font-size: 0.75rem;">
                                        <i class="bi bi-info-circle"></i> Showing current weather
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <!-- Fallback if weather data unavailable -->
                            <div class="weather-placeholder">
                                <div class="text-muted">
                                    <i class="bi bi-cloud" style="font-size: 2rem;"></i>
                                    <div class="mt-2 small">Weather data unavailable</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="sidebar-card">
                    <div class="sidebar-card-header">Location Details</div>
                    <div class="sidebar-card-body">
                        <?php if (GOOGLE_MAPS_KEY): ?>
                            <!-- Embed Google Maps iframe for the event location -->
                            <div class="location-map-container">
                                <iframe
                                    width="100%"
                                    height="200"
                                    style="border:0; border-radius: 6px;"
                                    loading="lazy"
                                    allowfullscreen
                                    referrerpolicy="no-referrer-when-downgrade"
                                    src="https://www.google.com/maps/embed/v1/place?key=<?= GOOGLE_MAPS_KEY ?>&q=<?= urlencode($event['location']) ?>">
                                </iframe>
                            </div>
                        <?php else: ?>
                            <!-- Fallback if API key not configured -->
                            <div class="location-map-placeholder">
                                <div class="text-center">
                                    <i class="bi bi-map" style="font-size: 2rem;"></i>
                                    <div class="mt-2">Map unavailable</div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="mt-3">
                            <strong><?= htmlspecialchars($event['location']) ?></strong>
                        </div>
                        <!-- Directions button opens Google Maps in new tab -->
                        <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($event['location']) ?>"
                           target="_blank"
                           class="btn btn-outline-secondary w-100 mt-3">
                            <i class="bi bi-geo-alt-fill me-2"></i>Get Directions
                        </a>
                    </div>
                </div>
                <?php endif; ?>
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
