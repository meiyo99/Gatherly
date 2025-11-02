<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/config/Database.php';
session_start();

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

$stmt = $db->prepare("SELECT * FROM events WHERE host_id = :user_id ORDER BY event_date DESC LIMIT 10");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$my_events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Gatherly</title>
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
        .page-header-content {
            flex: 1;
        }
        .profile-button {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #495057;
            text-decoration: none;
            font-size: 1.2rem;
            transition: all 0.3s;
        }
        .profile-button:hover {
            background: #f8f9fa;
            color: #212529;
            border-color: #adb5bd;
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stat-content h6 {
            color: #6c757d;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .stat-content h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            color: #212529;
        }
        .stat-icon {
            font-size: 2.5rem;
            color: #dee2e6;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }
        .btn-create-event {
            background: #000;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            transition: all 0.3s;
        }
        .btn-create-event:hover {
            background: #333;
            color: white;
        }
        .event-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            gap: 25px;
            align-items: center;
        }
        .event-icon {
            background: #e9ecef;
            width: 60px;
            height: 60px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #6c757d;
            flex-shrink: 0;
        }
        .event-content {
            flex: 1;
        }
        .event-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #212529;
        }
        .event-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .event-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #6c757d;
            font-size: 0.9rem;
        }
        .event-meta-item i {
            color: #adb5bd;
        }
        .event-actions {
            display: flex;
            gap: 10px;
        }
        .event-attendees {
            color: #495057;
            font-size: 0.9rem;
            margin-right: 15px;
        }
        .btn-view-details {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.9rem;
            text-decoration: none;
            font-weight: 500;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 8px;
        }
        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        .empty-state h3 {
            color: #6c757d;
            margin-bottom: 10px;
        }
        .empty-state p {
            color: #adb5bd;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand">Gatherly</div>
        <ul class="sidebar-nav">
            <li class="sidebar-nav-item">
                <a href="<?= BASE_URL ?>/dashboard.php" class="sidebar-nav-link active">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="<?= BASE_URL ?>/events.php" class="sidebar-nav-link">
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
            <div class="page-header-content">
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle">Welcome back! Here's what's happening with your events.</p>
            </div>
            <a href="<?= BASE_URL ?>/profile.php" class="profile-button" title="Profile">
                <i class="bi bi-person-circle"></i>
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-content">
                    <h6>Total Events</h6>
                    <h2><?= $total_events ?></h2>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-calendar-event"></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-content">
                    <h6>Upcoming</h6>
                    <h2><?= $upcoming_events ?></h2>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-clock"></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-content">
                    <h6>Total RSVPs</h6>
                    <h2><?= $total_rsvps ?></h2>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
            </div>
        </div>

        <div class="section-header">
            <h2 class="section-title">My Events</h2>
            <a href="<?= BASE_URL ?>/create_event.php" class="btn-create-event">
                <i class="bi bi-plus-circle"></i>
                Create Event
            </a>
        </div>

        <?php if (count($my_events) > 0): ?>
            <?php foreach ($my_events as $event): ?>
                <div class="event-card">
                    <div class="event-icon">
                        Event
                    </div>
                    <div class="event-content">
                        <h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
                        <div class="event-meta">
                            <div class="event-meta-item">
                                <i class="bi bi-calendar3"></i>
                                <span>
                                    <?= date('F d, Y', strtotime($event['event_date'])) ?>
                                    <?php if (!empty($event['event_time']) && $event['event_time'] !== '00:00:00'): ?>
                                        • <?= date('g:i A', strtotime($event['event_time'])) ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="event-meta-item">
                                <i class="bi bi-geo-alt"></i>
                                <span><?= htmlspecialchars($event['location']) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="event-actions">
                        <span class="event-attendees">
                            <?php
                            $stmt = $db->prepare("SELECT COUNT(*) FROM rsvps WHERE event_id = :event_id AND status = 'yes'");
                            $stmt->execute([':event_id' => $event['event_id']]);
                            $attendees = $stmt->fetchColumn();
                            echo $attendees . ' RSVPs';
                            ?>
                        </span>
                        <a href="<?= BASE_URL ?>/view_event.php?id=<?= $event['event_id'] ?>" class="btn btn-outline-secondary btn-view-details">
                            View Details
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-calendar-x"></i>
                <h3>No Events Yet</h3>
                <p>Get started by creating your first event!</p>
                <a href="<?= BASE_URL ?>/create_event.php" class="btn-create-event">
                    <i class="bi bi-plus-circle"></i>
                    Create Your First Event
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
