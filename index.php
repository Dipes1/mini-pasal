<?php
session_start();
require __DIR__ . '/db.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $bookId = (int)($_POST['book_id'] ?? 0);
    if ($bookId > 0) {
        $_SESSION['cart'][$bookId] = ($_SESSION['cart'][$bookId] ?? 0) + 1;
    }
    $redirect = $_POST['redirect'] ?? 'index.php';
    header('Location: ' . $redirect);
    exit;
}

if (isset($_GET['checkout']) && !isset($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php?title=' . urlencode($_GET['title'] ?? '') . '&amount=' . urlencode($_GET['amount'] ?? '');
    header('Location: signin.php');
    exit;
}

$seedData = require_once __DIR__ . '/seeds.php';
$books = loadBooks();
if (empty($books)) {
    $books = $seedData['books'] ?? [];
}

$search = trim($_GET['search'] ?? '');
$authorFilter = $_GET['author'] ?? 'all';
$priceFilter = $_GET['price'] ?? 'all';

$filteredBooks = [];
foreach ($books as $book) {
    $title = strtolower((string)($book['title'] ?? ''));
    $author = strtolower((string)($book['author'] ?? ''));
    $description = strtolower((string)($book['description'] ?? ''));
    $matchesSearch = $search === '' || strpos($title, strtolower($search)) !== false || strpos($author, strtolower($search)) !== false || strpos($description, strtolower($search)) !== false;
    $matchesAuthor = $authorFilter === 'all' || $author === strtolower($authorFilter);

    if ($matchesSearch && $matchesAuthor) {
        $filteredBooks[] = $book;
    }
}

if ($priceFilter === 'low') {
    usort($filteredBooks, function ($a, $b) { return (float)($a['price'] ?? 0) <=> (float)($b['price'] ?? 0); });
} elseif ($priceFilter === 'high') {
    usort($filteredBooks, function ($a, $b) { return (float)($b['price'] ?? 0) <=> (float)($a['price'] ?? 0); });
}

$cartCount = array_sum($_SESSION['cart']);
$userName = $_SESSION['user']['name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>मिनी पसल | Mini Pasal - Books</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f7f1e8;
            --paper: #fffdf9;
            --card: #fffaf3;
            --olive: #2d4b3d;
            --forest: #1f3d2c;
            --maroon: #5e2a2a;
            --terracotta: #b86b4b;
            --softer: #e6d7c4;
            --text: #2d2a27;
            --muted: #6b625b;
            --shadow-soft: 0 12px 30px rgba(31, 26, 21, 0.08);
            --shadow-card: 0 16px 30px rgba(46, 34, 26, 0.10);
            --radius-xl: 28px;
            --radius-lg: 20px;
            --radius-md: 14px;
        }

        body {
            background:
                linear-gradient(180deg, rgba(250, 245, 239, 0.98), rgba(247, 241, 232, 0.98)),
                radial-gradient(circle at top left, rgba(184, 107, 75, 0.08), transparent 35%),
                radial-gradient(circle at bottom right, rgba(45, 75, 61, 0.08), transparent 30%);
            color: var(--text);
            font-family: 'Inter', sans-serif;
        }

        h1, h2, h3, h4, h5, h6,
        .brand-title,
        .display-font {
            font-family: 'Cormorant Garamond', serif;
            letter-spacing: 0.02em;
        }

        .brand-title {
            font-weight: 700;
            font-size: clamp(1.7rem, 2vw, 2.4rem);
            line-height: 1.1;
        }

        .navbar {
            background: rgba(255, 253, 249, 0.82);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(46, 34, 26, 0.08);
            box-shadow: 0 8px 24px rgba(23, 20, 18, 0.06);
            position: sticky;
            top: 0;
            z-index: 1030;
        }

        .navbar-brand {
            color: var(--forest) !important;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .brand-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.4rem;
            height: 2.4rem;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--maroon), var(--forest));
            color: #fff;
            font-size: 1.15rem;
            box-shadow: 0 8px 18px rgba(94, 42, 42, 0.22);
        }

        .hero {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(97, 64, 48, 0.96), rgba(38, 53, 46, 0.92));
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-card);
            margin-top: 2rem;
            margin-bottom: 2.5rem;
        }

        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.06) 1px, transparent 1px);
            background-size: 22px 22px;
            mask-image: radial-gradient(circle at center, black 35%, transparent 100%);
        }

        .hero-content {
            position: relative;
            z-index: 1;
            padding: 3.5rem 2rem;
            color: #fff8f3;
        }

        .hero-badge {
            display: inline-block;
            padding: 0.5rem 0.9rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-size: 0.72rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .hero h1 {
            font-size: clamp(2.2rem, 5vw, 4.2rem);
            line-height: 0.95;
            margin-bottom: 1rem;
        }

        .hero p {
            max-width: 34rem;
            font-size: 1.05rem;
            color: rgba(255, 248, 243, 0.82);
            margin-bottom: 1.5rem;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.9rem;
        }

        .toolbar {
            background: rgba(255, 255, 255, 0.65);
            border: 1px solid rgba(31, 61, 44, 0.09);
            box-shadow: 0 12px 20px rgba(27, 22, 19, 0.04);
            border-radius: 18px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .toolbar .form-control,
        .toolbar .form-select {
            border-radius: 12px;
            border: 1px solid rgba(31, 61, 44, 0.12);
            padding: 0.7rem 0.85rem;
            background: rgba(255, 255, 255, 0.7);
        }

        .toolbar .btn-filter {
            border: none;
            background: linear-gradient(135deg, var(--forest), #2d5a48);
            color: #fff;
            border-radius: 12px;
            padding: 0.7rem 1.2rem;
            font-weight: 600;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #c98b5b, #b86b4b);
            border: none;
            color: #fff;
            padding: 0.8rem 1.3rem;
            border-radius: 999px;
            font-weight: 600;
            box-shadow: 0 12px 24px rgba(184, 107, 75, 0.28);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-primary-custom:hover {
            background: linear-gradient(135deg, #d68c5d, #b15f42);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(184, 107, 75, 0.32);
        }

        .section-label {
            letter-spacing: 0.14em;
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--maroon);
            margin-bottom: 0.7rem;
        }

        .section-title {
            font-size: clamp(2rem, 3vw, 3rem);
            color: var(--forest);
            margin-bottom: 0.5rem;
        }

        .book-card {
            border: 1px solid rgba(93, 68, 52, 0.08);
            border-radius: var(--radius-lg);
            overflow: hidden;
            background: var(--card);
            box-shadow: var(--shadow-soft);
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            height: 100%;
            cursor: pointer;
        }

        .book-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-card);
            border-color: rgba(46, 75, 61, 0.14);
        }

        .book-card img {
            width: 100%;
            height: 270px;
            object-fit: cover;
            display: block;
            background: linear-gradient(135deg, #ece0d2, #dfe6da);
        }

        .book-body {
            padding: 1.15rem 1.1rem 1.2rem;
            display: flex;
            flex-direction: column;
            height: calc(100% - 270px);
        }

        .book-title {
            font-size: 1.65rem;
            font-weight: 700;
            line-height: 1.1;
            color: var(--forest);
            margin-bottom: 0.15rem;
            min-height: 2.75rem;
        }

        .book-author {
            font-style: italic;
            color: var(--muted);
            margin-bottom: 0.75rem;
        }

        .book-price-wrap {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 0.5rem;
            border-top: 1px solid rgba(46, 75, 61, 0.08);
        }

        .book-price-label {
            font-size: 0.72rem;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 700;
        }

        .book-price {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--maroon);
            margin: 0;
        }

        .buy-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            margin-top: 0.75rem;
            border: none;
            background: linear-gradient(135deg, var(--forest), #2f5e4d);
            color: #fff;
            font-weight: 700;
            border-radius: 999px;
            padding: 0.8rem 1rem;
            text-decoration: none;
            box-shadow: 0 10px 18px rgba(31, 61, 44, 0.20);
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
        }

        .buy-btn:hover {
            text-decoration: none;
            color: #fff;
            filter: brightness(1.04);
            transform: translateY(-1px);
            box-shadow: 0 14px 22px rgba(31, 61, 44, 0.24);
        }

        .add-cart-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            border: 1px solid rgba(31, 61, 44, 0.15);
            background: rgba(255, 255, 255, 0.55);
            color: var(--forest);
            font-weight: 700;
            border-radius: 999px;
            padding: 0.72rem 1rem;
            margin-top: 0.55rem;
            transition: all 0.2s ease;
        }

        .add-cart-btn:hover {
            background: rgba(31, 61, 44, 0.08);
            color: var(--forest);
        }

        .details-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            margin-top: 0.7rem;
            border: 1px solid rgba(31, 61, 44, 0.18);
            background: rgba(255, 255, 255, 0.2);
            color: var(--forest);
            font-weight: 600;
            border-radius: 999px;
            padding: 0.7rem 1rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .details-btn:hover {
            background: rgba(31, 61, 44, 0.06);
            color: var(--forest);
            text-decoration: none;
        }

        .modal-content {
            border: none;
            border-radius: 22px;
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, #3a2d2c, #1f3d2c);
            color: #fff;
            border-bottom: none;
            padding: 1rem 1.2rem;
        }

        .modal-header .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        .modal-body img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 14px;
            margin-bottom: 1rem;
        }

        .modal-price {
            color: var(--maroon);
            font-size: 1.3rem;
            font-weight: 800;
            margin-bottom: 0.8rem;
        }

        .footer {
            border-top: 1px solid rgba(93, 68, 52, 0.08);
            background: rgba(255, 253, 249, 0.8);
            padding: 1.4rem 0;
            margin-top: 3rem;
        }

        @media (max-width: 991.98px) {
            .book-card img {
                height: 250px;
            }
        }

        @media (max-width: 767.98px) {
            .hero-content {
                padding: 2.5rem 1.25rem;
            }

            .section-title {
                font-size: 2.1rem;
            }

            .book-card img {
                height: 300px;
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container py-2 d-flex justify-content-between align-items-center gap-3">
        <a class="navbar-brand" href="index.php" aria-label="Mini Pasal home">
            <span class="brand-mark">📚</span>
            <span class="brand-title">मिनी पसल</span>
        </a>
        <div class="d-flex align-items-center gap-2">
            <?php if ($userName): ?>
                <span class="text-muted small me-2">Hello, <?= htmlspecialchars($userName) ?></span>
                <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
                    <a href="admin.php" class="btn btn-outline-primary btn-sm">Admin</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-outline-secondary btn-sm">Sign out</a>
            <?php else: ?>
                <a href="signin.php" class="btn btn-outline-secondary btn-sm">Sign in</a>
                <a href="signup.php" class="btn btn-dark btn-sm">Sign up</a>
            <?php endif; ?>
            <a href="cart.php" class="btn btn-primary-custom btn-sm position-relative">
                Cart
                <?php if ($cartCount > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-light text-dark">
                        <?= (int)$cartCount ?>
                    </span>
                <?php endif; ?>
            </a>
        </div>
    </div>
</nav>

<div class="container">
    <section class="hero">
        <div class="hero-content">
            <div class="hero-badge">Nepal’s bookstore pick</div>
            <h1>Books, delivered to your doorstep.</h1>
            <p>Discover thoughtful reads, local favorites, and everyday inspiration from our curated collection.</p>
            <div class="hero-actions">
                <a href="#catalog" class="btn btn-primary-custom">Shop now</a>
            </div>
        </div>
    </section>

    <section id="catalog" class="pb-4">
        <div class="mb-4">
            <div class="section-label">Popular titles</div>
            <h2 class="section-title">Browse our collection</h2>
        </div>

        <form method="GET" class="toolbar mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label small text-uppercase fw-bold text-muted">Search</label>
                    <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Search by title, author or keyword">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-uppercase fw-bold text-muted">Author</label>
                    <select name="author" class="form-select">
                        <option value="all" <?= $authorFilter === 'all' ? 'selected' : '' ?>>All authors</option>
                        <?php
                        $authors = [];
                        foreach ($books as $book) {
                            $authorName = trim((string)($book['author'] ?? ''));
                            if ($authorName !== '' && !in_array(strtolower($authorName), array_map('strtolower', $authors), true)) {
                                $authors[] = $authorName;
                            }
                        }
                        sort($authors, SORT_STRING);
                        foreach ($authors as $authorName):
                        ?>
                            <option value="<?= htmlspecialchars($authorName) ?>" <?= strtolower($authorName) === strtolower((string)$authorFilter) ? 'selected' : '' ?>><?= htmlspecialchars($authorName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-uppercase fw-bold text-muted">Price</label>
                    <select name="price" class="form-select">
                        <option value="all" <?= $priceFilter === 'all' ? 'selected' : '' ?>>Any price</option>
                        <option value="low" <?= $priceFilter === 'low' ? 'selected' : '' ?>>Low to high</option>
                        <option value="high" <?= $priceFilter === 'high' ? 'selected' : '' ?>>High to low</option>
                    </select>
                </div>
                <div class="col-12 col-md-1">
                    <button type="submit" class="btn btn-filter w-100">Filter</button>
                </div>
            </div>
        </form>

        <div class="row g-4">
            <?php foreach ($filteredBooks as $book): ?>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="book-card"
                     data-bs-toggle="modal"
                     data-bs-target="#bookModal"
                     data-title="<?= htmlspecialchars($book['title']) ?>"
                     data-author="<?= htmlspecialchars($book['author']) ?>"
                     data-price="<?= htmlspecialchars($book['price']) ?>"
                     data-image="<?= htmlspecialchars($book['img']) ?>"
                     data-description="<?= htmlspecialchars($book['description'] ?? 'A captivating read that brings depth, emotion, and insight to every page.') ?>">
                    <img src="<?= htmlspecialchars($book['img']) ?>" alt="<?= htmlspecialchars($book['title']) ?>">
                    <div class="book-body">
                        <h5 class="book-title"><?= htmlspecialchars($book['title']) ?></h5>
                        <p class="book-author">by <?= htmlspecialchars($book['author']) ?></p>

                        <div class="book-price-wrap">
                            <span class="book-price-label">Price</span>
                            <p class="book-price">रु <?= number_format($book['price']) ?></p>
                        </div>

                        <button type="button" class="details-btn" data-bs-toggle="modal" data-bs-target="#bookModal"
                            data-title="<?= htmlspecialchars($book['title']) ?>"
                            data-author="<?= htmlspecialchars($book['author']) ?>"
                            data-price="<?= htmlspecialchars($book['price']) ?>"
                            data-image="<?= htmlspecialchars($book['img']) ?>"
                            data-description="<?= htmlspecialchars($book['description'] ?? 'A captivating read that brings depth, emotion, and insight to every page.') ?>">
                            View Details
                        </button>

                        <form method="POST" action="index.php" class="mt-2">
                            <input type="hidden" name="book_id" value="<?= (int)($book['id'] ?? 0) ?>">
                            <input type="hidden" name="add_to_cart" value="1">
                            <input type="hidden" name="redirect" value="index.php">
                            <button type="submit" class="add-cart-btn">Add to Cart</button>
                        </form>

                        <?php if (isset($_SESSION['user'])): ?>
                            <a href="checkout.php?title=<?= urlencode($book['title']) ?>&amount=<?= $book['price'] ?>"
                               class="buy-btn">Buy Now</a>
                        <?php else: ?>
                            <a href="signin.php?redirect=checkout.php?title=<?= urlencode($book['title']) ?>&amount=<?= $book['price'] ?>"
                               class="buy-btn">Buy Now</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<div class="modal fade" id="bookModal" tabindex="-1" aria-labelledby="bookModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bookModalLabel">Book Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <img id="modalBookImage" src="" alt="Book cover">
                <h3 id="modalBookTitle" class="mb-2"></h3>
                <p id="modalBookAuthor" class="text-muted mb-2"></p>
                <div id="modalBookPrice" class="modal-price"></div>
                <p id="modalBookDescription" class="mb-0"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<footer class="footer mt-5">
    <div class="container text-center text-muted small">
        Made for <strong class="text-dark">[college project]</strong> · eSewa integrated
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const bookModal = document.getElementById('bookModal');
    if (bookModal) {
        document.querySelectorAll('.book-card, .details-btn').forEach(function (trigger) {
            trigger.addEventListener('click', function (event) {
                if (event.target.closest('a, form, button, .buy-btn, .add-cart-btn')) {
                    return;
                }
                const title = trigger.getAttribute('data-title');
                const author = trigger.getAttribute('data-author');
                const price = trigger.getAttribute('data-price');
                const image = trigger.getAttribute('data-image');
                const description = trigger.getAttribute('data-description');

                document.getElementById('modalBookTitle').textContent = title;
                document.getElementById('modalBookAuthor').textContent = 'by ' + author;
                document.getElementById('modalBookPrice').textContent = 'Rs. ' + Number(price).toLocaleString();
                document.getElementById('modalBookImage').src = image;
                document.getElementById('modalBookDescription').textContent = description;
            });
        });
    }
</script>
</body>
</html>
