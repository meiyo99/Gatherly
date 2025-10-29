<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/config/Database.php';
require_once __DIR__ . '/../app/models/Event.php';
require_once __DIR__ . '/../app/helpers/Email.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to send invitations.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$event_id = $_POST['event_id'] ?? null;
$guest_name = trim($_POST['guest_name'] ?? '');
$guest_email = trim($_POST['guest_email'] ?? '');

if (!$event_id || !$guest_name || !$guest_email) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

if (!filter_var($guest_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

try {
    $eventModel = new Event();
    $event = $eventModel->findById($event_id);

    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Event not found.']);
        exit;
    }

    if ($event['host_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to invite guests to this event.']);
        exit;
    }

    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT * FROM invitations WHERE event_id = :event_id AND guest_email = :guest_email");
    $stmt->execute([
        ':event_id' => $event_id,
        ':guest_email' => $guest_email
    ]);

    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'This guest has already been invited to this event.']);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO invitations (event_id, guest_name, guest_email, invited_by) VALUES (:event_id, :guest_name, :guest_email, :invited_by)");
    $stmt->execute([
        ':event_id' => $event_id,
        ':guest_name' => $guest_name,
        ':guest_email' => $guest_email,
        ':invited_by' => $_SESSION['user_id']
    ]);

    $host_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    $event_url = $_ENV['APP_URL'] . '/view_event.php?id=' . $event_id;

    Email::sendEventInvitation(
        $guest_email,
        $guest_name,
        $event['title'],
        $event['event_date'],
        $event['event_time'] ?? '00:00:00',
        $event['location'],
        $host_name,
        $event_url
    );

    echo json_encode(['success' => true, 'message' => 'Invitation sent successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred while sending the invitation.']);
}
