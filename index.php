<?php
require 'pdosql/mysql.php'; // 引入PDO数据库连接
require 'functions.php';    // 引入公共函数

// ========== API 路由处理 ==========
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    // 开启异常捕获，防止后端报错破坏JSON结构导致前端parsererror
    try {
        $action = $_GET['action'];
        $shop = $_GET['shop'] ?? '玖誉府店';
        
        require 'api_inventory.php';   // 盘点相关路由
        require 'api_transaction.php'; // 流水相关路由

        // 公共接口：调拨核对
        if ($action === 'get_transfer_check') {
            $month = $_GET['month'] ?? date('Y-m');
            $startDate = $month . '-01';
            $endDate = $month . '-31';
            $stmt = $pdo->prepare(
                "SELECT shop_name, SUM(amount) as total FROM transaction_records
                 WHERE category = '拿来刮刮乐' AND trans_date BETWEEN ? AND ? GROUP BY shop_name"
            );
            $stmt->execute([$startDate, $endDate]);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $result = ['shops' => [], 'total' => 0];
            $shops = ['玖誉府店' => 0, '黄金山店' => 0, '库存店' => 0];
            foreach ($list as $row) {
                if (isset($shops[$row['shop_name']])) {
                    $shops[$row['shop_name']] = floatval($row['total']);
                }
            }
            $result['shops'] = $shops;
            $result['total'] = array_sum($shops);

            echo json_encode(['code' => 0, 'data' => $result]);
            exit;
        }
    } catch (Throwable $e) {
        // 任何致命错误都会被拦截，强制返回合法JSON格式，前端弹窗显示具体原因
        echo json_encode(['code' => 1, 'msg' => '服务器报错: ' . $e->getMessage()]);
        exit;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <?php require 'header.php'; ?>
</head>
<body class="bg-light">
<div class="container-fluid mt-3">
    <?php require 'view_topbar.php'; ?>
    <?php require 'tab_inventory.php'; ?>
    <?php require 'tab_transaction.php'; ?>
</div>
<?php require 'footer.php'; ?>
