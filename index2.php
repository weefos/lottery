<?php
require 'pdosql/mysql.php'; // 引入PDO数据库连接

// ========== API 路由处理 ==========
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_GET['action'];
    $shop = $_GET['shop'] ?? '玖誉府店';

    if ($action === 'save_inventory') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['record_date'])) {
            echo json_encode(['code' => 1, 'msg' => '请先选择盘点日期']);
            exit;
        }
        $data['shop_name'] = $shop;

        // 计算散票和整票的小计金额
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

    if ($action === 'save_transaction') {
        $data = json_decode(file_get_contents('php://input'), true);
        $category = $data['category'];
        
        // 处理拆分的电脑票销量
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

        // 原有其他流水保存逻辑
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

        // 电脑票销量相关分类
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

            // 当日电脑票销量 = 快乐八+双色球+3D+七乐彩
            $stmtDayPc = $pdo->prepare(
                "SELECT COALESCE(SUM(amount),0) FROM transaction_records
                 WHERE shop_name = ? AND category IN ($pc_categories) AND trans_date = ?"
            );
            $stmtDayPc->execute([$shop, $current['record_date']]);
            $list[$i]['day_pc_sales'] = floatval($stmtDayPc->fetchColumn());

            // 当月电脑票累计销量
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

    exit;
}

function calcScatter($scat) {
    $sum = 0;
    foreach (json_decode($scat, true) ?? [] as $face => $expr)
        $sum += $face * floatval(evalExpr($expr));
    return $sum;
}

function calcWhole($whole) {
    $sum = 0;
    foreach (json_decode($whole, true) ?? [] as $face => $expr)
        $sum += $face * floatval(evalExpr($expr));
    return $sum;
}

function calcScatterWhole($s_scat, $w_scat, $s_whole, $w_whole) {
    return calcScatter($s_scat) + calcScatter($w_scat) + calcWhole($s_whole) + calcWhole($w_whole);
}

function evalExpr($expr) {
    if (!$expr) return 0;
    $expr = preg_replace('/\s+/', '', $expr);
    if (!preg_match('/^[0-9+\-\.]+$/', $expr)) return 0;
    $expr = str_replace('--', '+', $expr);
    $expr = preg_replace('/\+-|-\+/', '-', $expr);
    $tokens = preg_split('/([+-])/', $expr, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    $sum = 0;
    $sign = 1;
    foreach ($tokens as $token) {
        if ($token === '+') $sign = 1;
        elseif ($token === '-') $sign = -1;
        else $sum += $sign * floatval($token);
    }
    return $sum;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>彩票销售记账对账系统</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/4.6.1/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card { margin-bottom: 15px }
        .expr-input { width: 100% }
        .subtotal { font-weight: bold; color: #dc3545; font-size: 1.0rem; }
        .top-bar {
            background: #fff; padding: 15px; margin-bottom: 20px;
            border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .block-title {
            font-weight: bold; border-bottom: 2px solid #007bff;
            padding-bottom: 5px; margin-bottom: 10px; display: flex; justify-content: space-between;
        }
        .save-indicator { font-size: 12px; color: #28a745; display: none; }
        .form-group label { width: 100%; }
        .check-panel {
            background: #f8f9fa; border: 1px solid #e9ecef;
            border-radius: 8px; padding: 20px; margin-bottom: 20px; display: none;
        }
        .check-panel h5 { color: #343a40; }
        .check-shop-val { font-size: 1.5rem; font-weight: bold; }
        .td-remark-ellipsis {
            max-width: 120px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: default;
        }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid mt-3">

    <!-- 全局店铺与日期选择 -->
    <div class="top-bar form-row align-items-center">
        <div class="col-md-2">
            <label class="mb-0 font-weight-bold">当前操作店铺：</label>
            <select id="current_shop" class="form-control">
                <option>玖誉府店</option>
                <option>黄金山店</option>
                <option>库存店</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="mb-0 font-weight-bold">盘点日期：</label>
            <input type="date" id="inv_date" class="form-control" value="">
        </div>
        <div class="col-md-3">
            <label class="mb-0 font-weight-bold">调拨核对月份：</label>
            <div class="input-group">
                <input type="month" id="top_check_month" class="form-control" value="<?= date('Y-m') ?>">
                <div class="input-group-append">
                    <button class="btn btn-warning" onclick="toggleTransferCheck()">调拨核对</button>
                </div>
            </div>
        </div>
        <div class="col-md-5 text-right align-self-end">
            <span id="save_status" class="save-indicator">✓ 数据已自动暂存</span>
        </div>
    </div>

    <!-- 调拨核对详情展开面板 -->
    <div id="transfer_check_panel" class="check-panel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">调拨核对详情 <small class="text-muted">(理论合计值应等于0)</small></h5>
            <button class="btn btn-sm btn-outline-secondary" onclick="$('#transfer_check_panel').slideUp()">收起</button>
        </div>
        <div class="row text-center">
            <div class="col-md-3">
                <div class="card shadow-sm"><div class="card-body">
                    <h6 class="text-muted">玖誉府店</h6>
                    <div class="check-shop-val text-primary" id="check_jyf">0.00</div>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm"><div class="card-body">
                    <h6 class="text-muted">黄金山店</h6>
                    <div class="check-shop-val text-primary" id="check_hjs">0.00</div>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm"><div class="card-body">
                    <h6 class="text-muted">库存店</h6>
                    <div class="check-shop-val text-primary" id="check_kc">0.00</div>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card border-dark shadow-sm"><div class="card-body">
                    <h6 class="text-dark">调拨合计</h6>
                    <div class="check-shop-val text-danger" id="check_total">0.00</div>
                </div></div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item"><a class="nav-link active" href="#tab1" data-toggle="tab">盘点录入</a></li>
        <li class="nav-item"><a class="nav-link" href="#tab3" data-toggle="tab">盘点表</a></li>
        <li class="nav-item"><a class="nav-link" href="#tab2" data-toggle="tab">流水录入</a></li>
        <li class="nav-item"><a class="nav-link" href="#tab4" data-toggle="tab">分项统计</a></li>
    </ul>

    <div class="tab-content mt-2">

        <!-- 盘点录入 -->
        <div class="tab-pane fade show active" id="tab1">
            <div class="card">
                <div class="card-body">
                    <div class="form-row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>零钱 <span class="subtotal float-right">小计：￥<span id="zero_subtotal">0.00</span></span></label>
                                <input type="text" class="form-control expr-input" data-cat="zero" placeholder="如：10+20-5">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>电脑余额 <span class="subtotal float-right">小计：￥<span id="dianao_subtotal">0.00</span></span></label>
                                <input type="text" class="form-control expr-input" data-cat="dianao">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>电刮余额 <span class="subtotal float-right">小计：￥<span id="diangua_subtotal">0.00</span></span></label>
                                <input type="text" class="form-control expr-input" data-cat="diangua">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>体彩 <span class="subtotal float-right">小计：￥<span id="sports_lottery_subtotal">0.00</span></span></label>
                                <input type="text" class="form-control expr-input" data-cat="sports_lottery">
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>微信 <span class="subtotal float-right">小计：￥<span id="wechat_subtotal">0.00</span></span></label>
                                <input type="text" class="form-control expr-input" data-cat="wechat">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>支付宝 <span class="subtotal float-right">小计：￥<span id="alipay_subtotal">0.00</span></span></label>
                                <input type="text" class="form-control expr-input" data-cat="alipay">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>银行卡 <span class="subtotal float-right">小计：￥<span id="bank_card_subtotal">0.00</span></span></label>
                                <input type="text" class="form-control expr-input" data-cat="bank_card">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>备注</label>
                                <input type="text" id="inv_remark" class="form-control expr-input" data-cat="remark">
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-header">福彩散票 <span class="subtotal float-right">小计：￥<span id="w_scat_subtotal">0.00</span></span></div>
                                <div class="card-body">
                                    <div class="form-row">
                                        <div class="col-3"><input type="text" class="form-control expr-input" data-cat="w_scat_10" placeholder="10元"></div>
                                        <div class="col-3"><input type="text" class="form-control expr-input" data-cat="w_scat_20" placeholder="20元"></div>
                                        <div class="col-3"><input type="text" class="form-control expr-input" data-cat="w_scat_30" placeholder="30元"></div>
                                        <div class="col-3"><input type="text" class="form-control expr-input" data-cat="w_scat_50" placeholder="50元"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-header">体彩散票 <span class="subtotal float-right">小计：￥<span id="s_scat_subtotal">0.00</span></span></div>
                                <div class="card-body">
                                    <div class="form-row">
                                        <div class="col-3"><input type="text" class="form-control expr-input" data-cat="s_scat_10" placeholder="10元"></div>
                                        <div class="col-3"><input type="text" class="form-control expr-input" data-cat="s_scat_20" placeholder="20元"></div>
                                        <div class="col-3"><input type="text" class="form-control expr-input" data-cat="s_scat_30" placeholder="30元"></div>
                                        <div class="col-3"><input type="text" class="form-control expr-input" data-cat="s_scat_50" placeholder="50元"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row mt-3">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-header">福彩整票 <span class="subtotal float-right">小计：￥<span id="w_whole_subtotal">0.00</span></span></div>
                                <div class="card-body">
                                    <div class="form-row">
                                        <div class="col-4"><input type="text" class="form-control expr-input" data-cat="w_whole_500" placeholder="500元"></div>
                                        <div class="col-4"><input type="text" class="form-control expr-input" data-cat="w_whole_600" placeholder="600元"></div>
                                        <div class="col-4"><input type="text" class="form-control expr-input" data-cat="w_whole_1000" placeholder="1000元"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-header">体彩整票 <span class="subtotal float-right">小计：￥<span id="s_whole_subtotal">0.00</span></span></div>
                                <div class="card-body">
                                    <div class="form-row">
                                        <div class="col-4"><input type="text" class="form-control expr-input" data-cat="s_whole_500" placeholder="500元"></div>
                                        <div class="col-4"><input type="text" class="form-control expr-input" data-cat="s_whole_600" placeholder="600元"></div>
                                        <div class="col-4"><input type="text" class="form-control expr-input" data-cat="s_whole_1000" placeholder="1000元"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card bg-light mt-3">
                        <div class="card-header">欠款 <span class="subtotal float-right">小计：￥<span id="debts_subtotal">0.00</span></span></div>
                        <div class="card-body" id="debts-container"></div>
                        <div class="card-footer"><button class="btn btn-sm btn-secondary" onclick="addDebtRow()">+ 增加欠款</button></div>
                    </div>
                    <div class="well p-3 bg-dark text-white mt-3 rounded">
                        <h4 class="subtotal mb-0">盘点总计金额: ￥<span id="inv_total">0.00</span></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- 盘点表 -->
        <div class="tab-pane fade" id="tab3">
            <div class="card">
                <div class="card-body">
                    <button class="btn btn-info mb-2" onclick="loadInventoryList(1)">刷新盘点表</button>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th>日期</th>
                                    <th>总金额</th>
                                    <th>银行卡</th>
                                    <th>福彩刮刮乐</th>
                                    <th>体彩刮刮乐</th>
                                    <th>刮刮乐销量</th>
                                    <th>福彩日均</th>
                                    <th>体彩日均</th>
                                    <th>当日电脑票销量</th>
                                    <th>当月电脑票销量</th>
                                    <th>盘点备注</th>
                                </tr>
                            </thead>
                            <tbody id="inv_list_body"></tbody>
                        </table>
                    </div>
                    <nav><ul class="pagination" id="inv_pagination"></ul></nav>
                </div>
            </div>
        </div>

        <!-- 流水录入 -->
        <div class="tab-pane fade" id="tab2">
            <div class="card">
                <div class="card-body">
                    <div class="form-row">
                        <div class="col-md-2">
                            <label>日期</label>
                            <input type="date" id="trans_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-2">
                            <label>分项类型</label>
                            <select id="trans_category" class="form-control">
                                <option>拿来刮刮乐</option>
                                <option>微信支付宝提现</option>
                                <option>零钱存钱</option>
                                <option>销售终端充值</option>
                                <option>福彩刮刮乐订票</option>
                                <option>体彩刮刮乐订票</option>
                                <option>电脑票销量</option>
                                <option>快乐八销量</option>
                                <option>双色球销量</option>
                                <option>3D销量</option>
                                <option>七乐彩销量</option>
                                <option>体彩佣金</option>
                                <option>采购饮料</option>
                            </select>
                        </div>
                        <div class="col-md-2" id="single_expr_wrap">
                            <label>金额/表达式</label>
                            <input type="text" id="trans_expr" class="form-control" placeholder="如：2000 或 102+36">
                        </div>
                        <div class="col-md-3" id="pc_expr_wrap" style="display:none;">
                            <label>电脑票销量明细</label>
                            <div class="form-row">
                                <div class="col-3"><input type="text" class="form-control pc-expr" data-pc="快乐八销量" placeholder="快乐八"></div>
                                <div class="col-3"><input type="text" class="form-control pc-expr" data-pc="双色球销量" placeholder="双色球"></div>
                                <div class="col-3"><input type="text" class="form-control pc-expr" data-pc="3D销量" placeholder="3D"></div>
                                <div class="col-3"><input type="text" class="form-control pc-expr" data-pc="七乐彩销量" placeholder="七乐彩"></div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label>备注</label>
                            <input type="text" id="trans_remark" class="form-control">
                        </div>
                        <div class="col-md-2 align-self-end">
                            <button class="btn btn-success btn-block" onclick="saveTransaction()">保存流水</button>
                        </div>
                    </div>
                    <hr>
                    <h5>最近流水记录</h5>
                    <table class="table table-sm table-striped mt-2">
                        <thead>
                            <tr>
                                <th>日期</th><th>分项</th><th>输入表达式</th>
                                <th>发生额</th><th>银行卡变动</th><th>备注</th>
                            </tr>
                        </thead>
                        <tbody id="trans_list_body"></tbody>
                    </table>
                    <nav><ul class="pagination" id="trans_pagination"></ul></nav>
                </div>
            </div>
        </div>

        <!-- 分项统计 -->
        <div class="tab-pane fade" id="tab4">
            <div class="card">
                <div class="card-body">
                    <div class="form-row">
                        <div class="col-md-3">
                            <select id="stat_cat" class="form-control">
                                <option>银行卡</option>
                                <option>拿来刮刮乐</option>
                                <option>福彩刮刮乐订票</option>
                                <option>体彩刮刮乐订票</option>
                                <option>快乐八销量</option>
                                <option>双色球销量</option>
                                <option>3D销量</option>
                                <option>七乐彩销量</option>
                                <option>微信支付宝提现</option>
                                <option>零钱存钱</option>
                                <option>销售终端充值</option>
                                <option>体彩佣金</option>
                                <option>采购饮料</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary" onclick="loadStats()">查询</button>
                        </div>
                        <div class="col-md-7 text-right align-self-center">
                            <h4 id="stat_extra_info" class="text-primary"></h4>
                        </div>
                    </div>
                    <table class="table table-sm table-striped mt-3">
                        <thead>
                            <tr>
                                <th>日期</th><th>分项</th><th>输入表达式</th>
                                <th>发生额</th><th>银行卡变动</th><th>备注</th>
                            </tr>
                        </thead>
                        <tbody id="stat_list_body"></tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.bootcdn.net/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/4.6.1/js/bootstrap.min.js"></script>
<script>
let currentShop = $('#current_shop').val();

function calcExpr(expr) {
    if (!expr) return 0;
    expr = String(expr).replace(/\s+/g, '');
    if (!/^[0-9+\-\.]+$/.test(expr)) return 0;
    expr = expr.replace(/--/g, '+').replace(/\+-|-\+/g, '-');
    let tokens = expr.match(/[+-]?[^+-]+/g);
    if (!tokens) return 0;
    let sum = 0;
    tokens.forEach(t => sum += parseFloat(t) || 0);
    return sum;
}

function addDebtRow(name = '', expr = '') {
    $('#debts-container').append(`
        <div class="form-row debt-row mb-2">
            <div class="col-md-4"><input type="text" class="form-control debt-name" placeholder="人名" value="${name}"></div>
            <div class="col-md-6"><input type="text" class="form-control debt-expr expr-input" placeholder="金额表达式" value="${expr}"></div>
            <div class="col-md-2"><button class="btn btn-danger btn-block" onclick="this.closest('.debt-row').remove();calcAll();">删除</button></div>
        </div>
    `);
}

function calcAll() {
    let total = 0;
    let zero = calcExpr($("input[data-cat='zero']").val());
    total += zero; $('#zero_subtotal').text(zero.toFixed(2));
    let dianao = calcExpr($("input[data-cat='dianao']").val());
    total += dianao; $('#dianao_subtotal').text(dianao.toFixed(2));
    let diangua = calcExpr($("input[data-cat='diangua']").val());
    total += diangua; $('#diangua_subtotal').text(diangua.toFixed(2));
    let sports_lottery = calcExpr($("input[data-cat='sports_lottery']").val());
    total += sports_lottery; $('#sports_lottery_subtotal').text(sports_lottery.toFixed(2));
    let wechat = calcExpr($("input[data-cat='wechat']").val());
    total += wechat; $('#wechat_subtotal').text(wechat.toFixed(2));
    let alipay = calcExpr($("input[data-cat='alipay']").val());
    total += alipay; $('#alipay_subtotal').text(alipay.toFixed(2));
    let bank_card = calcExpr($("input[data-cat='bank_card']").val());
    total += bank_card; $('#bank_card_subtotal').text(bank_card.toFixed(2));

    let w_scat_sum = 0, s_scat_sum = 0;
    [10, 20, 30, 50].forEach(face => { w_scat_sum += calcExpr($(`input[data-cat='w_scat_${face}']`).val()) * face; });
    [10, 20, 30, 50].forEach(face => { s_scat_sum += calcExpr($(`input[data-cat='s_scat_${face}']`).val()) * face; });
    total += w_scat_sum + s_scat_sum;
    $(`#w_scat_subtotal`).text(w_scat_sum.toFixed(2));
    $(`#s_scat_subtotal`).text(s_scat_sum.toFixed(2));

    let w_whole_sum = 0, s_whole_sum = 0;
    [500, 600, 1000].forEach(face => { w_whole_sum += calcExpr($(`input[data-cat='w_whole_${face}']`).val()) * face; });
    [500, 600, 1000].forEach(face => { s_whole_sum += calcExpr($(`input[data-cat='s_whole_${face}']`).val()) * face; });
    total += w_whole_sum + s_whole_sum;
    $(`#w_whole_subtotal`).text(w_whole_sum.toFixed(2));
    $(`#s_whole_subtotal`).text(s_whole_sum.toFixed(2));

    let debts_sum = 0;
    $('.debt-row').each(function () { debts_sum += calcExpr($(this).find('.debt-expr').val()); });
    total += debts_sum;
    $(`#debts_subtotal`).text(debts_sum.toFixed(2));
    $('#inv_total').text(total.toFixed(2));
}

$(document).on('blur', '.expr-input, .debt-name', function () {
    calcAll();
    autoSaveInventory();
});

// 监听流水类型切换，控制输入框显示
$('#trans_category').change(function () {
    if ($(this).val() === '电脑票销量') {
        $('#single_expr_wrap').hide();
        $('#pc_expr_wrap').show();
    } else {
        $('#single_expr_wrap').show();
        $('#pc_expr_wrap').hide();
    }
});

function autoSaveInventory() {
    if (!$('#inv_date').val()) return;
    let data = {
        record_date: $('#inv_date').val(),
        remark: $("input[data-cat='remark']").val(),
        zero_cash_expr: $("input[data-cat='zero']").val(), zero_cash: calcExpr($("input[data-cat='zero']").val()),
        dianao_expr: $("input[data-cat='dianao']").val(), dianao: calcExpr($("input[data-cat='dianao']").val()),
        diangua_expr: $("input[data-cat='diangua']").val(), diangua: calcExpr($("input[data-cat='diangua']").val()),
        sports_lottery_expr: $("input[data-cat='sports_lottery']").val(), sports_lottery: calcExpr($("input[data-cat='sports_lottery']").val()),
        wechat_expr: $("input[data-cat='wechat']").val(), wechat: calcExpr($("input[data-cat='wechat']").val()),
        alipay_expr: $("input[data-cat='alipay']").val(), alipay: calcExpr($("input[data-cat='alipay']").val()),
        bank_card_expr: $("input[data-cat='bank_card']").val(), bank_card: calcExpr($("input[data-cat='bank_card']").val()),
        total_amount: parseFloat($('#inv_total').text()),
        welfare_scatter: {10: $("input[data-cat='w_scat_10']").val(), 20: $("input[data-cat='w_scat_20']").val(), 30: $("input[data-cat='w_scat_30']").val(), 50: $("input[data-cat='w_scat_50']").val()},
        sports_scatter: {10: $("input[data-cat='s_scat_10']").val(), 20: $("input[data-cat='s_scat_20']").val(), 30: $("input[data-cat='s_scat_30']").val(), 50: $("input[data-cat='s_scat_50']").val()},
        welfare_whole: {500: $("input[data-cat='w_whole_500']").val(), 600: $("input[data-cat='w_whole_600']").val(), 1000: $("input[data-cat='w_whole_1000']").val()},
        sports_whole: {500: $("input[data-cat='s_whole_500']").val(), 600: $("input[data-cat='s_whole_600']").val(), 1000: $("input[data-cat='s_whole_1000']").val()},
        debts: []
    };
    $('.debt-row').each(function () {
        let name = $(this).find('.debt-name').val();
        let expr = $(this).find('.debt-expr').val();
        if (name || expr) data.debts.push({name: name, amount_expr: expr, amount: calcExpr(expr)});
    });
    $.ajax({
        url: '?action=save_inventory&shop=' + currentShop,
        method: 'POST', contentType: 'application/json', data: JSON.stringify(data),
        success: function (res) {
            if (res.code === 0) $('#save_status').fadeIn().delay(1500).fadeOut();
            else alert(res.msg);
        }
    });
}

function loadCurrentInventory() {
    let date = $('#inv_date').val();
    if (!date) { clearInventoryForm(); return; }
    $.get('?action=get_current_inventory', {shop: currentShop, date: date}, function (res) {
        clearInventoryForm();
        if (res.data) {
            let d = res.data;
            $("input[data-cat='zero']").val(d.zero_cash_expr || d.zero_cash);
            $("input[data-cat='dianao']").val(d.dianao_expr || d.dianao);
            $("input[data-cat='diangua']").val(d.diangua_expr || d.diangua);
            $("input[data-cat='sports_lottery']").val(d.sports_lottery_expr || d.sports_lottery);
            $("input[data-cat='wechat']").val(d.wechat_expr || d.wechat);
            $("input[data-cat='alipay']").val(d.alipay_expr || d.alipay);
            $("input[data-cat='bank_card']").val(d.bank_card_expr || d.bank_card);
            $("input[data-cat='remark']").val(d.remark);
            let ws = JSON.parse(d.welfare_scatter || '{}');
            let ss = JSON.parse(d.sports_scatter || '{}');
            let ww = JSON.parse(d.welfare_whole || '{}');
            let sw = JSON.parse(d.sports_whole || '{}');
            for (let k in ws) $(`input[data-cat='w_scat_${k}']`).val(ws[k]);
            for (let k in ss) $(`input[data-cat='s_scat_${k}']`).val(ss[k]);
            for (let k in ww) $(`input[data-cat='w_whole_${k}']`).val(ww[k]);
            for (let k in sw) $(`input[data-cat='s_whole_${k}']`).val(sw[k]);
            let debts = JSON.parse(d.debts || '[]');
            if (debts.length > 0) { debts.forEach(deb => addDebtRow(deb.name, deb.amount_expr)); }
        }
        calcAll();
    });
}

function clearInventoryForm() {
    $('.expr-input[data-cat!="remark"]').val('');
    $('#debts-container').empty();
    addDebtRow();
}

function saveTransaction() {
    let category = $('#trans_category').val();
    
    // 处理拆分的电脑票销量
    if (category === '电脑票销量') {
        let items = [];
        let hasValue = false;
        $('.pc-expr').each(function () {
            let val = $(this).val().trim();
            if (val) {
                hasValue = true;
                items.push({
                    category: $(this).data('pc'),
                    amount_expression: val,
                    amount: calcExpr(val)
                });
            }
        });
        if (!hasValue) {
            alert('请至少输入一项电脑票销量');
            return;
        }
        let data = {
            trans_date: $('#trans_date').val(),
            category: category,
            items: items,
            remark: $('#trans_remark').val()
        };
        $.post('?action=save_transaction&shop=' + currentShop, JSON.stringify(data), function (res) {
            alert(res.msg);
            if (res.code === 0) {
                $('.pc-expr').val('');
                $('#trans_remark').val('');
                loadTransactionList();
            }
        });
        return;
    }

    // 原有其他流水保存逻辑
    let data = {
        trans_date: $('#trans_date').val(),
        category: category,
        amount_expression: $('#trans_expr').val(),
        amount: calcExpr($('#trans_expr').val()),
        remark: $('#trans_remark').val()
    };
    $.post('?action=save_transaction&shop=' + currentShop, JSON.stringify(data), function (res) {
        alert(res.msg);
        if (res.code === 0) {
            $('#trans_expr').val('');
            $('#trans_remark').val('');
            loadTransactionList();
        }
    });
}

function loadTransactionList(page = 1) {
    $.get('?action=get_transaction_list', {shop: currentShop, page: page}, function (res) {
        let html = '';
        res.data.forEach(row => {
            html += `<tr>
                <td>${row.trans_date}</td><td>${row.category}</td><td>${row.amount_expression}</td>
                <td>${row.amount}</td><td>${row.bank_change}</td><td>${row.remark}</td>
            </tr>`;
        });
        $('#trans_list_body').html(html);
        renderPagination('#trans_pagination', res.totalPages, res.currentPage, 'loadTransactionList');
    });
}

function loadInventoryList(page = 1) {
    $.get('?action=get_inventory_list', {shop: currentShop, page: page}, function (res) {
        let html = '';
        res.data.forEach(row => {
            let remarkText = row.remark || '';
            let safeTitle = remarkText.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
            
            html += `<tr>
                <td>${row.record_date}</td>
                <td>${row.total_amount}</td>
                <td>${row.bank_card.toFixed(2)}</td>
                <td class="font-weight-bold">${row.welfare_gua.toFixed(2)}</td>
                <td class="font-weight-bold">${row.sports_gua.toFixed(2)}</td>
                <td class="text-success font-weight-bold">${row.gua_sales !== undefined ? row.gua_sales.toFixed(2) : '-'}</td>
                <td class="text-warning font-weight-bold">${row.welfare_daily_sales !== undefined ? row.welfare_daily_sales.toFixed(2) : '-'}</td>
                <td class="text-warning font-weight-bold">${row.sports_daily_sales !== undefined ? row.sports_daily_sales.toFixed(2) : '-'}</td>
                <td class="text-primary font-weight-bold">${row.day_pc_sales.toFixed(2)}</td>
                <td class="text-primary font-weight-bold">${row.month_pc_sales.toFixed(2)}</td>
                <td class="td-remark-ellipsis" title="${safeTitle}">${remarkText}</td>
            </tr>`;
        });
        $('#inv_list_body').html(html);
        renderPagination('#inv_pagination', res.totalPages, res.currentPage, 'loadInventoryList');
    });
}

function loadStats() {
    let cat = $('#stat_cat').val();
    $.get('?action=get_category_stats', {shop: currentShop, category: cat}, function (res) {
        let html = '';
        res.data.forEach(row => {
            html += `<tr>
                <td>${row.trans_date}</td><td>${row.category}</td><td>${row.amount_expression}</td>
                <td>${row.amount}</td><td>${row.bank_change}</td><td>${row.remark}</td>
            </tr>`;
        });
        $('#stat_list_body').html(html);
        if (cat === '银行卡') {
            $('#stat_extra_info').text('当前店铺银行卡余额: ￥' + (res.extra.balance || 0).toFixed(2));
        } else {
            $('#stat_extra_info').text('当前店铺本月累计 ' + cat + ': ￥' + (res.extra.month_total || 0).toFixed(2));
        }
    });
}

function toggleTransferCheck() {
    let panel = $('#transfer_check_panel');
    if (panel.is(':visible')) { panel.slideUp(); return; }
    let month = $('#top_check_month').val();
    $.get('?action=get_transfer_check', {month: month}, function (res) {
        let d = res.data.shops;
        $('#check_jyf').text(d['玖誉府店'].toFixed(2));
        $('#check_hjs').text(d['黄金山店'].toFixed(2));
        $('#check_kc').text(d['库存店'].toFixed(2));
        let total = res.data.total;
        let totalCell = $('#check_total');
        totalCell.text(total.toFixed(2));
        if (total === 0) { totalCell.removeClass('text-danger').addClass('text-success'); }
        else { totalCell.removeClass('text-success').addClass('text-danger'); }
        panel.slideDown();
    });
}

function renderPagination(selector, totalPages, currentPage, callbackName) {
    if (totalPages <= 1) { $(selector).html(''); return; }
    let html = '';
    for (let i = 1; i <= totalPages; i++) {
        html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="${callbackName}(${i})">${i}</a>
        </li>`;
    }
    $(selector).html(html);
}

$('#current_shop').change(function () { currentShop = $(this).val(); loadCurrentInventory(); });
$('#inv_date').change(function () { loadCurrentInventory(); });

$(document).ready(function () {
    loadCurrentInventory();
    loadInventoryList();
    loadTransactionList();
});
</script>
</body>
</html>
