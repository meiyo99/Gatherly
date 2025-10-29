<?php
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$errors = [];

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

if (empty($email)) {
    $errors[] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format.';
}

if (empty($password)) {
    $errors[] = 'Password is required.';
}

if (!empty($errors)) {
    $_SESSION['error'] = implode(' ', $errors);
    $_SESSION['form_data'] = ['email' => $email];
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

try {
    require_once __DIR__ . '/../app/config/Database.php';
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("
        SELECT user_id, email, password_hash, first_name, last_name, role, email_verified
        FROM users
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['error'] = 'Invalid email or password.';
        $_SESSION['form_data'] = ['email' => $email];
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }

    if (!password_verify($password, $user['password_hash'])) {
        $_SESSION['error'] = 'Invalid email or password.';
        $_SESSION['form_data'] = ['email' => $email];
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }

    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();

    if ($remember) {
        ini_set('session.gc_maxlifetime', 30 * 24 * 60 * 60);
        session_set_cookie_params(30 * 24 * 60 * 60);
    }

    unset($_SESSION['form_data']);

    if ($user['role'] === 'admin') {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/dashboard.php');
    }
    exit;

} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());

    $_SESSION['error'] = 'An error occurred during login. Please try again.';
    $_SESSION['form_data'] = ['email' => $email];

    header('Location: ' . BASE_URL . '/login.php');
    exit;
}
