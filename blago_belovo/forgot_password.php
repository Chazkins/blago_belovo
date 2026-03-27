<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['login'])) {
    echo json_encode(['success' => false, 'error' => 'Неверный запрос']);
    exit;
}

$login = trim($_POST['login']);
if (empty($login)) {
    echo json_encode(['success' => false, 'error' => 'Логин не может быть пустым']);
    exit;
}

// Проверяем, существует ли пользователь
$stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE login = ?");
$stmt->execute([$login]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Пользователь с таким логином не найден']);
    exit;
}

// Находим всех администраторов
$admins = $pdo->query("SELECT id FROM users WHERE role = 'администратор'")->fetchAll(PDO::FETCH_COLUMN);

if (empty($admins)) {
    echo json_encode(['success' => false, 'error' => 'Нет администраторов в системе']);
    exit;
}

$message = "Пользователь " . ($user['full_name'] ?: $login) . " (логин: $login) запросил сброс пароля.";

// Создаём уведомление для каждого администратора
foreach ($admins as $admin_id) {
    create_notification($admin_id, 'password_reset', $message, 'index.php?table=users&edit_id=' . $user['id']);
}

echo json_encode(['success' => true]);