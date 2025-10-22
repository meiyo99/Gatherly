<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event - Gatherly</title>
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
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border: 1px solid #ccc;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: bold;
        }
        .required::after {
            content: " *";
            color: red;
        }
        input[type="text"],
        input[type="date"],
        input[type="time"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            font-size: 14px;
        }
        input:focus,
        textarea:focus,
        select:focus {
            outline: 2px solid #3498db;
        }
        textarea {
            min-height: 120px;
        }
        .form-row {
            display: table;
            width: 100%;
        }
        .form-row .form-group {
            display: table-cell;
            padding-right: 20px;
        }
        .flash-error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
            padding: 15px;
            margin-bottom: 20px;
        }
        .form-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #ccc;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin-right: 10px;
        }
        .btn:hover {
            opacity: 0.8;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create New Event</h1>
        <p class="subtitle">Fill in the details below to create your event</p>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="flash-error">
                <?php
                    echo htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/event/store">
            <div class="form-group">
                <label for="title" class="required">Event Title</label>
                <input type="text" id="title" name="title" required
                       placeholder="e.g., Summer BBQ Party"
                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"
                          placeholder="Tell your guests what this event is about..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                <div class="help-text">Optional: Add more details about your event</div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="event_date" class="required">Event Date</label>
                    <input type="date" id="event_date" name="event_date" required
                           value="<?php echo htmlspecialchars($_POST['event_date'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="event_time" class="required">Event Time</label>
                    <input type="time" id="event_time" name="event_time" required
                           value="<?php echo htmlspecialchars($_POST['event_time'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="location" class="required">Location</label>
                <input type="text" id="location" name="location" required
                       placeholder="e.g., Central Park, New York"
                       value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="max_guests">Maximum Guests</label>
                    <input type="number" id="max_guests" name="max_guests" min="1"
                           placeholder="e.g., 50"
                           value="<?php echo htmlspecialchars($_POST['max_guests'] ?? ''); ?>">
                    <div class="help-text">Leave empty for unlimited guests</div>
                </div>

                <div class="form-group">
                    <label for="status" class="required">Status</label>
                    <select id="status" name="status" required>
                        <option value="draft" <?php echo (($_POST['status'] ?? 'draft') === 'draft') ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo (($_POST['status'] ?? '') === 'published') ? 'selected' : ''; ?>>Published</option>
                    </select>
                    <div class="help-text">Save as draft or publish immediately</div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Event</button>
                <a href="/event/index" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
