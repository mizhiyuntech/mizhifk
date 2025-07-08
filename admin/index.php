<?php
session_start();
require_once 'auth.php';

// 获取统计数据
$totalRequirements = $db->count('requirements');
$pendingRequirements = $db->count('requirements', 'status = ?', ['pending']);
$processingRequirements = $db->count('requirements', 'status = ?', ['processing']);
$completedRequirements = $db->count('requirements', 'status = ?', ['completed']);
$totalCategories = $db->count('categories');

// 获取最新需求
$latestRequirements = $db->fetchAll("
    SELECT r.*, c.name as category_name 
    FROM requirements r 
    LEFT JOIN categories c ON r.category_id = c.id 
    ORDER BY r.created_at DESC 
    LIMIT 10
");

// 获取各状态统计
$statusStats = $db->fetchAll("
    SELECT status, COUNT(*) as count 
    FROM requirements 
    GROUP BY status
");

// 获取优先级统计
$priorityStats = $db->fetchAll("
    SELECT priority, COUNT(*) as count 
    FROM requirements 
    GROUP BY priority
");

$siteTitle = getSetting('site_title', '需求收集系统');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - <?php echo e($siteTitle); ?></title>
    <link rel="stylesheet" href="../layui/css/layui.css">
    <style>
        body { background-color: #f2f2f2; }
        .layui-layout-admin .layui-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .layui-nav .layui-nav-item a {
            color: rgba(255,255,255,0.9);
        }
        .layui-nav .layui-nav-item a:hover {
            color: white;
        }
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .recent-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .requirement-item {
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .requirement-item:last-child {
            border-bottom: none;
        }
        .requirement-title {
            font-weight: bold;
            margin-bottom: 8px;
        }
        .requirement-meta {
            font-size: 12px;
            color: #999;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            color: white;
            margin-left: 8px;
        }
        
        @media (max-width: 768px) {
            .layui-col-md3 { margin-bottom: 15px; }
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
                <li class="layui-nav-item layui-this"><a href="index.php">首页</a></li>
                <li class="layui-nav-item"><a href="requirements.php">需求管理</a></li>
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
            <!-- 统计卡片 -->
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md3">
                    <div class="stat-card">
                        <div class="stat-number" style="color: #1e9fff;"><?php echo $totalRequirements; ?></div>
                        <div class="stat-label">总需求数</div>
                    </div>
                </div>
                <div class="layui-col-md3">
                    <div class="stat-card">
                        <div class="stat-number" style="color: #ffb800;"><?php echo $pendingRequirements; ?></div>
                        <div class="stat-label">待处理</div>
                    </div>
                </div>
                <div class="layui-col-md3">
                    <div class="stat-card">
                        <div class="stat-number" style="color: #ff5722;"><?php echo $processingRequirements; ?></div>
                        <div class="stat-label">处理中</div>
                    </div>
                </div>
                <div class="layui-col-md3">
                    <div class="stat-card">
                        <div class="stat-number" style="color: #5fb878;"><?php echo $completedRequirements; ?></div>
                        <div class="stat-label">已完成</div>
                    </div>
                </div>
            </div>
            
            <div class="layui-row layui-col-space15">
                <!-- 最新需求 -->
                <div class="layui-col-md8">
                    <div class="recent-card">
                        <h3 style="margin: 0 0 20px; color: #333;">
                            <i class="layui-icon layui-icon-list"></i> 最新需求
                        </h3>
                        
                        <?php if (empty($latestRequirements)): ?>
                        <div style="text-align: center; color: #999; padding: 40px 0;">
                            <i class="layui-icon layui-icon-face-cry" style="font-size: 3em; display: block; margin-bottom: 10px;"></i>
                            暂无需求数据
                        </div>
                        <?php else: ?>
                        <?php foreach ($latestRequirements as $req): ?>
                        <div class="requirement-item">
                            <div class="requirement-title">
                                <a href="requirements.php?id=<?php echo $req['id']; ?>" style="color: #333; text-decoration: none;">
                                    <?php echo e(str_limit($req['title'], 50)); ?>
                                </a>
                                <span class="status-badge" style="background: <?php echo getStatusColor($req['status']); ?>">
                                    <?php echo getStatusText($req['status']); ?>
                                </span>
                            </div>
                            <div class="requirement-meta">
                                分类：<?php echo e($req['category_name'] ?: '未分类'); ?> | 
                                优先级：<?php echo getPriorityText($req['priority']); ?> | 
                                提交时间：<?php echo timeAgo($req['created_at']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div style="text-align: center; margin-top: 20px;">
                            <a href="requirements.php" class="layui-btn layui-btn-sm">查看全部需求</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- 统计图表 -->
                <div class="layui-col-md4">
                    <div class="recent-card">
                        <h3 style="margin: 0 0 20px; color: #333;">
                            <i class="layui-icon layui-icon-chart"></i> 状态统计
                        </h3>
                        
                        <?php foreach ($statusStats as $stat): ?>
                        <div style="margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span><?php echo getStatusText($stat['status']); ?></span>
                                <span><?php echo $stat['count']; ?></span>
                            </div>
                            <div class="layui-progress">
                                <div class="layui-progress-bar" 
                                     style="width: <?php echo $totalRequirements > 0 ? round($stat['count'] / $totalRequirements * 100) : 0; ?>%; 
                                            background: <?php echo getStatusColor($stat['status']); ?>;">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="recent-card" style="margin-top: 15px;">
                        <h3 style="margin: 0 0 20px; color: #333;">
                            <i class="layui-icon layui-icon-flag"></i> 优先级统计
                        </h3>
                        
                        <?php foreach ($priorityStats as $stat): ?>
                        <div style="margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span><?php echo getPriorityText($stat['priority']); ?></span>
                                <span><?php echo $stat['count']; ?></span>
                            </div>
                            <div class="layui-progress">
                                <div class="layui-progress-bar" 
                                     style="width: <?php echo $totalRequirements > 0 ? round($stat['count'] / $totalRequirements * 100) : 0; ?>%; 
                                            background: <?php echo getPriorityColor($stat['priority']); ?>;">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../layui/layui.js"></script>
    <script>
    layui.use(['element', 'layer'], function(){
        var element = layui.element;
        var layer = layui.layer;
    });
    </script>
</body>
</html>