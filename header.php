<meta charset="UTF-8">
<title>彩票销售记账对账系统</title>
<link href="https://cdn.staticfile.org/twitter-bootstrap/4.6.1/css/bootstrap.min.css" rel="stylesheet">
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
        max-width: 120px; white-space: nowrap;
        overflow: hidden; text-overflow: ellipsis; cursor: default;
    }
</style>
