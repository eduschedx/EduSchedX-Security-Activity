<?php
require dirname(__DIR__) . '/instructor-server/config.php';
header('Location: ' . (!empty($_SESSION['admin_authenticated']) ? 'dashboard.php' : 'login.php'));
exit;
