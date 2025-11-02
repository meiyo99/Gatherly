<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/models/Event.php';
require_once __DIR__ . '/app/config/Database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$user_role = $_SESSION['role'];

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';

$eventModel = new Event();
$all_events = $eventModel->getAll();

$events = array_filter($all_events, function($event) use ($search, $status_filter, $date_filter) {
    if (!empty($search)) {
        $search_lower = strtolower($search);
        if (stripos($event['title'], $search_lower) === false &&
            stripos($event['location'], $search_lower) === false) {
            return false;
        }
    }

    if (!empty($status_filter) && $event['status'] !== $status_filter) {
        return false;
    }

    if (!empty($date_filter)) {
        $today = date('Y-m-d');
        if ($date_filter === 'upcoming' && $event['event_date'] < $today) {
            return false;
        }
        if ($date_filter === 'past' && $event['event_date'] >= $today) {
            return false;
        }
    }

    return true;
});

$db = Database::getInstance()->getConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Events - Gatherly</title>
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
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .page-subtitle {
            color: #6c757d;
            font-size: 0.95rem;
        }
        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
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
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .event-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            gap: 25px;
        }
        .event-thumbnail {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: #6c757d;
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
        .event-description {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 12px;
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
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
            min-width: 150px;
        }
        .event-status {
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 10px;
        }
        .event-status.upcoming {
            background: #fff3cd;
            color: #856404;
        }
        .event-status.completed {
            background: #d4edda;
            color: #155724;
        }
        .event-attendees {
            color: #495057;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .btn-view-details {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.9rem;
            text-decoration: none;
            font-weight: 500;
            background: white;
            color: #000;
            border: 2px solid #e9ecef;
            text-align: center;
        }
        .btn-view-details:hover {
            background: #f8f9fa;
            border-color: #dee2e6;
        }
        .btn-edit-event {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.85rem;
            text-decoration: none;
            color: #6c757d;
            border: 1px solid #dee2e6;
        }
        .btn-edit-event:hover {
            background: #f8f9fa;
            color: #495057;
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
            <div class="page-header-content">
                <h1 class="page-title">Events</h1>
                <p class="page-subtitle">Manage and track all your hosted events</p>
            </div>
            <div class="header-actions">
                <a href="<?= BASE_URL ?>/create_event.php" class="btn-create-event">
                    <i class="bi bi-plus-circle"></i>
                    Create Event
                </a>
                <a href="<?= BASE_URL ?>/profile.php" class="profile-button" title="Profile">
                    <i class="bi bi-person-circle"></i>
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

        <div class="filter-section">
            <form method="GET" action="<?= BASE_URL ?>/events.php">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search" placeholder="Search by title or location"
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">All Statuses</option>
                            <option value="published" <?= $status_filter === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="draft" <?= $status_filter === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="date">
                            <option value="">All Dates</option>
                            <option value="upcoming" <?= $date_filter === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                            <option value="past" <?= $date_filter === 'past' ? 'selected' : '' ?>>Past</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i>Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <?php if (!empty($events) && is_array($events)): ?>
            <?php foreach ($events as $event): ?>
                <?php
                $today = date('Y-m-d');
                $event_status = ($event['event_date'] >= $today) ? 'UPCOMING' : 'COMPLETED';
                $status_class = ($event['event_date'] >= $today) ? 'upcoming' : 'completed';

                $stmt = $db->prepare("SELECT COUNT(*) FROM rsvps WHERE event_id = :event_id AND status = 'yes'");
                $stmt->execute([':event_id' => $event['event_id']]);
                $attendees = $stmt->fetchColumn();
                ?>
                <div class="event-card">
                    <div class="event-thumbnail">
                        Event
                    </div>
                    <div class="event-content">
                        <h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
                        <?php if (!empty($event['description'])): ?>
                            <p class="event-description">
                                <?= htmlspecialchars(substr($event['description'], 0, 150)) ?>
                                <?= strlen($event['description']) > 150 ? '...' : '' ?>
                            </p>
                        <?php endif; ?>
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
                            <div class="event-meta-item">
                                <i class="bi bi-person"></i>
                                <span>Hosted by <?= htmlspecialchars($user_name) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="event-actions">
                        <span class="event-status <?= $status_class ?>"><?= $event_status ?></span>
                        <span class="event-attendees"><?= $attendees ?> RSVPs</span>
                        <a href="<?= BASE_URL ?>/view_event.php?id=<?= $event['event_id'] ?>" class="btn-view-details">
                            View Details
                        </a>
                        <a href="<?= BASE_URL ?>/edit_event.php?id=<?= $event['event_id'] ?>" class="btn-edit-event">
                            Edit Event
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-calendar-x"></i>
                <h3>No Events Found</h3>
                <p>Try adjusting your filters or create a new event to get started.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
