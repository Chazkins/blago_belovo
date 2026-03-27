<?php
// notifications.php - объединённый файл: функции + страница уведомлений

// ========== ФУНКЦИИ (всегда доступны при подключении) ==========

if (!function_exists('create_notification')) {
    function create_notification($user_id, $type, $message, $link = null) {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$user_id, $type, $message, $link]);
    }
}

if (!function_exists('get_unread_notifications_count')) {
    function get_unread_notifications_count($user_id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    }
}

if (!function_exists('get_recent_notifications')) {
    function get_recent_notifications($user_id, $limit = 5) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll();
    }
}

if (!function_exists('mark_notification_read')) {
    function mark_notification_read($notification_id) {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        return $stmt->execute([$notification_id]);
    }
}

if (!function_exists('mark_all_notifications_read')) {
    function mark_all_notifications_read($user_id) {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }
}

// ========== ЕСЛИ ФАЙЛ ВЫЗВАН НАПРЯМУЮ (как страница) ==========
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    // Здесь начинается код страницы notifications.php
    session_start();
    require_once 'db.php'; // db.php уже должен подключать этот файл? Внимание: чтобы избежать циклических подключений, нужно убедиться, что db.php не подключает этот файл, или использовать условное подключение.
    // Поскольку мы сейчас внутри этого файла, а db.php может подключать его снова, нужно быть осторожным.
    // Лучше вынести подключение БД в начало, но здесь оно уже может быть выполнено.
    // Для простоты я продублирую проверку сессии и получение $pdo, но если db.php уже подключён, то $pdo уже есть.
    if (!isset($pdo)) {
        // если $pdo не определён, подключаем db.php
        require_once __DIR__ . '/db.php';
    }

    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit;
    }

    $user_id = $_SESSION['user_id'];

    // Обработка пометки прочитанным
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['mark_read'])) {
            $notif_id = (int)$_POST['mark_read'];
            mark_notification_read($notif_id);
            header("Location: notifications.php");
            exit;
        } elseif (isset($_POST['mark_all'])) {
            mark_all_notifications_read($user_id);
            echo json_encode(['success' => true]);
            exit;
        }
    }

    // Получаем все уведомления пользователя
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();

    $user_display_name = $_SESSION['full_name'] ?? $_SESSION['login'] ?? 'Пользователь';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Уведомления</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-panel">
    <button class="mobile-toggle d-lg-none"><i class="fas fa-bars"></i></button>

    <!-- Боковое меню (скопируйте из других страниц) -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo"><i class="fas fa-tree"></i></div>
            <div class="sidebar-title">BlagoBelovo</div>
        </div>
        <ul class="sidebar-nav">
            <li class="nav-item"><a href="index.php" class="nav-link"><i class="nav-icon fas fa-table"></i><span>Таблицы</span></a></li>
            <li class="nav-item"><a href="reports.php" class="nav-link"><i class="nav-icon fas fa-chart-bar"></i><span>Отчеты</span></a></li>
            <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i><span>Дашборд</span></a></li>
            <li class="nav-item"><a href="chat.php" class="nav-link"><i class="nav-icon fas fa-comments"></i><span>Сообщения</span></a></li>
            <?php if ($_SESSION['role'] == 'администратор'): ?>
                <li class="nav-item"><a href="backup.php" class="nav-link"><i class="nav-icon fas fa-database"></i><span>Бэкап</span></a></li>
                <li class="nav-item"><a href="audit.php" class="nav-link"><i class="nav-icon fas fa-history"></i><span>Журнал аудита</span></a></li>
            <?php endif; ?>
        </ul>
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar"><?= mb_substr($user_display_name, 0, 1, 'UTF-8') ?></div>
                <div class="user-details">
                    <h4><?= htmlspecialchars($user_display_name) ?></h4>
                    <p><?= htmlspecialchars($_SESSION['role'] ?? 'Пользователь') ?></p>
                </div>
            </div>
            <a href="index.php?action=logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Выйти</span></a>
        </div>
    </nav>

    <main class="main-content">
        <div class="content-header fade-in">
            <div class="header-title">
                <h1><i class="fas fa-bell me-2"></i>Уведомления</h1>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <div class="table-title">Все уведомления</div>
                <div class="table-count">
                    <span class="badge bg-primary"><?= count($notifications) ?> шт.</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Тип</th>
                            <th>Сообщение</th>
                            <th>Дата</th>
                            <th>Статус</th>
                            <th>Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($notifications) > 0): ?>
                            <?php foreach ($notifications as $notif): ?>
                                <tr class="<?= $notif['is_read'] ? '' : 'fw-bold' ?>">
                                    <td>
                                        <?php
                                        $icon = 'fa-info-circle';
                                        if ($notif['type'] == 'new_message') $icon = 'fa-envelope';
                                        elseif ($notif['type'] == 'new_plan') $icon = 'fa-calendar-plus';
                                        elseif ($notif['type'] == 'new_object') $icon = 'fa-tree';
                                        elseif ($notif['type'] == 'password_reset') $icon = 'fa-key';
                                        ?>
                                        <i class="fas <?= $icon ?>"></i> <?= htmlspecialchars($notif['type']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($notif['message']) ?></td>
                                    <td><?= date('d.m.Y H:i', strtotime($notif['created_at'])) ?></td>
                                    <td>
                                        <?php if ($notif['is_read']): ?>
                                            <span class="badge bg-secondary">Прочитано</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Новое</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$notif['is_read']): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="mark_read" value="<?= $notif['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Отметить прочитанным</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($notif['link']): ?>
                                            <a href="<?= htmlspecialchars($notif['link']) ?>" class="btn btn-sm btn-outline-secondary">Перейти</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted">Нет уведомлений</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.mobile-toggle')?.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>
<?php
} // конец проверки прямого вызова
?>