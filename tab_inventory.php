<div class="tab-content mt-2">
<!-- 盘点录入 -->
<div class="tab-pane fade show active" id="tab1">
    <div class="card"><div class="card-body">
        <div class="form-row">
            <div class="col-md-3"><div class="form-group"><label>零钱 <span class="subtotal float-right">小计：￥<span id="zero_subtotal">0.00</span></span></label><input type="text" class="form-control expr-input" data-cat="zero" placeholder="如：10+20-5"></div></div>
            <div class="col-md-3"><div class="form-group"><label>电脑余额 <span class="subtotal float-right">小计：￥<span id="dianao_subtotal">0.00</span></span></label><input type="text" class="form-control expr-input" data-cat="dianao"></div></div>
            <div class="col-md-3"><div class="form-group"><label>电刮余额 <span class="subtotal float-right">小计：￥<span id="diangua_subtotal">0.00</span></span></label><input type="text" class="form-control expr-input" data-cat="diangua"></div></div>
            <div class="col-md-3"><div class="form-group"><label>体彩 <span class="subtotal float-right">小计：￥<span id="sports_lottery_subtotal">0.00</span></span></label><input type="text" class="form-control expr-input" data-cat="sports_lottery"></div></div>
        </div>
        <div class="form-row">
            <div class="col-md-3"><div class="form-group"><label>微信 <span class="subtotal float-right">小计：￥<span id="wechat_subtotal">0.00</span></span></label><input type="text" class="form-control expr-input" data-cat="wechat"></div></div>
            <div class="col-md-3"><div class="form-group"><label>支付宝 <span class="subtotal float-right">小计：￥<span id="alipay_subtotal">0.00</span></span></label><input type="text" class="form-control expr-input" data-cat="alipay"></div></div>
            <div class="col-md-3"><div class="form-group"><label>银行卡 <span class="subtotal float-right">小计：￥<span id="bank_card_subtotal">0.00</span></span></label><input type="text" class="form-control expr-input" data-cat="bank_card"></div></div>
            <div class="col-md-3"><div class="form-group"><label>备注</label><input type="text" id="inv_remark" class="form-control expr-input" data-cat="remark"></div></div>
        </div>
        <div class="form-row">
            <div class="col-md-6"><div class="card bg-light"><div class="card-header">福彩散票 <span class="subtotal float-right">小计：￥<span id="w_scat_subtotal">0.00</span></span></div><div class="card-body"><div class="form-row">
                <div class="col-3"><input type="text" class="form-control expr-input" data-cat="w_scat_10" placeholder="10元"></div>
                <div class="col-3"><input type="text" class="form-control expr-input" data-cat="w_scat_20" placeholder="20元"></div>
                <div class="col-3"><input type="text" class="form-control expr-input" data-cat="w_scat_30" placeholder="30元"></div>
                <div class="col-3"><input type="text" class="form-control expr-input" data-cat="w_scat_50" placeholder="50元"></div>
            </div></div></div></div>
            <div class="col-md-6"><div class="card bg-light"><div class="card-header">体彩散票 <span class="subtotal float-right">小计：￥<span id="s_scat_subtotal">0.00</span></span></div><div class="card-body"><div class="form-row">
                <div class="col-3"><input type="text" class="form-control expr-input" data-cat="s_scat_10" placeholder="10元"></div>
                <div class="col-3"><input type="text" class="form-control expr-input" data-cat="s_scat_20" placeholder="20元"></div>
                <div class="col-3"><input type="text" class="form-control expr-input" data-cat="s_scat_30" placeholder="30元"></div>
                <div class="col-3"><input type="text" class="form-control expr-input" data-cat="s_scat_50" placeholder="50元"></div>
            </div></div></div></div>
        </div>
        <div class="form-row mt-3">
            <div class="col-md-6"><div class="card bg-light"><div class="card-header">福彩整票 <span class="subtotal float-right">小计：￥<span id="w_whole_subtotal">0.00</span></span></div><div class="card-body"><div class="form-row">
                <div class="col-4"><input type="text" class="form-control expr-input" data-cat="w_whole_500" placeholder="500元"></div>
                <div class="col-4"><input type="text" class="form-control expr-input" data-cat="w_whole_600" placeholder="600元"></div>
                <div class="col-4"><input type="text" class="form-control expr-input" data-cat="w_whole_1000" placeholder="1000元"></div>
            </div></div></div></div>
            <div class="col-md-6"><div class="card bg-light"><div class="card-header">体彩整票 <span class="subtotal float-right">小计：￥<span id="s_whole_subtotal">0.00</span></span></div><div class="card-body"><div class="form-row">
                <div class="col-4"><input type="text" class="form-control expr-input" data-cat="s_whole_500" placeholder="500元"></div>
                <div class="col-4"><input type="text" class="form-control expr-input" data-cat="s_whole_600" placeholder="600元"></div>
                <div class="col-4"><input type="text" class="form-control expr-input" data-cat="s_whole_1000" placeholder="1000元"></div>
            </div></div></div></div>
        </div>
        <div class="card bg-light mt-3"><div class="card-header">欠款 <span class="subtotal float-right">小计：￥<span id="debts_subtotal">0.00</span></span></div><div class="card-body" id="debts-container"></div><div class="card-footer"><button class="btn btn-sm btn-secondary" onclick="addDebtRow()">+ 增加欠款</button></div></div>
        <div class="well p-3 bg-dark text-white mt-3 rounded"><h4 class="subtotal mb-0">盘点总计金额: ￥<span id="inv_total">0.00</span></h4></div>
    </div></div>
</div>

<!-- 盘点表 -->
<div class="tab-pane fade" id="tab3">
    <div class="card"><div class="card-body">
        <button class="btn btn-info mb-2" onclick="loadInventoryList(1)">刷新盘点表</button>
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="thead-dark">
                    <tr>
                        <th>日期</th><th>总金额</th><th>银行卡</th><th>福彩刮刮乐</th><th>体彩刮刮乐</th>
                        <th>刮刮乐销量</th><th>福彩日均</th><th>体彩日均</th>
                        <th>当日电脑票销量</th><th>当月电脑票销量</th><th>盘点备注</th>
                    </tr>
                </thead>
                <tbody id="inv_list_body"></tbody>
            </table>
        </div>
        <nav><ul class="pagination" id="inv_pagination"></ul></nav>
    </div></div>
</div>
