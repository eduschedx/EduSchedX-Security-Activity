<?php
require __DIR__ . '/config.php';

if (!empty($_SESSION['student_verified']) && isset($_SESSION['student_id'], $_SESSION['full_name'], $_SESSION['assigned_station'])) {
    header('Location: ' . ((!empty($_SESSION['submitted']) || !empty($_SESSION['already_submitted'])) ? 'submitted.php' : 'welcome.php'));
} else {
    header('Location: login.php');
}
exit;
