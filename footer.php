<!-- 双重CDN容灾机制：主CDN失败自动切换备用CDN -->
<script src="https://lib.baomitu.com/jquery/3.6.0/jquery.min.js"></script>
<script>
    if (typeof jQuery == 'undefined') {
        document.write('<script src="https://cdn.staticfile.org/jquery/3.6.0/jquery.min.js"><\/script>');
    }
</script>

<script src="https://lib.baomitu.com/twitter-bootstrap/4.6.1/js/bootstrap.min.js"></script>
<script>
    if (typeof $.fn.modal == 'undefined') {
        document.write('<script src="https://cdn.staticfile.org/twitter-bootstrap/4.6.1/js/bootstrap.min.js"><\/script>');
    }
</script>

<script>
// ========== 公共脚本 ==========
let currentShop = '';

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

function addDebtRow(name, expr) {
    name = name || '';
    expr = expr || '';
    $('#debts-container').append(
        '<div class="form-row debt-row mb-2">' +
        '  <div class="col-md-4"><input type="text" class="form-control debt-name" placeholder="人名" value="' + name + '"></div>' +
        '  <div class="col-md-6"><input type="text" class="form-control debt-expr expr-input" placeholder="金额表达式" value="' + expr + '"></div>' +
        '  <div class="col-md-2"><button class="btn btn-danger btn-block" onclick="this.closest(\'.debt-row\').remove();calcAll();">删除</button></div>' +
        '</div>'
    );
}

function calcAll() {
    let total = 0;
    let fields = ['zero', 'dianao', 'diangua', 'sports_lottery', 'wechat', 'alipay', 'bank_card'];
    
    for (let i = 0; i < fields.length; i++) {
        let f = fields[i];
        let val = calcExpr($("input[data-cat='" + f + "']").val());
        total += val;
        $('#' + f + '_subtotal').text(val.toFixed(2));
    }

    let w_scat = 0, s_scat = 0;
    let faces1 = [10, 20, 30, 50];
    for (let i = 0; i < faces1.length; i++) {
        w_scat += calcExpr($("input[data-cat='w_scat_" + faces1[i] + "']").val()) * faces1[i];
        s_scat += calcExpr($("input[data-cat='s_scat_" + faces1[i] + "']").val()) * faces1[i];
    }
    total += w_scat + s_scat;
    $('#w_scat_subtotal').text(w_scat.toFixed(2));
    $('#s_scat_subtotal').text(s_scat.toFixed(2));

    let w_whole = 0, s_whole = 0;
    let faces2 = [500, 600, 1000];
    for (let i = 0; i < faces2.length; i++) {
        w_whole += calcExpr($("input[data-cat='w_whole_" + faces2[i] + "']").val()) * faces2[i];
        s_whole += calcExpr($("input[data-cat='s_whole_" + faces2[i] + "']").val()) * faces2[i];
    }
    total += w_whole + s_whole;
    $('#w_whole_subtotal').text(w_whole.toFixed(2));
    $('#s_whole_subtotal').text(s_whole.toFixed(2));

    let debts = 0;
    $('.debt-row').each(function () {
        debts += calcExpr($(this).find('.debt-expr').val());
    });
    total += debts;
    $('#debts_subtotal').text(debts.toFixed(2));
    $('#inv_total').text(total.toFixed(2));
}

function autoSaveInventory() {
    // 不再前端拦截，直接发给后端判断，确保必定发出请求便于排查
    let data = {
        record_date: $('#inv_date').val(),
        remark: $("input[data-cat='remark']").val(),
        zero_cash_expr: $("input[data-cat='zero']").val(),
        zero_cash: calcExpr($("input[data-cat='zero']").val()),
        dianao_expr: $("input[data-cat='dianao']").val(),
        dianao: calcExpr($("input[data-cat='dianao']").val()),
        diangua_expr: $("input[data-cat='diangua']").val(),
        diangua: calcExpr($("input[data-cat='diangua']").val()),
        sports_lottery_expr: $("input[data-cat='sports_lottery']").val(),
        sports_lottery: calcExpr($("input[data-cat='sports_lottery']").val()),
        wechat_expr: $("input[data-cat='wechat']").val(),
        wechat: calcExpr($("input[data-cat='wechat']").val()),
        alipay_expr: $("input[data-cat='alipay']").val(),
        alipay: calcExpr($("input[data-cat='alipay']").val()),
        bank_card_expr: $("input[data-cat='bank_card']").val(),
        bank_card: calcExpr($("input[data-cat='bank_card']").val()),
        total_amount: parseFloat($('#inv_total').text()),
        welfare_scatter: {
            10: $("input[data-cat='w_scat_10']").val(),
            20: $("input[data-cat='w_scat_20']").val(),
            30: $("input[data-cat='w_scat_30']").val(),
            50: $("input[data-cat='w_scat_50']").val()
        },
        sports_scatter: {
            10: $("input[data-cat='s_scat_10']").val(),
            20: $("input[data-cat='s_scat_20']").val(),
            30: $("input[data-cat='s_scat_30']").val(),
            50: $("input[data-cat='s_scat_50']").val()
        },
        welfare_whole: {
            500: $("input[data-cat='w_whole_500']").val(),
            600: $("input[data-cat='w_whole_600']").val(),
            1000: $("input[data-cat='w_whole_1000']").val()
        },
        sports_whole: {
            500: $("input[data-cat='s_whole_500']").val(),
            600: $("input[data-cat='s_whole_600']").val(),
            1000: $("input[data-cat='s_whole_1000']").val()
        },
        debts: []
    };
    
    $('.debt-row').each(function () {
        let n = $(this).find('.debt-name').val();
        let e = $(this).find('.debt-expr').val();
        if (n || e) {
            data.debts.push({name: n, amount_expr: e, amount: calcExpr(e)});
        }
    });

    $.ajax({
        url: '?action=save_inventory&shop=' + currentShop,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function (r) {
            if (r.code === 0) {
                $('#save_status').fadeIn().delay(1500).fadeOut();
            } else {
                alert(r.msg);
            }
        },
        error: function (xhr, status, error) {
            alert('保存请求失败，状态: ' + status);
        }
    });
}

function loadCurrentInventory() {
    let date = $('#inv_date').val();
    if (!date) {
        clearInventoryForm();
        return;
    }
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
            
            for (let k in ws) { $("#input[data-cat='w_scat_" + k + "']").val(ws[k]); }
            for (let k in ss) { $("#input[data-cat='s_scat_" + k + "']").val(ss[k]); }
            for (let k in ww) { $("#input[data-cat='w_whole_" + k + "']").val(ww[k]); }
            for (let k in sw) { $("#input[data-cat='s_whole_" + k + "']").val(sw[k]); }
            
            let debts = JSON.parse(d.debts || '[]');
            if (debts.length > 0) {
                for (let i = 0; i < debts.length; i++) {
                    addDebtRow(debts[i].name, debts[i].amount_expr);
                }
            }
        }
        calcAll();
    });
}

function clearInventoryForm() {
    $('.expr-input[data-cat!="remark"]').val('');
    $('#debts-container').empty();
    addDebtRow();
}

function toggleTransferCheck() {
    let p = $('#transfer_check_panel');
    if (p.is(':visible')) {
        p.slideUp();
        return;
    }
    $.get('?action=get_transfer_check', {month: $('#top_check_month').val()}, function (res) {
        let d = res.data.shops;
        $('#check_jyf').text(d['玖誉府店'].toFixed(2));
        $('#check_hjs').text(d['黄金山店'].toFixed(2));
        $('#check_kc').text(d['库存店'].toFixed(2));
        let t = res.data.total, c = $('#check_total');
        c.text(t.toFixed(2));
        if (t === 0) {
            c.removeClass('text-danger').addClass('text-success');
        } else {
            c.removeClass('text-success').addClass('text-danger');
        }
        p.slideDown();
    });
}

function renderPagination(sel, total, cur, cb) {
    if (total <= 1) { $(sel).html(''); return; }
    let h = '';
    for (let i = 1; i <= total; i++) {
        h += '<li class="page-item ' + (i === cur ? 'active' : '') + '"><a class="page-link" href="javascript:void(0)" onclick="' + cb + '(' + i + ')">' + i + '</a></li>';
    }
    $(sel).html(h);
}

function loadInventoryList(page) {
    page = page || 1;
    $.get('?action=get_inventory_list', {shop: currentShop, page: page}, function (res) {
        let h = '';
        for (let i = 0; i < res.data.length; i++) {
            let r = res.data[i];
            let rt = r.remark || '';
            let st = rt.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
            h += '<tr><td>' + r.record_date + '</td><td>' + r.total_amount + '</td><td>' + r.bank_card.toFixed(2) + '</td><td class="font-weight-bold">' + r.welfare_gua.toFixed(2) + '</td><td class="font-weight-bold">' + r.sports_gua.toFixed(2) + '</td><td class="text-success font-weight-bold">' + (r.gua_sales !== undefined ? r.gua_sales.toFixed(2) : '-') + '</td><td class="text-warning font-weight-bold">' + (r.welfare_daily_sales !== undefined ? r.welfare_daily_sales.toFixed(2) : '-') + '</td><td class="text-warning font-weight-bold">' + (r.sports_daily_sales !== undefined ? r.sports_daily_sales.toFixed(2) : '-') + '</td><td class="text-primary font-weight-bold">' + r.day_pc_sales.toFixed(2) + '</td><td class="text-primary font-weight-bold">' + r.month_pc_sales.toFixed(2) + '</td><td class="td-remark-ellipsis" title="' + st + '">' + rt + '</td></tr>';
        }
        $('#inv_list_body').html(h);
        renderPagination('#inv_pagination', res.totalPages, res.currentPage, 'loadInventoryList');
    });
}

function saveTransaction() {
    let cat = $('#trans_category').val();
    if (cat === '电脑票销量') {
        let items = [], has = false;
        $('.pc-expr').each(function () {
            let v = $(this).val().trim();
            if (v) {
                has = true;
                items.push({category: $(this).data('pc'), amount_expression: v, amount: calcExpr(v)});
            }
        });
        if (!has) { alert('请至少输入一项'); return; }
        $.post('?action=save_transaction&shop=' + currentShop, JSON.stringify({trans_date: $('#trans_date').val(), category: cat, items: items, remark: $('#trans_remark').val()}), function (r) {
            alert(r.msg); if (r.code === 0) { $('.pc-expr').val(''); $('#trans_remark').val(''); loadTransactionList(); }
        });
        return;
    }
    $.post('?action=save_transaction&shop=' + currentShop, JSON.stringify({trans_date: $('#trans_date').val(), category: cat, amount_expression: $('#trans_expr').val(), amount: calcExpr($('#trans_expr').val()), remark: $('#trans_remark').val()}), function (r) {
        alert(r.msg); if (r.code === 0) { $('#trans_expr').val(''); $('#trans_remark').val(''); loadTransactionList(); }
    });
}

function loadTransactionList(page) {
    page = page || 1;
    $.get('?action=get_transaction_list', {shop: currentShop, page: page}, function (res) {
        let h = '';
        for (let i = 0; i < res.data.length; i++) {
            let r = res.data[i];
            h += '<tr><td>' + r.trans_date + '</td><td>' + r.category + '</td><td>' + r.amount_expression + '</td><td>' + r.amount + '</td><td>' + r.bank_change + '</td><td>' + r.remark + '</td></tr>';
        }
        $('#trans_list_body').html(h);
        renderPagination('#trans_pagination', res.totalPages, res.currentPage, 'loadTransactionList');
    });
}

function loadStats() {
    let cat = $('#stat_cat').val();
    $.get('?action=get_category_stats', {shop: currentShop, category: cat}, function (res) {
        let h = '';
        for (let i = 0; i < res.data.length; i++) {
            let r = res.data[i];
            h += '<tr><td>' + r.trans_date + '</td><td>' + r.category + '</td><td>' + r.amount_expression + '</td><td>' + r.amount + '</td><td>' + r.bank_change + '</td><td>' + r.remark + '</td></tr>';
        }
        $('#stat_list_body').html(h);
        if (cat === '银行卡') {
            $('#stat_extra_info').text('当前店铺银行卡余额: ￥' + (res.extra.balance || 0).toFixed(2));
        } else {
            $('#stat_extra_info').text('当前店铺本月累计 ' + cat + ': ￥' + (res.extra.month_total || 0).toFixed(2));
        }
    });
}

// ========== 初始化及事件绑定 ==========
$(document).ready(function () {
    currentShop = $('#current_shop').val();

    // 绑定失焦事件
    $(document).on('blur', '.expr-input, .debt-name', function () {
        calcAll();
        autoSaveInventory();
    });

    // 绑定下拉切换事件
    $('#trans_category').change(function () {
        if ($(this).val() === '电脑票销量') {
            $('#single_expr_wrap').hide();
            $('#pc_expr_wrap').show();
        } else {
            $('#single_expr_wrap').show();
            $('#pc_expr_wrap').hide();
        }
    });

    // 绑定全局切换事件
    $('#current_shop').change(function () {
        currentShop = $(this).val();
        loadCurrentInventory();
    });

    $('#inv_date').change(function () {
        loadCurrentInventory();
    });

    // 初始化加载
    loadCurrentInventory();
    loadInventoryList();
    loadTransactionList();
});
</script>
</body>
</html>
