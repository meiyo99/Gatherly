<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/config/Database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$user_email = $_SESSION['email'] ?? null;

if (!$user_email) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT email FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
        $user_email = $user['email'];
        $_SESSION['email'] = $user_email;
    }
}

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT i.*, e.title as event_title, e.event_date, e.event_time, e.location, e.event_id, e.description,
           u.first_name as host_first_name, u.last_name as host_last_name,
           r.status as rsvp_status
    FROM invitations i
    INNER JOIN events e ON i.event_id = e.event_id
    INNER JOIN users u ON e.host_id = u.user_id
    LEFT JOIN rsvps r ON r.event_id = e.event_id AND r.guest_id = :user_id
    WHERE i.guest_email = :email
    ORDER BY e.event_date DESC
");
$stmt->execute([':email' => $user_email, ':user_id' => $_SESSION['user_id']]);
$invitations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invitations - Gatherly</title>
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
        .invitation-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            gap: 25px;
        }
        .invitation-icon {
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
        .invitation-content {
            flex: 1;
        }
        .invitation-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #212529;
        }
        .invitation-description {
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        .invitation-meta {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 15px;
        }
        .invitation-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #495057;
            font-size: 0.9rem;
        }
        .invitation-meta-item i {
            color: #6c757d;
        }
        .host-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .host-avatar {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #0d6efd;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .invitation-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-width: 150px;
            align-items: flex-end;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 10px;
        }
        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-badge.accepted {
            background: #d4edda;
            color: #155724;
        }
        .status-badge.declined {
            background: #f8d7da;
            color: #721c24;
        }
        .btn-rsvp {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-accept {
            background: #000;
            color: white;
        }
        .btn-accept:hover {
            background: #333;
        }
        .btn-maybe {
            background: white;
            color: #000;
            border: 2px solid #e9ecef;
        }
        .btn-maybe:hover {
            border-color: #dee2e6;
            background: #f8f9fa;
        }
        .btn-decline {
            background: white;
            color: #000;
            border: 2px solid #e9ecef;
        }
        .btn-decline:hover {
            border-color: #dee2e6;
            background: #f8f9fa;
        }
        .btn-accepted {
            background: #28a745;
            color: white;
        }
        .btn-view-details {
            background: transparent;
            border: none;
            color: #6c757d;
            font-size: 0.9rem;
            cursor: pointer;
            padding: 5px 0;
            text-decoration: none;
        }
        .btn-view-details:hover {
            color: #495057;
            text-decoration: underline;
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
                <a href="<?= BASE_URL ?>/events.php" class="sidebar-nav-link">
                    <i class="bi bi-calendar-event"></i>
                    <span>Events</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="<?= BASE_URL ?>/invitations.php" class="sidebar-nav-link active">
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
                <h1 class="page-title">Invitations</h1>
                <p class="page-subtitle">Manage your event invitations and RSVPs</p>
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

        <?php if (count($invitations) > 0): ?>
            <?php foreach ($invitations as $invitation): ?>
                <?php
                $rsvp_status = $invitation['rsvp_status'] ?? 'pending';
                $status_text = match($rsvp_status) {
                    'yes' => 'Accepted',
                    'maybe' => 'Pending',
                    'no' => 'Declined',
                    default => 'Pending'
                };
                $status_class = match($rsvp_status) {
                    'yes' => 'accepted',
                    'maybe' => 'pending',
                    'no' => 'declined',
                    default => 'pending'
                };
                ?>
                <div class="invitation-card">
                    <div class="invitation-icon">
                        Event
                    </div>
                    <div class="invitation-content">
                        <h3 class="invitation-title"><?= htmlspecialchars($invitation['event_title']) ?></h3>
                        <p class="invitation-description">
                            <?php if (!empty($invitation['description'])): ?>
                                <?= htmlspecialchars(substr($invitation['description'], 0, 150)) ?>
                                <?= strlen($invitation['description']) > 150 ? '...' : '' ?>
                            <?php else: ?>
                                Join us for this event!
                            <?php endif; ?>
                        </p>
                        <div class="invitation-meta">
                            <div class="invitation-meta-item">
                                <i class="bi bi-calendar3"></i>
                                <span>
                                    <?= date('F d, Y', strtotime($invitation['event_date'])) ?>
                                    <?php if (!empty($invitation['event_time']) && $invitation['event_time'] !== '00:00:00'): ?>
                                        • <?= date('g:i A', strtotime($invitation['event_time'])) ?> - <?= date('g:i A', strtotime($invitation['event_time'] . ' +2 hours')) ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="invitation-meta-item">
                                <i class="bi bi-geo-alt"></i>
                                <span><?= htmlspecialchars($invitation['location']) ?></span>
                            </div>
                            <div class="invitation-meta-item host-info">
                                <i class="bi bi-person"></i>
                                <span>Hosted by <?= htmlspecialchars($invitation['host_first_name'] . ' ' . $invitation['host_last_name']) ?></span>
                            </div>
                        </div>
                        <a href="<?= BASE_URL ?>/view_event.php?id=<?= $invitation['event_id'] ?>" class="btn-view-details">
                            View Details
                        </a>
                    </div>
                    <div class="invitation-actions">
                        <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>

                        <?php if ($rsvp_status === 'yes'): ?>
                            <button class="btn-rsvp btn-accepted" disabled>
                                <i class="bi bi-check-circle me-2"></i>Accepted
                            </button>
                            <button class="btn-rsvp btn-maybe" onclick="submitRSVP(<?= $invitation['event_id'] ?>, 'maybe')">
                                Maybe
                            </button>
                            <button class="btn-rsvp btn-decline" onclick="submitRSVP(<?= $invitation['event_id'] ?>, 'not_attending')">
                                Decline
                            </button>
                        <?php else: ?>
                            <button class="btn-rsvp btn-accept" onclick="submitRSVP(<?= $invitation['event_id'] ?>, 'attending')">
                                Accept
                            </button>
                            <button class="btn-rsvp btn-maybe" onclick="submitRSVP(<?= $invitation['event_id'] ?>, 'maybe')">
                                Maybe
                            </button>
                            <button class="btn-rsvp btn-decline" onclick="submitRSVP(<?= $invitation['event_id'] ?>, 'not_attending')">
                                Decline
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-envelope"></i>
                <h3>No Invitations Yet</h3>
                <p>You don't have any event invitations at the moment.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function submitRSVP(eventId, status) {
            fetch('<?= BASE_URL ?>/handlers/rsvp_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `event_id=${eventId}&rsvp_status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
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
