<?php
session_start();
    require_once 'db/db.php';
    logToDatabase('logout', 'account');
session_destroy();
header('Location: login.php');
exit;
?>