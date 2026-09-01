<?php
session_start();
require __DIR__ . '/db.php';

if (!isset($_SESSION['user'])) {
    $redirectTarget = 'checkout.php?' . http_build_query([
        'title' => $_GET['title'] ?? '',
        'amount' => $_GET['amount'] ?? '',
    ]);
    $_SESSION['redirect_after_login'] = $redirectTarget;
    header('Location: signin.php?redirect=' . urlencode($redirectTarget));
    exit;
}

require 'config.php';

$books = loadBooks();
if (empty($books)) {
    $seedData = require __DIR__ . '/seeds.php';
    $books = $seedData['books'] ?? [];
}

$cart = $_SESSION['cart'] ?? [];
$cartItems = [];
$totalAmount = 0.0;

if (!empty($cart)) {
    foreach ($cart as $bookId => $quantity) {
        $quantity = max(1, (int)$quantity);
        foreach ($books as $book) {
            if ((int)($book['id'] ?? 0) === (int)$bookId) {
                $price = (float)($book['price'] ?? 0);
                $lineTotal = $price * $quantity;
                $totalAmount += $lineTotal;
                $cartItems[] = [
                    'title' => (string)($book['title'] ?? 'Book'),
                    'price' => $price,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ];
                break;
            }
        }
    }
}

$title = trim((string)($_GET['title'] ?? ''));
$amount = $_GET['amount'] ?? '';

if (!empty($cartItems)) {
    $title = implode(', ', array_map(fn($item) => $item['title'], $cartItems));
    $amount = (string)$totalAmount;
} elseif ($title === '' || $amount === '' || !is_numeric($amount)) {
    die("Invalid product. <a href='index.php'>Go back</a>");
}

$loggedInUser = $_SESSION['user'] ?? null;
$defaultCustomerName = $loggedInUser['name'] ?? '';
$defaultEmail = $loggedInUser['email'] ?? '';

// If the buyer submitted their details, build the eSewa form and auto-redirect
$showEsewaForm = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($customer_name && $phone) {
        $transaction_uuid = 'MP-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
        $total_amount = (float)$amount;

        $signature = generateEsewaSignature($total_amount, $transaction_uuid, ESEWA_PRODUCT_CODE);

        require 'db.php';
        $stmt = $conn->prepare("INSERT INTO orders (book_title, amount, customer_name, phone, transaction_uuid, status) VALUES (?, ?, ?, ?, ?, 'PENDING')");
        $stmt->bind_param("sdsss", $title, $total_amount, $customer_name, $phone, $transaction_uuid);
        $stmt->execute();
        $stmt->close();
        $conn->close();

        $_SESSION['last_order'] = [
            'title' => $title,
            'amount' => $total_amount,
            'customer_name' => $customer_name,
            'email' => $email,
            'phone' => $phone,
            'transaction_uuid' => $transaction_uuid,
        ];

        $showEsewaForm = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Checkout - Mini Pasal</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark mb-4">
  <div class="container"><span class="navbar-brand h1 mb-0">📚 मिनी पसल — Checkout</span></div>
</nav>

<div class="container" style="max-width:480px;">
  <div class="card shadow-sm p-4">
    <h5><?= htmlspecialchars($title) ?></h5>
    <p class="text-muted">Amount: Rs. <?= htmlspecialchars($amount) ?></p>

    <?php if (!$showEsewaForm): ?>
      <?php if (!empty($cartItems)): ?>
        <div class="mb-3">
          <h6 class="text-muted mb-2">Items in cart</h6>
          <ul class="list-group list-group-flush small">
            <?php foreach ($cartItems as $item): ?>
              <li class="list-group-item px-0 d-flex justify-content-between">
                <span><?= htmlspecialchars($item['title']) ?> × <?= (int)$item['quantity'] ?></span>
                <span>Rs. <?= number_format($item['line_total']) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Full Name</label>
          <input type="text" name="customer_name" class="form-control" value="<?= htmlspecialchars($defaultCustomerName) ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($defaultEmail) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Phone Number</label>
          <input type="text" name="phone" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success w-100">Proceed to Pay with eSewa</button>
      </form>
    <?php else: ?>
      <p>Redirecting to eSewa...</p>
      <form id="esewaForm" action="<?= ESEWA_PAYMENT_URL ?>" method="POST">
        <input type="hidden" name="amount" value="<?= htmlspecialchars($total_amount) ?>">
        <input type="hidden" name="tax_amount" value="0">
        <input type="hidden" name="total_amount" value="<?= htmlspecialchars($total_amount) ?>">
        <input type="hidden" name="transaction_uuid" value="<?= htmlspecialchars($transaction_uuid) ?>">
        <input type="hidden" name="product_code" value="<?= ESEWA_PRODUCT_CODE ?>">
        <input type="hidden" name="product_service_charge" value="0">
        <input type="hidden" name="product_delivery_charge" value="0">
        <input type="hidden" name="success_url" value="<?= SUCCESS_URL ?>">
        <input type="hidden" name="failure_url" value="<?= FAILURE_URL ?>">
        <input type="hidden" name="signed_field_names" value="total_amount,transaction_uuid,product_code">
        <input type="hidden" name="signature" value="<?= htmlspecialchars($signature) ?>">
      </form>
      <script>
        document.getElementById('esewaForm').submit();
      </script>
    <?php endif; ?>

  </div>
</div>

</body>
</html>
