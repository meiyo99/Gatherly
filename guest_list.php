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
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
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
        .stat-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-card.success { border-left: 4px solid #28a745; }
        .stat-card.warning { border-left: 4px solid #ffc107; }
        .stat-card.danger { border-left: 4px solid #dc3545; }
        .stat-card.primary { border-left: 4px solid #2c3e50; }
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #212529;
            line-height: 1;
            margin-bottom: 8px;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .guests-card {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .guests-card-header {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
            background: #f8f9fa;
        }
        .badge {
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 500;
        }
        .btn-download {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }
        .btn-download:hover {
            background: #218838;
            color: white;
        }
        .btn-back {
            background: white;
            color: #495057;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            border: 2px solid #dee2e6;
        }
        .btn-back:hover {
            background: #f8f9fa;
            color: #495057;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
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
            <div>
                <h1 class="page-title">Guest List</h1>
                <p class="page-subtitle"><?= htmlspecialchars($event['title']) ?></p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/handlers/download_guest_list.php?id=<?= $event_id ?>" class="btn-download">
                    <i class="bi bi-download"></i>Download CSV
                </a>
                <a href="<?= BASE_URL ?>/view_event.php?id=<?= $event_id ?>" class="btn-back">
                    <i class="bi bi-arrow-left"></i>Back to Event
                </a>
            </div>
        </div>

        <div class="stat-cards">
            <div class="stat-card success">
                <div class="stat-number"><?= $attending ?></div>
                <div class="stat-label">Attending</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number"><?= $maybe ?></div>
                <div class="stat-label">Maybe</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-number"><?= $not_attending ?></div>
                <div class="stat-label">Can't Go</div>
            </div>
            <div class="stat-card primary">
                <div class="stat-number"><?= $total_rsvps ?></div>
                <div class="stat-label">Total Responses</div>
            </div>
        </div>

        <?php if (count($guests) > 0): ?>
        <div class="guests-card">
            <h2 class="guests-card-header">All Guests</h2>
            <div class="table-responsive">
                <table class="table table-hover">
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
                            <td><?= $guest['response_date'] ? date('M d, Y', strtotime($guest['response_date'])) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="guests-card">
            <div class="empty-state">
                <i class="bi bi-people"></i>
                <h4>No RSVPs Yet</h4>
                <p class="text-muted">No one has RSVP'd to this event yet.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
