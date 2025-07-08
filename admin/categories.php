<?php
session_start();
require_once 'auth.php';

$message = '';
$messageType = '';

// 处理操作
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $token = $_POST['_token'] ?? '';
    
    if (!verifyCsrfToken($token)) {
        $message = '表单验证失败';
        $messageType = 'error';
    } else {
        switch ($action) {
            case 'add':
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $sortOrder = (int)($_POST['sort_order'] ?? 0);
                
                if (empty($name)) {
                    $message = '分类名称不能为空';
                    $messageType = 'error';
                } else {
                    // 检查分类名是否已存在
                    $exists = $db->fetch("SELECT id FROM categories WHERE name = ?", [$name]);
                    if ($exists) {
                        $message = '分类名称已存在';
                        $messageType = 'error';
                    } else {
                        $db->insert('categories', [
                            'name' => $name,
                            'description' => $description,
                            'sort_order' => $sortOrder
                        ]);
                        $message = '分类添加成功';
                        $messageType = 'success';
                    }
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $sortOrder = (int)($_POST['sort_order'] ?? 0);
                
                if (empty($name)) {
                    $message = '分类名称不能为空';
                    $messageType = 'error';
                } else {
                    // 检查分类名是否已存在（排除当前编辑的分类）
                    $exists = $db->fetch("SELECT id FROM categories WHERE name = ? AND id != ?", [$name, $id]);
                    if ($exists) {
                        $message = '分类名称已存在';
                        $messageType = 'error';
                    } else {
                        $db->update('categories', [
                            'name' => $name,
                            'description' => $description,
                            'sort_order' => $sortOrder
                        ], 'id = ?', [$id]);
                        $message = '分类更新成功';
                        $messageType = 'success';
                    }
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                
                // 检查是否有关联的需求
                $count = $db->count('requirements', 'category_id = ?', [$id]);
                if ($count > 0) {
                    $message = "无法删除，该分类下还有 $count 个需求";
                    $messageType = 'error';
                } else {
                    $db->delete('categories', 'id = ?', [$id]);
                    $message = '分类删除成功';
                    $messageType = 'success';
                }
                break;
        }
    }
}

// 获取分类列表
$categories = $db->fetchAll("
    SELECT c.*, COUNT(r.id) as requirement_count 
    FROM categories c 
    LEFT JOIN requirements r ON c.id = r.category_id 
    GROUP BY c.id 
    ORDER BY c.sort_order ASC, c.id ASC
");

$siteTitle = getSetting('site_title', '需求收集系统');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分类管理 - <?php echo e($siteTitle); ?></title>
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
        }
        .category-item {
            padding: 15px;
            border: 1px solid #e6e6e6;
            border-radius: 8px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .category-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .category-name {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }
        .category-description {
            color: #666;
            margin-bottom: 10px;
            line-height: 1.4;
        }
        .category-meta {
            font-size: 12px;
            color: #999;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .category-actions {
            display: flex;
            gap: 8px;
        }
        
        @media (max-width: 768px) {
            .category-meta {
                flex-direction: column;
                align-items: flex-start;
            }
            .category-actions {
                margin-top: 10px;
            }
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
                <li class="layui-nav-item layui-this"><a href="categories.php">分类管理</a></li>
                <li class="layui-nav-item"><a href="settings.php">系统设置</a></li>
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
            <div class="content-card">
                <h2 style="margin: 0 0 20px; color: #333;">
                    <i class="layui-icon layui-icon-tabs"></i> 分类管理
                </h2>
                
                <?php if ($message): ?>
                <div class="layui-elem-quote layui-quote-nm" style="border-left: 5px solid <?php echo $messageType === 'success' ? '#5fb878' : '#ff5722'; ?>; color: <?php echo $messageType === 'success' ? '#5fb878' : '#ff5722'; ?>;">
                    <i class="layui-icon <?php echo $messageType === 'success' ? 'layui-icon-ok' : 'layui-icon-close'; ?>"></i> <?php echo e($message); ?>
                </div>
                <?php endif; ?>
                
                <!-- 添加分类按钮 -->
                <div style="margin-bottom: 20px;">
                    <button class="layui-btn" onclick="addCategory()">
                        <i class="layui-icon layui-icon-add-1"></i> 添加分类
                    </button>
                    <span style="margin-left: 15px; color: #999;">
                        共 <?php echo count($categories); ?> 个分类
                    </span>
                </div>
                
                <!-- 分类列表 -->
                <?php if (empty($categories)): ?>
                <div style="text-align: center; color: #999; padding: 60px 0;">
                    <i class="layui-icon layui-icon-face-cry" style="font-size: 3em; display: block; margin-bottom: 15px;"></i>
                    <p>暂无分类，请添加第一个分类</p>
                    <button class="layui-btn layui-btn-sm" onclick="addCategory()">添加分类</button>
                </div>
                <?php else: ?>
                <?php foreach ($categories as $category): ?>
                <div class="category-item">
                    <div class="category-name">
                        <?php echo e($category['name']); ?>
                    </div>
                    
                    <?php if ($category['description']): ?>
                    <div class="category-description">
                        <?php echo e($category['description']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="category-meta">
                        <div>
                            排序：<?php echo $category['sort_order']; ?> | 
                            需求数：<?php echo $category['requirement_count']; ?> | 
                            创建时间：<?php echo date('Y-m-d H:i', strtotime($category['created_at'])); ?>
                        </div>
                        <div class="category-actions">
                            <button class="layui-btn layui-btn-xs layui-btn-normal" 
                                    onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                <i class="layui-icon layui-icon-edit"></i> 编辑
                            </button>
                            <button class="layui-btn layui-btn-xs layui-btn-danger" 
                                    onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo e($category['name']); ?>', <?php echo $category['requirement_count']; ?>)">
                                <i class="layui-icon layui-icon-delete"></i> 删除
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 隐藏的表单 -->
    <form id="categoryForm" method="post" style="display: none;">
        <input type="hidden" name="_token" value="<?php echo generateCsrfToken(); ?>">
        <input type="hidden" name="action" id="formAction">
        <input type="hidden" name="id" id="formId">
    </form>
    
    <script src="../layui/layui.js"></script>
    <script>
    layui.use(['element', 'form', 'layer'], function(){
        var element = layui.element;
        var form = layui.form;
        var layer = layui.layer;
    });
    
    // 添加分类
    function addCategory() {
        var html = `
        <form class="layui-form" style="padding: 20px;">
            <div class="layui-form-item">
                <label class="layui-form-label">分类名称 *</label>
                <div class="layui-input-block">
                    <input type="text" name="name" placeholder="请输入分类名称" class="layui-input" lay-verify="required">
                </div>
            </div>
            <div class="layui-form-item layui-form-text">
                <label class="layui-form-label">分类描述</label>
                <div class="layui-input-block">
                    <textarea name="description" placeholder="请输入分类描述" class="layui-textarea"></textarea>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">排序值</label>
                <div class="layui-input-block">
                    <input type="number" name="sort_order" value="0" placeholder="数字越小排序越靠前" class="layui-input">
                </div>
            </div>
        </form>
        `;
        
        layer.open({
            title: '添加分类',
            content: html,
            area: ['500px', '400px'],
            btn: ['确定', '取消'],
            yes: function(index, layero) {
                var formData = layero.find('form').serialize();
                submitForm('add', formData);
            }
        });
    }
    
    // 编辑分类
    function editCategory(category) {
        var html = `
        <form class="layui-form" style="padding: 20px;">
            <div class="layui-form-item">
                <label class="layui-form-label">分类名称 *</label>
                <div class="layui-input-block">
                    <input type="text" name="name" value="${category.name}" placeholder="请输入分类名称" class="layui-input" lay-verify="required">
                </div>
            </div>
            <div class="layui-form-item layui-form-text">
                <label class="layui-form-label">分类描述</label>
                <div class="layui-input-block">
                    <textarea name="description" placeholder="请输入分类描述" class="layui-textarea">${category.description || ''}</textarea>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">排序值</label>
                <div class="layui-input-block">
                    <input type="number" name="sort_order" value="${category.sort_order}" placeholder="数字越小排序越靠前" class="layui-input">
                </div>
            </div>
        </form>
        `;
        
        layer.open({
            title: '编辑分类',
            content: html,
            area: ['500px', '400px'],
            btn: ['确定', '取消'],
            yes: function(index, layero) {
                var formData = layero.find('form').serialize() + '&id=' + category.id;
                submitForm('edit', formData);
            }
        });
    }
    
    // 删除分类
    function deleteCategory(id, name, requirementCount) {
        if (requirementCount > 0) {
            layer.alert('该分类下还有 ' + requirementCount + ' 个需求，无法删除', {icon: 2});
            return;
        }
        
        layer.confirm('确定要删除分类 "' + name + '" 吗？', {icon: 3, title:'提示'}, function(index){
            submitForm('delete', 'id=' + id);
        });
    }
    
    // 提交表单
    function submitForm(action, data) {
        var form = document.getElementById('categoryForm');
        form.querySelector('input[name="action"]').value = action;
        
        // 解析并添加数据
        var params = new URLSearchParams(data);
        params.forEach(function(value, key) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        });
        
        form.submit();
    }
    </script>
</body>
</html>