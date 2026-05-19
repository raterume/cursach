<?php
// Простейший тест - возвращает чистый JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => true,
    'test' => 'Работает!',
    'time' => date('H:i:s'),
    'post' => $_POST ?? [],
    'files' => $_FILES ?? []
]);
exit();
?>