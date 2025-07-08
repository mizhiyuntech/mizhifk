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
</head>
<body>
    <div class="layui-container" style="margin-top: 30px;">
        <div class="layui-row">
            <div class="layui-col-md8 layui-col-md-offset2">
                <!-- 页面头部 -->
                <div class="layui-card">
                    <div class="layui-card-header" style="text-align: center; background: linear-gradient(135deg, #1e9fff 0%, #5fb878 100%); color: white;">
                        <h1 style="margin: 20px 0; font-size: 28px;"><?php echo e($siteTitle); ?></h1>
                        <p style="margin: 0 0 20px; font-size: 16px; opacity: 0.9;"><?php echo e($siteDescription); ?></p>
                    </div>
                    
                    <div class="layui-card-body">
                        <?php if ($error): ?>
                        <div class="layui-elem-quote layui-quote-nm" style="border-left: 5px solid #ff5722; color: #ff5722;">
                            <i class="layui-icon layui-icon-close"></i> <?php echo e($error); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="layui-elem-quote layui-quote-nm" style="border-left: 5px solid #5fb878; color: #5fb878;">
                            <i class="layui-icon layui-icon-ok"></i> <?php echo e($success); ?>
                        </div>
                        <?php endif; ?>
                        
                        <form class="layui-form" method="post" lay-filter="requirementForm">
                            <input type="hidden" name="_token" value="<?php echo generateCsrfToken(); ?>">
                            
                            <!-- 需求分类 -->
                            <div class="layui-form-item">
                                <label class="layui-form-label">需求分类<span style="color: #ff5722;">*</span></label>
                                <div class="layui-input-block">
                                    <select name="category_id" lay-verify="required" lay-search="">
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
                            
                            <!-- 需求标题 -->
                            <div class="layui-form-item">
                                <label class="layui-form-label">需求标题<span style="color: #ff5722;">*</span></label>
                                <div class="layui-input-block">
                                    <input type="text" name="title" value="<?php echo e($_POST['title'] ?? ''); ?>" 
                                           placeholder="请简要描述您的需求" class="layui-input" lay-verify="title">
                                </div>
                            </div>
                            
                            <!-- 需求描述 -->
                            <div class="layui-form-item layui-form-text">
                                <label class="layui-form-label">需求描述<span style="color: #ff5722;">*</span></label>
                                <div class="layui-input-block">
                                    <textarea name="description" placeholder="请详细描述您的需求，包括具体的功能要求、使用场景等" 
                                              class="layui-textarea" lay-verify="description" style="min-height: 120px;"><?php echo e($_POST['description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <!-- 联系信息 -->
                            <div class="layui-row layui-col-space15">
                                <div class="layui-col-md6">
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">联系人姓名</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="contact_name" value="<?php echo e($_POST['contact_name'] ?? ''); ?>" 
                                                   placeholder="您的姓名" class="layui-input">
                                        </div>
                                    </div>
                                </div>
                                <div class="layui-col-md6">
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">联系邮箱</label>
                                        <div class="layui-input-block">
                                            <input type="email" name="contact_email" value="<?php echo e($_POST['contact_email'] ?? ''); ?>" 
                                                   placeholder="您的邮箱地址" class="layui-input" lay-verify="contact_email">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="layui-row layui-col-space15">
                                <div class="layui-col-md6">
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">联系电话</label>
                                        <div class="layui-input-block">
                                            <input type="tel" name="contact_phone" value="<?php echo e($_POST['contact_phone'] ?? ''); ?>" 
                                                   placeholder="您的手机号码" class="layui-input" lay-verify="contact_phone">
                                        </div>
                                    </div>
                                </div>
                                <div class="layui-col-md6">
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">优先级</label>
                                        <div class="layui-input-block">
                                            <input type="radio" name="priority" value="low" title="低优先级" 
                                                   <?php echo (($_POST['priority'] ?? 'medium') == 'low') ? 'checked' : ''; ?>>
                                            <input type="radio" name="priority" value="medium" title="中优先级" 
                                                   <?php echo (($_POST['priority'] ?? 'medium') == 'medium') ? 'checked' : ''; ?>>
                                            <input type="radio" name="priority" value="high" title="高优先级" 
                                                   <?php echo (($_POST['priority'] ?? 'medium') == 'high') ? 'checked' : ''; ?>>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 提交按钮 -->
                            <div class="layui-form-item">
                                <div class="layui-input-block">
                                    <button type="submit" class="layui-btn layui-btn-fluid layui-btn-lg" lay-submit lay-filter="submit">
                                        <i class="layui-icon layui-icon-release"></i> 提交需求
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
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
        form.on('submit(submit)', function(data){
            var index = layer.load(2, {shade: 0.3});
            return true; // 允许表单提交
        });
    });
    </script>
</body>
</html>
