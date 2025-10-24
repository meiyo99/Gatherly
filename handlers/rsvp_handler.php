<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/config/Database.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to RSVP.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$event_id = $_POST['event_id'] ?? null;
$rsvp_status = $_POST['rsvp_status'] ?? null;

if (!$event_id || !$rsvp_status) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

$status_map = [
    'attending' => 'yes',
    'maybe' => 'maybe',
    'not_attending' => 'no'
];

if (!isset($status_map[$rsvp_status])) {
    echo json_encode(['success' => false, 'message' => 'Invalid RSVP status.']);
    exit;
}

$status = $status_map[$rsvp_status];

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT email, first_name, last_name FROM users WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    $guest_name = $user['first_name'] . ' ' . $user['last_name'];
    $guest_email = $user['email'];

    $stmt = $db->prepare("SELECT * FROM rsvps WHERE event_id = :event_id AND guest_id = :guest_id");
    $stmt->execute([
        ':event_id' => $event_id,
        ':guest_id' => $_SESSION['user_id']
    ]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $db->prepare("UPDATE rsvps SET status = :status WHERE event_id = :event_id AND guest_id = :guest_id");
        $stmt->execute([
            ':status' => $status,
            ':event_id' => $event_id,
            ':guest_id' => $_SESSION['user_id']
        ]);
    } else {
        $rsvp_token = bin2hex(random_bytes(16));
        $stmt = $db->prepare("INSERT INTO rsvps (event_id, guest_id, guest_email, guest_name, status, rsvp_token, response_date) VALUES (:event_id, :guest_id, :guest_email, :guest_name, :status, :rsvp_token, NOW())");
        $stmt->execute([
            ':event_id' => $event_id,
            ':guest_id' => $_SESSION['user_id'],
            ':guest_email' => $guest_email,
            ':guest_name' => $guest_name,
            ':status' => $status,
            ':rsvp_token' => $rsvp_token
        ]);
    }

    echo json_encode(['success' => true, 'message' => 'RSVP updated successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
}
