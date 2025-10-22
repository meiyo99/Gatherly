<?php
require_once __DIR__ . '/config/config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$user_role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gatherly</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f0f0f0;
        }
        .card {
            border: 2px solid #ddd;
            border-radius: 0;
            box-shadow: none;
        }
        .btn {
            border-radius: 0;
        }
        .alert {
            border-radius: 0;
            border-width: 2px;
        }
        .navbar {
            border-radius: 0;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= BASE_URL ?>/dashboard.php">
                <i class="bi bi-calendar-event me-2"></i>Gatherly
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($user_name) ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/handlers/logout_handler.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Dashboard</h1>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?= htmlspecialchars($_SESSION['success']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <div class="alert alert-info">
                    <h4 class="alert-heading">Welcome, <?= htmlspecialchars($_SESSION['first_name']) ?>!</h4>
                    <p>You are logged in as: <strong><?= htmlspecialchars($user_role) ?></strong></p>
                    <hr>
                    <p class="mb-0">This is your main dashboard. Use the buttons below to manage your events!</p>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-3">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-plus text-primary" style="font-size: 3rem;"></i>
                        <h5 class="card-title mt-3">Create Event</h5>
                        <p class="card-text">Plan your next celebration</p>
                        <a href="#" class="btn btn-primary">Coming Soon</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-check text-success" style="font-size: 3rem;"></i>
                        <h5 class="card-title mt-3">My Events</h5>
                        <p class="card-text">View and manage events</p>
                        <a href="<?= BASE_URL ?>/events.php" class="btn btn-success">View Events</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-envelope text-warning" style="font-size: 3rem;"></i>
                        <h5 class="card-title mt-3">Invitations</h5>
                        <p class="card-text">Respond to RSVPs</p>
                        <a href="#" class="btn btn-warning">Coming Soon</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
