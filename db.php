<?php
// db.php - MySQL connection with a JSON fallback for local demo environments.
// Prefer a dedicated app user instead of the root account.
$host = getenv('DB_HOST') ?: 'localhost';
$dbuser = getenv('DB_USER') ?: 'mini_pasal_user';
$dbpass = getenv('DB_PASS') ?: 'mini_pasal123';
$dbname = getenv('DB_NAME') ?: 'mini_pasal';

// If you already created a different DB user, set these in the environment before running PHP:
// export DB_HOST=localhost
// export DB_USER=mini_pasal_user
// export DB_PASS=your_password
// export DB_NAME=mini_pasal

class LocalFileDataStore {
    private $baseDir;

    public function __construct() {
        $this->baseDir = __DIR__;

        foreach (['books.json', 'users.json', 'orders.json'] as $file) {
            $path = $this->baseDir . '/' . $file;
            if (!file_exists($path)) {
                file_put_contents($path, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }
        }
    }

    public function readJsonFile(string $file): array {
        $path = $this->baseDir . '/' . $file;
        if (!file_exists($path)) {
            file_put_contents($path, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return [];
        }

        $raw = file_get_contents($path);
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    public function writeJsonFile(string $file, array $data): void {
        $path = $this->baseDir . '/' . $file;
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}

class LocalFileOrderConnection extends LocalFileDataStore {
    public function __construct() {
        parent::__construct();
    }

    public function prepare($sql) {
        return new LocalFileOrderStatement($this, $sql);
    }

    public function close() {
        return true;
    }
}

class LocalFileOrderStatement {
    private $store;
    private $sql;
    private $params = [];

    public function __construct(LocalFileDataStore $store, $sql) {
        $this->store = $store;
        $this->sql = $sql;
    }

    public function bind_param($types, &...$args) {
        $this->params = $args;
        return true;
    }

    public function close() {
        $this->params = [];
        $this->sql = '';
        return true;
    }

    public function execute() {
        $sql = trim($this->sql);

        if (stripos($sql, 'INSERT INTO orders') === 0) {
            $orders = $this->store->readJsonFile('orders.json');
            $bookTitle = $this->params[0] ?? '';
            $amount = $this->params[1] ?? 0;
            $customerName = $this->params[2] ?? '';
            $phone = $this->params[3] ?? '';
            $transactionUuid = $this->params[4] ?? '';

            $orders[] = [
                'book_title' => $bookTitle,
                'amount' => (string)$amount,
                'customer_name' => $customerName,
                'phone' => $phone,
                'transaction_uuid' => $transactionUuid,
                'status' => 'PENDING',
                'created_at' => date('c'),
            ];

            $this->store->writeJsonFile('orders.json', $orders);
            return true;
        }

        if (stripos($sql, 'UPDATE orders SET status') === 0) {
            $orders = $this->store->readJsonFile('orders.json');
            $transactionUuid = $this->params[0] ?? '';
            $updated = false;

            foreach ($orders as &$order) {
                if (($order['transaction_uuid'] ?? '') === $transactionUuid) {
                    $order['status'] = 'PAID';
                    $updated = true;
                }
            }
            unset($order);

            if ($updated) {
                $this->store->writeJsonFile('orders.json', $orders);
            }

            return true;
        }

        return false;
    }
}

function initializeDatabaseSchema(mysqli $mysqli): void {
    $mysqli->set_charset('utf8mb4');

    $schema = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            username VARCHAR(100) NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('user', 'admin') DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS books (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            author VARCHAR(150) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            image_url TEXT NULL,
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            book_title VARCHAR(255) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            customer_name VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            transaction_uuid VARCHAR(100) UNIQUE NOT NULL,
            status VARCHAR(20) DEFAULT 'PENDING',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        );
    ";

    if (!$mysqli->multi_query($schema)) {
        return;
    }

    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());

    $seed = require_once __DIR__ . '/seeds.php';
    $adminData = $seed['admin'] ?? ['username' => 'admin', 'password' => 'admin123'];
    $adminUsername = (string)($adminData['username'] ?? 'admin');
    $adminEmail = (string)($adminData['email'] ?? 'admin@mini-pasal.local');
    $adminPassword = (string)($adminData['password'] ?? 'admin123');

    $check = $mysqli->query("SELECT id FROM users WHERE username = '{$mysqli->real_escape_string($adminUsername)}' OR email = '{$mysqli->real_escape_string($adminEmail)}' LIMIT 1");
    if ($check && $check->num_rows === 0) {
        $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
        $mysqli->query("INSERT INTO users (name, email, username, password, role) VALUES ('Admin', '{$mysqli->real_escape_string($adminEmail)}', '{$mysqli->real_escape_string($adminUsername)}', '{$mysqli->real_escape_string($hash)}', 'admin')");
    }

    $bookCount = $mysqli->query('SELECT COUNT(*) as total FROM books');
    if ($bookCount && $bookCount->fetch_assoc()['total'] == 0) {
        $books = $seed['books'] ?? [];
        foreach ($books as $book) {
            $title = $mysqli->real_escape_string((string)($book['title'] ?? 'Untitled Book'));
            $author = $mysqli->real_escape_string((string)($book['author'] ?? 'Unknown Author'));
            $price = (float)($book['price'] ?? 0);
            $img = $mysqli->real_escape_string((string)($book['img'] ?? $book['image_url'] ?? ''));
            $description = $mysqli->real_escape_string((string)($book['description'] ?? 'A captivating read that brings depth, emotion, and insight to every page.'));
            $mysqli->query("INSERT INTO books (title, author, price, image_url, description) VALUES ('{$title}', '{$author}', {$price}, '{$img}', '{$description}')");
        }
    }
}

function connectToDatabase(): ?mysqli {
    global $host, $dbuser, $dbpass, $dbname;

    if (!class_exists('mysqli')) {
        return null;
    }

    try {
        $serverConnection = new mysqli($host, $dbuser, $dbpass);
        if (!$serverConnection || $serverConnection->connect_error !== null) {
            return null;
        }

        $serverConnection->query("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $serverConnection->select_db($dbname);
        initializeDatabaseSchema($serverConnection);
        return $serverConnection;
    } catch (Throwable $e) {
        return null;
    }
}

$conn = false;

if (class_exists('mysqli')) {
    try {
        $mysqli = connectToDatabase();
        if ($mysqli instanceof mysqli && $mysqli->connect_error === null) {
            $conn = $mysqli;
        }
    } catch (Throwable $e) {
        $conn = false;
    }
}

if ($conn === false) {
    $conn = new LocalFileOrderConnection();
}

function normalizeBookRow(array $book): array {
    $normalized = $book;
    $normalized['id'] = (int)($normalized['id'] ?? 0);
    $normalized['title'] = (string)($normalized['title'] ?? 'Untitled Book');
    $normalized['author'] = (string)($normalized['author'] ?? 'Unknown Author');
    $normalized['price'] = (float)($normalized['price'] ?? 0);
    $normalized['description'] = (string)($normalized['description'] ?? 'A captivating read that brings depth, emotion, and insight to every page.');
    if (!isset($normalized['img']) && isset($normalized['image_url'])) {
        $normalized['img'] = $normalized['image_url'];
    }
    if (!isset($normalized['image_url']) && isset($normalized['img'])) {
        $normalized['image_url'] = $normalized['img'];
    }
    if (($normalized['img'] ?? '') === '') {
        $normalized['img'] = 'data:image/svg+xml;charset=UTF-8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="600" height="840"><rect width="600" height="840" fill="#1f3d2c"/><text x="50%" y="50%" text-anchor="middle" fill="#fff" font-size="32" font-family="Arial, sans-serif">Book</text></svg>');
    }
    return $normalized;
}

function normalizeUserRow(array $user): array {
    $normalized = $user;
    $normalized['id'] = (int)($normalized['id'] ?? 0);
    $normalized['name'] = (string)($normalized['name'] ?? '');
    $normalized['email'] = (string)($normalized['email'] ?? '');
    $normalized['username'] = (string)($normalized['username'] ?? '');
    $normalized['role'] = (string)($normalized['role'] ?? 'user');
    return $normalized;
}

function loadBooks(): array {
    global $conn;

    if ($conn instanceof mysqli) {
        $result = $conn->query('SELECT * FROM books ORDER BY id ASC');
        $books = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $books[] = normalizeBookRow($row);
            }
        }
        return $books;
    }

    $file = __DIR__ . '/books.json';
    if (!file_exists($file)) {
        $seed = require_once __DIR__ . '/seeds.php';
        file_put_contents($file, json_encode($seed['books'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data)) {
        return [];
    }

    foreach ($data as &$book) {
        $book = normalizeBookRow($book);
    }
    unset($book);

    return $data;
}

function saveBooks(array $books): void {
    global $conn;

    if ($conn instanceof mysqli) {
        $conn->query('DELETE FROM books');
        foreach ($books as $book) {
            $title = $conn->real_escape_string((string)($book['title'] ?? 'Untitled Book'));
            $author = $conn->real_escape_string((string)($book['author'] ?? 'Unknown Author'));
            $price = (float)($book['price'] ?? 0);
            $img = $conn->real_escape_string((string)($book['img'] ?? $book['image_url'] ?? ''));
            $desc = $conn->real_escape_string((string)($book['description'] ?? 'A captivating read that brings depth, emotion, and insight to every page.'));
            $conn->query("INSERT INTO books (title, author, price, image_url, description) VALUES ('{$title}', '{$author}', {$price}, '{$img}', '{$desc}')");
        }
        return;
    }

    $file = __DIR__ . '/books.json';
    file_put_contents($file, json_encode(array_map('normalizeBookRow', $books), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function loadUsers(): array {
    global $conn;

    if ($conn instanceof mysqli) {
        $result = $conn->query('SELECT * FROM users ORDER BY id ASC');
        $users = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = normalizeUserRow($row);
            }
        }
        return $users;
    }

    $file = __DIR__ . '/users.json';
    if (!file_exists($file)) {
        file_put_contents($file, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? array_map('normalizeUserRow', $data) : [];
}

function saveUsers(array $users): void {
    global $conn;

    if ($conn instanceof mysqli) {
        $conn->query('DELETE FROM users');
        foreach ($users as $user) {
            $name = $conn->real_escape_string((string)($user['name'] ?? ''));
            $email = $conn->real_escape_string((string)($user['email'] ?? ''));
            $username = $conn->real_escape_string((string)($user['username'] ?? ''));
            $password = $conn->real_escape_string((string)($user['password'] ?? ''));
            $role = $conn->real_escape_string((string)($user['role'] ?? 'user'));
            $conn->query("INSERT INTO users (name, email, username, password, role) VALUES ('{$name}', '{$email}', '{$username}', '{$password}', '{$role}')");
        }
        return;
    }

    $file = __DIR__ . '/users.json';
    file_put_contents($file, json_encode(array_map('normalizeUserRow', $users), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function getAdminSeed(): array {
    $seed = require_once __DIR__ . '/seeds.php';
    return $seed['admin'] ?? ['username' => 'admin', 'password' => 'admin123'];
}
?>
