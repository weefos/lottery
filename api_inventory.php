<?php
if ($action === 'save_inventory') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['record_date'])) {
        echo json_encode(['code' => 1, 'msg' => '请先选择盘点日期']);
        exit;
    }
    $data['shop_name'] = $shop;

    $w_scat_total = calcScatter(json_encode($data['welfare_scatter']));
    $s_scat_total = calcScatter(json_encode($data['sports_scatter']));
    $w_whole_total = calcWhole(json_encode($data['welfare_whole']));
    $s_whole_total = calcWhole(json_encode($data['sports_whole']));

    $stmt = $pdo->prepare(
        "INSERT INTO inventory_records (
            shop_name, record_date, zero_cash, zero_cash_expr,
            dianao, dianao_expr, diangua, diangua_expr,
            sports_lottery, sports_lottery_expr, wechat, wechat_expr,
            alipay, alipay_expr, bank_card, bank_card_expr,
            welfare_scatter, w_scat_total, sports_scatter, s_scat_total,
            welfare_whole, w_whole_total, sports_whole, s_whole_total,
            debts, total_amount, remark
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            zero_cash=VALUES(zero_cash), zero_cash_expr=VALUES(zero_cash_expr),
            dianao=VALUES(dianao), dianao_expr=VALUES(dianao_expr),
            diangua=VALUES(diangua), diangua_expr=VALUES(diangua_expr),
            sports_lottery=VALUES(sports_lottery), sports_lottery_expr=VALUES(sports_lottery_expr),
            wechat=VALUES(wechat), wechat_expr=VALUES(wechat_expr),
            alipay=VALUES(alipay), alipay_expr=VALUES(alipay_expr),
            bank_card=VALUES(bank_card), bank_card_expr=VALUES(bank_card_expr),
            welfare_scatter=VALUES(welfare_scatter), w_scat_total=VALUES(w_scat_total),
            sports_scatter=VALUES(sports_scatter), s_scat_total=VALUES(s_scat_total),
            welfare_whole=VALUES(welfare_whole), w_whole_total=VALUES(w_whole_total),
            sports_whole=VALUES(sports_whole), s_whole_total=VALUES(s_whole_total),
            debts=VALUES(debts), total_amount=VALUES(total_amount), remark=VALUES(remark)"
    );
    $debtsJson = json_encode($data['debts'] ?? [], JSON_UNESCAPED_UNICODE);
    $stmt->execute([
        $data['shop_name'], $data['record_date'],
        $data['zero_cash'], $data['zero_cash_expr'],
        $data['dianao'], $data['dianao_expr'],
        $data['diangua'], $data['diangua_expr'],
        $data['sports_lottery'], $data['sports_lottery_expr'],
        $data['wechat'], $data['wechat_expr'],
        $data['alipay'], $data['alipay_expr'],
        $data['bank_card'], $data['bank_card_expr'],
        json_encode($data['welfare_scatter']), $w_scat_total,
        json_encode($data['sports_scatter']), $s_scat_total,
        json_encode($data['welfare_whole']), $w_whole_total,
        json_encode($data['sports_whole']), $s_whole_total,
        $debtsJson, $data['total_amount'], $data['remark']
    ]);
    echo json_encode(['code' => 0, 'msg' => '自动暂存成功']);
    exit;
}

if ($action === 'get_current_inventory') {
    $date = $_GET['date'] ?? '';
    if (empty($date)) {
        echo json_encode(['code' => 0, 'data' => null]);
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM inventory_records WHERE shop_name = ? AND record_date = ?");
    $stmt->execute([$shop, $date]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['code' => 0, 'data' => $data]);
    exit;
}

if ($action === 'get_inventory_list') {
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = 18;
    $offset = ($page - 1) * $limit;

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM inventory_records WHERE shop_name = ?");
    $stmtCount->execute([$shop]);
    $total = $stmtCount->fetchColumn();
    $totalPages = ceil($total / $limit);

    $stmt = $pdo->prepare(
        "SELECT * FROM inventory_records WHERE shop_name = ? ORDER BY record_date DESC LIMIT ?, ?"
    );
    $stmt->execute([$shop, $offset, $limit]);
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $pc_categories = "'快乐八销量', '双色球销量', '3D销量', '七乐彩销量'";

    for ($i = 0; $i < count($list); $i++) {
        $current = $list[$i];

        $list[$i]['w_scat_total'] = floatval($current['w_scat_total'] ?? calcScatter($current['welfare_scatter']));
        $list[$i]['s_scat_total'] = floatval($current['s_scat_total'] ?? calcScatter($current['sports_scatter']));
        $list[$i]['w_whole_total'] = floatval($current['w_whole_total'] ?? calcWhole($current['welfare_whole']));
        $list[$i]['s_whole_total'] = floatval($current['s_whole_total'] ?? calcWhole($current['sports_whole']));

        $welfare_gua = $list[$i]['w_scat_total'] + $list[$i]['w_whole_total'];
        $sports_gua = $list[$i]['s_scat_total'] + $list[$i]['s_whole_total'];
        $list[$i]['welfare_gua'] = $welfare_gua;
        $list[$i]['sports_gua'] = $sports_gua;
        $list[$i]['bank_card'] = floatval($current['bank_card']);

        $stmtPrev = $pdo->prepare(
            "SELECT * FROM inventory_records WHERE shop_name = ? AND record_date < ? ORDER BY record_date DESC LIMIT 1"
        );
        $stmtPrev->execute([$shop, $current['record_date']]);
        $prev = $stmtPrev->fetch(PDO::FETCH_ASSOC);

        if ($prev) {
            $prev_w_scat = floatval($prev['w_scat_total'] ?? calcScatter($prev['welfare_scatter']));
            $prev_s_scat = floatval($prev['s_scat_total'] ?? calcScatter($prev['sports_scatter']));
            $prev_w_whole = floatval($prev['w_whole_total'] ?? calcWhole($prev['welfare_whole']));
            $prev_s_whole = floatval($prev['s_whole_total'] ?? calcWhole($prev['sports_whole']));
            
            $prev_welfare_gua = $prev_w_scat + $prev_w_whole;
            $prev_sports_gua = $prev_s_scat + $prev_s_whole;

            $stmt2 = $pdo->prepare(
                "SELECT category, SUM(amount) as total FROM transaction_records
                 WHERE shop_name = ? AND trans_date > ? AND trans_date <= ? GROUP BY category"
            );
            $stmt2->execute([$shop, $prev['record_date'], $current['record_date']]);
            $trans = $stmt2->fetchAll(PDO::FETCH_KEY_PAIR);

            $ding_piao_w = floatval($trans['福彩刮刮乐订票'] ?? 0);
            $ding_piao_s = floatval($trans['体彩刮刮乐订票'] ?? 0);
            $na_lai_gua = floatval($trans['拿来刮刮乐'] ?? 0);

            $list[$i]['gua_sales'] = $prev_welfare_gua + $prev_sports_gua + $na_lai_gua + $ding_piao_w + $ding_piao_s - $welfare_gua - $sports_gua;
            
            $welfare_sales = $prev_welfare_gua + $na_lai_gua + $ding_piao_w - $welfare_gua;
            $sports_sales = $prev_sports_gua + $ding_piao_s - $sports_gua;

            $datetime1 = new DateTime($prev['record_date']);
            $datetime2 = new DateTime($current['record_date']);
            $interval = $datetime1->diff($datetime2);
            $days = $interval->days;

            $list[$i]['welfare_daily_sales'] = $days > 0 ? $welfare_sales / $days : 0;
            $list[$i]['sports_daily_sales'] = $days > 0 ? $sports_sales / $days : 0;
        } else {
            $list[$i]['gua_sales'] = 0;
            $list[$i]['welfare_daily_sales'] = 0;
            $list[$i]['sports_daily_sales'] = 0;
        }

        $stmtDayPc = $pdo->prepare(
            "SELECT COALESCE(SUM(amount),0) FROM transaction_records
             WHERE shop_name = ? AND category IN ($pc_categories) AND trans_date = ?"
        );
        $stmtDayPc->execute([$shop, $current['record_date']]);
        $list[$i]['day_pc_sales'] = floatval($stmtDayPc->fetchColumn());

        $recMonth = substr($current['record_date'], 0, 7);
        $stmtMonthPc = $pdo->prepare(
            "SELECT COALESCE(SUM(amount),0) FROM transaction_records
             WHERE shop_name = ? AND category IN ($pc_categories) AND trans_date >= ? AND trans_date <= ?"
        );
        $stmtMonthPc->execute([$shop, $recMonth . '-01', $current['record_date']]);
        $list[$i]['month_pc_sales'] = floatval($stmtMonthPc->fetchColumn());
    }

    echo json_encode([
        'code' => 0, 'data' => $list,
        'totalPages' => $totalPages, 'currentPage' => $page
    ]);
    exit;
}
