<?php
require __DIR__ . '/db.php';

$seed = require __DIR__ . '/seeds.php';
$books = $seed['books'] ?? [];
$admin = $seed['admin'] ?? ['username' => 'admin', 'password' => 'admin123', 'email' => 'admin@mini-pasal.local'];

if (!($conn instanceof mysqli)) {
    $booksFile = __DIR__ . '/books.json';
    file_put_contents($booksFile, json_encode($books, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $usersFile = __DIR__ . '/users.json';
    $existingUsers = is_array(json_decode(file_get_contents($usersFile), true)) ? json_decode(file_get_contents($usersFile), true) : [];
    $alreadyHasAdmin = false;
    foreach ($existingUsers as $user) {
        if (strtolower((string)($user['username'] ?? '')) === strtolower((string)$admin['username']) || strtolower((string)($user['email'] ?? '')) === strtolower((string)($admin['email'] ?? ''))) {
            $alreadyHasAdmin = true;
            break;
        }
    }
    if (!$alreadyHasAdmin) {
        $existingUsers[] = [
            'id' => count($existingUsers) + 1,
            'name' => 'Admin',
            'email' => $admin['email'] ?? 'admin@mini-pasal.local',
            'username' => $admin['username'] ?? 'admin',
            'password' => $admin['password'] ?? 'admin123',
            'role' => 'admin',
        ];
        file_put_contents($usersFile, json_encode($existingUsers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    echo "Seed data written to JSON fallback files.\n";
    exit(0);
}

$mysqli = $conn;

$mysqli->query('DELETE FROM books');

foreach ($books as $book) {
    $title = $mysqli->real_escape_string((string)($book['title'] ?? 'Untitled Book'));
    $author = $mysqli->real_escape_string((string)($book['author'] ?? 'Unknown Author'));
    $price = (float)($book['price'] ?? 0);
    $img = $mysqli->real_escape_string((string)($book['img'] ?? $book['image_url'] ?? ''));
    $description = $mysqli->real_escape_string((string)($book['description'] ?? 'A captivating read that brings depth, emotion, and insight to every page.'));
    $mysqli->query("INSERT INTO books (title, author, price, image_url, description) VALUES ('{$title}', '{$author}', {$price}, '{$img}', '{$description}')");
}

$adminUsername = $mysqli->real_escape_string((string)($admin['username'] ?? 'admin'));
$adminEmail = $mysqli->real_escape_string((string)($admin['email'] ?? 'admin@mini-pasal.local'));
$adminPassword = (string)($admin['password'] ?? 'admin123');
$adminCheck = $mysqli->query("SELECT id FROM users WHERE username = '{$adminUsername}' OR email = '{$adminEmail}' LIMIT 1");
if ($adminCheck && $adminCheck->num_rows === 0) {
    $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
    $mysqli->query("INSERT INTO users (name, email, username, password, role) VALUES ('Admin', '{$adminEmail}', '{$adminUsername}', '{$mysqli->real_escape_string($hash)}', 'admin')");
}

$count = $mysqli->query('SELECT COUNT(*) AS total FROM books');
$row = $count ? $count->fetch_assoc() : ['total' => 0];

echo "Seeded " . (int)($row['total'] ?? 0) . " books successfully.\n";
