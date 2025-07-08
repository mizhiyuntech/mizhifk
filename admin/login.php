<?php
session_start();

// 检查是否已安装
if (!file_exists('../config.php')) {
    header('Location: ../install.php');
    exit;
}

require_once '../includes/database.php';
require_once '../includes/functions.php';

// 如果已登录，直接跳转到后台首页
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_POST) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $token = $_POST['_token'] ?? '';
    
    if (!verifyCsrfToken($token)) {
        $error = '表单验证失败，请重新提交';
    } elseif (empty($username) || empty($password)) {
        $error = '请填写用户名和密码';
    } else {
        $db = Database::getInstance();
        $admin = $db->fetch("SELECT * FROM admins WHERE username = ?", [$username]);
        
        if ($admin && password_verify($password, $admin['password'])) {
            // 登录成功
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            
            // 更新最后登录时间
            $db->update('admins', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$admin['id']]);
            
            header('Location: index.php');
            exit;
        } else {
            $error = '用户名或密码错误';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - <?php echo getSetting('site_title', '需求收集系统'); ?></title>
    <link rel="stylesheet" href="../layui/css/layui.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e9fff 0%, #5fb878 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        @media (max-width: 768px) {
            body { padding: 10px; }
            .layui-container { margin-top: 20px !important; }
        }
    </style>
</head>
  <body>
    <div class="layui-container" style="margin-top: 80px;">
        <div class="layui-row">
            <div class="layui-col-md6 layui-col-md-offset3 layui-col-sm8 layui-col-sm-offset2">
                <div class="layui-card">
                    <div class="layui-card-header" style="text-align: center; background: linear-gradient(135deg, #1e9fff 0%, #5fb878 100%); color: white;">
                        <h1 style="margin: 20px 0; font-size: 24px;">管理后台</h1>
                        <p style="margin: 0 0 20px; opacity: 0.9;">请登录您的管理员账号</p>
                    </div>
                    
                    <div class="layui-card-body" style="padding: 30px;">
                        <?php if ($error): ?>
                        <div class="layui-elem-quote layui-quote-nm" style="border-left: 5px solid #ff5722; color: #ff5722;">
                            <i class="layui-icon layui-icon-close"></i> <?php echo e($error); ?>
                        </div>
                        <?php endif; ?>
                        
                        <form class="layui-form" method="post">
                            <input type="hidden" name="_token" value="<?php echo generateCsrfToken(); ?>">
                            
                            <div class="layui-form-item">
                                <label class="layui-form-label">用户名</label>
                                <div class="layui-input-block">
                                    <input type="text" name="username" value="<?php echo e($_POST['username'] ?? ''); ?>" 
                                           placeholder="请输入用户名" class="layui-input" lay-verify="required" autofocus>
                                </div>
                            </div>
                            
                            <div class="layui-form-item">
                                <label class="layui-form-label">密码</label>
                                <div class="layui-input-block">
                                    <input type="password" name="password" 
                                           placeholder="请输入密码" class="layui-input" lay-verify="required">
                                </div>
                            </div>
                            
                            <div class="layui-form-item">
                                <div class="layui-input-block">
                                    <button type="submit" class="layui-btn layui-btn-fluid layui-btn-lg">
                                        <i class="layui-icon layui-icon-username"></i> 登录
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <div style="text-align: center; margin-top: 20px;">
                            <a href="../index.php" class="layui-btn layui-btn-primary layui-btn-sm">
                                <i class="layui-icon layui-icon-return"></i> 返回首页
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../layui/layui.js"></script>
    <script>
    layui.use(['form'], function(){
        var form = layui.form;
        
        // 表单验证
        form.verify({
            username: function(value, item){
                if(!value){
                    return '请输入用户名';
                }
            },
            password: function(value, item){
                if(!value){
                    return '请输入密码';
                }
            }
        });
    });
    </script>
</body>
</html>