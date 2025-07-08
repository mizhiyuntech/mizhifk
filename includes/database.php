<?php
class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        if (!file_exists(__DIR__ . '/../config.php')) {
            die('配置文件不存在，请先运行安装程序');
        }
        
        $config = include __DIR__ . '/../config.php';
        
        try {
            $dsn = "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}";
            $this->pdo = new PDO($dsn, $config['database']['username'], $config['database']['password']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // 设置时区
            date_default_timezone_set($config['site']['timezone']);
        } catch (PDOException $e) {
            die('数据库连接失败：' . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        return $this->pdo->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        foreach (array_keys($data) as $key) {
            $set[] = "{$key} = :{$key}";
        }
        
        // 转换WHERE条件中的位置参数为命名参数
        $whereNamedParams = [];
        $paramIndex = 0;
        $whereConverted = preg_replace_callback('/\?/', function() use (&$paramIndex, $whereParams, &$whereNamedParams) {
            $paramName = 'where_param_' . $paramIndex;
            if (isset($whereParams[$paramIndex])) {
                $whereNamedParams[$paramName] = $whereParams[$paramIndex];
            }
            $paramIndex++;
            return ':' . $paramName;
        }, $where);
        
        $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE {$whereConverted}";
        return $this->query($sql, array_merge($data, $whereNamedParams));
    }
    
    public function delete($table, $where, $params = []) {
        // 转换WHERE条件中的位置参数为命名参数
        $whereNamedParams = [];
        $paramIndex = 0;
        $whereConverted = preg_replace_callback('/\?/', function() use (&$paramIndex, $params, &$whereNamedParams) {
            $paramName = 'param_' . $paramIndex;
            if (isset($params[$paramIndex])) {
                $whereNamedParams[$paramName] = $params[$paramIndex];
            }
            $paramIndex++;
            return ':' . $paramName;
        }, $where);
        
        $sql = "DELETE FROM {$table} WHERE {$whereConverted}";
        return $this->query($sql, $whereNamedParams);
    }
    
    public function count($table, $where = '1=1', $params = []) {
        // 转换WHERE条件中的位置参数为命名参数
        $whereNamedParams = [];
        $paramIndex = 0;
        $whereConverted = preg_replace_callback('/\?/', function() use (&$paramIndex, $params, &$whereNamedParams) {
            $paramName = 'param_' . $paramIndex;
            if (isset($params[$paramIndex])) {
                $whereNamedParams[$paramName] = $params[$paramIndex];
            }
            $paramIndex++;
            return ':' . $paramName;
        }, $where);
        
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$whereConverted}";
        $result = $this->fetch($sql, $whereNamedParams);
        return $result['count'];
    }
}
?>