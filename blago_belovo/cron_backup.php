<?php
// Скрипт для автоматического создания бэкапов по расписанию
// Вызывается из cron (например, каждый час)

require 'db.php';

// Проверяем настройки
if (!file_exists(__DIR__ . '/backup_config.php')) {
    exit; // настройки не заданы
}

$config = include __DIR__ . '/backup_config.php';
if (!$config['enabled']) {
    exit; // авто-бэкап отключён
}

// Параметры подключения (из db.php)
$host = '127.0.0.1';
$dbname = 'blagoustroistvo_belovo';
$user = 'root';
$pass = '';

// Функция создания дампа (такая же, как в backup.php)
function createDatabaseDump($host, $user, $pass, $dbname, $filepath) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $output = "-- Автоматическая резервная копия\n-- Дата: " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
            if (isset($create['Create Table'])) {
                $output .= "\n-- Структура таблицы `$table`\n";
                $output .= $create['Create Table'] . ";\n\n";
            }
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
        $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($filepath, $output);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Определяем, нужно ли создавать бэкап сейчас
$now = time();
$lastBackupFile = __DIR__ . '/backups/last_autobackup.txt';
$lastBackup = file_exists($lastBackupFile) ? (int)file_get_contents($lastBackupFile) : 0;

$shouldRun = false;
switch ($config['period']) {
    case 'daily':
        // Проверяем, что прошло больше суток
        if ($now - $lastBackup > 24 * 3600) $shouldRun = true;
        break;
    case 'weekly':
        if ($now - $lastBackup > 7 * 24 * 3600) $shouldRun = true;
        break;
    case 'monthly':
        if ($now - $lastBackup > 30 * 24 * 3600) $shouldRun = true;
        break;
}

if ($shouldRun) {
    $filename = 'autobackup_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath = __DIR__ . '/backups/' . $filename;
    if (createDatabaseDump($host, $user, $pass, $dbname, $filepath)) {
        file_put_contents($lastBackupFile, $now);
        // Можно отправить уведомление администратору, если нужно
    }
}