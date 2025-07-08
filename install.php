<?php
session_start();

// 检查配置文件是否已存在
if (file_exists('config.php')) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_POST) {
    $db_host = trim($_POST['db_host']);
    $db_name = trim($_POST['db_name']);
    $db_user = trim($_POST['db_user']);
    $db_pass = trim($_POST['db_pass']);
    $admin_user = trim($_POST['admin_user']);
    $admin_pass = trim($_POST['admin_pass']);
    $site_title = trim($_POST['site_title']);
    
    if (empty($db_host) || empty($db_name) || empty($db_user) || empty($admin_user) || empty($admin_pass)) {
        $error = '请填写所有必填项';
    } else {
        try {
            // 测试数据库连接
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 创建表结构
            $sql = "
            CREATE TABLE IF NOT EXISTS `categories` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL,
                `description` text,
                `sort_order` int(11) DEFAULT 0,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            
            CREATE TABLE IF NOT EXISTS `requirements` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `category_id` int(11) NOT NULL,
                `title` varchar(255) NOT NULL,
                `description` text NOT NULL,
                `contact_name` varchar(100),
                `contact_email` varchar(100),
                `contact_phone` varchar(20),
                `priority` enum('low','medium','high') DEFAULT 'medium',
                `status` enum('pending','processing','completed','rejected') DEFAULT 'pending',
                `ip_address` varchar(45),
                `user_agent` text,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `category_id` (`category_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            
            CREATE TABLE IF NOT EXISTS `admins` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `username` varchar(50) NOT NULL,
                `password` varchar(255) NOT NULL,
                `email` varchar(100),
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                `last_login` timestamp NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            
            CREATE TABLE IF NOT EXISTS `settings` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `setting_key` varchar(100) NOT NULL,
                `setting_value` text,
                `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `setting_key` (`setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
            
            $pdo->exec($sql);
            
            // 插入管理员账号
            $admin_pass_hash = password_hash($admin_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
            $stmt->execute([$admin_user, $admin_pass_hash]);
            
            // 插入默认设置
            $settings = [
                'site_title' => $site_title ?: '需求收集系统',
                'site_description' => '欢迎提交您的需求建议',
                'admin_email' => '',
                'items_per_page' => '20'
            ];
            
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($settings as $key => $value) {
                $stmt->execute([$key, $value]);
            }
            
            // 插入默认分类
            $stmt = $pdo->prepare("INSERT INTO categories (name, description, sort_order) VALUES (?, ?, ?)");
            $categories = [
                ['功能建议', '新功能或功能改进建议', 1],
                ['问题反馈', '系统问题或Bug反馈', 2],
                ['技术支持', '使用过程中的技术问题', 3],
                ['其他', '其他类型的需求', 4]
            ];
            foreach ($categories as $cat) {
                $stmt->execute($cat);
            }
            
            // 创建配置文件
            $config_content = "<?php
return [
    'database' => [
        'host' => '$db_host',
        'dbname' => '$db_name',
        'username' => '$db_user',
        'password' => '$db_pass',
        'charset' => 'utf8mb4'
    ],
    'site' => [
        'timezone' => 'Asia/Shanghai'
    ]
];
?>";
            
            file_put_contents('config.php', $config_content);
            
            $success = '安装成功！正在跳转到首页...';
            echo "<script>setTimeout(function(){ window.location.href='index.php'; }, 2000);</script>";
            
        } catch (PDOException $e) {
            $error = '数据库连接失败：' . $e->getMessage();
        } catch (Exception $e) {
            $error = '安装失败：' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统安装 - 需求收集系统</title>
    <link rel="stylesheet" href="layui/css/layui.css">
    <style>
        body { background-color: #f2f2f2; }
        .install-container { 
            max-width: 600px; 
            margin: 50px auto; 
            background: white; 
            border-radius: 8px; 
            box-shadow: 0 2px 12px rgba(0,0,0,0.1); 
        }
        .install-header { 
            text-align: center; 
            padding: 30px 0; 
            border-bottom: 1px solid #e6e6e6; 
        }
        .install-body { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: bold; 
            color: #333; 
        }
        .required { color: #ff5722; }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1 style="color: #2f4f4f; margin: 0;">需求收集系统</h1>
            <p style="color: #999; margin: 10px 0 0;">欢迎使用系统安装向导</p>
        </div>
        
        <div class="install-body">
            <?php if ($error): ?>
            <div class="layui-elem-quote layui-quote-nm" style="border-left: 5px solid #ff5722; color: #ff5722;">
                <i class="layui-icon layui-icon-close"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="layui-elem-quote layui-quote-nm" style="border-left: 5px solid #5fb878; color: #5fb878;">
                <i class="layui-icon layui-icon-ok"></i> <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>
            
            <form class="layui-form" method="post">
                <fieldset class="layui-elem-field">
                    <legend>数据库配置</legend>
                    <div class="layui-field-box">
                        <div class="form-group">
                            <label>数据库主机 <span class="required">*</span></label>
                            <input type="text" name="db_host" value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost'); ?>" 
                                   placeholder="localhost" class="layui-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label>数据库名称 <span class="required">*</span></label>
                            <input type="text" name="db_name" value="<?php echo htmlspecialchars($_POST['db_name'] ?? ''); ?>" 
                                   placeholder="请输入数据库名称" class="layui-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label>数据库用户名 <span class="required">*</span></label>
                            <input type="text" name="db_user" value="<?php echo htmlspecialchars($_POST['db_user'] ?? ''); ?>" 
                                   placeholder="请输入数据库用户名" class="layui-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label>数据库密码</label>
                            <input type="password" name="db_pass" value="<?php echo htmlspecialchars($_POST['db_pass'] ?? ''); ?>" 
                                   placeholder="请输入数据库密码" class="layui-input">
                        </div>
                    </div>
                </fieldset>
                
                <fieldset class="layui-elem-field">
                    <legend>管理员账号</legend>
                    <div class="layui-field-box">
                        <div class="form-group">
                            <label>管理员用户名 <span class="required">*</span></label>
                            <input type="text" name="admin_user" value="<?php echo htmlspecialchars($_POST['admin_user'] ?? ''); ?>" 
                                   placeholder="请输入管理员用户名" class="layui-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label>管理员密码 <span class="required">*</span></label>
                            <input type="password" name="admin_pass" value="" 
                                   placeholder="请输入管理员密码" class="layui-input" required>
                        </div>
                    </div>
                </fieldset>
                
                <fieldset class="layui-elem-field">
                    <legend>网站配置</legend>
                    <div class="layui-field-box">
                        <div class="form-group">
                            <label>网站标题</label>
                            <input type="text" name="site_title" value="<?php echo htmlspecialchars($_POST['site_title'] ?? '需求收集系统'); ?>" 
                                   placeholder="需求收集系统" class="layui-input">
                        </div>
                    </div>
                </fieldset>
                
                <div class="form-group">
                    <button type="submit" class="layui-btn layui-btn-fluid layui-btn-lg">
                        <i class="layui-icon layui-icon-ok"></i> 开始安装
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="layui/layui.js"></script>
    <script>
    layui.use(['form'], function(){
        var form = layui.form;
    });
    </script>
</body>
</html>