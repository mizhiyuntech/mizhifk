<?php
session_start();
require_once 'auth.php';

$message = '';
$messageType = '';

// 处理表单提交
if ($_POST) {
    $token = $_POST['_token'] ?? '';
    
    if (!verifyCsrfToken($token)) {
        $message = '表单验证失败';
        $messageType = 'error';
    } else {
        $siteTitle = trim($_POST['site_title'] ?? '');
        $siteDescription = trim($_POST['site_description'] ?? '');
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $itemsPerPage = (int)($_POST['items_per_page'] ?? 20);
        
        if (empty($siteTitle)) {
            $message = '网站标题不能为空';
            $messageType = 'error';
        } elseif (!empty($adminEmail) && !isValidEmail($adminEmail)) {
            $message = '管理员邮箱格式不正确';
            $messageType = 'error';
        } elseif ($itemsPerPage < 5 || $itemsPerPage > 100) {
            $message = '每页显示数量必须在5-100之间';
            $messageType = 'error';
        } else {
            try {
                updateSetting('site_title', $siteTitle);
                updateSetting('site_description', $siteDescription);
                updateSetting('admin_email', $adminEmail);
                updateSetting('items_per_page', $itemsPerPage);
                
                $message = '设置保存成功';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = '保存失败：' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// 获取当前设置
$settings = [
    'site_title' => getSetting('site_title', '需求收集系统'),
    'site_description' => getSetting('site_description', '欢迎提交您的需求建议'),
    'admin_email' => getSetting('admin_email', ''),
    'items_per_page' => getSetting('items_per_page', 20)
];

// 获取统计信息
$stats = [
    'total_requirements' => $db->count('requirements'),
    'total_categories' => $db->count('categories'),
    'total_admins' => $db->count('admins'),
    'disk_usage' => 'N/A' // 可以后续添加磁盘使用量统计
];

$siteTitle = $settings['site_title'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统设置 - <?php echo e($siteTitle); ?></title>
    <link rel="stylesheet" href="../layui/css/layui.css">
    <style>
        body { background-color: #f2f2f2; }
        .layui-layout-admin .layui-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .layui-nav .layui-nav-item a {
            color: rgba(255,255,255,0.9);
        }
        .content-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stat-item {
            text-align: center;
            padding: 20px;
            border: 1px solid #e6e6e6;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .stat-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 8px;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .form-section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #667eea;
        }
    </style>
</head>
<body class="layui-layout-body">
    <div class="layui-layout layui-layout-admin">
        <div class="layui-header">
            <div class="layui-logo" style="color: white; font-weight: bold;">
                <i class="layui-icon layui-icon-home"></i> <?php echo e($siteTitle); ?>
            </div>
            <ul class="layui-nav layui-layout-left">
                <li class="layui-nav-item"><a href="index.php">首页</a></li>
                <li class="layui-nav-item"><a href="requirements.php">需求管理</a></li>
                <li class="layui-nav-item"><a href="categories.php">分类管理</a></li>
                <li class="layui-nav-item layui-this"><a href="settings.php">系统设置</a></li>
            </ul>
            <ul class="layui-nav layui-layout-right">
                <li class="layui-nav-item layui-hide layui-show-md-inline-block">
                    <a href="../index.php" target="_blank">
                        <i class="layui-icon layui-icon-website"></i> 前台
                    </a>
                </li>
                <li class="layui-nav-item layui-hide layui-show-md-inline-block">
                    <a href="javascript:;">
                        <i class="layui-icon layui-icon-username"></i> <?php echo e($currentAdmin['username']); ?>
                    </a>
                    <dl class="layui-nav-child">
                        <dd><a href="?action=logout">退出</a></dd>
                    </dl>
                </li>
            </ul>
        </div>
        
        <div class="layui-body" style="padding: 20px;">
            <!-- 系统统计 -->
            <div class="content-card">
                <h2 style="margin: 0 0 20px; color: #333;">
                    <i class="layui-icon layui-icon-chart"></i> 系统统计
                </h2>
                
                <div class="layui-row layui-col-space15">
                    <div class="layui-col-md3">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $stats['total_requirements']; ?></div>
                            <div class="stat-label">总需求数</div>
                        </div>
                    </div>
                    <div class="layui-col-md3">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $stats['total_categories']; ?></div>
                            <div class="stat-label">分类数</div>
                        </div>
                    </div>
                    <div class="layui-col-md3">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $stats['total_admins']; ?></div>
                            <div class="stat-label">管理员数</div>
                        </div>
                    </div>
                    <div class="layui-col-md3">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $stats['disk_usage']; ?></div>
                            <div class="stat-label">磁盘使用</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 系统设置 -->
            <div class="content-card">
                <h2 style="margin: 0 0 20px; color: #333;">
                    <i class="layui-icon layui-icon-set"></i> 系统设置
                </h2>
                
                <?php if ($message): ?>
                <div class="layui-elem-quote layui-quote-nm" style="border-left: 5px solid <?php echo $messageType === 'success' ? '#5fb878' : '#ff5722'; ?>; color: <?php echo $messageType === 'success' ? '#5fb878' : '#ff5722'; ?>; margin-bottom: 20px;">
                    <i class="layui-icon <?php echo $messageType === 'success' ? 'layui-icon-ok' : 'layui-icon-close'; ?>"></i> <?php echo e($message); ?>
                </div>
                <?php endif; ?>
                
                <form class="layui-form" method="post">
                    <input type="hidden" name="_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <!-- 网站基本信息 -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="layui-icon layui-icon-website"></i> 网站基本信息
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">网站标题 *</label>
                            <div class="layui-input-block">
                                <input type="text" name="site_title" value="<?php echo e($settings['site_title']); ?>" 
                                       placeholder="请输入网站标题" class="layui-input" lay-verify="required">
                                <div class="layui-form-mid layui-word-aux">显示在浏览器标题栏和网站头部</div>
                            </div>
                        </div>
                        
                        <div class="layui-form-item layui-form-text">
                            <label class="layui-form-label">网站描述</label>
                            <div class="layui-input-block">
                                <textarea name="site_description" placeholder="请输入网站描述" class="layui-textarea"><?php echo e($settings['site_description']); ?></textarea>
                                <div class="layui-form-mid layui-word-aux">显示在网站首页的描述信息</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 系统配置 -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="layui-icon layui-icon-set-sm"></i> 系统配置
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">管理员邮箱</label>
                            <div class="layui-input-block">
                                <input type="email" name="admin_email" value="<?php echo e($settings['admin_email']); ?>" 
                                       placeholder="请输入管理员邮箱" class="layui-input">
                                <div class="layui-form-mid layui-word-aux">用于接收系统通知（可选）</div>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">每页显示数量</label>
                            <div class="layui-input-block">
                                <input type="number" name="items_per_page" value="<?php echo e($settings['items_per_page']); ?>" 
                                       min="5" max="100" placeholder="20" class="layui-input">
                                <div class="layui-form-mid layui-word-aux">后台列表每页显示的记录数量（5-100）</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 系统信息 -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="layui-icon layui-icon-about"></i> 系统信息
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">PHP版本</label>
                            <div class="layui-input-block">
                                <div class="layui-form-mid"><?php echo PHP_VERSION; ?></div>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">系统版本</label>
                            <div class="layui-input-block">
                                <div class="layui-form-mid">需求收集系统 v1.0</div>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">服务器时间</label>
                            <div class="layui-input-block">
                                <div class="layui-form-mid"><?php echo date('Y-m-d H:i:s'); ?></div>
                            </div>
                        </div>
                        
                        <div class="layui-form-item">
                            <label class="layui-form-label">数据库</label>
                            <div class="layui-input-block">
                                <div class="layui-form-mid">MySQL</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="layui-form-item">
                        <div class="layui-input-block">
                            <button type="submit" class="layui-btn layui-btn-lg" style="padding: 0 40px;">
                                <i class="layui-icon layui-icon-ok"></i> 保存设置
                            </button>
                            <button type="button" class="layui-btn layui-btn-primary layui-btn-lg" 
                                    onclick="window.location.reload()" style="margin-left: 10px;">
                                <i class="layui-icon layui-icon-refresh"></i> 重置
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../layui/layui.js"></script>
    <script>
    layui.use(['element', 'form', 'layer'], function(){
        var element = layui.element;
        var form = layui.form;
        var layer = layui.layer;
        
        // 表单验证
        form.verify({
            site_title: function(value, item){
                if(!value){
                    return '请输入网站标题';
                }
                if(value.length < 2){
                    return '网站标题至少2个字符';
                }
                if(value.length > 50){
                    return '网站标题不能超过50个字符';
                }
            },
            admin_email: function(value, item){
                if(value && !/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(value)){
                    return '邮箱格式不正确';
                }
            },
            items_per_page: function(value, item){
                if(value && (value < 5 || value > 100)){
                    return '每页显示数量必须在5-100之间';
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