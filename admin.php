<?php
require __DIR__ . '/db.php';

$defaultBooks = (require_once __DIR__ . '/seeds.php')['books'] ?? [];

$makeBookImage = function(string $title): string {
    $safeTitle = preg_replace('/[^A-Za-z0-9 ]+/', ' ', $title);
    $safeTitle = trim($safeTitle);
    if ($safeTitle === '') {
        $safeTitle = 'Book';
    }

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="840" viewBox="0 0 600 840">'
        . '<defs><linearGradient id="g" x1="0" x2="1" y1="0" y2="1"><stop offset="0%" stop-color="#5e2a2a"/><stop offset="100%" stop-color="#1f3d2c"/></linearGradient></defs>'
        . '<rect width="600" height="840" fill="url(#g)" rx="28"/>'
        . '<circle cx="300" cy="280" r="130" fill="rgba(255,255,255,0.12)"/>'
        . '<path d="M170 610c28-104 84-150 130-150s102 46 130 150" fill="rgba(255,255,255,0.18)"/>'
        . '<text x="50%" y="62%" text-anchor="middle" fill="#fff" font-family="Arial, sans-serif" font-size="34" font-weight="700">'.htmlspecialchars($safeTitle, ENT_QUOTES, 'UTF-8').'</text>'
        . '</svg>';

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
};

$books = loadBooks();
if (empty($books)) {
    $books = $defaultBooks;
    saveBooks($books);
} else {
    foreach ($books as &$book) {
        $book = normalizeBookRow($book);
    }
    unset($book);
}

$message = '';
$editingBook = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $deleteId = (int)($_POST['delete_id'] ?? 0);
        $books = array_values(array_filter($books, function ($book) use ($deleteId) {
            return (int)($book['id'] ?? 0) !== $deleteId;
        }));
        saveBooks($books);
        $message = 'Book deleted successfully.';
    } elseif (isset($_POST['update_id'])) {
        $updateId = (int)($_POST['update_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $price = trim($_POST['price'] ?? '');
        $img = trim($_POST['img'] ?? '');
        $description = trim($_POST['description'] ?? '');

        foreach ($books as &$book) {
            if ((int)($book['id'] ?? 0) === $updateId) {
                $book['title'] = $title;
                $book['author'] = $author;
                $book['price'] = (float)$price;
                $book['img'] = $img !== '' ? $img : $makeBookImage($title);
                $book['description'] = $description !== '' ? $description : 'A captivating read that brings depth, emotion, and insight to every page.';
                break;
            }
        }
        unset($book);

        saveBooks($books);
        $message = 'Book updated successfully.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $price = trim($_POST['price'] ?? '');
        $img = trim($_POST['img'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($title !== '' && $author !== '' && $price !== '' && is_numeric($price)) {
            $nextId = 1;
            foreach ($books as $book) {
                $nextId = max($nextId, (int)($book['id'] ?? 0) + 1);
            }

            $books[] = [
                'id' => $nextId,
                'title' => $title,
                'author' => $author,
                'price' => (float)$price,
                'img' => $img !== '' ? $img : $makeBookImage($title),
                'description' => $description !== '' ? $description : 'A captivating read that brings depth, emotion, and insight to every page.'
            ];

            saveBooks($books);
            $message = 'Book added successfully.';
        } else {
            $message = 'Please enter a valid title, author, and numeric price.';
        }
    }
}

if (isset($_GET['edit_id'])) {
    $editId = (int)$_GET['edit_id'];
    foreach ($books as $book) {
        if ((int)($book['id'] ?? 0) === $editId) {
            $editingBook = $book;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Mini Pasal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(180deg, #f7f1e8 0%, #fdfaf5 100%);
            font-family: 'Segoe UI', sans-serif;
            color: #2d2a27;
        }
        .admin-shell {
            max-width: 1100px;
            margin: 48px auto;
            padding: 0 20px 40px;
        }
        .card {
            border: 0;
            border-radius: 22px;
            box-shadow: 0 18px 34px rgba(38, 31, 25, 0.08);
        }
        .panel-header {
            background: linear-gradient(135deg, #3b2f2c, #1f3d2c);
            color: #fff;
            border-radius: 22px 22px 0 0;
        }
        .btn-submit {
            background: linear-gradient(135deg, #1f3d2c, #416653);
            border: none;
            border-radius: 999px;
            padding: 0.8rem 1.4rem;
            font-weight: 600;
        }
        .btn-submit:hover {
            filter: brightness(1.05);
        }
        .success-banner {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            background: linear-gradient(135deg, #edf8ee, #e2f5e3);
            border: 1px solid #b9e0bc;
            border-left: 5px solid #2d7a4b;
            border-radius: 16px;
            color: #1d4d2f;
            padding: 0.9rem 1rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
            box-shadow: 0 10px 18px rgba(45, 122, 75, 0.08);
        }
        .success-banner .icon {
            width: 2rem;
            height: 2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(45, 122, 75, 0.12);
            border-radius: 50%;
            font-size: 1.1rem;
        }
        .btn-danger-soft {
            background: #f8e4e1;
            color: #7d2e2e;
            border: 1px solid #e6b7af;
            border-radius: 999px;
            font-weight: 600;
        }
        .btn-warning-soft {
            background: #f9f0d9;
            color: #7b5e1b;
            border: 1px solid #e1d3a5;
            border-radius: 999px;
            font-weight: 600;
        }
        .table thead th {
            background: #f3e8dc;
            color: #3d2f29;
        }
        .book-row td {
            vertical-align: middle;
        }
        .book-thumb {
            width: 56px;
            height: 70px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>
    <div class="admin-shell">
        <div class="card overflow-hidden">
            <div class="panel-header px-4 py-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <div class="text-uppercase small fw-semibold opacity-75">Mini Pasal</div>
                        <h1 class="h3 mb-0">Admin Panel</h1>
                    </div>
                    <a href="index.php" class="btn btn-light">View Shop</a>
                </div>
            </div>

            <div class="p-4 p-lg-5">
                <?php if ($message): ?>
                    <div class="success-banner">
                        <span class="icon">✓</span>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                <?php endif; ?>

                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body p-4">
                                <h2 class="h4 mb-3"><?= $editingBook ? 'Edit Book' : 'Add New Book' ?></h2>
                                <form method="POST">
                                    <?php if ($editingBook): ?>
                                        <input type="hidden" name="update_id" value="<?= (int)($editingBook['id'] ?? 0) ?>">
                                    <?php endif; ?>
                                    <div class="mb-3">
                                        <label class="form-label">Book Title</label>
                                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($editingBook['title'] ?? '') ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Author</label>
                                        <input type="text" name="author" class="form-control" value="<?= htmlspecialchars($editingBook['author'] ?? '') ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Price</label>
                                        <input type="number" name="price" min="1" step="0.01" class="form-control" value="<?= htmlspecialchars((string)($editingBook['price'] ?? '')) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Image URL</label>
                                        <input type="url" name="img" class="form-control" value="<?= htmlspecialchars($editingBook['img'] ?? '') ?>" placeholder="Optional">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea name="description" class="form-control" rows="4" placeholder="Short book description"><?= htmlspecialchars($editingBook['description'] ?? '') ?></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-submit text-white w-100"><?= $editingBook ? 'Update Book' : 'Save Book' ?></button>
                                    <?php if ($editingBook): ?>
                                        <a href="admin.php" class="btn btn-outline-secondary w-100 mt-2">Cancel Edit</a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body p-4">
                                <h2 class="h4 mb-3">Current Books</h2>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Image</th>
                                                <th>Title</th>
                                                <th>Author</th>
                                                <th>Price</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($books as $book): ?>
                                                <tr class="book-row">
                                                    <td>
                                                        <img src="<?= htmlspecialchars($book['img'] ?? 'https://via.placeholder.com/200x280?text=Book') ?>" class="book-thumb" alt="<?= htmlspecialchars($book['title'] ?? 'Book') ?>">
                                                    </td>
                                                    <td><?= htmlspecialchars($book['title'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($book['author'] ?? '') ?></td>
                                                    <td>Rs. <?= number_format((float)($book['price'] ?? 0), 0) ?></td>
                                                    <td>
                                                        <div class="d-flex gap-2">
                                                            <a href="admin.php?edit_id=<?= (int)($book['id'] ?? 0) ?>" class="btn btn-warning-soft btn-sm">Edit</a>
                                                            <form method="POST" onsubmit="return confirm('Delete this book?');">
                                                                <input type="hidden" name="delete_id" value="<?= (int)($book['id'] ?? 0) ?>">
                                                                <button type="submit" class="btn btn-danger-soft btn-sm">Delete</button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
