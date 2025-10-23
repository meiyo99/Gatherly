<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/Event.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    $_SESSION['error'] = 'You do not have permission to delete this event.';
    header('Location: ' . BASE_URL . '/events.php');
    exit;
}

$success = $eventModel->delete($event_id);

if ($success) {
    $_SESSION['success'] = 'Event deleted successfully!';
} else {
    $_SESSION['error'] = 'Failed to delete event. Please try again.';
}

header('Location: ' . BASE_URL . '/events.php');
exit;
