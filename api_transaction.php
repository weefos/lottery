<?php
if ($action === 'save_transaction') {
    $data = json_decode(file_get_contents('php://input'), true);
    $category = $data['category'];
    
    if ($category === '电脑票销量') {
        $items = $data['items'] ?? [];
        $stmt = $pdo->prepare(
            "INSERT INTO transaction_records (shop_name, trans_date, category, amount_expression, amount, bank_change, remark)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $count = 0;
        foreach ($items as $item) {
            $amount = abs(floatval($item['amount']));
            if ($amount > 0) {
                $stmt->execute([
                    $shop, $data['trans_date'], $item['category'],
                    $item['amount_expression'], $amount, 0, $data['remark'] ?? ''
                ]);
                $count++;
            }
        }
        echo json_encode(['code' => 0, 'msg' => $count > 0 ? '电脑票销量保存成功' : '未输入有效金额']);
        exit;
    }

    $amount = abs($data['amount']);
    $bank_change = 0;
    if ($category === '拿来刮刮乐') $bank_change = -$amount;
    if ($category === '微信支付宝提现') $bank_change = $amount;
    if ($category === '零钱存钱') $bank_change = $amount;
    if ($category === '销售终端充值') $bank_change = -$amount;
    if ($category === '采购饮料') $bank_change = -$amount;
    
    $stmt = $pdo->prepare(
        "INSERT INTO transaction_records (shop_name, trans_date, category, amount_expression, amount, bank_change, remark)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $shop, $data['trans_date'], $category,
        $data['amount_expression'], $amount, $bank_change, $data['remark']
    ]);
    echo json_encode(['code' => 0, 'msg' => '流水保存成功']);
    exit;
}

if ($action === 'get_transaction_list') {
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = 18;
    $offset = ($page - 1) * $limit;
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime('-45 days'));

    $stmtCount = $pdo->prepare(
        "SELECT COUNT(*) FROM transaction_records WHERE shop_name = ? AND trans_date BETWEEN ? AND ?"
    );
    $stmtCount->execute([$shop, $startDate, $endDate]);
    $total = $stmtCount->fetchColumn();
    $totalPages = ceil($total / $limit);

    $stmt = $pdo->prepare(
        "SELECT * FROM transaction_records WHERE shop_name = ? AND trans_date BETWEEN ? AND ?
         ORDER BY trans_date DESC, id DESC LIMIT ?, ?"
    );
    $stmt->execute([$shop, $startDate, $endDate, $offset, $limit]);
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'code' => 0, 'data' => $list,
        'totalPages' => $totalPages, 'currentPage' => $page
    ]);
    exit;
}

if ($action === 'get_category_stats') {
    $category = $_GET['category'] ?? '';
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime('-45 days'));
    $monthStart = date('Y-m-01');

    if ($category === '银行卡') {
        $stmt = $pdo->prepare(
            "SELECT * FROM transaction_records
             WHERE shop_name = ? AND bank_change != 0 AND trans_date BETWEEN ? AND ?
             ORDER BY trans_date DESC, id DESC"
        );
        $stmt->execute([$shop, $startDate, $endDate]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT * FROM transaction_records
             WHERE shop_name = ? AND category = ? AND trans_date BETWEEN ? AND ?
             ORDER BY trans_date DESC, id DESC"
        );
        $stmt->execute([$shop, $category, $startDate, $endDate]);
    }
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $extra = [];
    if ($category === '银行卡') {
        $stmt2 = $pdo->prepare(
            "SELECT bank_card, record_date FROM inventory_records
             WHERE shop_name = ? AND record_date <= ? ORDER BY record_date DESC LIMIT 1"
        );
        $stmt2->execute([$shop, $endDate]);
        $lastInv = $stmt2->fetch();
        $baseBalance = floatval($lastInv['bank_card'] ?? 0);
        $baseDate = $lastInv['record_date'] ?? $endDate;

        $stmt3 = $pdo->prepare(
            "SELECT SUM(bank_change) as total FROM transaction_records
             WHERE shop_name = ? AND trans_date > ? AND trans_date <= ?"
        );
        $stmt3->execute([$shop, $baseDate, $endDate]);
        $change = floatval($stmt3->fetchColumn());
        $extra['balance'] = $baseBalance + $change;
    } else {
        $stmt2 = $pdo->prepare(
            "SELECT SUM(amount) as total FROM transaction_records
             WHERE shop_name = ? AND category = ? AND trans_date >= ?"
        );
        $stmt2->execute([$shop, $category, $monthStart]);
        $extra['month_total'] = floatval($stmt2->fetchColumn());
    }

    echo json_encode(['code' => 0, 'data' => $list, 'extra' => $extra]);
    exit;
}
