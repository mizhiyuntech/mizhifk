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
            case 'update_status':
                $id = (int)$_POST['id'];
                $status = $_POST['status'];
                if (in_array($status, ['pending', 'processing', 'processed', 'completed', 'rejected'])) {
                    $db->update('requirements', ['status' => $status], 'id = ?', [$id]);
                    $message = '状态更新成功';
                    $messageType = 'success';
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                $db->delete('requirements', 'id = ?', [$id]);
                $message = '删除成功';
                $messageType = 'success';
                break;
                
            case 'batch_delete':
                $ids = $_POST['ids'] ?? [];
                if (!empty($ids)) {
                    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                    $db->query("DELETE FROM requirements WHERE id IN ($placeholders)", $ids);
                    $message = '批量删除成功';
                    $messageType = 'success';
                }
                break;
        }
    }
}

// 搜索和筛选
$search = trim($_GET['search'] ?? '');
$categoryFilter = (int)($_GET['category'] ?? 0);
$statusFilter = $_GET['status'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';

// 分页
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)getSetting('items_per_page', 20);

// 构建查询条件
$whereConditions = ['1=1'];
$params = [];

if ($search) {
    $whereConditions[] = '(r.title LIKE ? OR r.description LIKE ? OR r.contact_name LIKE ?)';
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($categoryFilter > 0) {
    $whereConditions[] = 'r.category_id = ?';
    $params[] = $categoryFilter;
}

if ($statusFilter) {
    $whereConditions[] = 'r.status = ?';
    $params[] = $statusFilter;
}

if ($priorityFilter) {
    $whereConditions[] = 'r.priority = ?';
    $params[] = $priorityFilter;
}

$whereClause = implode(' AND ', $whereConditions);

// 获取总数
$total = $db->fetch("
    SELECT COUNT(*) as count 
    FROM requirements r 
    WHERE $whereClause
", $params)['count'];

// 分页计算
$pagination = paginate($total, $perPage, $page, '');

// 获取需求列表
$requirements = $db->fetchAll("
    SELECT r.*, c.name as category_name 
    FROM requirements r 
    LEFT JOIN categories c ON r.category_id = c.id 
    WHERE $whereClause 
    ORDER BY r.created_at DESC 
    LIMIT {$pagination['offset']}, $perPage
", $params);

// 获取分类列表用于筛选
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY sort_order ASC");

$siteTitle = getSetting('site_title', '需求收集系统');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>需求管理 - <?php echo e($siteTitle); ?></title>
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
        .search-bar {
            margin-bottom: 20px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            color: white;
        }
        .priority-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 11px;
            color: white;
        }
        .requirement-description {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        @media (max-width: 768px) {
            .layui-body { padding: 10px !important; }
            .content-card { padding: 15px; }
            .layui-table { font-size: 12px; }
            .requirement-description { max-width: 120px; }
            .search-bar .layui-inline { 
                display: block; 
                margin-bottom: 10px; 
                width: 100%;
            }
            .search-bar .layui-input,
            .search-bar .layui-btn { 
                width: 100%; 
            }
        }
        @media (max-width: 480px) {
            .layui-table thead th:nth-child(3),
            .layui-table tbody td:nth-child(3),
            .layui-table thead th:nth-child(4),
            .layui-table tbody td:nth-child(4) {
                display: none;
            }
            .requirement-description { max-width: 80px; }
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
                <li class="layui-nav-item layui-this"><a href="requirements.php">需求管理</a></li>
                <li class="layui-nav-item"><a href="categories.php">分类管理</a></li>
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
                    <i class="layui-icon layui-icon-list"></i> 需求管理
                </h2>
                
                <?php if ($message): ?>
                <div class="layui-elem-quote layui-quote-nm" style="border-left: 5px solid <?php echo $messageType === 'success' ? '#5fb878' : '#ff5722'; ?>; color: <?php echo $messageType === 'success' ? '#5fb878' : '#ff5722'; ?>;">
                    <i class="layui-icon <?php echo $messageType === 'success' ? 'layui-icon-ok' : 'layui-icon-close'; ?>"></i> <?php echo e($message); ?>
                </div>
                <?php endif; ?>
                
                <!-- 搜索筛选 -->
                <div class="search-bar">
                    <form class="layui-form layui-form-pane" method="get">
                        <div class="layui-form-item">
                            <div class="layui-inline">
                                <input type="text" name="search" value="<?php echo e($search); ?>" 
                                       placeholder="搜索标题、描述或联系人" class="layui-input">
                            </div>
                            <div class="layui-inline">
                                <select name="category" lay-search="">
                                    <option value="">全部分类</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($cat['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="layui-inline">
                                <select name="status">
                                    <option value="">全部状态</option>
                                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>待处理</option>
                                    <option value="processing" <?php echo $statusFilter === 'processing' ? 'selected' : ''; ?>>处理中</option>
                                    <option value="processed" <?php echo $statusFilter === 'processed' ? 'selected' : ''; ?>>已处理</option>
                                    <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>已完成</option>
                                    <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>已拒绝</option>
                                </select>
                            </div>
                            <div class="layui-inline">
                                <select name="priority">
                                    <option value="">全部优先级</option>
                                    <option value="low" <?php echo $priorityFilter === 'low' ? 'selected' : ''; ?>>低</option>
                                    <option value="medium" <?php echo $priorityFilter === 'medium' ? 'selected' : ''; ?>>中</option>
                                    <option value="high" <?php echo $priorityFilter === 'high' ? 'selected' : ''; ?>>高</option>
                                </select>
                            </div>
                            <div class="layui-inline">
                                <button type="submit" class="layui-btn">搜索</button>
                                <a href="requirements.php" class="layui-btn layui-btn-primary">重置</a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- 批量操作 -->
                <div style="margin-bottom: 15px;">
                    <button class="layui-btn layui-btn-sm layui-btn-danger" onclick="batchDelete()">
                        <i class="layui-icon layui-icon-delete"></i> 批量删除
                    </button>
                    <span style="margin-left: 15px; color: #999;">
                        共 <?php echo $total; ?> 条记录
                    </span>
                </div>
                
                <!-- 需求列表 -->
                <table class="layui-table" lay-even>
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" lay-skin="primary" lay-filter="checkAll">
                            </th>
                            <th>标题</th>
                            <th width="100">分类</th>
                            <th width="150">联系人</th>
                            <th width="80">优先级</th>
                            <th width="80">状态</th>
                            <th width="120">提交时间</th>
                            <th width="150">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requirements)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: #999; padding: 40px 0;">
                                <i class="layui-icon layui-icon-face-cry" style="font-size: 2em; display: block; margin-bottom: 10px;"></i>
                                暂无数据
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($requirements as $req): ?>
                        <tr>
                            <td>
                                <input type="checkbox" lay-skin="primary" value="<?php echo $req['id']; ?>" lay-filter="checkItem">
                            </td>
                            <td>
                                <div style="font-weight: bold; margin-bottom: 5px;">
                                    <?php echo e(str_limit($req['title'], 40)); ?>
                                </div>
                                <div class="requirement-description" style="color: #999; font-size: 12px;" title="<?php echo e($req['description']); ?>">
                                    <?php echo e(str_limit($req['description'], 60)); ?>
                                </div>
                            </td>
                            <td><?php echo e($req['category_name'] ?: '未分类'); ?></td>
                            <td>
                                <div><?php echo e($req['contact_name'] ?: '-'); ?></div>
                                <?php if ($req['contact_email']): ?>
                                <div style="font-size: 11px; color: #999;"><?php echo e($req['contact_email']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="priority-badge" style="background: <?php echo getPriorityColor($req['priority']); ?>">
                                    <?php echo getPriorityText($req['priority']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge" style="background: <?php echo getStatusColor($req['status']); ?>">
                                    <?php echo getStatusText($req['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div><?php echo date('m-d H:i', strtotime($req['created_at'])); ?></div>
                                <div style="font-size: 11px; color: #999;"><?php echo timeAgo($req['created_at']); ?></div>
                            </td>
                            <td>
                                <button class="layui-btn layui-btn-xs" onclick="viewRequirement(<?php echo $req['id']; ?>)">查看</button>
                                <button class="layui-btn layui-btn-xs layui-btn-normal" onclick="updateStatus(<?php echo $req['id']; ?>, '<?php echo $req['status']; ?>')">状态</button>
                                <button class="layui-btn layui-btn-xs layui-btn-danger" onclick="deleteRequirement(<?php echo $req['id']; ?>)">删除</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- 分页 -->
                <?php if ($pagination['total_pages'] > 1): ?>
                <div id="pagination"></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 隐藏的表单 -->
    <form id="hiddenForm" method="post" style="display: none;">
        <input type="hidden" name="_token" value="<?php echo generateCsrfToken(); ?>">
        <input type="hidden" name="action" id="hiddenAction">
        <input type="hidden" name="id" id="hiddenId">
        <input type="hidden" name="status" id="hiddenStatus">
    </form>
    
    <script src="../layui/layui.js"></script>
    <script>
    layui.use(['element', 'form', 'layer', 'laypage'], function(){
        var element = layui.element;
        var form = layui.form;
        var layer = layui.layer;
        var laypage = layui.laypage;
        
        // 分页
        <?php if ($pagination['total_pages'] > 1): ?>
        laypage.render({
            elem: 'pagination',
            count: <?php echo $total; ?>,
            limit: <?php echo $perPage; ?>,
            curr: <?php echo $page; ?>,
            jump: function(obj, first){
                if(!first){
                    var url = new URL(window.location);
                    url.searchParams.set('page', obj.curr);
                    window.location.href = url.toString();
                }
            }
        });
        <?php endif; ?>
        
        // 全选
        form.on('checkbox(checkAll)', function(data){
            var checked = data.elem.checked;
            $('input[lay-filter="checkItem"]').each(function(){
                this.checked = checked;
            });
            form.render('checkbox');
        });
        
        // 单选
        form.on('checkbox(checkItem)', function(data){
            var allChecked = true;
            $('input[lay-filter="checkItem"]').each(function(){
                if(!this.checked){
                    allChecked = false;
                    return false;
                }
            });
            $('input[lay-filter="checkAll"]')[0].checked = allChecked;
            form.render('checkbox');
        });
    });
    
    // 查看需求详情
    function viewRequirement(id) {
        layer.open({
            type: 2,
            title: '需求详情',
            area: ['80%', '80%'],
            content: 'requirement_detail.php?id=' + id
        });
    }
    
    // 更新状态
    function updateStatus(id, currentStatus) {
        var statusOptions = [
            {value: 'pending', text: '待处理', color: '#ff9800'},
            {value: 'processing', text: '处理中', color: '#1e9fff'},
            {value: 'processed', text: '已处理', color: '#009688'},
            {value: 'completed', text: '已完成', color: '#5fb878'},
            {value: 'rejected', text: '已拒绝', color: '#ff5722'}
        ];
        
        var html = '<div style="padding: 20px;">' +
                  '<form class="layui-form" lay-filter="statusForm">' +
                  '<div class="layui-form-item">' +
                  '<label class="layui-form-label">状态</label>' +
                  '<div class="layui-input-block">' +
                  '<select name="status" lay-verify="required" lay-filter="statusSelect">';
        
        statusOptions.forEach(function(option) {
            html += '<option value="' + option.value + '"' + 
                   (option.value === currentStatus ? ' selected' : '') + 
                   '>' + option.text + '</option>';
        });
        
        html += '</select></div></div></form></div>';
        
        layer.open({
            type: 1,
            title: '更新状态',
            content: html,
            area: ['400px', '250px'],
            btn: ['确定', '取消'],
            success: function(layero, index) {
                // 重新渲染表单
                layui.form.render('select', 'statusForm');
            },
            yes: function(index) {
                var newStatus = layero.find('select[name="status"]').val();
                if (newStatus !== currentStatus) {
                    document.getElementById('hiddenAction').value = 'update_status';
                    document.getElementById('hiddenId').value = id;
                    document.getElementById('hiddenStatus').value = newStatus;
                    document.getElementById('hiddenForm').submit();
                } else {
                    layer.close(index);
                }
            }
        });
    }
    
    // 删除需求
    function deleteRequirement(id) {
        layer.confirm('确定要删除这个需求吗？', {icon: 3, title:'提示'}, function(index){
            document.getElementById('hiddenAction').value = 'delete';
            document.getElementById('hiddenId').value = id;
            document.getElementById('hiddenForm').submit();
        });
    }
    
    // 批量删除
    function batchDelete() {
        var checkedIds = [];
        $('input[lay-filter="checkItem"]:checked').each(function(){
            checkedIds.push($(this).val());
        });
        
        if(checkedIds.length === 0) {
            layer.msg('请选择要删除的项目');
            return;
        }
        
        layer.confirm('确定要删除选中的 ' + checkedIds.length + ' 个需求吗？', {icon: 3, title:'提示'}, function(index){
            var form = document.getElementById('hiddenForm');
            form.querySelector('input[name="action"]').value = 'batch_delete';
            
            // 添加选中的ID
            checkedIds.forEach(function(id) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                form.appendChild(input);
            });
            
            form.submit();
        });
    }
    </script>
</body>
</html>