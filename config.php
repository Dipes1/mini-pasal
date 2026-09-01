<?php
// config.php - eSewa SANDBOX (test) credentials
// These are eSewa's publicly documented test values — fine for a college demo.
// Do NOT use these for real/production payments.

define('ESEWA_SECRET_KEY', '8gBm/:&EnhH.1/q');   // test secret key
define('ESEWA_PRODUCT_CODE', 'EPAYTEST');         // test merchant code
define('ESEWA_PAYMENT_URL', 'https://rc-epay.esewa.com.np/api/epay/main/v2/form');
define('ESEWA_STATUS_CHECK_URL', 'https://rc.esewa.com.np/api/epay/transaction/status/');

// Apache is serving this project under /mini-pasal/
define('SUCCESS_URL', 'http://localhost:8000/success.php');
define('FAILURE_URL', 'http://localhost:8000/failure.php');

/**
 * eSewa v2 signature: HMAC-SHA256 over "total_amount,transaction_uuid,product_code"
 * signed with the secret key, base64-encoded.
 */
function generateEsewaSignature($total_amount, $transaction_uuid, $product_code) {
    $message = "total_amount={$total_amount},transaction_uuid={$transaction_uuid},product_code={$product_code}";
    $hash = hash_hmac('sha256', $message, ESEWA_SECRET_KEY, true);
    return base64_encode($hash);
}
?>
