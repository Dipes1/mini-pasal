<?php
require 'config.php';
require 'db.php';

// eSewa redirects here with ?data=<base64 encoded JSON>
$encodedData = $_GET['data'] ?? '';

if (empty($encodedData)) {
    die("No payment data received. <a href='index.php'>Go home</a>");
}

$decoded = json_decode(base64_decode($encodedData), true);

if (!$decoded || !isset($decoded['transaction_uuid'])) {
    die("Could not read payment response. <a href='index.php'>Go home</a>");
}

$transaction_uuid = $decoded['transaction_uuid'];
$total_amount     = $decoded['total_amount'] ?? null;
$refId            = $decoded['transaction_code'] ?? null;

// --- IMPORTANT: never trust the redirect alone. Confirm with eSewa's status check API. ---
$statusUrl = ESEWA_STATUS_CHECK_URL . "?" . http_build_query([
    'product_code'     => ESEWA_PRODUCT_CODE,
    'total_amount'     => $total_amount,
    'transaction_uuid' => $transaction_uuid,
]);

$ch = curl_init($statusUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$statusData = json_decode($response, true);
$verifiedStatus = $statusData['status'] ?? 'UNKNOWN';

$orderConfirmed = false;

if ($verifiedStatus === 'COMPLETE') {
    // Update the order to PAID
    $stmt = $conn->prepare("UPDATE orders SET status = 'PAID' WHERE transaction_uuid = ?");
    $stmt->bind_param("s", $transaction_uuid);
    $stmt->execute();
    $stmt->close();
    $orderConfirmed = true;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order Status - Mini Pasal</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container d-flex align-items-center justify-content-center" style="min-height:80vh;">
  <div class="card shadow-sm p-5 text-center" style="max-width:480px;">
    <?php if ($orderConfirmed): ?>
      <h1 class="text-success mb-3">✅</h1>
      <h4>Payment Successful!</h4>
      <p class="text-muted">Transaction ID: <?= htmlspecialchars($transaction_uuid) ?></p>
      <p>Your order has been confirmed. Thank you for shopping at मिनी पसल!</p>
    <?php else: ?>
      <h1 class="text-danger mb-3">⚠️</h1>
      <h4>Payment Not Verified</h4>
      <p class="text-muted">Status: <?= htmlspecialchars($verifiedStatus) ?></p>
      <p>We couldn't confirm this payment. Please contact support if you were charged.</p>
    <?php endif; ?>
    <a href="index.php" class="btn btn-outline-dark mt-3">Back to Shop</a>
  </div>
</div>
</body>
</html>
