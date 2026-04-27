<!-- 流水录入 -->
<div class="tab-pane fade" id="tab2">
    <div class="card"><div class="card-body">
        <div class="form-row">
            <div class="col-md-2"><label>日期</label><input type="date" id="trans_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
            <div class="col-md-2"><label>分项类型</label>
                <select id="trans_category" class="form-control">
                    <option>拿来刮刮乐</option><option>微信支付宝提现</option><option>零钱存钱</option>
                    <option>销售终端充值</option><option>福彩刮刮乐订票</option><option>体彩刮刮乐订票</option>
                    <option>电脑票销量</option><option>快乐八销量</option><option>双色球销量</option>
                    <option>3D销量</option><option>七乐彩销量</option><option>体彩佣金</option><option>采购饮料</option>
                </select>
            </div>
            <div class="col-md-2" id="single_expr_wrap"><label>金额/表达式</label><input type="text" id="trans_expr" class="form-control" placeholder="如：2000 或 102+36"></div>
            <div class="col-md-3" id="pc_expr_wrap" style="display:none;"><label>电脑票销量明细</label>
                <div class="form-row">
                    <div class="col-3"><input type="text" class="form-control pc-expr" data-pc="快乐八销量" placeholder="快乐八"></div>
                    <div class="col-3"><input type="text" class="form-control pc-expr" data-pc="双色球销量" placeholder="双色球"></div>
                    <div class="col-3"><input type="text" class="form-control pc-expr" data-pc="3D销量" placeholder="3D"></div>
                    <div class="col-3"><input type="text" class="form-control pc-expr" data-pc="七乐彩销量" placeholder="七乐彩"></div>
                </div>
            </div>
            <div class="col-md-2"><label>备注</label><input type="text" id="trans_remark" class="form-control"></div>
            <div class="col-md-1 align-self-end"><button class="btn btn-success btn-block" onclick="saveTransaction()">保存</button></div>
        </div>
        <hr>
        <h5>最近流水记录</h5>
        <table class="table table-sm table-striped mt-2">
            <thead><tr><th>日期</th><th>分项</th><th>输入表达式</th><th>发生额</th><th>银行卡变动</th><th>备注</th></tr></thead>
            <tbody id="trans_list_body"></tbody>
        </table>
        <nav><ul class="pagination" id="trans_pagination"></ul></nav>
    </div></div>
</div>

<!-- 分项统计 -->
<div class="tab-pane fade" id="tab4">
    <div class="card"><div class="card-body">
        <div class="form-row">
            <div class="col-md-3">
                <select id="stat_cat" class="form-control">
                    <option>银行卡</option><option>拿来刮刮乐</option><option>福彩刮刮乐订票</option><option>体彩刮刮乐订票</option>
                    <option>快乐八销量</option><option>双色球销量</option><option>3D销量</option><option>七乐彩销量</option>
                    <option>微信支付宝提现</option><option>零钱存钱</option><option>销售终端充值</option><option>体彩佣金</option><option>采购饮料</option>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary" onclick="loadStats()">查询</button></div>
            <div class="col-md-7 text-right align-self-center"><h4 id="stat_extra_info" class="text-primary"></h4></div>
        </div>
        <table class="table table-sm table-striped mt-3">
            <thead><tr><th>日期</th><th>分项</th><th>输入表达式</th><th>发生额</th><th>银行卡变动</th><th>备注</th></tr></thead>
            <tbody id="stat_list_body"></tbody>
        </table>
    </div></div>
</div>
</div> <!-- 关闭 tab-content -->
