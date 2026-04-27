<?php
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
