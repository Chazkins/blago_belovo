<?php
// audit.php - функция логирования и интерфейс просмотра логов для администратора

// Функция логирования (доступна при подключении файла)
function log_action($action, $table = null, $record_id = null, $old_data = null, $new_data = null) {
    global $pdo;
    static $pdo_checked = false;
    
    if (!$pdo_checked) {
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            $db_path = __DIR__ . '/db.php';
            if (file_exists($db_path)) {
                require_once $db_path;
            } else {
                return false;
            }
        }
        $pdo_checked = true;
    }

    $user_id = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $old_json = $old_data ? json_encode($old_data, JSON_UNESCAPED_UNICODE) : null;
    $new_json = $new_data ? json_encode($new_data, JSON_UNESCAPED_UNICODE) : null;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, old_data, new_data, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $table, $record_id, $old_json, $new_json, $ip, $ua]);
        return true;
    } catch (Exception $e) {
        error_log("Log action failed: " . $e->getMessage());
        return false;
    }
}

// Если файл вызван напрямую, показываем интерфейс администратора
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    session_start();
    require_once 'db.php';
    
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'администратор') {
        header("Location: index.php");
        exit;
    }

    // Очистка логов
    if (isset($_POST['clear_logs'])) {
        $pdo->exec("TRUNCATE TABLE audit_log");
        log_action('CLEAR_LOGS', 'audit_log');
        header("Location: audit.php?msg=cleared");
        exit;
    }

    // Экспорт в CSV
    if (isset($_GET['export'])) {
        $stmt = $pdo->query("SELECT * FROM audit_log ORDER BY created_at DESC");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="audit_log_'.date('Y-m-d_H-i-s').'.csv"');
        
        $output = fopen('php://output', 'w');
        if (!empty($logs)) {
            fputcsv($output, array_keys($logs[0]));
        } else {
            fputcsv($output, ['ID', 'User ID', 'Action', 'Table', 'Record ID', 'Old Data', 'New Data', 'IP', 'User Agent', 'Created At']);
        }
        foreach ($logs as $log) {
            if (isset($log['old_data']) && strlen($log['old_data']) > 32768) {
                $log['old_data'] = substr($log['old_data'], 0, 32768) . '... (truncated)';
            }
            if (isset($log['new_data']) && strlen($log['new_data']) > 32768) {
                $log['new_data'] = substr($log['new_data'], 0, 32768) . '... (truncated)';
            }
            fputcsv($output, $log);
        }
        fclose($output);
        exit;
    }

    // Получение списка пользователей для фильтра
    $users = $pdo->query("SELECT id, login, full_name FROM users ORDER BY full_name")->fetchAll();

    // Фильтры
    $filter_user = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    $filter_action = isset($_GET['action']) ? trim($_GET['action']) : '';
    $filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
    $filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

    // Построение запроса с фильтрами
    $sql = "SELECT * FROM audit_log WHERE 1=1";
    $params = [];
    $count_sql = "SELECT COUNT(*) FROM audit_log WHERE 1=1";

    if ($filter_user > 0) {
        $sql .= " AND user_id = ?";
        $count_sql .= " AND user_id = ?";
        $params[] = $filter_user;
    }
    if ($filter_action !== '') {
        $sql .= " AND action LIKE ?";
        $count_sql .= " AND action LIKE ?";
        $params[] = "%$filter_action%";
    }
    if ($filter_date_from !== '') {
        $sql .= " AND DATE(created_at) >= ?";
        $count_sql .= " AND DATE(created_at) >= ?";
        $params[] = $filter_date_from;
    }
    if ($filter_date_to !== '') {
        $sql .= " AND DATE(created_at) <= ?";
        $count_sql .= " AND DATE(created_at) <= ?";
        $params[] = $filter_date_to;
    }

    // Пагинация
    $per_page = 50;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $per_page;

    // Общее количество
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $per_page);

    // Финальный запрос
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

    $user_display_name = $_SESSION['full_name'] ?? $_SESSION['login'] ?? 'Администратор';

    // Данные для уведомлений
    $unread_notifications = get_unread_notifications_count($_SESSION['user_id']);
    $recent_notifications = get_recent_notifications($_SESSION['user_id'], 5);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Журнал аудита</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .filter-form { background: #f8f9fa; padding: 20px; border-radius: 12px; margin-bottom: 20px; }
        .table th, .table td { white-space: nowrap; }
        .table td.large-text { max-width: 300px; white-space: normal; word-wrap: break-word; }
        pre { margin: 0; background: #f5f5f5; padding: 5px; border-radius: 4px; }
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
            <li class="nav-item"><a href="chat.php" class="nav-link"><i class="nav-icon fas fa-comments"></i><span>Сообщения</span></a></li>
            <li class="nav-item"><a href="backup.php" class="nav-link"><i class="nav-icon fas fa-database"></i><span>Бэкап</span></a></li>
            <li class="nav-item"><a href="audit.php" class="nav-link active"><i class="nav-icon fas fa-history"></i><span>Журнал аудита</span></a></li>
        </ul>
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar"><?= mb_substr($user_display_name, 0, 1, 'UTF-8') ?></div>
                <div class="user-details">
                    <h4><?= htmlspecialchars($user_display_name) ?></h4>
                    <p>Администратор</p>
                </div>
            </div>
            <a href="index.php?action=logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Выйти</span></a>
        </div>
    </nav>

    <main class="main-content">
        <div class="content-header fade-in">
            <div class="header-title">
                <h1><i class="fas fa-history me-2"></i>Журнал аудита</h1>
                <p>Все действия пользователей в системе</p>
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
                <a href="?export=1" class="btn btn-success"><i class="fas fa-download"></i> Скачать CSV</a>
                <button class="btn btn-danger" onclick="if(confirm('Очистить весь журнал? Это действие необратимо.')) document.getElementById('clearForm').submit();">
                    <i class="fas fa-trash-alt"></i> Очистить журнал
                </button>
                <form id="clearForm" method="POST" style="display:none;">
                    <input type="hidden" name="clear_logs" value="1">
                </form>
            </div>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'cleared'): ?>
            <div class="alert alert-success">Журнал аудита очищен.</div>
        <?php endif; ?>

        <!-- Фильтры -->
        <div class="filter-form">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Пользователь</label>
                    <select name="user_id" class="form-select">
                        <option value="">Все пользователи</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $filter_user == $u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['full_name'] ?: $u['login']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Действие (содержит)</label>
                    <input type="text" name="action" class="form-control" value="<?= htmlspecialchars($filter_action) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Дата с</label>
                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($filter_date_from) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Дата по</label>
                    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($filter_date_to) ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Применить</button>
                </div>
            </form>
        </div>

        <!-- Таблица логов -->
        <div class="table-container">
            <div class="table-header">
                <div class="table-title">Записи аудита</div>
                <div class="table-count">
                    <span class="badge bg-primary"><?= $total_records ?> записей</span>
                    <?php if ($total_pages > 1): ?>
                        <span class="ms-2">Страница <?= $page ?> из <?= $total_pages ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Пользователь</th>
                            <th>Действие</th>
                            <th>Таблица</th>
                            <th>Запись ID</th>
                            <th>Старые данные</th>
                            <th>Новые данные</th>
                            <th>IP</th>
                            <th>User Agent</th>
                            <th>Дата</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($logs) > 0): ?>
                            <?php foreach ($logs as $log): 
                                $username = '—';
                                if ($log['user_id']) {
                                    $u_stmt = $pdo->prepare("SELECT full_name, login FROM users WHERE id = ?");
                                    $u_stmt->execute([$log['user_id']]);
                                    $u = $u_stmt->fetch();
                                    $username = $u ? ($u['full_name'] ?: $u['login']) : 'ID: '.$log['user_id'];
                                }
                            ?>
                            <tr>
                                <td><?= $log['id'] ?></td>
                                <td><?= htmlspecialchars($username) ?></td>
                                <td><?= htmlspecialchars($log['action']) ?></td>
                                <td><?= htmlspecialchars($log['table_name'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($log['record_id'] ?? '—') ?></td>
                                <td class="large-text">
                                    <?php if ($log['old_data']): ?>
                                        <pre><?= htmlspecialchars(substr($log['old_data'], 0, 100)) ?><?= strlen($log['old_data']) > 100 ? '...' : '' ?></pre>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td class="large-text">
                                    <?php if ($log['new_data']): ?>
                                        <pre><?= htmlspecialchars(substr($log['new_data'], 0, 100)) ?><?= strlen($log['new_data']) > 100 ? '...' : '' ?></pre>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($log['ip_address'] ?? '—') ?></td>
                                <td><span title="<?= htmlspecialchars($log['user_agent'] ?? '') ?>"><?= mb_substr(htmlspecialchars($log['user_agent'] ?? ''), 0, 30) ?>...</span></td>
                                <td><?= htmlspecialchars($log['created_at']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="10" class="text-center py-5 text-muted"><i class="fas fa-clipboard-list fa-2x mb-2"></i><p>Нет записей в журнале</p></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-center mt-3">
                <nav><ul class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): 
                        $url = '?' . http_build_query(array_merge($_GET, ['page' => $i]));
                    ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>"><a class="page-link" href="<?= htmlspecialchars($url) ?>"><?= $i ?></a></li>
                    <?php endfor; ?>
                </ul></nav>
            </div>
            <?php endif; ?>
        </div>
    </main>

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
<?php
}
?>