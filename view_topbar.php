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
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body">
            <h6 class="text-muted">玖誉府店</h6>
            <div class="check-shop-val text-primary" id="check_jyf">0.00</div>
        </div></div></div>
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body">
            <h6 class="text-muted">黄金山店</h6>
            <div class="check-shop-val text-primary" id="check_hjs">0.00</div>
        </div></div></div>
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body">
            <h6 class="text-muted">库存店</h6>
            <div class="check-shop-val text-primary" id="check_kc">0.00</div>
        </div></div></div>
        <div class="col-md-3"><div class="card border-dark shadow-sm"><div class="card-body">
            <h6 class="text-dark">调拨合计</h6>
            <div class="check-shop-val text-danger" id="check_total">0.00</div>
        </div></div></div>
    </div>
</div>

<ul class="nav nav-tabs" id="myTab" role="tablist">
    <li class="nav-item"><a class="nav-link active" href="#tab1" data-toggle="tab">盘点录入</a></li>
    <li class="nav-item"><a class="nav-link" href="#tab3" data-toggle="tab">盘点表</a></li>
    <li class="nav-item"><a class="nav-link" href="#tab2" data-toggle="tab">流水录入</a></li>
    <li class="nav-item"><a class="nav-link" href="#tab4" data-toggle="tab">分项统计</a></li>
</ul>
