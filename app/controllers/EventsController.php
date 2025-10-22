<?php

require_once __DIR__ . '/../models/Event.php';

class EventsController
{
    private $eventModel;

    public function __construct()
    {
        $this->eventModel = new Event();
    }

    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Please log in to view your events';
            header('Location: /login');
            exit;
        }

        $hostId = $_SESSION['user_id'];
        $events = $this->eventModel->findByHostId($hostId);

        require_once __DIR__ . '/../views/events/index.php';
    }

    public function create()
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Please log in to create events';
            header('Location: /login');
            exit;
        }

        require_once __DIR__ . '/../views/events/create.php';
    }

    public function store()
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Please log in to create events';
            header('Location: /login');
            exit;
        }

        $errors = [];
        if (empty($_POST['title'])) {
            $errors[] = 'Title is required';
        }
        if (empty($_POST['event_date'])) {
            $errors[] = 'Event date is required';
        }
        if (empty($_POST['location'])) {
            $errors[] = 'Location is required';
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode(', ', $errors);
            header('Location: /event/create');
            exit;
        }

        $data = [
            'host_id' => $_SESSION['user_id'],
            'title' => trim($_POST['title']),
            'description' => trim($_POST['description'] ?? ''),
            'event_date' => $_POST['event_date'],
            'event_time' => $_POST['event_time'] ?? '00:00:00',
            'location' => trim($_POST['location']),
            'location_lat' => $_POST['location_lat'] ?? null,
            'location_lng' => $_POST['location_lng'] ?? null,
            'max_guests' => (int)($_POST['max_guests'] ?? 0),
            'status' => $_POST['status'] ?? 'draft'
        ];

        $eventId = $this->eventModel->create($data);

        if ($eventId) {
            $_SESSION['success'] = 'Event created successfully';
            header('Location: /event/index');
        } else {
            $_SESSION['error'] = 'Failed to create event';
            header('Location: /event/create');
        }
        exit;
    }

    public function show($id)
    {
        $event = $this->eventModel->findById($id);

        if (!$event) {
            $_SESSION['error'] = 'Event not found';
            header('Location: /event/index');
            exit;
        }

        require_once __DIR__ . '/../views/events/show.php';
    }

    public function edit($id)
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Please log in to edit events';
            header('Location: /login');
            exit;
        }

        $event = $this->eventModel->findById($id);

        if (!$event) {
            $_SESSION['error'] = 'Event not found';
            header('Location: /event/index');
            exit;
        }

        // make sure they own the event before letting them edit it
        if ($event['host_id'] != $_SESSION['user_id']) {
            $_SESSION['error'] = 'You do not have permission to edit this event';
            header('Location: /event/index');
            exit;
        }

        require_once __DIR__ . '/../views/events/edit.php';
    }

    public function update($id)
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Please log in to update events';
            header('Location: /login');
            exit;
        }

        $event = $this->eventModel->findById($id);

        if (!$event) {
            $_SESSION['error'] = 'Event not found';
            header('Location: /event/index');
            exit;
        }

        if ($event['host_id'] != $_SESSION['user_id']) {
            $_SESSION['error'] = 'You do not have permission to edit this event';
            header('Location: /event/index');
            exit;
        }

        $errors = [];
        if (empty($_POST['title'])) {
            $errors[] = 'Title is required';
        }
        if (empty($_POST['event_date'])) {
            $errors[] = 'Event date is required';
        }
        if (empty($_POST['location'])) {
            $errors[] = 'Location is required';
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode(', ', $errors);
            header("Location: /event/edit/$id");
            exit;
        }

        $data = [
            'title' => trim($_POST['title']),
            'description' => trim($_POST['description'] ?? ''),
            'event_date' => $_POST['event_date'],
            'event_time' => $_POST['event_time'] ?? '00:00:00',
            'location' => trim($_POST['location']),
            'location_lat' => $_POST['location_lat'] ?? null,
            'location_lng' => $_POST['location_lng'] ?? null,
            'max_guests' => (int)($_POST['max_guests'] ?? 0),
            'status' => $_POST['status'] ?? 'draft'
        ];

        $success = $this->eventModel->update($id, $data);

        if ($success) {
            $_SESSION['success'] = 'Event updated successfully';
            header('Location: /event/index');
        } else {
            $_SESSION['error'] = 'Failed to update event';
            header("Location: /event/edit/$id");
        }
        exit;
    }

    public function destroy($id)
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Please log in to delete events';
            header('Location: /login');
            exit;
        }

        $event = $this->eventModel->findById($id);

        if (!$event) {
            $_SESSION['error'] = 'Event not found';
            header('Location: /event/index');
            exit;
        }

        if ($event['host_id'] != $_SESSION['user_id']) {
            $_SESSION['error'] = 'You do not have permission to delete this event';
            header('Location: /event/index');
            exit;
        }

        $success = $this->eventModel->delete($id);

        if ($success) {
            $_SESSION['success'] = 'Event cancelled successfully';
        } else {
            $_SESSION['error'] = 'Failed to cancel event';
        }

        header('Location: /event/index');
        exit;
    }
}
