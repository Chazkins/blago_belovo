<?php
session_start();
require 'db.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];

// --- Убедимся, что в таблице messages есть нужные поля (можно выполнить один раз вручную)
// ALTER TABLE messages ADD COLUMN deleted_by_sender TINYINT(1) NOT NULL DEFAULT 0 AFTER is_read;
// ALTER TABLE messages ADD COLUMN deleted_by_receiver TINYINT(1) NOT NULL DEFAULT 0 AFTER deleted_by_sender;

// --- Отправка сообщения (с файлом) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_id = (int)$_POST['receiver_id'];
    $message = trim($_POST['message']);
    $file_name = null;
    $file_path = null;

    // Обработка загруженного файла
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/chat/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $original_name = basename($_FILES['attachment']['name']);
        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $new_filename = uniqid() . '_' . time() . '.' . $extension;
        $destination = $upload_dir . $new_filename;
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $destination)) {
            $file_name = $original_name;
            $file_path = $destination;
        }
    }

    if ($receiver_id && ($message || $file_path)) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, file_name, file_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$current_user_id, $receiver_id, $message, $file_name, $file_path]);
        
        // Уведомление получателю
        $sender_name = $_SESSION['full_name'] ?? $_SESSION['login'] ?? 'Пользователь';
        create_notification($receiver_id, 'new_message', "Новое сообщение от $sender_name", "chat.php?contact_id=" . $current_user_id);
    }
    header("Location: chat.php?contact_id=" . $receiver_id);
    exit;
}

// --- Пересылка сообщения ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forward_message'])) {
    $receiver_id = (int)$_POST['receiver_id'];
    $original_message_id = (int)$_POST['message_id'];
    
    // Получаем оригинальное сообщение
    $stmt = $pdo->prepare("SELECT message, file_name, file_path FROM messages WHERE id = ?");
    $stmt->execute([$original_message_id]);
    $orig = $stmt->fetch();
    
    if ($orig && $receiver_id) {
        // Копируем файл, если он есть
        $new_file_name = null;
        $new_file_path = null;
        if ($orig['file_path'] && file_exists($orig['file_path'])) {
            $upload_dir = 'uploads/chat/';
            $extension = pathinfo($orig['file_name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '_forward_' . time() . '.' . $extension;
            $destination = $upload_dir . $new_filename;
            if (copy($orig['file_path'], $destination)) {
                $new_file_name = $orig['file_name'];
                $new_file_path = $destination;
            }
        }
        
        // Добавляем пометку о пересылке
        $forward_text = "⟳ Пересланное сообщение:\n\n" . $orig['message'];
        
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, file_name, file_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$current_user_id, $receiver_id, $forward_text, $new_file_name, $new_file_path]);
    }
    header("Location: chat.php?contact_id=" . $receiver_id);
    exit;
}

// --- Удаление одного сообщения (только своих) ---
if (isset($_GET['delete_message'])) {
    $message_id = (int)$_GET['delete_message'];
    // Проверяем, что сообщение принадлежит текущему пользователю
    $stmt = $pdo->prepare("SELECT sender_id, file_path FROM messages WHERE id = ?");
    $stmt->execute([$message_id]);
    $msg = $stmt->fetch();
    if ($msg && $msg['sender_id'] == $current_user_id) {
        // Удаляем файл, если есть
        if ($msg['file_path'] && file_exists($msg['file_path'])) {
            unlink($msg['file_path']);
        }
        $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
        $stmt->execute([$message_id]);
    }
    // Возвращаемся в тот же диалог
    $back_contact = isset($_GET['contact_id']) ? (int)$_GET['contact_id'] : 0;
    header("Location: chat.php?contact_id=" . $back_contact);
    exit;
}

// --- Очистка всей истории с контактом (ТОЛЬКО ДЛЯ СЕБЯ) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_history'])) {
    $contact_id = (int)$_POST['contact_id'];
    if ($contact_id) {
        // Помечаем сообщения, где текущий пользователь - отправитель
        $stmt = $pdo->prepare("UPDATE messages SET deleted_by_sender = 1 WHERE sender_id = ? AND receiver_id = ?");
        $stmt->execute([$current_user_id, $contact_id]);
        // Помечаем сообщения, где текущий пользователь - получатель
        $stmt = $pdo->prepare("UPDATE messages SET deleted_by_receiver = 1 WHERE sender_id = ? AND receiver_id = ?");
        $stmt->execute([$contact_id, $current_user_id]);
        
        header("Location: chat.php?contact_id=" . $contact_id);
        exit;
    }
}

// --- Получаем список всех пользователей, кроме текущего ---
$users = $pdo->prepare("SELECT id, login, full_name, role FROM users WHERE id != ? ORDER BY full_name");
$users->execute([$current_user_id]);
$users = $users->fetchAll();

// --- Выбранный контакт ---
$contact_id = isset($_GET['contact_id']) ? (int)$_GET['contact_id'] : 0;
$contact = null;
$messages = [];

if ($contact_id) {
    $stmt = $pdo->prepare("SELECT id, login, full_name FROM users WHERE id = ?");
    $stmt->execute([$contact_id]);
    $contact = $stmt->fetch();

    if ($contact) {
        // Помечаем сообщения от контакта как прочитанные
        $update = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
        $update->execute([$contact_id, $current_user_id]);

        // Получаем переписку, исключая удалённые для текущего пользователя
        $stmt = $pdo->prepare("
            SELECT * FROM messages 
            WHERE (sender_id = ? AND receiver_id = ? AND deleted_by_sender = 0) 
               OR (sender_id = ? AND receiver_id = ? AND deleted_by_receiver = 0)
            ORDER BY created_at ASC
        ");
        $stmt->execute([$current_user_id, $contact_id, $contact_id, $current_user_id]);
        $messages = $stmt->fetchAll();
    } else {
        $contact_id = 0;
    }
}

// --- Подсчёт непрочитанных для каждого пользователя ---
$unread_per_user = [];
$stmt = $pdo->prepare("
    SELECT sender_id, COUNT(*) as cnt 
    FROM messages 
    WHERE receiver_id = ? AND is_read = 0 
    GROUP BY sender_id
");
$stmt->execute([$current_user_id]);
foreach ($stmt->fetchAll() as $row) {
    $unread_per_user[$row['sender_id']] = $row['cnt'];
}

$user_display_name = $_SESSION['full_name'] ?? $_SESSION['login'] ?? 'Пользователь';

// Получаем данные для уведомлений
$unread_notifications = get_unread_notifications_count($_SESSION['user_id']);
$recent_notifications = get_recent_notifications($_SESSION['user_id'], 5);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Чат сотрудников</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .chat-container { display: flex; height: calc(100vh - 140px); background: white; border-radius: 12px; overflow: hidden; box-shadow: var(--shadow); }
        .contacts-sidebar { width: 280px; border-right: 1px solid #e5e7eb; overflow-y: auto; background: #f9fafb; }
        .chat-main { flex: 1; display: flex; flex-direction: column; }
        .chat-header { padding: 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; background: white; }
        .chat-messages { flex: 1; padding: 20px; overflow-y: auto; background: #f9fafb; }
        .chat-input { padding: 20px; border-top: 1px solid #e5e7eb; background: white; }
        .message { margin-bottom: 15px; max-width: 70%; clear: both; position: relative; }
        .message.sent { float: right; }
        .message.received { float: left; }
        .message-bubble { padding: 12px 16px; border-radius: 18px; display: inline-block; word-wrap: break-word; max-width: 100%; }
        .sent .message-bubble { background: #2ecc71; color: white; border-bottom-right-radius: 4px; }
        .received .message-bubble { background: white; border: 1px solid #e5e7eb; border-bottom-left-radius: 4px; }
        .message-time { font-size: 11px; color: #6c757d; margin-top: 4px; text-align: right; }
        .message-file { margin-top: 8px; padding: 8px; background: rgba(0,0,0,0.03); border-radius: 8px; }
        .message-file a { text-decoration: none; }
        .read-status { font-size: 12px; color: #2ecc71; margin-left: 8px; }
        .message-actions { position: absolute; top: 0; right: -30px; display: none; background: white; border-radius: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); padding: 2px; }
        .message:hover .message-actions { display: flex; }
        .message-actions button, .message-actions a { border: none; background: none; padding: 5px 8px; font-size: 12px; color: #6c757d; cursor: pointer; }
        .message-actions button:hover, .message-actions a:hover { color: #dc3545; }
        .message-actions a:hover { color: #0d6efd; }
        .contact-item { padding: 15px 20px; border-bottom: 1px solid #e5e7eb; cursor: pointer; transition: background 0.2s; }
        .contact-item:hover { background: #e9ecef; }
        .contact-item.active { background: #d4edda; border-left: 4px solid #2ecc71; }
        .contact-name { font-weight: 500; }
        .unread-badge { background: #dc3545; color: white; border-radius: 20px; padding: 2px 8px; font-size: 12px; margin-left: 8px; }
        .btn-outline-danger { border-color: #dc3545; color: #dc3545; }
        .btn-outline-danger:hover { background: #dc3545; color: white; }
    </style>
</head>
<body class="admin-panel">
    <button class="mobile-toggle d-lg-none"><i class="fas fa-bars"></i></button>

    <nav class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo"><i class="fas fa-tree"></i></div>
            <div class="sidebar-title">BlagoBelovo</div>
        </div>
        <ul class="sidebar-nav">
            <li class="nav-item"><a href="index.php" class="nav-link"><i class="nav-icon fas fa-table"></i><span>Таблицы</span></a></li>
            <li class="nav-item"><a href="reports.php" class="nav-link"><i class="nav-icon fas fa-chart-bar"></i><span>Отчеты</span></a></li>
            <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i><span>Дашборд</span></a></li>
            <li class="nav-item"><a href="chat.php" class="nav-link active"><i class="nav-icon fas fa-comments"></i><span>Сообщения</span></a></li>
             <?php if ($_SESSION['role'] == 'администратор'): ?>
                    <li class="nav-item">
                    <a href="backup.php" class="nav-link">
                    <i class="nav-icon fas fa-database"></i><span>Бэкап</span>
                    </a>
                    </li>
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
                <h1><i class="fas fa-comments me-2"></i>Чат сотрудников</h1>
                <p>Общайтесь с коллегами</p>
            </div>
            <div class="header-actions">
                <!-- Иконка уведомлений -->
                <div class="dropdown d-inline-block me-2">
                    <button class="btn btn-outline-secondary position-relative" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_notifications > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $unread_notifications ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" style="width: 300px;">
                        <div class="dropdown-header d-flex justify-content-between align-items-center">
                            <span>Уведомления</span>
                            <?php if ($unread_notifications > 0): ?>
                                <a href="#" class="text-decoration-none small" onclick="markAllNotificationsRead(); return false;">Прочитать все</a>
                            <?php endif; ?>
                        </div>
                        <div class="dropdown-divider"></div>
                        <?php if (count($recent_notifications) > 0): ?>
                            <?php foreach ($recent_notifications as $notif): ?>
                                <a class="dropdown-item <?= $notif['is_read'] ? '' : 'fw-bold' ?>" href="<?= htmlspecialchars($notif['link'] ?? '#') ?>">
                                    <div class="d-flex">
                                        <div class="me-2">
                                            <?php
                                            $icon = 'fa-info-circle';
                                            if ($notif['type'] == 'new_message') $icon = 'fa-envelope';
                                            elseif ($notif['type'] == 'new_plan') $icon = 'fa-calendar-plus';
                                            elseif ($notif['type'] == 'new_object') $icon = 'fa-tree';
                                            elseif ($notif['type'] == 'password_reset') $icon = 'fa-key';
                                            ?>
                                            <i class="fas <?= $icon ?>"></i>
                                        </div>
                                        <div>
                                            <div><?= htmlspecialchars($notif['message']) ?></div>
                                            <small class="text-muted"><?= date('d.m.Y H:i', strtotime($notif['created_at'])) ?></small>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-center" href="notifications.php">Все уведомления</a>
                        <?php else: ?>
                            <div class="dropdown-item text-muted text-center">Нет уведомлений</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="chat-container">
            <!-- Список контактов -->
            <div class="contacts-sidebar">
                <div class="p-3 fw-bold border-bottom">Сотрудники</div>
                <?php foreach ($users as $user): 
                    $unread = $unread_per_user[$user['id']] ?? 0;
                ?>
                    <a href="chat.php?contact_id=<?= $user['id'] ?>" class="text-decoration-none text-dark">
                        <div class="contact-item <?= $contact_id == $user['id'] ? 'active' : '' ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="contact-name"><?= htmlspecialchars($user['full_name'] ?: $user['login']) ?></span>
                                <?php if ($unread > 0): ?>
                                    <span class="unread-badge"><?= $unread ?></span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted"><?= htmlspecialchars($user['role']) ?></small>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Область переписки -->
            <div class="chat-main">
                <?php if ($contact): ?>
                    <div class="chat-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-user me-2"></i><?= htmlspecialchars($contact['full_name'] ?: $contact['login']) ?>
                        </div>
                        <div>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Очистить историю переписки с этим пользователем? Это удалит сообщения только для вас, другой пользователь их по-прежнему будет видеть.');">
                                <input type="hidden" name="contact_id" value="<?= $contact_id ?>">
                                <button type="submit" name="clear_history" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash-alt"></i> Очистить историю
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="chat-messages" id="chatMessages">
                        <?php foreach ($messages as $msg): 
                            $isMine = $msg['sender_id'] == $current_user_id;
                        ?>
                            <div class="message <?= $isMine ? 'sent' : 'received' ?>" id="msg-<?= $msg['id'] ?>">
                                <div class="message-bubble">
                                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                    <?php if (!empty($msg['file_name'])): ?>
                                        <div class="message-file">
                                            <a href="<?= htmlspecialchars($msg['file_path']) ?>" target="_blank" class="text-decoration-none">
                                                <i class="fas fa-paperclip me-1"></i><?= htmlspecialchars($msg['file_name']) ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    <div class="d-flex align-items-center justify-content-end mt-1">
                                        <span class="message-time"><?= date('d.m.Y H:i', strtotime($msg['created_at'])) ?></span>
                                        <?php if ($isMine && $msg['is_read']): ?>
                                            <span class="read-status"><i class="fas fa-check-double ms-1"></i> прочитано</span>
                                        <?php elseif ($isMine): ?>
                                            <span class="read-status"><i class="fas fa-check ms-1"></i> доставлено</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <!-- Кнопки действий для своих сообщений -->
                                <?php if ($isMine): ?>
                                    <div class="message-actions">
                                        <a href="?contact_id=<?= $contact_id ?>&delete_message=<?= $msg['id'] ?>" 
                                           onclick="return confirm('Удалить это сообщение? Это действие необратимо для всех.')" 
                                           title="Удалить">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <button type="button" onclick="forwardMessage(<?= $msg['id'] ?>)" title="Переслать">
                                            <i class="fas fa-share"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="chat-input">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="receiver_id" value="<?= $contact_id ?>">
                            <div class="input-group mb-2">
                                <textarea name="message" class="form-control" rows="2" placeholder="Введите сообщение..."></textarea>
                                <button type="submit" name="send_message" class="btn btn-success">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                            <div class="input-group">
                                <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                                <span class="input-group-text"><i class="fas fa-paperclip"></i></span>
                            </div>
                        </form>
                    </div>
                    <script>
                        // Прокрутка вниз
                        document.getElementById('chatMessages').scrollTop = document.getElementById('chatMessages').scrollHeight;
                        
                        function forwardMessage(messageId) {
                            // Сохраняем ID сообщения в скрытом поле и открываем модалку выбора контакта
                            document.getElementById('forward_message_id').value = messageId;
                            new bootstrap.Modal(document.getElementById('forwardModal')).show();
                        }
                    </script>
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                        <div class="text-center">
                            <i class="fas fa-comment-dots fa-4x mb-3"></i>
                            <p>Выберите сотрудника из списка слева, чтобы начать диалог</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Модальное окно пересылки сообщения -->
    <div class="modal fade" id="forwardModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Переслать сообщение</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="message_id" id="forward_message_id" value="">
                    <div class="mb-3">
                        <label class="form-label">Выберите получателя</label>
                        <select name="receiver_id" class="form-select" required>
                            <option value="">-- Выберите сотрудника --</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['full_name'] ?: $user['login']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" name="forward_message" class="btn btn-primary">Переслать</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.mobile-toggle')?.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        function markAllNotificationsRead() {
            fetch('notifications.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'mark_all=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
    </script>
</body>
</html>