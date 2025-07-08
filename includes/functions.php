<?php
// 获取网站设置
function getSetting($key, $default = '') {
    $db = Database::getInstance();
    $result = $db->fetch("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
    return $result ? $result['setting_value'] : $default;
}

// 更新网站设置
function updateSetting($key, $value) {
    $db = Database::getInstance();
    $existing = $db->fetch("SELECT id FROM settings WHERE setting_key = ?", [$key]);
    
    if ($existing) {
        $db->update('settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
    } else {
        $db->insert('settings', ['setting_key' => $key, 'setting_value' => $value]);
    }
}

// 获取客户端IP
function getClientIp() {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (array_map('trim', explode(',', $_SERVER[$key])) as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

// 安全输出HTML
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// 截取字符串
function str_limit($value, $limit = 100, $end = '...') {
    if (mb_strwidth($value, 'UTF-8') <= $limit) {
        return $value;
    }
    return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
}

// 格式化时间
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return '刚刚';
    } elseif ($time < 3600) {
        return floor($time / 60) . '分钟前';
    } elseif ($time < 86400) {
        return floor($time / 3600) . '小时前';
    } elseif ($time < 2592000) {
        return floor($time / 86400) . '天前';
    } else {
        return date('Y-m-d', strtotime($datetime));
    }
}

// 验证邮箱
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// 验证手机号
function isValidPhone($phone) {
    return preg_match('/^1[3-9]\d{9}$/', $phone);
}

// 生成CSRF Token
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// 验证CSRF Token
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// 分页函数
function paginate($total, $perPage, $currentPage, $url) {
    $totalPages = ceil($total / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    
    $pagination = [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'prev_page' => $currentPage - 1,
        'next_page' => $currentPage + 1,
        'offset' => ($currentPage - 1) * $perPage
    ];
    
    return $pagination;
}

// 状态文本映射
function getStatusText($status) {
    $statusMap = [
        'pending' => '待处理',
        'processing' => '处理中',
        'processed' => '已处理',
        'completed' => '已完成',
        'rejected' => '已拒绝'
    ];
    return $statusMap[$status] ?? $status;
}

// 优先级文本映射
function getPriorityText($priority) {
    $priorityMap = [
        'low' => '低',
        'medium' => '中',
        'high' => '高'
    ];
    return $priorityMap[$priority] ?? $priority;
}

// 状态颜色映射
function getStatusColor($status) {
    $colorMap = [
        'pending' => '#ffb800',
        'processing' => '#1e9fff',
        'processed' => '#009688',
        'completed' => '#5fb878',
        'rejected' => '#ff5722'
    ];
    return $colorMap[$status] ?? '#666';
}

// 优先级颜色映射
function getPriorityColor($priority) {
    $colorMap = [
        'low' => '#5fb878',
        'medium' => '#ffb800',
        'high' => '#ff5722'
    ];
    return $colorMap[$priority] ?? '#666';
}

// JSON响应
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 重定向
function redirect($url, $code = 302) {
    http_response_code($code);
    header("Location: $url");
    exit;
}
?>