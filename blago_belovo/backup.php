<?php
session_start();
require 'db.php';


// Только для администратора
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'администратор') {
    header("Location: index.php");
    exit;
}

// Параметры подключения (берём из db.php, но можно продублировать)
$host = '127.0.0.1';
$dbname = 'blagoustroistvo_belovo';
$user = 'root';
$pass = '';

// Функция создания дампа базы данных
function createDatabaseDump($host, $user, $pass, $dbname, $filepath) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $output = "-- Резервная копия базы данных: $dbname\n";
        $output .= "-- Дата создания: " . date('Y-m-d H:i:s') . "\n\n";
        $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        // Получаем список таблиц
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            // Структура таблицы
            $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
            if (isset($create['Create Table'])) {
                $output .= "\n-- Структура таблицы `$table`\n";
                $output .= $create['Create Table'] . ";\n\n";
            }

            // Данные таблицы
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            if (count($rows) > 0) {
                $output .= "-- Данные таблицы `$table`\n";
                foreach ($rows as $row) {
                    $columns = array_map(function($col) { return "`$col`"; }, array_keys($row));
                    $values = array_map(function($val) use ($pdo) {
                        if ($val === null) return 'NULL';
                        return $pdo->quote($val);
                    }, array_values($row));
                    $output .= "INSERT INTO `$table` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
                }
                $output .= "\n";
            }
        }

        $output .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($filepath, $output);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Функция создания полного бэкапа (файлы + дамп БД)
function createFullSystemBackup() {
    $backupDir = __DIR__ . '/backups/';
    if (!is_dir($backupDir)) mkdir($backupDir, 0777, true);
    
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "full_backup_{$timestamp}";
    
    // Проверяем доступные методы архивации
    $zipAvailable = class_exists('ZipArchive');
    $pharAvailable = class_exists('PharData');
    
    if (!$zipAvailable && !$pharAvailable) {
        return ['error' => 'Не найдено расширение для создания архивов (ZipArchive или Phar). Включите расширение zip в php.ini.'];
    }
    
    // Создаём временную папку для сбора файлов
    $tempDir = $backupDir . 'temp_' . uniqid() . '/';
    mkdir($tempDir, 0777, true);
    
    // Копируем все файлы проекта (кроме самой папки backups)
    $excludeDirs = ['backups', 'temp'];
    $rootPath = __DIR__;
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $file) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($rootPath) + 1);
        
        // Исключаем ненужные директории
        $exclude = false;
        foreach ($excludeDirs as $dir) {
            if (strpos($relativePath, $dir . DIRECTORY_SEPARATOR) === 0 || $relativePath === $dir) {
                $exclude = true;
                break;
            }
        }
        if ($exclude) continue;
        
        $target = $tempDir . $relativePath;
        $targetDir = dirname($target);
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        copy($filePath, $target);
    }
    
    // Создаём дамп БД и сохраняем во временную папку
    $dumpFile = $tempDir . 'database_dump.sql';
    global $host, $user, $pass, $dbname;
    $dumpSuccess = createDatabaseDump($host, $user, $pass, $dbname, $dumpFile);
    if (!$dumpSuccess) {
        // Очищаем временную папку
        array_map('unlink', glob("$tempDir/*.*"));
        rmdir($tempDir);
        return ['error' => 'Не удалось создать дамп базы данных.'];
    }
    
    // Создаём архив
    if ($zipAvailable) {
        $archivePath = $backupDir . $filename . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($archivePath, ZipArchive::CREATE) !== true) {
            return ['error' => 'Не удалось создать zip-архив.'];
        }
        
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($tempDir));
            $zip->addFile($filePath, $relativePath);
        }
        $zip->close();
    } elseif ($pharAvailable) {
        $archivePath = $backupDir . $filename . '.tar';
        $phar = new PharData($archivePath);
        $phar->buildFromDirectory($tempDir);
    } else {
        // Не должно сюда попасть
        return ['error' => 'Нет доступного метода архивации.'];
    }
    
    // Удаляем временную папку
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        if ($file->isDir()) rmdir($file->getRealPath());
        else unlink($file->getRealPath());
    }
    rmdir($tempDir);
    
    return ['success' => $archivePath];
}

// Обработка ручного создания бэкапа (обычный дамп БД)
if (isset($_POST['create_backup'])) {
    $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath = __DIR__ . '/backups/' . $filename;
    if (createDatabaseDump($host, $user, $pass, $dbname, $filepath)) {
        header("Location: backup.php?success=" . urlencode("Резервная копия создана: $filename"));
        exit;
    } else {
        header("Location: backup.php?error=" . urlencode("Ошибка при создании резервной копии. Проверьте права на запись в папку backups."));
        exit;
    }
}

// Обработка создания полного бэкапа
if (isset($_POST['create_full_backup'])) {
    $result = createFullSystemBackup();
    if (isset($result['success'])) {
        $basename = basename($result['success']);
        header("Location: backup.php?success=" . urlencode("Полный бэкап создан: $basename"));
    } else {
        header("Location: backup.php?error=" . urlencode($result['error']));
    }
    exit;
}

// Сохранение настроек авто-бэкапа
if (isset($_POST['save_settings'])) {
    $enabled = isset($_POST['autobackup_enabled']) ? 1 : 0;
    $time = $_POST['backup_time'] ?? '02:00';
    $period = $_POST['backup_period'] ?? 'daily';
    $configContent = "<?php\nreturn [\n    'enabled' => $enabled,\n    'time' => '$time',\n    'period' => '$period'\n];\n";
    file_put_contents(__DIR__ . '/backup_config.php', $configContent);
    header("Location: backup.php?success=" . urlencode("Настройки авто-бэкапа сохранены."));
    exit;
}

// Получаем сообщения из GET
$success = isset($_GET['success']) ? $_GET['success'] : null;
$error = isset($_GET['error']) ? $_GET['error'] : null;

// Загрузка текущих настроек
$autobackup_enabled = false;
$backup_time = '02:00';
$backup_period = 'daily';
if (file_exists(__DIR__ . '/backup_config.php')) {
    $config = include __DIR__ . '/backup_config.php';
    $autobackup_enabled = $config['enabled'] ?? false;
    $backup_time = $config['time'] ?? '02:00';
    $backup_period = $config['period'] ?? 'daily';
}

// Список существующих бэкапов (сортировка по дате, новые сверху)
$backup_files = glob(__DIR__ . '/backups/*.{sql,zip,tar}', GLOB_BRACE);
usort($backup_files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

$user_display_name = $_SESSION['full_name'] ?? $_SESSION['login'] ?? 'Администратор';

// Данные для уведомлений
$unread_notifications = get_unread_notifications_count($_SESSION['user_id']);
$recent_notifications = get_recent_notifications($_SESSION['user_id'], 5);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление бэкапами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .backup-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
        }
        .backup-list {
            max-height: 400px;
            overflow-y: auto;
        }
        .backup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        .backup-item:hover {
            background: #f8f9fa;
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
            <li class="nav-item"><a href="reports.php" class="nav-link"><i class="nav-icon fas fa-chart-bar"></i><span>Отчеты</span></a></li>
            <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i><span>Дашборд</span></a></li>
            <li class="nav-item"><a href="chat.php" class="nav-link"><i class="nav-icon fas fa-comments"></i><span>Сообщения</span></a></li>
            <li class="nav-item"><a href="backup.php" class="nav-link active"><i class="nav-icon fas fa-database"></i><span>Бэкап</span></a></li>
            <li class="nav-item"><a href="audit.php" class="nav-link"><i class="nav-icon fas fa-history"></i><span>Журнал аудита</span></a></li>
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
                <h1><i class="fas fa-database me-2"></i>Резервное копирование</h1>
                <p>Создание и управление бэкапами базы данных и файлов</p>
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

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <!-- Ручное создание -->
                <div class="backup-card">
                    <h5><i class="fas fa-plus-circle me-2 text-success"></i>Создать бэкап вручную</h5>
                    <form method="POST" style="display:inline-block;">
                        <button type="submit" name="create_backup" class="btn btn-success">
                            <i class="fas fa-save"></i> Только БД (SQL)
                        </button>
                    </form>
                    <form method="POST" style="display:inline-block; margin-left:10px;">
                        <button type="submit" name="create_full_backup" class="btn btn-primary">
                            <i class="fas fa-archive"></i> Полный бэкап (файлы+БД)
                        </button>
                    </form>
                </div>

                <!-- Настройки авто-бэкапа -->
                <div class="backup-card">
                    <h5><i class="fas fa-clock me-2 text-primary"></i>Автоматический бэкап</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="autobackup_enabled" id="autobackupSwitch" <?= $autobackup_enabled ? 'checked' : '' ?>>
                                <label class="form-check-label" for="autobackupSwitch">Включить авто-бэкап</label>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Время (ЧЧ:ММ)</label>
                                <input type="time" name="backup_time" class="form-control" value="<?= $backup_time ?>" step="60">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Периодичность</label>
                                <select name="backup_period" class="form-select">
                                    <option value="daily" <?= $backup_period == 'daily' ? 'selected' : '' ?>>Ежедневно</option>
                                    <option value="weekly" <?= $backup_period == 'weekly' ? 'selected' : '' ?>>Еженедельно (пн)</option>
                                    <option value="monthly" <?= $backup_period == 'monthly' ? 'selected' : '' ?>>Ежемесячно (1-го числа)</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="save_settings" class="btn btn-primary">Сохранить настройки</button>
                    </form>
                    <hr>
                    <p class="text-muted small"><i class="fas fa-info-circle"></i> Для работы авто-бэкапа настройте задание cron, выполняющее файл <code>cron_backup.php</code>.</p>
                </div>
            </div>

            <div class="col-md-6">
                <!-- Список сохранённых бэкапов -->
                <div class="backup-card">
                    <h5><i class="fas fa-history me-2"></i>Сохранённые бэкапы</h5>
                    <div class="backup-list">
                        <?php if (count($backup_files) > 0): ?>
                            <?php foreach ($backup_files as $file): 
                                $filename = basename($file);
                                $filesize = round(filesize($file) / 1024, 2) . ' КБ';
                                $date = date('d.m.Y H:i:s', filemtime($file));
                            ?>
                                <div class="backup-item">
                                    <div>
                                        <strong><?= htmlspecialchars($filename) ?></strong><br>
                                        <small class="text-muted"><?= $date ?> (<?= $filesize ?>)</small>
                                    </div>
                                    <div>
                                        <a href="backups/<?= urlencode($filename) ?>" download class="btn btn-sm btn-outline-primary" title="Скачать">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center py-3">Нет сохранённых бэкапов</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
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