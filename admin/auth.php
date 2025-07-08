<?php
// 检查是否已安装
if (!file_exists('../config.php')) {
    header('Location: ../install.php');
    exit;
}

require_once '../includes/database.php';
require_once '../includes/functions.php';

// 检查登录状态
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// 获取当前管理员信息
$db = Database::getInstance();
$currentAdmin = $db->fetch("SELECT * FROM admins WHERE id = ?", [$_SESSION['admin_id']]);

if (!$currentAdmin) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// 处理退出登录
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>