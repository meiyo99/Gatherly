<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/config/Database.php';
require_once __DIR__ . '/../app/models/Event.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'You must be logged in to download guest lists.';
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$event_id = $_GET['id'] ?? null;

if (!$event_id) {
    $_SESSION['error'] = 'Event ID is required.';
    header('Location: ' . BASE_URL . '/events.php');
    exit;
}

try {
    $eventModel = new Event();
    $event = $eventModel->findById($event_id);

    if (!$event) {
        $_SESSION['error'] = 'Event not found.';
        header('Location: ' . BASE_URL . '/events.php');
        exit;
    }

    if ($event['host_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
        $_SESSION['error'] = 'You do not have permission to download this guest list.';
        header('Location: ' . BASE_URL . '/events.php');
        exit;
    }

    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT
            guest_name,
            guest_email,
            status,
            plus_ones,
            dietary_restrictions,
            response_date
        FROM rsvps
        WHERE event_id = :event_id
        ORDER BY response_date DESC
    ");
    $stmt->execute([':event_id' => $event_id]);
    $guests = $stmt->fetchAll();

    $filename = 'guest_list_' . preg_replace('/[^a-z0-9]+/', '_', strtolower($event['title'])) . '_' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    fputcsv($output, ['Name', 'Email', 'Status', 'Plus Ones', 'Dietary Restrictions', 'Response Date']);

    foreach ($guests as $guest) {
        fputcsv($output, [
            $guest['guest_name'],
            $guest['guest_email'],
            ucfirst($guest['status']),
            $guest['plus_ones'],
            $guest['dietary_restrictions'] ?? '',
            date('M d, Y g:i A', strtotime($guest['response_date']))
        ]);
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    $_SESSION['error'] = 'An error occurred while downloading the guest list.';
    header('Location: ' . BASE_URL . '/events.php');
    exit;
}
