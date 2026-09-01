<?php
session_start();
require __DIR__ . '/db.php';

if (!isset($_SESSION['user']) && isset($_GET['redirect'])) {
    $_SESSION['redirect_after_login'] = $_GET['redirect'];
}

$books = loadBooks();
if (empty($books)) {
    $seedData = require __DIR__ . '/seeds.php';
    $books = $seedData['books'] ?? [];
}

$cart = $_SESSION['cart'] ?? [];
$cartItems = [];
$total = 0;
foreach ($cart as $bookId => $quantity) {
    foreach ($books as $book) {
        if ((int)($book['id'] ?? 0) === (int)$bookId) {
            $price = (float)($book['price'] ?? 0);
            $lineTotal = $price * (int)$quantity;
            $total += $lineTotal;
            $cartItems[] = [
                'id' => (int)$bookId,
                'title' => $book['title'] ?? 'Book',
                'author' => $book['author'] ?? 'Unknown',
                'price' => $price,
                'quantity' => (int)$quantity,
                'line_total' => $lineTotal,
            ];
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $removeId = (int)($_POST['remove_item'] ?? 0);
    unset($_SESSION['cart'][$removeId]);
    header('Location: cart.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart | Mini Pasal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(180deg, #f7f1e8 0%, #fdfaf5 100%);
            font-family: 'Segoe UI', sans-serif;
        }
        .cart-shell {
            max-width: 980px;
            margin: 60px auto;
            padding: 0 20px 40px;
        }
        .card {
            border-radius: 24px;
            border: 0;
            box-shadow: 0 18px 30px rgba(34, 32, 29, 0.08);
        }
        .summary {
            background: linear-gradient(135deg, #3b2f2c, #1f3d2c);
            color: white;
            border-radius: 24px 24px 0 0;
            padding: 1.5rem 1.25rem;
        }
        .btn-pay {
            background: linear-gradient(135deg, #1f3d2c, #416653);
            border: none;
            border-radius: 999px;
            padding: 0.8rem 1.2rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="cart-shell">
        <div class="card overflow-hidden">
            <div class="summary d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <div class="text-uppercase small fw-semibold opacity-75">Mini Pasal</div>
                    <h1 class="h3 mb-0">Your Cart</h1>
                </div>
                <a href="index.php" class="btn btn-light">Continue Shopping</a>
            </div>
            <div class="p-4 p-lg-5">
                <?php if (empty($cartItems)): ?>
                    <div class="text-center py-5">
                        <h3 class="mb-3">Your cart is empty.</h3>
                        <a href="index.php" class="btn btn-pay text-white">Browse Books</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($item['title']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($item['author']) ?></small>
                                        </td>
                                        <td>Rs. <?= number_format($item['price']) ?></td>
                                        <td><?= (int)$item['quantity'] ?></td>
                                        <td>Rs. <?= number_format($item['line_total']) ?></td>
                                        <td>
                                            <form method="POST">
                                                <input type="hidden" name="remove_item" value="<?= (int)$item['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-3">
                        <h4 class="mb-0">Grand total</h4>
                        <h4 class="mb-0 text-success">Rs. <?= number_format($total) ?></h4>
                    </div>

                    <div class="mt-4 d-flex gap-3 justify-content-end">
                        <a href="index.php" class="btn btn-outline-secondary">Keep Shopping</a>
                        <?php if (isset($_SESSION['user'])): ?>
                            <a href="checkout.php?title=Cart+Items&amount=<?= (float)$total ?>" class="btn btn-pay text-white">Checkout</a>
                        <?php else: ?>
                            <a href="signin.php?redirect=checkout.php?title=Cart+Items&amount=<?= (float)$total ?>" class="btn btn-pay text-white">Checkout</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
