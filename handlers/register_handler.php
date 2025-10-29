<?php
require_once __DIR__ . '/../config/config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/register.php');
    exit;
}

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$errors = [];

$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($first_name)) {
    $errors[] = 'First name is required.';
} elseif (strlen($first_name) > 100) {
    $errors[] = 'First name must be less than 100 characters.';
}

if (empty($last_name)) {
    $errors[] = 'Last name is required.';
} elseif (strlen($last_name) > 100) {
    $errors[] = 'Last name must be less than 100 characters.';
}

if (empty($email)) {
    $errors[] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format.';
} elseif (strlen($email) > 255) {
    $errors[] = 'Email must be less than 255 characters.';
}

if (empty($password)) {
    $errors[] = 'Password is required.';
} elseif (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters long.';
} elseif (!preg_match('/[A-Z]/', $password)) {
    $errors[] = 'Password must contain at least one uppercase letter.';
} elseif (!preg_match('/[0-9]/', $password)) {
    $errors[] = 'Password must contain at least one number.';
}

if (empty($confirm_password)) {
    $errors[] = 'Please confirm your password.';
} elseif ($password !== $confirm_password) {
    $errors[] = 'Passwords do not match.';
}

if (!empty($errors)) {
    $_SESSION['error'] = implode(' ', $errors);
    $_SESSION['form_data'] = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email
    ];
    header('Location: ' . BASE_URL . '/register.php');
    exit;
}

try {
    require_once __DIR__ . '/../app/config/Database.php';
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        $_SESSION['error'] = 'An account with this email already exists.';
        $_SESSION['form_data'] = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email
        ];
        header('Location: ' . BASE_URL . '/register.php');
        exit;
    }

    $verification_token = bin2hex(random_bytes(32));
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare("
        INSERT INTO users (email, password_hash, first_name, last_name, role, email_verified, verification_token, created_at)
        VALUES (?, ?, ?, ?, 'host', 0, ?, NOW())
    ");

    $stmt->execute([
        $email,
        $password_hash,
        $first_name,
        $last_name,
        $verification_token
    ]);

    $user_id = $db->lastInsertId();

    $_SESSION['success'] = 'Registration successful! Please check your email to verify your account.';
    unset($_SESSION['form_data']);

    header('Location: ' . BASE_URL . '/login.php');
    exit;

} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());

    $_SESSION['error'] = 'An error occurred during registration. Please try again.';
    $_SESSION['form_data'] = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email
    ];

    header('Location: ' . BASE_URL . '/register.php');
    exit;
}
