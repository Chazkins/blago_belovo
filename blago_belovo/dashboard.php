<?php
session_start();
require 'db.php';


$unread_count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $unread_count = $stmt->fetchColumn();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_role = $_SESSION['role'] ?? 'исполнитель';
$unread_notifications = get_unread_notifications_count($_SESSION['user_id']);
$recent_notifications = get_recent_notifications($_SESSION['user_id'], 5);

try {
    // Основная статистика
    $stats = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM objects) as total_objects,
            (SELECT COUNT(*) FROM work_plans WHERE status NOT IN ('выполнено','отменено') AND planned_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)) as upcoming_plans,
            (SELECT COUNT(*) FROM work_executions WHERE MONTH(date_performed) = MONTH(CURDATE())) as monthly_executions,
            (SELECT COUNT(*) FROM work_plans WHERE planned_end < CURDATE() AND status NOT IN ('выполнено','отменено')) as overdue_plans
    ")->fetch();

    // Последние добавленные объекты
    $recent_objects = $pdo->query("
        SELECT * FROM objects 
        ORDER BY id DESC 
        LIMIT 5
    ")->fetchAll();

    // Статусы объектов
    $status_stats = $pdo->query("
        SELECT status, COUNT(*) as count
        FROM objects
        GROUP BY status
    ")->fetchAll();

    // Активность по месяцам
    $monthly_activity = $pdo->query("
        SELECT 
            DATE_FORMAT(date_performed, '%Y-%m') as month,
            COUNT(*) as count
        FROM work_executions
        WHERE date_performed >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(date_performed, '%Y-%m')
        ORDER BY month
    ")->fetchAll();

    // Для формы добавления плана нужны списки объектов
    $objects_list = $pdo->query("SELECT id, name, address FROM objects ORDER BY name")->fetchAll();

    // Расширенный набор планов для календаря
    $all_plans = $pdo->query("
        SELECT p.*, o.name as object_name, o.address
        FROM work_plans p
        LEFT JOIN objects o ON p.object_id = o.id
        WHERE p.planned_start BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 MONTH) AND DATE_ADD(CURDATE(), INTERVAL 12 MONTH)
          AND p.status NOT IN ('выполнено','отменено')
        ORDER BY p.planned_start ASC
    ")->fetchAll();

    // Для виджета "Планы работ" оставляем только ближайшие 3
    $upcoming_for_widget = array_filter($all_plans, function($plan) {
        return $plan['planned_start'] >= date('Y-m-d');
    });
    $upcoming_for_widget = array_slice($upcoming_for_widget, 0, 3);

} catch (PDOException $e) {
    $error = "Ошибка загрузки данных: " . $e->getMessage();
}

$currentMonth = date('m');
$currentYear = date('Y');
$monthNames = [
    1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
    5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
    9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Дашборд - Благоустройство Белово</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard-widget { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); height: 100%; transition: transform 0.3s; }
        .dashboard-widget:hover { transform: translateY(-5px); box-shadow: 0 6px 12px rgba(0,0,0,0.15); }
        .widget-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0; }
        .widget-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; }
        .stat-number { font-size: 2.5rem; font-weight: bold; margin: 10px 0; }
        .stat-label { color: #6c757d; font-size: 0.9rem; }
        .quick-action { background: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 10px; text-decoration: none; color: #333; display: block; transition: all 0.3s; }
        .quick-action:hover { background: #e9ecef; transform: translateX(5px); }
        .calendar-day { text-align: center; padding: 5px; border-radius: 5px; margin: 2px; cursor: pointer; transition: background 0.2s; }
        .calendar-day:hover { background: #e9ecef; }
        .calendar-day.has-event { background: #d4edda; font-weight: bold; }
        .calendar-day.selected { background: #2ecc71; color: white; }
        .activity-item { padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
        .activity-item:last-child { border-bottom: none; }
        #selectedDateEvents { margin-top: 15px; max-height: 200px; overflow-y: auto; }
        .event-badge { background: #f1c40f; color: #2c3e50; padding: 2px 8px; border-radius: 12px; font-size: 12px; margin-left: 8px; }
        .calendar-nav { display: flex; align-items: center; gap: 10px; }
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
            <li class="nav-item"><a href="dashboard.php" class="nav-link active"><i class="nav-icon fas fa-tachometer-alt"></i><span>Дашборд</span></a></li>
            <li class="nav-item"><a href="chat.php" class="nav-link"><i class="nav-icon fas fa-comments"></i><span>Сообщения</span>
            <?php if ($_SESSION['role'] == 'администратор'): ?><li class="nav-item"><a href="backup.php" class="nav-link"><i class="nav-icon fas fa-database"></i><span>Бэкап</span></a></li>
                    <?php endif; ?>
        <?php if ($unread_count > 0): ?>
            <span class="badge bg-danger ms-2"><?= $unread_count ?></span>
        <?php endif; ?>
        <?php if ($_SESSION['role'] == 'администратор'): ?>
    <li class="nav-item">
        <a href="audit.php" class="nav-link">
            <i class="nav-icon fas fa-history"></i><span>Журнал аудита</span>
        </a>
    </li>
<?php endif; ?>
    </a>
</li>
        </ul>
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar"><?= mb_substr($_SESSION['full_name'] ?? $_SESSION['login'] ?? 'П', 0, 1, 'UTF-8') ?></div>
                <div class="user-details">
                    <h4><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['login'] ?? 'Пользователь') ?></h4>
                    <p><?= htmlspecialchars($_SESSION['role'] ?? 'Пользователь') ?></p>
                </div>
            </div>
            <a href="index.php?action=logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Выйти</span></a>
        </div>
    </nav>

    <main class="main-content">
        <div class="content-header fade-in">
            <div class="header-title">
                <h1><i class="fas fa-tachometer-alt me-2"></i>Дашборд</h1>
                <p>Обзор состояния благоустройства города Белово</p>
            </div>
            <div class="header-actions">
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
                <span class="text-muted"><i class="fas fa-calendar-alt me-1"></i><?= date('d.m.Y') ?></span>
            </div>
        </div>

        <!-- Основные метрики -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="dashboard-widget">
                    <div class="widget-header">
                        <h5 class="mb-0">Объекты</h5>
                        <div class="widget-icon" style="background: linear-gradient(135deg, #27ae60, #f1c40f);">
                            <i class="fas fa-city"></i>
                        </div>
                    </div>
                    <div class="stat-number" style="color: #27ae60;"><?= $stats['total_objects'] ?></div>
                    <div class="stat-label">Всего объектов</div>
                    <div class="mt-3">
                        <?php foreach ($status_stats as $stat): ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span><?= htmlspecialchars($stat['status']) ?></span>
                            <span class="fw-bold"><?= $stat['count'] ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="dashboard-widget">
                    <div class="widget-header">
                        <h5 class="mb-0">Планы работ</h5>
                        <div class="widget-icon" style="background: linear-gradient(135deg, #f1c40f, #f39c12);">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                    <div class="stat-number" style="color: #f39c12;"><?= $stats['upcoming_plans'] ?></div>
                    <div class="stat-label">Ближайшие (7 дней)</div>
                    <div class="mt-3">
                        <?php if(count($upcoming_for_widget) > 0): ?>
                            <?php foreach ($upcoming_for_widget as $plan): ?>
                            <div class="activity-item">
                                <small class="text-muted"><?= date('d.m', strtotime($plan['planned_start'])) ?></small>
                                <div><?= htmlspecialchars($plan['object_name']) ?> — <?= htmlspecialchars($plan['work_type']) ?></div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                <p>Нет ближайших планов</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="dashboard-widget">
                    <div class="widget-header">
                        <h5 class="mb-0">Выполнение</h5>
                        <div class="widget-icon" style="background: linear-gradient(135deg, #3498db, #2ecc71);">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                    </div>
                    <div class="stat-number" style="color: #2ecc71;"><?= $stats['monthly_executions'] ?></div>
                    <div class="stat-label">Работ в этом месяце</div>
                    <div class="mt-3">
                        <div class="text-center">
                            <div class="display-6 text-success mb-2"><i class="fas fa-check-double"></i></div>
                            <p class="text-muted">План выполняется</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="dashboard-widget">
                    <div class="widget-header">
                        <h5 class="mb-0">Просрочки</h5>
                        <div class="widget-icon" style="background: linear-gradient(135deg, #e74c3c, #f39c12);">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                    <div class="stat-number" style="color: #e74c3c;"><?= $stats['overdue_plans'] ?></div>
                    <div class="stat-label">Требуют внимания</div>
                    <div class="mt-3">
                        <?php if($stats['overdue_plans'] > 0): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                Необходимо принять меры
                            </div>
                            <a href="index.php?table=work_plans&status=просрочено" class="btn btn-sm btn-warning w-100">
                                <i class="fas fa-tasks me-1"></i> Перейти к просрочкам
                            </a>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                Все планы в срок
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Быстрые действия (с учётом роли) -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="dashboard-widget">
                    <div class="widget-header">
                        <h5 class="mb-0">Быстрые действия</h5>
                        <i class="fas fa-bolt" style="color: #f39c12;"></i>
                    </div>
                    <div class="row">
                        <?php
                        $quick_actions = [];
                        // Доступно всем: записать выполнение и отчёт
                        $quick_actions[] = [
                            'url' => 'index.php?table=work_executions',
                            'icon' => 'clipboard-list',
                            'color' => '#3498db',
                            'title' => 'Записать выполнение',
                            'sub' => 'Отчёт о работах'
                        ];
                        $quick_actions[] = [
                            'url' => 'reports.php',
                            'icon' => 'chart-line',
                            'color' => '#f39c12',
                            'title' => 'Сформировать отчет',
                            'sub' => 'Статистика'
                        ];
                        if ($user_role == 'администратор' || $user_role == 'планировщик') {
                            $quick_actions[] = [
                                'url' => 'index.php?table=work_plans',
                                'icon' => 'calendar-plus',
                                'color' => '#f1c40f',
                                'title' => 'Создать план',
                                'sub' => 'Запланировать работы'
                            ];
                        }
                        if ($user_role == 'администратор') {
                            $quick_actions[] = [
                                'url' => 'index.php?table=objects',
                                'icon' => 'plus-circle',
                                'color' => '#27ae60',
                                'title' => 'Добавить объект',
                                'sub' => 'Новый объект'
                            ];
                        }
                        // Выводим действия
                        foreach ($quick_actions as $action): 
                        ?>
                        <div class="col-md-6">
                            <a href="<?= $action['url'] ?>" class="quick-action">
                                <div class="d-flex align-items-center">
                                    <div class="me-3"><i class="fas fa-<?= $action['icon'] ?> fa-2x" style="color: <?= $action['color'] ?>;"></i></div>
                                    <div><h6 class="mb-0"><?= $action['title'] ?></h6><small class="text-muted"><?= $action['sub'] ?></small></div>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="dashboard-widget">
                    <div class="widget-header">
                        <h5 class="mb-0">Последние добавленные объекты</h5>
                        <i class="fas fa-history" style="color: #6c757d;"></i>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Название</th><th>Тип</th><th>Статус</th><th>Дата</th></tr></thead>
                            <tbody>
                                <?php foreach ($recent_objects as $obj): ?>
                                <tr>
                                    <td><a href="index.php?table=objects&edit_id=<?= $obj['id'] ?>" class="text-decoration-none"><?= htmlspecialchars(mb_substr($obj['name'],0,20)) ?>...</a></td>
                                    <td><?= htmlspecialchars($obj['type']) ?></td>
                                    <td><span class="badge bg-<?= $obj['status']=='активен'?'success':'warning' ?>"><?= htmlspecialchars($obj['status']) ?></span></td>
                                    <td><?= htmlspecialchars($obj['created_at']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Интерактивный календарь -->
        <div class="row">
            <div class="col-md-12">
                <div class="dashboard-widget">
                    <div class="widget-header">
                        <h5 class="mb-0">Календарь планов работ</h5>
                        <div class="calendar-nav">
                            <select id="monthSelect" class="form-select form-select-sm" style="width: auto;">
                                <?php foreach ($monthNames as $num => $name): ?>
                                <option value="<?= $num ?>" <?= $num == $currentMonth ? 'selected' : '' ?>><?= $name ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="yearSelect" class="form-select form-select-sm" style="width: auto;">
                                <?php for ($y = $currentYear - 2; $y <= $currentYear + 2; $y++): ?>
                                <option value="<?= $y ?>" <?= $y == $currentYear ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                            <button class="btn btn-sm btn-outline-secondary" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
                            <?php if ($user_role == 'администратор' || $user_role == 'планировщик'): ?>
                            <button class="btn btn-sm btn-success" onclick="openAddPlanModal()">
                                <i class="fas fa-plus"></i> Запланировать
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8" id="calendarContainer">
                            <!-- Календарь будет отрисован через JS -->
                        </div>
                        <div class="col-md-4">
                            <h6>События на <span id="selectedDateLabel">выбранную дату</span></h6>
                            <div id="selectedDateEvents" class="mt-2">
                                <p class="text-muted">Кликните на день в календаре</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Модальное окно добавления плана работ (доступно только администратору и планировщику) -->
    <?php if ($user_role == 'администратор' || $user_role == 'планировщик'): ?>
    <div class="modal fade" id="addPlanModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form method="POST" action="index.php?table=work_plans" class="modal-content" enctype="multipart/form-data">
                <div class="modal-header" style="background: linear-gradient(135deg, #2ecc71, #f1c40f);">
                    <h5 class="modal-title text-white">Добавление плана работ</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="save_record" value="1">
                    <input type="hidden" name="id" value="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Объект</label>
                            <select name="columns[object_id]" class="form-select" required>
                                <option value="">-- Выберите объект --</option>
                                <?php foreach ($objects_list as $obj): ?>
                                <option value="<?= $obj['id'] ?>"><?= htmlspecialchars($obj['name'] . ' (' . $obj['address'] . ')') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Тип работ</label>
                            <input type="text" name="columns[work_type]" class="form-control" list="workTypes" required>
                            <datalist id="workTypes">
                                <option value="покос травы">
                                <option value="уборка мусора">
                                <option value="ремонт лавочек">
                                <option value="обрезка деревьев">
                                <option value="покраска">
                                <option value="освещение">
                            </datalist>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Дата начала</label>
                            <input type="date" name="columns[planned_start]" class="form-control" id="planStartDate" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Дата окончания</label>
                            <input type="date" name="columns[planned_end]" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ответственный</label>
                            <input type="text" name="columns[responsible]" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Статус</label>
                            <select name="columns[status]" class="form-select">
                                <option value="запланировано">Запланировано</option>
                                <option value="в работе">В работе</option>
                                <option value="выполнено">Выполнено</option>
                                <option value="просрочено">Просрочено</option>
                                <option value="отменено">Отменено</option>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Описание</label>
                            <textarea name="columns[description]" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Прикрепить файл</label>
                            <input type="file" name="attachment" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-success">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    <script>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.mobile-toggle')?.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        const allPlans = <?= json_encode($all_plans) ?>;
        let selectedYear = <?= $currentYear ?>;
        let selectedMonth = <?= $currentMonth ?>;
        let selectedDay = null;

        function formatDate(year, month, day) {
            return year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');
        }

        function daysInMonth(year, month) {
            return new Date(year, month, 0).getDate();
        }

        function getFirstDayOfMonth(year, month) {
            let day = new Date(year, month - 1, 1).getDay();
            return day === 0 ? 6 : day - 1;
        }

        function renderCalendar() {
            const container = document.getElementById('calendarContainer');
            const days = daysInMonth(selectedYear, selectedMonth);
            const firstDay = getFirstDayOfMonth(selectedYear, selectedMonth);

            let html = `<div class="mb-3"><h6>${selectedYear} ${getMonthName(selectedMonth)}</h6></div>`;
            html += '<div class="row">';
            const weekDays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
            weekDays.forEach(day => {
                html += `<div class="col-1 p-1 text-center fw-bold">${day}</div>`;
            });
            html += '</div><div class="row">';

            for (let i = 0; i < firstDay; i++) {
                html += '<div class="col-1 p-1"><div class="calendar-day"></div></div>';
            }

            for (let d = 1; d <= days; d++) {
                const dateStr = formatDate(selectedYear, selectedMonth, d);
                const hasEvent = allPlans.some(plan => plan.planned_start === dateStr);
                const isSelected = (selectedDay === d) ? 'selected' : '';
                const isToday = (dateStr === new Date().toISOString().split('T')[0]) ? 'bg-primary text-white' : '';

                html += `<div class="col-1 p-1">`;
                html += `<div class="calendar-day ${hasEvent ? 'has-event' : ''} ${isSelected} ${isToday}" data-day="${d}">${d}`;
                if (hasEvent) html += '<div class="small">•</div>';
                html += '</div></div>';
            }

            const totalCells = firstDay + days;
            const remaining = 7 - (totalCells % 7);
            if (remaining < 7) {
                for (let i = 0; i < remaining; i++) {
                    html += '<div class="col-1 p-1"><div class="calendar-day"></div></div>';
                }
            }

            html += '</div>';
            container.innerHTML = html;

            document.querySelectorAll('.calendar-day[data-day]').forEach(el => {
                el.addEventListener('click', function() {
                    const day = parseInt(this.dataset.day);
                    selectedDay = day;
                    document.querySelectorAll('.calendar-day').forEach(d => d.classList.remove('selected'));
                    this.classList.add('selected');

                    const dateStr = formatDate(selectedYear, selectedMonth, day);
                    document.getElementById('selectedDateLabel').textContent = dateStr;

                    const events = allPlans.filter(plan => plan.planned_start === dateStr);
                    const eventsContainer = document.getElementById('selectedDateEvents');
                    if (events.length > 0) {
                        let evHtml = '';
                        events.forEach(plan => {
                            evHtml += `<div class="activity-item">
                                <div class="d-flex justify-content-between">
                                    <strong>${plan.object_name}</strong>
                                    <span class="event-badge">${plan.work_type}</span>
                                </div>
                                <small>${plan.description || '—'}</small>
                                <small class="text-muted d-block">Ответственный: ${plan.responsible || '—'}</small>
                            </div>`;
                        });
                        eventsContainer.innerHTML = evHtml;
                    } else {
                        eventsContainer.innerHTML = '<p class="text-muted">Нет планов на этот день</p>';
                    }
                });
            });
        }

        function getMonthName(month) {
            const months = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
                            'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
            return months[month - 1];
        }

        function changeMonth(delta) {
            selectedMonth += delta;
            if (selectedMonth < 1) {
                selectedMonth = 12;
                selectedYear--;
            } else if (selectedMonth > 12) {
                selectedMonth = 1;
                selectedYear++;
            }
            document.getElementById('monthSelect').value = selectedMonth;
            document.getElementById('yearSelect').value = selectedYear;
            selectedDay = null;
            document.getElementById('selectedDateLabel').textContent = 'выбранную дату';
            document.getElementById('selectedDateEvents').innerHTML = '<p class="text-muted">Кликните на день в календаре</p>';
            renderCalendar();
        }

        document.getElementById('monthSelect').addEventListener('change', function() {
            selectedMonth = parseInt(this.value);
            selectedDay = null;
            document.getElementById('selectedDateLabel').textContent = 'выбранную дату';
            document.getElementById('selectedDateEvents').innerHTML = '<p class="text-muted">Кликните на день в календаре</p>';
            renderCalendar();
        });
        document.getElementById('yearSelect').addEventListener('change', function() {
            selectedYear = parseInt(this.value);
            selectedDay = null;
            document.getElementById('selectedDateLabel').textContent = 'выбранную дату';
            document.getElementById('selectedDateEvents').innerHTML = '<p class="text-muted">Кликните на день в календаре</p>';
            renderCalendar();
        });

        function openAddPlanModal() {
            let date = '';
            if (selectedDay) {
                date = formatDate(selectedYear, selectedMonth, selectedDay);
            } else {
                const today = new Date();
                date = today.toISOString().split('T')[0];
            }
            document.getElementById('planStartDate').value = date;

            const modal = document.getElementById('addPlanModal');
            modal.querySelector('form').reset();
            document.getElementById('planStartDate').value = date;

            new bootstrap.Modal(modal).show();
        }

        renderCalendar();

        document.addEventListener('DOMContentLoaded', function() {
            const counters = document.querySelectorAll('.stat-number');
            counters.forEach(counter => {
                const target = parseInt(counter.innerText);
                const increment = target / 50;
                let current = 0;
                const updateCounter = () => {
                    if (current < target) {
                        current += increment;
                        counter.innerText = Math.ceil(current);
                        setTimeout(updateCounter, 20);
                    } else {
                        counter.innerText = target;
                    }
                };
                updateCounter();
            });
        });
    </script>
</body>
</html>