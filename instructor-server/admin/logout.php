<?php
require dirname(__DIR__) . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && adminCsrfIsValid($_POST['csrf_token'] ?? null)) {
    $_SESSION = [];
    session_destroy();
}

header('Location: login.php');
exit;
