<?php
session_start();

// 检查是否已安装
if (!file_exists('config.php')) {
    header('Location: install.php');
    exit;
}

require_once 'includes/database.php';
require_once 'includes/functions.php';

$db = Database::getInstance();

// 获取网站设置
$siteTitle = getSetting('site_title', '需求收集系统');
$siteDescription = getSetting('site_description', '欢迎提交您的需求建议');

// 获取分类列表
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY sort_order ASC");

$error = '';
$success = '';

// 处理表单提交
if ($_POST) {
    $token = $_POST['_token'] ?? '';
    
    if (!verifyCsrfToken($token)) {
        $error = '表单验证失败，请重新提交';
    } else {
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $contactName = trim($_POST['contact_name'] ?? '');
        $contactEmail = trim($_POST['contact_email'] ?? '');
        $contactPhone = trim($_POST['contact_phone'] ?? '');
        $priority = $_POST['priority'] ?? 'medium';
        
        // 验证必填字段
        if (empty($title)) {
            $error = '请填写需求标题';
        } elseif (empty($description)) {
            $error = '请填写需求描述';
        } elseif ($categoryId <= 0) {
            $error = '请选择需求分类';
        } elseif (!empty($contactEmail) && !isValidEmail($contactEmail)) {
            $error = '邮箱格式不正确';
        } elseif (!empty($contactPhone) && !isValidPhone($contactPhone)) {
            $error = '手机号格式不正确';
        } else {
            try {
                // 插入需求数据
                $requirementData = [
                    'category_id' => $categoryId,
                    'title' => $title,
                    'description' => $description,
                    'contact_name' => $contactName,
                    'contact_email' => $contactEmail,
                    'contact_phone' => $contactPhone,
                    'priority' => $priority,
                    'ip_address' => getClientIp(),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                ];
                
                $db->insert('requirements', $requirementData);
                $success = '需求提交成功！我们会尽快处理您的需求。';
                
                // 清空表单数据
                $_POST = [];
                
            } catch (Exception $e) {
                $error = '提交失败，请稍后重试';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($siteTitle); ?></title>
    <link rel="stylesheet" href="layui/css/layui.css">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 15px;
        }
        .main-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header-section h1 {
            margin: 0 0 10px;
            font-size: 2.5em;
            font-weight: 300;
        }
        .header-section p {
            margin: 0;
            opacity: 0.9;
            font-size: 1.1em;
        }
        .form-section {
            padding: 40px 30px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        .required {
            color: #ff5722;
        }
        .layui-input, .layui-select, .layui-textarea {
            border-radius: 8px;
            border: 2px solid #e8e8e8;
            transition: all 0.3s ease;
        }
        .layui-input:focus, .layui-select:focus, .layui-textarea:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .layui-textarea {
            min-height: 120px;
            resize: vertical;
        }
        .priority-options {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .priority-option {
            flex: 1;
            min-width: 120px;
        }
        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 15px 40px;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.3s ease;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        .admin-link {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 10px 15px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 12px;
            transition: background 0.3s ease;
        }
        .admin-link:hover {
            background: rgba(0,0,0,0.9);
            color: white;
        }
        
        /* 移动端适配 */
        @media (max-width: 768px) {
            body { padding: 10px 0; }
            .container { padding: 0 10px; }
            .header-section {
                padding: 30px 20px;
            }
            .header-section h1 {
                font-size: 2em;
            }
            .form-section {
                padding: 30px 20px;
            }
            .priority-options {
                flex-direction: column;
            }
            .priority-option {
                min-width: auto;
            }
            .admin-link {
                bottom: 10px;
                right: 10px;
                font-size: 11px;
                padding: 8px 12px;
            }
        }
        
        @media (max-width: 480px) {
            .header-section h1 {
                font-size: 1.8em;
            }
            .header-section p {
                font-size: 1em;
            }
            .form-section {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-card">
            <div class="header-section">
                <h1><?php echo e($siteTitle); ?></h1>
                <p><?php echo e($siteDescription); ?></p>
            </div>
            
            <div class="form-section">
                <?php if ($error): ?>
                <div class="layui-elem-quote layui-quote-nm" style="border-left: 5px solid #ff5722; color: #ff5722; margin-bottom: 25px;">
                    <i class="layui-icon layui-icon-close"></i> <?php echo e($error); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="layui-elem-quote layui-quote-nm" style="border-left: 5px solid #5fb878; color: #5fb878; margin-bottom: 25px;">
                    <i class="layui-icon layui-icon-ok"></i> <?php echo e($success); ?>
                </div>
                <?php endif; ?>
                
                <form class="layui-form" method="post">
                    <input type="hidden" name="_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="layui-row layui-col-space15">
                        <div class="layui-col-md12">
                            <div class="form-group">
                                <label>需求分类 <span class="required">*</span></label>
                                <select name="category_id" class="layui-input" lay-verify="required">
                                    <option value="">请选择需求分类</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                        <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo e($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>需求标题 <span class="required">*</span></label>
                        <input type="text" name="title" value="<?php echo e($_POST['title'] ?? ''); ?>" 
                               placeholder="请简要描述您的需求" class="layui-input" lay-verify="required">
                    </div>
                    
                    <div class="form-group">
                        <label>需求描述 <span class="required">*</span></label>
                        <textarea name="description" placeholder="请详细描述您的需求，包括具体的功能要求、使用场景等" 
                                  class="layui-textarea" lay-verify="required"><?php echo e($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="layui-row layui-col-space15">
                        <div class="layui-col-md6">
                            <div class="form-group">
                                <label>联系人姓名</label>
                                <input type="text" name="contact_name" value="<?php echo e($_POST['contact_name'] ?? ''); ?>" 
                                       placeholder="您的姓名" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-col-md6">
                            <div class="form-group">
                                <label>联系邮箱</label>
                                <input type="email" name="contact_email" value="<?php echo e($_POST['contact_email'] ?? ''); ?>" 
                                       placeholder="您的邮箱地址" class="layui-input">
                            </div>
                        </div>
                    </div>
                    
                    <div class="layui-row layui-col-space15">
                        <div class="layui-col-md6">
                            <div class="form-group">
                                <label>联系电话</label>
                                <input type="tel" name="contact_phone" value="<?php echo e($_POST['contact_phone'] ?? ''); ?>" 
                                       placeholder="您的手机号码" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-col-md6">
                            <div class="form-group">
                                <label>优先级</label>
                                <div class="priority-options">
                                    <div class="priority-option">
                                        <input type="radio" name="priority" value="low" title="低优先级" 
                                               <?php echo (($_POST['priority'] ?? 'medium') == 'low') ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="priority-option">
                                        <input type="radio" name="priority" value="medium" title="中优先级" 
                                               <?php echo (($_POST['priority'] ?? 'medium') == 'medium') ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="priority-option">
                                        <input type="radio" name="priority" value="high" title="高优先级" 
                                               <?php echo (($_POST['priority'] ?? 'medium') == 'high') ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-top: 40px;">
                        <button type="submit" class="layui-btn layui-btn-fluid submit-btn">
                            <i class="layui-icon layui-icon-release"></i> 提交需求
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <a href="admin/login.php" class="admin-link">
        <i class="layui-icon layui-icon-username"></i> 管理后台
    </a>
    
    <script src="layui/layui.js"></script>
    <script>
    layui.use(['form', 'layer'], function(){
        var form = layui.form;
        var layer = layui.layer;
        
        // 表单验证
        form.verify({
            title: function(value, item){
                if(!value){
                    return '请填写需求标题';
                }
                if(value.length < 5){
                    return '标题至少5个字符';
                }
                if(value.length > 100){
                    return '标题不能超过100个字符';
                }
            },
            description: function(value, item){
                if(!value){
                    return '请填写需求描述';
                }
                if(value.length < 10){
                    return '描述至少10个字符';
                }
                if(value.length > 2000){
                    return '描述不能超过2000个字符';
                }
            },
            contact_email: function(value, item){
                if(value && !/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(value)){
                    return '邮箱格式不正确';
                }
            },
            contact_phone: function(value, item){
                if(value && !/^1[3-9]\d{9}$/.test(value)){
                    return '手机号格式不正确';
                }
            }
        });
        
        // 表单提交
        form.on('submit()', function(data){
            var index = layer.load(2, {shade: 0.3});
            return true; // 允许表单提交
        });
    });
    </script>
</body>
</html>
