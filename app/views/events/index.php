<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Events - Gatherly</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: #eeeeee;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border: 1px solid #ddd;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .header-actions {
            margin-bottom: 30px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        .btn:hover {
            opacity: 0.8;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-success {
            background: #2ecc71;
            color: white;
        }
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        .flash-message {
            padding: 15px;
            margin-bottom: 20px;
            border: 2px solid;
        }
        .flash-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .flash-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border: 1px solid #ddd;
        }
        th {
            background: #34495e;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 12px;
            border: 1px solid #ddd;
        }
        .badge {
            padding: 4px 10px;
            font-size: 12px;
            display: inline-block;
        }
        .badge-draft {
            background: #95a5a6;
            color: white;
        }
        .badge-published {
            background: #2ecc71;
            color: white;
        }
        .badge-completed {
            background: #3498db;
            color: white;
        }
        .badge-cancelled {
            background: #e74c3c;
            color: white;
        }
        .actions {
            display: inline;
        }
        .no-events {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .no-events p {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-actions">
            <h1>My Events Dashboard</h1>
            <a href="/event/create" class="btn btn-success">+ Create New Event</a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="flash-message flash-success">
                <?php
                    echo htmlspecialchars($_SESSION['success']);
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="flash-message flash-error">
                <?php
                    echo htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($events)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Max Guests</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($event['title']); ?></strong></td>
                            <td>
                                <?php
                                    echo date('M d, Y', strtotime($event['event_date']));
                                    if (!empty($event['event_time']) && $event['event_time'] !== '00:00:00') {
                                        echo ' at ' . date('g:i A', strtotime($event['event_time']));
                                    }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($event['location']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo htmlspecialchars($event['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($event['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo (int)$event['max_guests']; ?></td>
                            <td>
                                <div class="actions">
                                    <a href="/event/show/<?php echo $event['event_id']; ?>" class="btn btn-primary btn-sm">View</a>
                                    <a href="/event/edit/<?php echo $event['event_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <form method="POST" action="/event/destroy/<?php echo $event['event_id']; ?>" style="display: inline;"
                                          onsubmit="return confirm('Are you sure you want to cancel this event?');">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-events">
                <p>You haven't created any events yet.</p>
                <a href="/event/create" class="btn btn-success">Create Your First Event</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
