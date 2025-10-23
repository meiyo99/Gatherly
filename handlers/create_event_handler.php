<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/Event.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/create_event.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
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

if (!in_array($status, ['draft', 'published'])) {
    $status = 'draft';
}

if (!empty($errors)) {
    $_SESSION['error'] = implode(' ', $errors);
    $_SESSION['form_data'] = $_POST;
    header('Location: ' . BASE_URL . '/create_event.php');
    exit;
}

$eventModel = new Event();

$eventData = [
    'host_id' => $_SESSION['user_id'],
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

$eventId = $eventModel->create($eventData);

if ($eventId) {
    $_SESSION['success'] = 'Event created successfully!';
    header('Location: ' . BASE_URL . '/events.php');
} else {
    $_SESSION['error'] = 'Failed to create event. Please try again.';
    $_SESSION['form_data'] = $_POST;
    header('Location: ' . BASE_URL . '/create_event.php');
}
exit;
