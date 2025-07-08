<?php
session_start();
require_once 'auth.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('参数错误');
}

// 获取需求详情
$requirement = $db->fetch("
    SELECT r.*, c.name as category_name 
    FROM requirements r 
    LEFT JOIN categories c ON r.category_id = c.id 
    WHERE r.id = ?
", [$id]);

if (!$requirement) {
    die('需求不存在');
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>需求详情</title>
    <link rel="stylesheet" href="../layui/css/layui.css">
    <style>
        body { 
            background: white; 
            padding: 20px; 
            font-family: 'Microsoft YaHei', sans-serif;
        }
        .detail-section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        .detail-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .detail-label {
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .detail-value {
            color: #666;
            line-height: 1.6;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            color: white;
            font-weight: normal;
        }
        .priority-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            color: white;
            margin-left: 10px;
        }
        .meta-info {
            background: #f8f8f8;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .meta-item {
            margin-bottom: 8px;
            font-size: 13px;
            color: #666;
        }
        .meta-item:last-child {
            margin-bottom: 0;
        }
        
        @media (max-width: 768px) {
            body { padding: 15px; }
            .detail-label { font-size: 13px; }
            .detail-value { font-size: 13px; }
        }
    </style>
</head>
<body>
    <div class="detail-section">
        <div class="detail-label">需求标题</div>
        <div class="detail-value" style="font-size: 18px; font-weight: bold; color: #333;">
            <?php echo e($requirement['title']); ?>
            <span class="status-badge" style="background: <?php echo getStatusColor($requirement['status']); ?>">
                <?php echo getStatusText($requirement['status']); ?>
            </span>
            <span class="priority-badge" style="background: <?php echo getPriorityColor($requirement['priority']); ?>">
                <?php echo getPriorityText($requirement['priority']); ?>
            </span>
        </div>
    </div>
    
    <div class="detail-section">
        <div class="detail-label">需求分类</div>
        <div class="detail-value">
            <?php echo e($requirement['category_name'] ?: '未分类'); ?>
        </div>
    </div>
    
    <div class="detail-section">
        <div class="detail-label">需求描述</div>
        <div class="detail-value" style="white-space: pre-wrap;">
            <?php echo e($requirement['description']); ?>
        </div>
    </div>
    
    <?php if ($requirement['contact_name'] || $requirement['contact_email'] || $requirement['contact_phone']): ?>
    <div class="detail-section">
        <div class="detail-label">联系信息</div>
        <div class="detail-value">
            <?php if ($requirement['contact_name']): ?>
            <div><strong>姓名：</strong><?php echo e($requirement['contact_name']); ?></div>
            <?php endif; ?>
            
            <?php if ($requirement['contact_email']): ?>
            <div><strong>邮箱：</strong>
                <a href="mailto:<?php echo e($requirement['contact_email']); ?>" style="color: #1e9fff;">
                    <?php echo e($requirement['contact_email']); ?>
                </a>
            </div>
            <?php endif; ?>
            
            <?php if ($requirement['contact_phone']): ?>
            <div><strong>电话：</strong>
                <a href="tel:<?php echo e($requirement['contact_phone']); ?>" style="color: #1e9fff;">
                    <?php echo e($requirement['contact_phone']); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="meta-info">
        <div class="meta-item">
            <i class="layui-icon layui-icon-time"></i>
            提交时间：<?php echo date('Y-m-d H:i:s', strtotime($requirement['created_at'])); ?>
            （<?php echo timeAgo($requirement['created_at']); ?>）
        </div>
        
        <?php if ($requirement['updated_at'] !== $requirement['created_at']): ?>
        <div class="meta-item">
            <i class="layui-icon layui-icon-edit"></i>
            更新时间：<?php echo date('Y-m-d H:i:s', strtotime($requirement['updated_at'])); ?>
            （<?php echo timeAgo($requirement['updated_at']); ?>）
        </div>
        <?php endif; ?>
        
        <div class="meta-item">
            <i class="layui-icon layui-icon-location"></i>
            IP地址：<?php echo e($requirement['ip_address'] ?: '未知'); ?>
        </div>
        
        <?php if ($requirement['user_agent']): ?>
        <div class="meta-item">
            <i class="layui-icon layui-icon-cellphone"></i>
            用户代理：<?php echo e(str_limit($requirement['user_agent'], 100)); ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="../layui/layui.js"></script>
</body>
</html>