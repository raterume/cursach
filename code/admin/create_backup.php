<?php
session_start();
require_once '../db/db.php';

$type = $_POST['type'] ?? 'full';
$conn = get_db_connection();

// Создаем директорию для бэкапов если её нет
$backup_dir = '../backups/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

$filename = 'backup_' . date('Y-m-d_H-i-s') . '_' . $type . '.sql';
$filepath = $backup_dir . $filename;

// Функция для создания бэкапа 
function createBackupPHP($conn, $filepath, $type) {
    $tables = array();
    $result = $conn->query('SHOW TABLES');
    
    while($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    
    $sql = "-- Social Network Backup\n";
    $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Type: " . $type . "\n\n";
    
    foreach($tables as $table) {
        // Получаем структуру таблицы
        if ($type != 'data') {
            $result = $conn->query("SHOW CREATE TABLE `$table`");
            $row = $result->fetch_row();
            $sql .= "\n-- --------------------------------------------------------\n\n";
            $sql .= "--\n-- Структура таблицы `$table`\n--\n\n";
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $row[1] . ";\n\n";
        }
        
        // Получаем данные таблицы
        if ($type != 'structure') {
            $result = $conn->query("SELECT * FROM `$table`");
            if($result->num_rows > 0) {
                $sql .= "--\n-- Дамп данных таблицы `$table`\n--\n\n";
                
                // Получаем названия колонок
                $columns = array();
                $fields = $conn->query("SHOW COLUMNS FROM `$table`");
                while($field = $fields->fetch_assoc()) {
                    $columns[] = $field['Field'];
                }
                
                while($row = $result->fetch_assoc()) {
                    $sql .= "INSERT INTO `$table` (`" . implode("`, `", $columns) . "`) VALUES (";
                    
                    $values = array();
                    foreach($row as $value) {
                        if ($value === null) {
                            $values[] = "NULL";
                        } else {
                            $values[] = "'" . $conn->real_escape_string($value) . "'";
                        }
                    }
                    $sql .= implode(", ", $values) . ");\n";
                }
                $sql .= "\n";
            }
        }
    }
    
    // Добавляем комментарий в конце
    $sql .= "--\n-- Конец резервной копии\n--\n";
    
    // Сохраняем файл
    if(file_put_contents($filepath, $sql)) {
        return true;
    }
    return false;
}

// Создаем бэкап
$success = createBackupPHP($conn, $filepath, $type);


if($success) {
    $file_size = round(filesize($filepath) / 1024, 2);
    $details = "Создан бэкап базы данных (" . $type . "): " . $filename . " (" . $file_size . " KB)";
    $_SESSION['backup_success'] = "Бэкап успешно создан! Размер: " . $file_size . " KB";
    
    // Создаем также простой текстовый отчет
    createSimpleReport($backup_dir);
    
} else {
    $details = "Ошибка при создании бэкапа: " . $filename;
    $_SESSION['backup_error'] = "Ошибка при создании бэкапа";
}

// Функция для создания простого текстового отчета
function createSimpleReport($backup_dir) {
    $report_file = $backup_dir . 'backup_report_' . date('Y-m-d_H-i-s') . '.txt';
    
    $report = "ОТЧЕТ О РЕЗЕРВНОМ КОПИРОВАНИИ\n";
    $report .= "=============================\n\n";
    $report .= "Дата создания: " . date('d.m.Y H:i:s') . "\n";
    $report .= "Сервер: " . $_SERVER['SERVER_NAME'] . "\n";
    $report .= "IP: " . $_SERVER['SERVER_ADDR'] . "\n\n";
    
    $report .= "ИНФОРМАЦИЯ О СИСТЕМЕ:\n";
    $report .= "----------------------\n";
    $report .= "PHP версия: " . PHP_VERSION . "\n";
    $report .= "ОС: " . PHP_OS . "\n";
    $report .= "Время выполнения: " . date('H:i:s') . "\n\n";
    
    $report .= "ДИРЕКТОРИЯ БЭКАПОВ:\n";
    $report .= "--------------------\n";
    
    $files = glob($backup_dir . '*.sql');
    $report .= "Всего файлов: " . count($files) . "\n";
    $report .= "Последние 5 файлов:\n";
    
    if ($files) {
        // Сортируем по дате изменения
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $count = 0;
        foreach($files as $file) {
            if ($count++ >= 5) break;
            $size = round(filesize($file) / 1024, 2);
            $date = date('d.m.Y H:i', filemtime($file));
            $report .= "- " . basename($file) . " (" . $size . " KB, " . $date . ")\n";
        }
    }
    
    file_put_contents($report_file, $report);
}

// Перенаправляем на dashboard.php
header('Location: dashboard.php');
exit;
?>