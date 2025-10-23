<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/Event.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/events.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$event_id = trim($_POST['event_id'] ?? '');

if (empty($event_id)) {
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
    $_SESSION['error'] = 'You do not have permission to edit this event.';
    header('Location: ' . BASE_URL . '/events.php');
    exit;
}

$errors = [];

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$event_date = trim($_POST['event_date'] ?? '');
$event_time = trim($_POST['event_time'] ?? '00:00:00');
$location = trim($_POST['location'] ?? '');
$max_guests = trim($_POST['max_guests'] ?? '');
$status = trim($_POST['status'] ?? 'draft');

if (empty($title)) {
    $errors[] = 'Event title is required.';
}

if (empty($event_date)) {
    $errors[] = 'Event date is required.';
}

if (empty($location)) {
    $errors[] = 'Location is required.';
}

if (!empty($max_guests) && (!is_numeric($max_guests) || $max_guests < 1)) {
    $errors[] = 'Maximum guests must be a positive number.';
}

if (!in_array($status, ['draft', 'published', 'completed', 'cancelled'])) {
    $status = 'draft';
}

if (!empty($errors)) {
    $_SESSION['error'] = implode(' ', $errors);
    $_SESSION['form_data'] = $_POST;
    header('Location: ' . BASE_URL . '/edit_event.php?id=' . $event_id);
    exit;
}

$eventData = [
    'title' => $title,
    'description' => $description,
    'event_date' => $event_date,
    'event_time' => $event_time,
    'location' => $location,
    'location_lat' => null,
    'location_lng' => null,
    'max_guests' => !empty($max_guests) ? (int)$max_guests : 0,
    'status' => $status
];

$success = $eventModel->update($event_id, $eventData);

if ($success) {
    $_SESSION['success'] = 'Event updated successfully!';
    header('Location: ' . BASE_URL . '/events.php');
} else {
    $_SESSION['error'] = 'Failed to update event. Please try again.';
    $_SESSION['form_data'] = $_POST;
    header('Location: ' . BASE_URL . '/edit_event.php?id=' . $event_id);
}
exit;
