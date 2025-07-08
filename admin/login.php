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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 0;
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-header h1 {
            margin: 0 0 10px;
            font-size: 1.8em;
            font-weight: 300;
        }
        .login-header p {
            margin: 0;
            opacity: 0.9;
        }
        .login-body {
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .layui-input {
            border-radius: 8px;
            border: 2px solid #e8e8e8;
            transition: all 0.3s ease;
            padding: 12px 15px;
        }
        .layui-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .login-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.3s ease;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #999;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link a:hover {
            color: #667eea;
        }
        
        @media (max-width: 480px) {
            body { padding: 10px; }
            .login-header {
                padding: 20px;
            }
            .login-header h1 {
                font-size: 1.5em;
            }
            .login-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>管理后台</h1>
            <p>请登录您的管理员账号</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
            <div class="layui-elem-quote layui-quote-nm" style="border-left: 5px solid #ff5722; color: #ff5722; margin-bottom: 20px;">
                <i class="layui-icon layui-icon-close"></i> <?php echo e($error); ?>
            </div>
            <?php endif; ?>
            
            <form class="layui-form" method="post">
                <input type="hidden" name="_token" value="<?php echo generateCsrfToken(); ?>">
                
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text" name="username" value="<?php echo e($_POST['username'] ?? ''); ?>" 
                           placeholder="请输入用户名" class="layui-input" lay-verify="required" autofocus>
                </div>
                
                <div class="form-group">
                    <label>密码</label>
                    <input type="password" name="password" 
                           placeholder="请输入密码" class="layui-input" lay-verify="required">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="layui-btn layui-btn-fluid login-btn">
                        <i class="layui-icon layui-icon-username"></i> 登录
                    </button>
                </div>
            </form>
            
            <div class="back-link">
                <a href="../index.php">
                    <i class="layui-icon layui-icon-return"></i> 返回首页
                </a>
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