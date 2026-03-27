<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';
require 'audit.php'; // файл с функцией log_action()

// Массив перевода названий колонок на русский
$column_ru = [
    'id'            => 'ID',
    'name'          => 'Название',
    'type'          => 'Тип',
    'address'       => 'Адрес',
    'status'        => 'Статус',
    'responsible'   => 'Ответственный',
    'created_at'    => 'Дата создания',
    'login'         => 'Логин',
    'password'      => 'Пароль',
    'full_name'     => 'Полное имя',
    'role'          => 'Роль',
    'object_id'     => 'Объект',
    'work_type'     => 'Тип работ',
    'planned_start' => 'План. начало',
    'planned_end'   => 'План. окончание',
    'description'   => 'Описание',
    'plan_id'       => 'План работ',
    'date_performed'=> 'Дата выполнения',
    'result'        => 'Результат',
    'file_name'     => 'Файл',
    'file_path'     => 'Путь к файлу',
    'overdue_reason'=> 'Причина просрочки',
    'object_name'   => 'Объект',
    'plan_work_type'=> 'Тип работ (план)',
];

// Подсчёт непрочитанных сообщений для текущего пользователя
$unread_count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $unread_count = $stmt->fetchColumn();
}

// Выход
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    if (isset($_SESSION['user_id'])) {
        log_action('LOGOUT', 'users', $_SESSION['user_id']);
    }
    session_destroy();
    header("Location: index.php");
    exit;
}

// Обработка входа
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_login'])) {
    $login = trim($_POST['login']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    if ($user && $user['password'] === $password) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'] ?? $user['login'];
        $_SESSION['role'] = $user['role'] ?? 'исполнитель';
        $_SESSION['login'] = $user['login'];
        log_action('LOGIN', 'users', $user['id']);
        header("Location: dashboard.php");
        exit;
    }
    $login_error = "Неверный логин или пароль";
}

// Если не авторизован – показываем форму входа
if (!isset($_SESSION['user_id'])) {
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Вход в систему планирования работ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .password-toggle {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        .password-toggle:hover {
            color: #2ecc71;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container fade-in">
        <div class="login-card">
            <div class="login-header">
                <h1>🏙️ Система планирования благоустройства</h1>
                <p>г. Белово, Кемеровская область</p>
            </div>
            <?php if($login_error): ?>
                <div class="alert alert-danger"><?= $login_error ?></div>
            <?php endif; ?>
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label>Логин</label>
                    <input type="text" name="login" class="form-control" required>
                </div>
                <div class="form-group position-relative">
                    <label>Пароль</label>
                    <input type="password" name="password" class="form-control" id="passwordInput" required>
                    <span class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="togglePasswordIcon"></i>
                    </span>
                </div>
                <button type="submit" name="do_login" class="login-btn">Войти</button>
            </form>
            <!-- Ссылка "Забыли пароль?" -->
            <div class="text-center mt-3">
                <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal" class="text-muted">Забыли пароль?</a>
            </div>
            <div class="login-footer">© <?= date('Y') ?> "Управление" УЖКиДК УБГО</div>
        </div>
    </div>

    <!-- Модальное окно запроса сброса пароля -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Восстановление доступа</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Администратор примет ваш запрос и лично выдаст вам новый пароль. Ожидайте.</p>
                    <div class="mb-3">
                        <label for="forgotLogin" class="form-label">Ваш логин</label>
                        <input type="text" class="form-control" id="forgotLogin" placeholder="Введите логин">
                    </div>
                    <div id="forgotResult" class="alert d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    <button type="button" class="btn btn-primary" id="sendForgotRequest">Отправить запрос</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('passwordInput');
            const icon = document.getElementById('togglePasswordIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        document.getElementById('sendForgotRequest').addEventListener('click', function() {
            const login = document.getElementById('forgotLogin').value.trim();
            if (!login) {
                alert('Введите логин');
                return;
            }
            fetch('forgot_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'login=' + encodeURIComponent(login)
            })
            .then(response => response.json())
            .then(data => {
                const resultDiv = document.getElementById('forgotResult');
                resultDiv.classList.remove('d-none', 'alert-success', 'alert-danger');
                if (data.success) {
                    resultDiv.classList.add('alert-success');
                    resultDiv.textContent = 'Запрос отправлен администратору. Ожидайте.';
                    document.getElementById('forgotLogin').value = '';
                } else {
                    resultDiv.classList.add('alert-danger');
                    resultDiv.textContent = data.error || 'Ошибка при отправке запроса.';
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    </script>
</body>
</html>
<?php
    exit;
}

// --- Автоматическое обновление статуса просрочки ---
try {
    $pdo->exec("UPDATE work_plans SET status = 'просрочено' WHERE planned_end < CURDATE() AND status NOT IN ('выполнено', 'отменено')");
} catch (PDOException $e) {
    // игнорируем
}

// --- Ролевой доступ ---
$user_role = $_SESSION['role'] ?? 'исполнитель';

$all_tables = [
    'objects'        => 'Объекты благоустройства',
    'work_plans'     => 'Планы работ',
    'work_executions'=> 'Выполнение работ',
    'users'          => 'Пользователи'
];

if ($user_role == 'администратор') {
    $tables = $all_tables;
} elseif ($user_role == 'планировщик') {
    $tables = [
        'work_plans'     => 'Планы работ',
        'work_executions'=> 'Выполнение работ'
    ];
} else { // исполнитель
    $tables = [
        'objects'        => 'Объекты благоустройства',
        'work_plans'     => 'Планы работ',
        'work_executions'=> 'Выполнение работ'
    ];
}

$current_table = isset($_GET['table']) && array_key_exists($_GET['table'], $tables) ? $_GET['table'] : array_key_first($tables);

// --- Сортировка ---
$allowed_sorts = [
    'objects' => ['name_asc', 'name_desc', 'created_at_desc', 'created_at_asc', 'status'],
    'work_plans' => ['object_name_asc', 'object_name_desc', 'planned_start_desc', 'planned_start_asc', 'status'],
    'work_executions' => ['date_performed_desc', 'date_performed_asc', 'object_name_asc', 'object_name_desc'],
    'users' => ['full_name_asc', 'full_name_desc', 'login_asc', 'login_desc', 'role']
];
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sorts[$current_table] ?? []) ? $_GET['sort'] : '';

// --- Поиск ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Проверка прав на запись/удаление
function can_write($table, $role) {
    if ($role == 'администратор') return true;
    if ($role == 'планировщик' && in_array($table, ['work_plans', 'work_executions'])) return true;
    if ($role == 'исполнитель' && in_array($table, ['work_executions', 'objects'])) return true;
    return false;
}
function can_delete($table, $role) {
    if ($role == 'администратор') return true;
    if ($role == 'планировщик' && in_array($table, ['work_plans', 'work_executions'])) return true;
    if ($role == 'исполнитель' && in_array($table, ['work_executions', 'objects'])) return true;
    return false;
}

// --- Сохранение причины просрочки ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_overdue_reason'])) {
    $plan_id = (int)$_POST['plan_id'];
    $reason = trim($_POST['overdue_reason']);
    $other_text = trim($_POST['other_reason'] ?? '');
    
    if ($reason === 'other' && !empty($other_text)) {
        $reason = $other_text;
    }
    
    if ($plan_id && !empty($reason)) {
        try {
            $stmt = $pdo->prepare("UPDATE work_plans SET overdue_reason = ? WHERE id = ?");
            $stmt->execute([$reason, $plan_id]);
            log_action('UPDATE_REASON', 'work_plans', $plan_id, null, ['overdue_reason' => $reason]);
        } catch (PDOException $e) {
            log_action('ERROR', null, null, ['message' => $e->getMessage()], null);
        }
    }
    header("Location: index.php?table=work_plans");
    exit;
}

// Удаление записи
if (isset($_GET['delete_id'])) {
    if (!can_delete($current_table, $user_role)) {
        die('Нет прав на удаление');
    }
    $id = $_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM $current_table WHERE id = ?");
        $stmt->execute([$id]);
        $old_row = $stmt->fetch();
        
        if ($old_row && !empty($old_row['file_path']) && file_exists($old_row['file_path'])) {
            unlink($old_row['file_path']);
        }
        
        $stmt = $pdo->prepare("DELETE FROM $current_table WHERE id = ?");
        $stmt->execute([$id]);
        
        log_action('DELETE', $current_table, $id, $old_row, null);
        
        header("Location: index.php?table=$current_table&msg=deleted");
        exit;
    } catch (PDOException $e) {
        $error = "Ошибка удаления: " . $e->getMessage();
        log_action('ERROR', $current_table, $id, ['error' => $e->getMessage()], null);
    }
}

// Сохранение (добавление/редактирование)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_record'])) {
    if (!can_write($current_table, $user_role)) {
        die('Нет прав на запись');
    }

    $id = $_POST['id'] ?? null;
    $columns = $_POST['columns'] ?? [];
    $delete_attachment = isset($_POST['delete_attachment']);
    $uploaded_file = $_FILES['attachment'] ?? null;

    $old_row = null;
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM $current_table WHERE id = ?");
        $stmt->execute([$id]);
        $old_row = $stmt->fetch();
    }

    if ($uploaded_file && $uploaded_file['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $original_name = basename($uploaded_file['name']);
        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $new_filename = uniqid() . '_' . time() . '.' . $extension;
        $destination = $upload_dir . $new_filename;
        if (move_uploaded_file($uploaded_file['tmp_name'], $destination)) {
            if ($old_row && !empty($old_row['file_path']) && file_exists($old_row['file_path'])) {
                unlink($old_row['file_path']);
            }
            $columns['file_name'] = $original_name;
            $columns['file_path'] = $destination;
        }
    } elseif ($delete_attachment && $old_row && !empty($old_row['file_path'])) {
        if (file_exists($old_row['file_path'])) {
            unlink($old_row['file_path']);
        }
        $columns['file_name'] = null;
        $columns['file_path'] = null;
    }

    $values = [];
    $fields = [];
    $placeholders = [];
    foreach ($columns as $col => $val) {
        if (in_array($col, ['object_id', 'plan_id']) && $val === '') {
            continue;
        }
        $fields[] = "$col = ?";
        $placeholders[] = "?";
        $values[] = $val;
    }

    try {
        if (!empty($id)) {
            $sql = "UPDATE $current_table SET " . implode(', ', $fields) . " WHERE id = ?";
            $values[] = $id;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            log_action('UPDATE', $current_table, $id, $old_row, $columns);
        } else {
            $cols_str = implode(', ', array_keys($columns));
            $vals_str = implode(', ', $placeholders);
            $sql = "INSERT INTO $current_table ($cols_str) VALUES ($vals_str)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            $new_id = $pdo->lastInsertId();
            log_action('CREATE', $current_table, $new_id, null, $columns);

            // Создание уведомлений для новых записей
            $notification_type = '';
            $notification_message = '';
            $link = "index.php?table=$current_table&edit_id=$new_id";
            if ($current_table == 'objects') {
                $notification_type = 'new_object';
                $notification_message = 'Добавлен новый объект: ' . ($columns['name'] ?? '');
            } elseif ($current_table == 'work_plans') {
                $notification_type = 'new_plan';
                $notification_message = 'Новый план работ: ' . ($columns['work_type'] ?? '');
            } elseif ($current_table == 'work_executions') {
                $notification_type = 'new_execution';
                $notification_message = 'Новая запись о выполнении работ';
            }
            if ($notification_type) {
                $all_users = $pdo->query("SELECT id FROM users WHERE id != " . $_SESSION['user_id'])->fetchAll(PDO::FETCH_COLUMN);
                foreach ($all_users as $uid) {
                    create_notification($uid, $notification_type, $notification_message, $link);
                }
            }
        }
        header("Location: index.php?table=$current_table&msg=saved");
        exit;
    } catch (PDOException $e) {
        $error = "Ошибка сохранения: " . $e->getMessage();
        log_action('ERROR', $current_table, $id ?? null, ['error' => $e->getMessage()], $columns);
    }
}

// --- Построение запроса с учётом поиска и сортировки ---
$where = '';
$params = [];

if (!empty($search)) {
    $searchTerm = "%$search%";
    switch ($current_table) {
        case 'objects':
            $conditions = [
                "name LIKE ?",
                "type LIKE ?",
                "address LIKE ?",
                "responsible LIKE ?"
            ];
            $where = "WHERE " . implode(' OR ', $conditions);
            $params = array_fill(0, count($conditions), $searchTerm);
            break;

        case 'work_plans':
            // Используем алиасы: p.*, o.name as object_name
            $conditions = [
                "p.work_type LIKE ?",
                "p.description LIKE ?",
                "p.responsible LIKE ?",
                "o.name LIKE ?"
            ];
            $where = "WHERE " . implode(' OR ', $conditions);
            $params = array_fill(0, count($conditions), $searchTerm);
            break;

        case 'work_executions':
            $conditions = [
                "e.work_type LIKE ?",
                "e.description LIKE ?",
                "e.result LIKE ?",
                "e.responsible LIKE ?",
                "o.name LIKE ?",
                "p.work_type LIKE ?"
            ];
            $where = "WHERE " . implode(' OR ', $conditions);
            $params = array_fill(0, count($conditions), $searchTerm);
            break;

        case 'users':
            $conditions = [
                "login LIKE ?",
                "full_name LIKE ?",
                "role LIKE ?"
            ];
            $where = "WHERE " . implode(' OR ', $conditions);
            $params = array_fill(0, count($conditions), $searchTerm);
            break;
    }
}

// Определяем ORDER BY
$order_by = '';
if ($current_table == 'objects') {
    if ($sort == 'name_asc') $order_by = 'ORDER BY name ASC';
    elseif ($sort == 'name_desc') $order_by = 'ORDER BY name DESC';
    elseif ($sort == 'created_at_desc') $order_by = 'ORDER BY created_at DESC';
    elseif ($sort == 'created_at_asc') $order_by = 'ORDER BY created_at ASC';
    elseif ($sort == 'status') $order_by = 'ORDER BY status';
} elseif ($current_table == 'work_plans') {
    if ($sort == 'object_name_asc') $order_by = 'ORDER BY object_name ASC';
    elseif ($sort == 'object_name_desc') $order_by = 'ORDER BY object_name DESC';
    elseif ($sort == 'planned_start_desc') $order_by = 'ORDER BY planned_start DESC';
    elseif ($sort == 'planned_start_asc') $order_by = 'ORDER BY planned_start ASC';
    elseif ($sort == 'status') $order_by = 'ORDER BY status';
} elseif ($current_table == 'work_executions') {
    if ($sort == 'date_performed_desc') $order_by = 'ORDER BY date_performed DESC';
    elseif ($sort == 'date_performed_asc') $order_by = 'ORDER BY date_performed ASC';
    elseif ($sort == 'object_name_asc') $order_by = 'ORDER BY object_name ASC';
    elseif ($sort == 'object_name_desc') $order_by = 'ORDER BY object_name DESC';
} elseif ($current_table == 'users') {
    if ($sort == 'full_name_asc') $order_by = 'ORDER BY full_name ASC';
    elseif ($sort == 'full_name_desc') $order_by = 'ORDER BY full_name DESC';
    elseif ($sort == 'login_asc') $order_by = 'ORDER BY login ASC';
    elseif ($sort == 'login_desc') $order_by = 'ORDER BY login DESC';
    elseif ($sort == 'role') $order_by = 'ORDER BY role';
}

// Выполнение запроса
try {
    switch ($current_table) {
        case 'work_plans':
            $sql = "SELECT p.*, o.name as object_name 
                    FROM work_plans p 
                    LEFT JOIN objects o ON p.object_id = o.id 
                    $where 
                    $order_by";
            break;
        case 'work_executions':
            $sql = "SELECT e.*, o.name as object_name, p.work_type as plan_work_type
                    FROM work_executions e 
                    LEFT JOIN objects o ON e.object_id = o.id 
                    LEFT JOIN work_plans p ON e.plan_id = p.id 
                    $where 
                    $order_by";
            break;
        default:
            $sql = "SELECT * FROM $current_table $where $order_by";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Ошибка загрузки данных: " . $e->getMessage();
    log_action('ERROR', $current_table, null, ['error' => $e->getMessage()], null);
    $rows = [];
}

// Списки для выпадающих полей
$objects_list = [];
if (in_array($current_table, ['work_plans', 'work_executions'])) {
    $stmt = $pdo->query("SELECT id, name, address FROM objects ORDER BY name");
    $objects_list = $stmt->fetchAll();
}
$plans_list = [];
if ($current_table == 'work_executions') {
    $stmt = $pdo->query("SELECT id, object_id, work_type, planned_start FROM work_plans ORDER BY planned_start DESC");
    $plans_list = $stmt->fetchAll();
}

if (count($rows) > 0) {
    $columns_info = array_keys($rows[0]);
} else {
    try {
        $q = $pdo->prepare("DESCRIBE $current_table");
        $q->execute();
        $columns_info = $q->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $error = "Ошибка получения структуры таблицы: " . $e->getMessage();
        log_action('ERROR', $current_table, null, ['error' => $e->getMessage()], null);
        $columns_info = [];
    }
}

// Базовый список отображаемых колонок (исключаем служебные)
$display_columns = array_filter($columns_info, fn($col) => !in_array($col, ['file_name', 'file_path', 'overdue_reason']));

// Убираем дублирующиеся поля-ссылки для связанных таблиц
if ($current_table == 'work_plans') {
    $display_columns = array_filter($display_columns, fn($col) => !in_array($col, ['object_id']));
}
if ($current_table == 'work_executions') {
    $display_columns = array_filter($display_columns, fn($col) => !in_array($col, ['object_id', 'plan_id', 'work_type']));
}

$edit_row = null;
if (isset($_GET['edit_id']) && can_write($current_table, $user_role)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM $current_table WHERE id = ?");
        $stmt->execute([$_GET['edit_id']]);
        $edit_row = $stmt->fetch();
    } catch (PDOException $e) {
        $error = "Ошибка загрузки записи: " . $e->getMessage();
        log_action('ERROR', $current_table, $_GET['edit_id'], ['error' => $e->getMessage()], null);
    }
}

$overdue_plans = [];
try {
    $stmt = $pdo->query("
        SELECT p.*, o.name as object_name 
        FROM work_plans p
        LEFT JOIN objects o ON p.object_id = o.id
        WHERE p.planned_end < CURDATE() AND p.status NOT IN ('выполнено', 'отменено')
        ORDER BY p.planned_end ASC
    ");
    $overdue_plans = $stmt->fetchAll();
} catch (PDOException $e) {}

$user_display_name = $_SESSION['full_name'] ?? $_SESSION['login'] ?? 'Пользователь';

// Получаем данные для уведомлений (функции из notifications.php)
$unread_notifications = function_exists('get_unread_notifications_count') ? get_unread_notifications_count($_SESSION['user_id']) : 0;
$recent_notifications = function_exists('get_recent_notifications') ? get_recent_notifications($_SESSION['user_id'], 5) : [];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Система планирования благоустройства</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-panel">
    <button class="mobile-toggle d-lg-none">
        <i class="fas fa-bars"></i>
    </button>

    <nav class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo"><i class="fas fa-tree"></i></div>
            <div class="sidebar-title">BlagoBelovo</div>
        </div>
        <ul class="sidebar-nav">
            <?php foreach ($tables as $tbl => $name): ?>
            <li class="nav-item">
                <a href="?table=<?= $tbl ?>" class="nav-link <?= $current_table == $tbl ? 'active' : '' ?>">
                    <i class="nav-icon fas fa-<?= 
                        $tbl == 'objects' ? 'city' : 
                        ($tbl == 'work_plans' ? 'calendar-alt' : 
                        ($tbl == 'work_executions' ? 'clipboard-check' : 
                        ($tbl == 'users' ? 'users' : 'table'))) ?>"></i>
                    <span><?= $name ?></span>
                </a>
            </li>
            <?php endforeach; ?>
            <li class="nav-item">
                <a href="reports.php" class="nav-link">
                    <i class="nav-icon fas fa-chart-bar"></i><span>Отчеты</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link">
                    <i class="nav-icon fas fa-tachometer-alt"></i><span>Дашборд</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="chat.php" class="nav-link">
                    <i class="nav-icon fas fa-comments"></i>
                    <span>Сообщения</span>
                    <?php if ($unread_count > 0): ?>
                        <span class="badge bg-danger ms-2"><?= $unread_count ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php if ($_SESSION['role'] == 'администратор'): ?>
                <li class="nav-item">
                    <a href="backup.php" class="nav-link">
                        <i class="nav-icon fas fa-database"></i><span>Бэкап</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="audit.php" class="nav-link">
                        <i class="nav-icon fas fa-history"></i><span>Журнал аудита</span>
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
            <a href="?action=logout" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i><span>Выйти</span>
            </a>
        </div>
    </nav>

    <main class="main-content">
        <div class="content-header fade-in">
            <div class="header-title">
                <h1><?= $tables[$current_table] ?></h1>
                <p>Управление данными по благоустройству г. Белово</p>
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

                <?php 
                $show_add_button = (
                    $user_role == 'администратор' ||
                    ($user_role == 'планировщик' && in_array($current_table, ['work_plans', 'work_executions'])) ||
                    ($user_role == 'исполнитель' && in_array($current_table, ['work_executions', 'objects']))
                );
                if ($show_add_button): 
                ?>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#editModal" onclick="clearForm()">
                    <i class="fas fa-plus"></i> Добавить запись
                </button>
                <?php endif; ?>
                <?php if(count($overdue_plans) > 0): ?>
                <button class="btn btn-danger ms-2" data-bs-toggle="modal" data-bs-target="#overdueModal">
                    <i class="fas fa-exclamation-triangle"></i> Просрочено: <?= count($overdue_plans) ?>
                </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger fade-in"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if(isset($_GET['msg'])): ?>
            <?php if($_GET['msg'] == 'saved'): ?>
                <div class="alert alert-success fade-in">✅ Данные сохранены!</div>
            <?php elseif($_GET['msg'] == 'deleted'): ?>
                <div class="alert alert-warning fade-in">🗑️ Запись удалена!</div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Статистика для объектов -->
        <?php if($current_table == 'objects'): ?>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Всего объектов</h5>
                        <p class="card-text display-6"><?= count($rows) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Активны</h5>
                        <?php 
                        $active = array_filter($rows, fn($item) => $item['status'] == 'активен');
                        ?>
                        <p class="card-text display-6"><?= count($active) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">На реконструкции</h5>
                        <?php 
                        $reconstr = array_filter($rows, fn($item) => $item['status'] == 'на реконструкции');
                        ?>
                        <p class="card-text display-6"><?= count($reconstr) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h5 class="card-title">Просроченные планы</h5>
                        <p class="card-text display-6"><?= count($overdue_plans) ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Таблица данных -->
        <div class="table-container fade-in">
            <div class="table-header">
                <div class="table-title">Записи таблицы</div>
                <div class="table-count d-flex align-items-center">
                    <!-- Форма поиска -->
                    <form method="GET" class="d-flex align-items-center me-3">
                        <input type="hidden" name="table" value="<?= $current_table ?>">
                        <?php if (!empty($sort)): ?>
                            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                        <?php endif; ?>
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <input type="text" name="search" class="form-control" placeholder="Поиск..." value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                            <?php if (!empty($search)): ?>
                                <a href="?table=<?= $current_table ?><?= !empty($sort) ? '&sort='.urlencode($sort) : '' ?>" class="btn btn-outline-danger" title="Сбросить поиск"><i class="fas fa-times"></i></a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <!-- Выпадающий список сортировки -->
                    <div class="me-3">
                        <label class="me-2">Сортировка:</label>
                        <select name="sort" class="form-select form-select-sm w-auto" onchange="window.location.href='?table=<?= $current_table ?>&sort='+this.value<?= !empty($search) ? "+'&search='+encodeURIComponent('".htmlspecialchars($search)."')" : '' ?>">
                            <option value="">По умолчанию</option>
                            <?php if ($current_table == 'objects'): ?>
                                <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>По названию (А-Я)</option>
                                <option value="name_desc" <?= $sort == 'name_desc' ? 'selected' : '' ?>>По названию (Я-А)</option>
                                <option value="created_at_desc" <?= $sort == 'created_at_desc' ? 'selected' : '' ?>>Сначала новые</option>
                                <option value="created_at_asc" <?= $sort == 'created_at_asc' ? 'selected' : '' ?>>Сначала старые</option>
                                <option value="status" <?= $sort == 'status' ? 'selected' : '' ?>>По статусу</option>
                            <?php elseif ($current_table == 'work_plans'): ?>
                                <option value="object_name_asc" <?= $sort == 'object_name_asc' ? 'selected' : '' ?>>По объекту (А-Я)</option>
                                <option value="object_name_desc" <?= $sort == 'object_name_desc' ? 'selected' : '' ?>>По объекту (Я-А)</option>
                                <option value="planned_start_desc" <?= $sort == 'planned_start_desc' ? 'selected' : '' ?>>По дате начала (сначала новые)</option>
                                <option value="planned_start_asc" <?= $sort == 'planned_start_asc' ? 'selected' : '' ?>>По дате начала (сначала старые)</option>
                                <option value="status" <?= $sort == 'status' ? 'selected' : '' ?>>По статусу</option>
                            <?php elseif ($current_table == 'work_executions'): ?>
                                <option value="date_performed_desc" <?= $sort == 'date_performed_desc' ? 'selected' : '' ?>>По дате (сначала новые)</option>
                                <option value="date_performed_asc" <?= $sort == 'date_performed_asc' ? 'selected' : '' ?>>По дате (сначала старые)</option>
                                <option value="object_name_asc" <?= $sort == 'object_name_asc' ? 'selected' : '' ?>>По объекту (А-Я)</option>
                                <option value="object_name_desc" <?= $sort == 'object_name_desc' ? 'selected' : '' ?>>По объекту (Я-А)</option>
                            <?php elseif ($current_table == 'users'): ?>
                                <option value="full_name_asc" <?= $sort == 'full_name_asc' ? 'selected' : '' ?>>По имени (А-Я)</option>
                                <option value="full_name_desc" <?= $sort == 'full_name_desc' ? 'selected' : '' ?>>По имени (Я-А)</option>
                                <option value="login_asc" <?= $sort == 'login_asc' ? 'selected' : '' ?>>По логину (А-Я)</option>
                                <option value="login_desc" <?= $sort == 'login_desc' ? 'selected' : '' ?>>По логину (Я-А)</option>
                                <option value="role" <?= $sort == 'role' ? 'selected' : '' ?>>По роли</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <span class="badge bg-primary"><?= count($rows) ?> записей</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <?php foreach ($display_columns as $col): ?>
                                <th><?= htmlspecialchars($column_ru[$col] ?? $col) ?></th>
                            <?php endforeach; ?>
                            
                            <?php if ($current_table == 'work_plans'): ?>
                                <th><?= htmlspecialchars($column_ru['overdue_reason'] ?? 'Причина просрочки') ?></th>
                            <?php endif; ?>
                            
                            <?php if (in_array($current_table, ['work_plans', 'work_executions'])): ?>
                                <th>Файл</th>
                            <?php endif; ?>
                            
                            <?php 
                            $show_actions = ($current_table != 'users') && (
                                $user_role == 'администратор' || 
                                ($user_role == 'планировщик' && in_array($current_table, ['work_plans', 'work_executions'])) ||
                                ($user_role == 'исполнитель' && in_array($current_table, ['work_executions', 'objects']))
                            );
                            if ($show_actions): 
                            ?>
                                <th>Действия</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($rows) > 0): ?>
                            <?php foreach ($rows as $row): ?>
                            <tr>
                                <?php foreach ($display_columns as $col): ?>
                                    <td class="table-cell">
                                        <?php if($col == 'object_id' && in_array($current_table, ['work_plans', 'work_executions'])): ?>
                                            <?= htmlspecialchars($row['object_name'] ?? 'Неизвестно') ?>
                                        <?php elseif($col == 'plan_id' && $current_table == 'work_executions'): ?>
                                            <?php if(!empty($row['plan_id'])): ?>
                                                <a href="?table=work_plans&edit_id=<?= $row['plan_id'] ?>">План #<?= $row['plan_id'] ?></a>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        <?php elseif($col == 'status'): ?>
                                            <span class="badge bg-<?= 
                                                $row[$col] == 'активен' || $row[$col] == 'выполнено' ? 'success' : 
                                                ($row[$col] == 'просрочено' ? 'danger' : 
                                                ($row[$col] == 'в работе' ? 'warning' : 'secondary')) ?>">
                                                <?= htmlspecialchars($row[$col]) ?>
                                            </span>
                                        <?php elseif($col == 'result'): ?>
                                            <span class="badge bg-<?= $row[$col] == 'выполнено' ? 'success' : 'danger' ?>">
                                                <?= htmlspecialchars($row[$col]) ?>
                                            </span>
                                        <?php elseif(in_array($col, ['planned_start', 'planned_end', 'date_performed', 'created_at']) && !empty($row[$col])): ?>
                                            <?php 
                                            $date = new DateTime($row[$col]);
                                            $now = new DateTime();
                                            $interval = $now->diff($date);
                                            $days = (int)$interval->format('%R%a');
                                            $class = '';
                                            if ($col == 'planned_end' && $row['status'] != 'выполнено') {
                                                if ($days < 0) $class = 'bg-danger text-white';
                                                elseif ($days <= 7) $class = 'bg-warning';
                                            }
                                            ?>
                                            <span class="badge <?= $class ?>">
                                                <?= htmlspecialchars($row[$col]) ?>
                                                <?php if($col == 'planned_end' && $row['status'] != 'выполнено'): ?>
                                                    <br><small><?= $days > 0 ? "через $days дн." : "просрочено " . abs($days) . " дн." ?></small>
                                                <?php endif; ?>
                                            </span>
                                        <?php elseif($col == 'password'): ?>
                                            ••••••••
                                        <?php elseif($col == 'object_name' || $col == 'plan_work_type'): ?>
                                            <?= htmlspecialchars($row[$col] ?? '') ?>
                                        <?php else: ?>
                                            <?= htmlspecialchars($row[$col] ?? '') ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>

                                <!-- Колонка причины для work_plans -->
                                <?php if ($current_table == 'work_plans'): ?>
                                    <td>
                                        <?php
                                        $reason = $row['overdue_reason'] ?? '';
                                        $is_overdue = (
                                            isset($row['planned_end']) && 
                                            $row['planned_end'] < date('Y-m-d') && 
                                            !in_array($row['status'] ?? '', ['выполнено', 'отменено'])
                                        );
                                        $can_edit = ($user_role == 'исполнитель' && $is_overdue);

                                        if ($can_edit):
                                            $safe_reason = htmlspecialchars($reason, ENT_QUOTES);
                                            if (empty($reason)):
                                        ?>
                                                <button class="btn btn-sm btn-warning" onclick="openReasonModal(<?= $row['id'] ?>, '')">Указать причину</button>
                                            <?php else: ?>
                                                <?= htmlspecialchars($reason) ?>
                                                <button class="btn btn-sm btn-outline-warning" onclick="openReasonModal(<?= $row['id'] ?>, '<?= $safe_reason ?>')" title="Изменить">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?= htmlspecialchars($reason ?: '—') ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>

                                <!-- Колонка файла для work_plans / work_executions -->
                                <?php if (in_array($current_table, ['work_plans', 'work_executions'])): ?>
                                    <td>
                                        <?php if (!empty($row['file_path']) && file_exists($row['file_path'])): ?>
                                            <a href="<?= htmlspecialchars($row['file_path']) ?>" target="_blank" title="Открыть в браузере" class="me-2">
                                                <i class="fas fa-eye text-primary"></i>
                                            </a>
                                            <a href="<?= htmlspecialchars($row['file_path']) ?>" download="<?= htmlspecialchars($row['file_name']) ?>" title="Скачать файл">
                                                <i class="fas fa-download text-success"></i>
                                            </a>
                                            <span class="ms-2 small"><?= htmlspecialchars($row['file_name']) ?></span>
                                        <?php elseif (!empty($row['file_name'])): ?>
                                            <span class="text-muted" title="Файл отсутствует на сервере">
                                                <i class="fas fa-exclamation-triangle text-warning me-1"></i><?= htmlspecialchars($row['file_name']) ?> (не найден)
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>

                                <!-- Действия -->
                                <?php if ($show_actions): ?>
                                    <td class="action-cell">
                                        <div class="action-buttons">
                                            <a href="?table=<?= $current_table ?>&edit_id=<?= $row['id'] ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?><?= !empty($sort) ? '&sort='.urlencode($sort) : '' ?>" 
                                               class="btn-icon btn-primary" title="Редактировать">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?table=<?= $current_table ?>&delete_id=<?= $row['id'] ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?><?= !empty($sort) ? '&sort='.urlencode($sort) : '' ?>" 
                                               class="btn-icon btn-danger" 
                                               onclick="return confirm('Удалить запись?')" title="Удалить">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="100%" class="no-data text-center py-5">
                                    <i class="fas fa-database fa-3x mb-3 text-muted"></i>
                                    <p class="text-muted">Нет записей</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Модальное окно редактирования -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form method="POST" class="modal-content" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title"><?= isset($edit_row) ? 'Редактирование' : 'Добавление' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $edit_row['id'] ?? '' ?>">
                    <input type="hidden" name="save_record" value="1">
                    <div class="row">
                        <?php 
                        $form_columns = array_filter($columns_info, fn($col) => !in_array($col, ['id', 'object_name', 'plan_work_type', 'file_name', 'file_path', 'overdue_reason']));
                        foreach ($form_columns as $col): 
                            $val = $edit_row[$col] ?? '';
                        ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $column_ru[$col] ?? ucfirst(str_replace('_', ' ', $col)) ?></label>

                            <?php if($col == 'object_id' && in_array($current_table, ['work_plans', 'work_executions'])): ?>
                                <select name="columns[<?= $col ?>]" class="form-select" <?= $col=='object_id' ? 'required' : '' ?>>
                                    <option value="">-- Выберите объект --</option>
                                    <?php foreach ($objects_list as $obj): ?>
                                    <option value="<?= $obj['id'] ?>" <?= $val == $obj['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($obj['name'] . ' (' . $obj['address'] . ')') ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>

                            <?php elseif($col == 'plan_id' && $current_table == 'work_executions'): ?>
                                <select name="columns[<?= $col ?>]" class="form-select">
                                    <option value="">-- Без привязки к плану --</option>
                                    <?php foreach ($plans_list as $plan): ?>
                                    <option value="<?= $plan['id'] ?>" <?= $val == $plan['id'] ? 'selected' : '' ?>>
                                        План #<?= $plan['id'] ?>: <?= htmlspecialchars($plan['work_type']) ?> (нач. <?= $plan['planned_start'] ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>

                            <?php elseif($col == 'status' && $current_table == 'objects'): ?>
                                <select name="columns[<?= $col ?>]" class="form-select">
                                    <option value="активен" <?= $val=='активен'?'selected':'' ?>>Активен</option>
                                    <option value="на реконструкции" <?= $val=='на реконструкции'?'selected':'' ?>>На реконструкции</option>
                                    <option value="закрыт" <?= $val=='закрыт'?'selected':'' ?>>Закрыт</option>
                                </select>

                            <?php elseif($col == 'status' && $current_table == 'work_plans'): ?>
                                <select name="columns[<?= $col ?>]" class="form-select">
                                    <option value="запланировано" <?= $val=='запланировано'?'selected':'' ?>>Запланировано</option>
                                    <option value="в работе" <?= $val=='в работе'?'selected':'' ?>>В работе</option>
                                    <option value="выполнено" <?= $val=='выполнено'?'selected':'' ?>>Выполнено</option>
                                    <option value="просрочено" <?= $val=='просрочено'?'selected':'' ?>>Просрочено</option>
                                    <option value="отменено" <?= $val=='отменено'?'selected':'' ?>>Отменено</option>
                                </select>

                            <?php elseif($col == 'result' && $current_table == 'work_executions'): ?>
                                <select name="columns[<?= $col ?>]" class="form-select">
                                    <option value="выполнено" <?= $val=='выполнено'?'selected':'' ?>>Выполнено</option>
                                    <option value="частично" <?= $val=='частично'?'selected':'' ?>>Частично</option>
                                    <option value="не выполнено" <?= $val=='не выполнено'?'selected':'' ?>>Не выполнено</option>
                                </select>

                            <?php elseif(in_array($col, ['work_type'])): ?>
                                <input type="text" name="columns[<?= $col ?>]" value="<?= htmlspecialchars($val) ?>" class="form-control" list="workTypes">
                                <datalist id="workTypes">
                                    <option value="покос травы">
                                    <option value="уборка мусора">
                                    <option value="ремонт лавочек">
                                    <option value="обрезка деревьев">
                                    <option value="покраска">
                                    <option value="освещение">
                                </datalist>

                            <?php elseif(in_array($col, ['description'])): ?>
                                <textarea name="columns[<?= $col ?>]" class="form-control" rows="3"><?= htmlspecialchars($val) ?></textarea>

                            <?php elseif(in_array($col, ['planned_start', 'planned_end', 'date_performed', 'created_at'])): ?>
                                <input type="date" name="columns[<?= $col ?>]" value="<?= htmlspecialchars($val) ?>" class="form-control">

                            <?php elseif($col == 'password'): ?>
                                <input type="password" name="columns[<?= $col ?>]" class="form-control" placeholder="Введите пароль" <?= empty($edit_row) ? 'required' : '' ?>>
                                <?php if(!empty($edit_row)): ?><small class="text-muted">Оставьте пустым, чтобы не менять</small><?php endif; ?>

                            <?php else: ?>
                                <input type="text" name="columns[<?= $col ?>]" value="<?= htmlspecialchars($val) ?>" class="form-control">
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>

                        <!-- Поле для загрузки файла (только для work_plans и work_executions) -->
                        <?php if (in_array($current_table, ['work_plans', 'work_executions'])): ?>
                            <div class="col-12 mb-3">
                                <label class="form-label">Прикрепить файл</label>
                                <input type="file" name="attachment" class="form-control">
                                <?php if (!empty($edit_row['file_name'])): ?>
                                    <div class="mt-2">
                                        <span>Текущий файл: <a href="<?= htmlspecialchars($edit_row['file_path']) ?>" download="<?= htmlspecialchars($edit_row['file_name']) ?>"><?= htmlspecialchars($edit_row['file_name']) ?></a></span>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="delete_attachment" id="deleteAttachment">
                                            <label class="form-check-label" for="deleteAttachment">Удалить файл</label>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальное окно просроченных планов -->
    <?php if(count($overdue_plans) > 0): ?>
    <div class="modal fade" id="overdueModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">Просроченные планы работ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Обнаружены планы с истекшим сроком выполнения
                    </div>
                    <div class="list-group">
                        <?php foreach ($overdue_plans as $plan): ?>
                        <div class="list-group-item list-group-item-danger">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?= htmlspecialchars($plan['object_name']) ?></h6>
                                <small>ПРОСРОЧЕНО</small>
                            </div>
                            <p class="mb-1">
                                <?= htmlspecialchars($plan['work_type']) ?><br>
                                Срок: <?= htmlspecialchars($plan['planned_end']) ?>
                            </p>
                            <small>Ответственный: <?= htmlspecialchars($plan['responsible'] ?? '—') ?></small>
                            <?php if (!empty($plan['overdue_reason'])): ?>
                                <br><small>Причина: <?= htmlspecialchars($plan['overdue_reason']) ?></small>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    <a href="?table=work_plans" class="btn btn-primary">Перейти к планам</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Модальное окно для указания причины просрочки -->
    <div class="modal fade" id="reasonModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Укажите причину просрочки</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="plan_id" id="reasonPlanId">
                    <div class="mb-3">
                        <label class="form-label">Выберите причину:</label>
                        <select name="overdue_reason" class="form-select" id="reasonSelect" onchange="toggleOtherReason()">
                            <option value="Не успеваю по срокам">Не успеваю по срокам</option>
                            <option value="Были более важные планы">Были более важные планы</option>
                            <option value="Отсутствие материалов/техники">Отсутствие материалов/техники</option>
                            <option value="Погодные условия">Погодные условия</option>
                            <option value="other">Другая причина...</option>
                        </select>
                    </div>
                    <div class="mb-3" id="otherReasonBlock" style="display: none;">
                        <label class="form-label">Опишите причину:</label>
                        <textarea name="other_reason" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" name="save_overdue_reason" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.mobile-toggle')?.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        <?php if (isset($edit_row) && !empty($edit_row)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            new bootstrap.Modal(document.getElementById('editModal')).show();
        });
        <?php endif; ?>

        function clearForm() {
            document.querySelector('#editModal input[name="id"]').value = '';
            document.querySelectorAll('#editModal input, #editModal textarea, #editModal select').forEach(el => {
                if (el.type !== 'hidden' && el.name !== 'save_record' && el.name !== 'attachment' && el.type !== 'file') {
                    if (el.type === 'select-one') el.selectedIndex = 0;
                    else el.value = '';
                }
            });
            document.querySelector('#editModal input[type="file"]').value = '';
            const fileBlock = document.querySelector('#editModal .mt-2');
            if (fileBlock) fileBlock.style.display = 'none';
        }

        // Функции для модалки причины
        function openReasonModal(planId, currentReason) {
            document.getElementById('reasonPlanId').value = planId;
            var select = document.getElementById('reasonSelect');
            var otherBlock = document.getElementById('otherReasonBlock');
            var otherTextarea = document.querySelector('textarea[name="other_reason"]');
            
            select.value = 'Не успеваю по срокам';
            otherBlock.style.display = 'none';
            otherTextarea.value = '';
            
            if (currentReason) {
                var found = false;
                for (var i = 0; i < select.options.length; i++) {
                    if (select.options[i].value === currentReason) {
                        select.selectedIndex = i;
                        found = true;
                        break;
                    }
                }
                if (!found) {
                    select.value = 'other';
                    otherTextarea.value = currentReason;
                    otherBlock.style.display = 'block';
                }
            }
            
            new bootstrap.Modal(document.getElementById('reasonModal')).show();
        }

        function toggleOtherReason() {
            var select = document.getElementById('reasonSelect');
            var otherBlock = document.getElementById('otherReasonBlock');
            otherBlock.style.display = select.value === 'other' ? 'block' : 'none';
        }

        // Функция отметки всех уведомлений прочитанными
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