<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

try {
    // Основная статистика
    $stats = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM objects) AS total_objects,
            (SELECT COUNT(*) FROM objects WHERE status = 'активен') AS active_objects,
            (SELECT COUNT(*) FROM work_plans WHERE status = 'выполнено') AS completed_plans,
            (SELECT COUNT(*) FROM work_plans 
             WHERE planned_end < CURDATE() 
               AND status NOT IN ('выполнено', 'отменено')) AS overdue_plans
    ")->fetch();

    // Просроченные планы
    $overdue_plans = $pdo->query("
        SELECT p.*, o.name AS object_name, o.address,
               DATEDIFF(CURDATE(), p.planned_end) AS days_overdue
        FROM work_plans p
        LEFT JOIN objects o ON p.object_id = o.id
        WHERE p.planned_end < CURDATE() 
          AND p.status NOT IN ('выполнено', 'отменено')
        ORDER BY p.planned_end ASC
    ")->fetchAll();

    // Выполненные планы (последние 10)
    $completed_plans = $pdo->query("
        SELECT p.*, o.name AS object_name, o.address
        FROM work_plans p
        LEFT JOIN objects o ON p.object_id = o.id
        WHERE p.status = 'выполнено'
        ORDER BY p.planned_end DESC
        LIMIT 10
    ")->fetchAll();

    // Общее количество выполненных планов
    $total_completed = $pdo->query("SELECT COUNT(*) FROM work_plans WHERE status = 'выполнено'")->fetchColumn();

} catch (PDOException $e) {
    $error = "Ошибка загрузки данных: " . $e->getMessage();
}

$user_display_name = $_SESSION['full_name'] ?? $_SESSION['login'] ?? 'Пользователь';
$unread_notifications = get_unread_notifications_count($_SESSION['user_id']);
$recent_notifications = get_recent_notifications($_SESSION['user_id'], 5);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отчёты — Благоустройство Белово</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Дополнительные стили для единообразия с дашбордом */
        .dashboard-widget {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            height: 100%;
            transition: transform 0.3s;
        }
        .dashboard-widget:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        .widget-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        .widget-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body class="admin-panel">
    <button class="mobile-toggle d-lg-none"><i class="fas fa-bars"></i></button>

    <!-- Боковое меню -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo"><i class="fas fa-tree"></i></div>
            <div class="sidebar-title">BlagoBelovo</div>
        </div>
        <ul class="sidebar-nav">
            <li class="nav-item"><a href="index.php" class="nav-link"><i class="nav-icon fas fa-table"></i><span>Таблицы</span></a></li>
            <li class="nav-item"><a href="reports.php" class="nav-link active"><i class="nav-icon fas fa-chart-bar"></i><span>Отчёты</span></a></li>
            <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i><span>Дашборд</span></a></li>
            <li class="nav-item"><a href="chat.php" class="nav-link"><i class="nav-icon fas fa-comments"></i><span>Сообщения</span></a></li>
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
                <h1><i class="fas fa-chart-bar me-2"></i>Отчёты</h1>
                <p>Ключевые показатели благоустройства</p>
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
                <button class="btn btn-success" onclick="window.print()">
                    <i class="fas fa-print"></i> Печать
                </button>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Карточки статистики -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="dashboard-widget">
                    <div class="widget-header">
                        <h5 class="mb-0">Всего объектов</h5>
                        <div class="widget-icon" style="background: linear-gradient(135deg, #2ecc71, #27ae60);">
                            <i class="fas fa-city"></i>
                        </div>
                    </div>
                    <div class="stat-number" style="color: #27ae60;"><?= $stats['total_objects'] ?></div>
                    <div class="stat-label">Объектов благоустройства</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-widget">
                    <div class="widget-header">
                        <h5 class="mb-0">Активных</h5>
                        <div class="widget-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-number" style="color: #3498db;"><?= $stats['active_objects'] ?></div>
                    <div class="stat-label">Объектов в работе</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-widget">
                    <div class="widget-header">
                        <h5 class="mb-0">Выполнено планов</h5>
                        <div class="widget-icon" style="background: linear-gradient(135deg, #f1c40f, #f39c12);">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                    <div class="stat-number" style="color: #f39c12;"><?= $stats['completed_plans'] ?></div>
                    <div class="stat-label">Планов завершено</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-widget">
                    <div class="widget-header">
                        <h5 class="mb-0">Просрочено</h5>
                        <div class="widget-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                    <div class="stat-number" style="color: #e74c3c;"><?= $stats['overdue_plans'] ?></div>
                    <div class="stat-label">Требуют внимания</div>
                </div>
            </div>
        </div>

        <!-- Таблица просроченных планов -->
        <div class="table-container mb-4">
            <div class="table-header">
                <div class="table-title"><i class="fas fa-clock text-danger me-2"></i>Просроченные планы</div>
                <div class="table-count"><span class="badge bg-danger"><?= count($overdue_plans) ?> шт.</span></div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Объект</th>
                            <th>Тип работ</th>
                            <th>План. окончание</th>
                            <th>Просрочено дней</th>
                            <th>Ответственный</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($overdue_plans) > 0): ?>
                            <?php foreach ($overdue_plans as $plan): ?>
                            <tr>
                                <td><?= htmlspecialchars($plan['object_name'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($plan['work_type']) ?></td>
                                <td><span class="badge bg-danger"><?= htmlspecialchars($plan['planned_end']) ?></span></td>
                                <td><span class="fw-bold text-danger"><?= $plan['days_overdue'] ?></span></td>
                                <td><?= htmlspecialchars($plan['responsible'] ?? '—') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                                    <p class="mb-0">Нет просроченных планов</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Таблица выполненных планов -->
        <div class="table-container">
            <div class="table-header">
                <div class="table-title"><i class="fas fa-check-circle text-success me-2"></i>Выполненные планы (всего <?= $total_completed ?>)</div>
                <div class="table-count"><span class="badge bg-success"><?= count($completed_plans) ?> шт. (последние 10)</span></div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Объект</th>
                            <th>Тип работ</th>
                            <th>Дата окончания</th>
                            <th>Ответственный</th>
                            <th>Описание</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($completed_plans) > 0): ?>
                            <?php foreach ($completed_plans as $plan): ?>
                            <tr>
                                <td><?= htmlspecialchars($plan['object_name'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($plan['work_type']) ?></td>
                                <td><?= htmlspecialchars($plan['planned_end']) ?></td>
                                <td><?= htmlspecialchars($plan['responsible'] ?? '—') ?></td>
                                <td><?= htmlspecialchars(mb_substr($plan['description'] ?? '', 0, 50)) ?>...</td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-clipboard-list fa-2x mb-2"></i>
                                    <p class="mb-0">Нет выполненных планов</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
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
    </script>
</body>
</html>