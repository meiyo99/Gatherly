<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/config/Database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/profile.php');
    exit;
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');

$_SESSION['form_data'] = [
    'first_name' => $first_name,
    'last_name' => $last_name,
    'email' => $email
];

if (empty($first_name) || empty($last_name) || empty($email)) {
    $_SESSION['error'] = 'All fields are required.';
    header('Location: ' . BASE_URL . '/profile.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Invalid email format.';
    header('Location: ' . BASE_URL . '/profile.php');
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = :email AND user_id != :user_id");
    $stmt->execute([
        ':email' => $email,
        ':user_id' => $_SESSION['user_id']
    ]);

    if ($stmt->fetch()) {
        $_SESSION['error'] = 'Email already in use by another account.';
        header('Location: ' . BASE_URL . '/profile.php');
        exit;
    }

    $stmt = $db->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email WHERE user_id = :user_id");
    $stmt->execute([
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':email' => $email,
        ':user_id' => $_SESSION['user_id']
    ]);

    $_SESSION['first_name'] = $first_name;
    $_SESSION['last_name'] = $last_name;
    $_SESSION['email'] = $email;

    unset($_SESSION['form_data']);
    $_SESSION['success'] = 'Profile updated successfully.';
    header('Location: ' . BASE_URL . '/profile.php');
    exit;

} catch (Exception $e) {
    $_SESSION['error'] = 'An error occurred while updating your profile.';
    header('Location: ' . BASE_URL . '/profile.php');
    exit;
}
