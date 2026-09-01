<?php
session_start();
require __DIR__ . '/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        $users = loadUsers();

        foreach ($users as $user) {
            if (strtolower((string)($user['email'] ?? '')) === strtolower($email)) {
                $error = 'An account with this email already exists.';
                break;
            }
        }

        if ($error === '') {
            $users[] = [
                'id' => time(),
                'name' => $name,
                'email' => $email,
                'username' => strtolower(str_replace(' ', '', $name)),
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'user',
            ];
            saveUsers($users);
            $_SESSION['user'] = ['id' => $users[count($users) - 1]['id'], 'name' => $name, 'email' => $email];
            $success = 'Account created successfully.';
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | Mini Pasal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(180deg, #f7f1e8 0%, #fdfaf5 100%);
            font-family: 'Segoe UI', sans-serif;
        }
        .auth-card {
            max-width: 460px;
            margin: 80px auto;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(33, 29, 25, 0.08);
            border: 0;
        }
        .auth-header {
            background: linear-gradient(135deg, #3b2f2c, #1f3d2c);
            color: white;
            padding: 2rem 1.5rem;
        }
        .auth-body {
            padding: 2rem 1.5rem;
            background: #fff;
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, #1f3d2c, #416653);
            border: none;
            border-radius: 999px;
            padding: 0.8rem 1.2rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="card auth-card">
        <div class="auth-header">
            <div class="text-uppercase small fw-semibold opacity-75">Mini Pasal</div>
            <h1 class="h3 mb-0 mt-2">Create account</h1>
        </div>
        <div class="auth-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary-custom text-white w-100">Sign Up</button>
            </form>
            <p class="mb-0 mt-3 text-center">
                Already have an account? <a href="signin.php">Sign in</a>
            </p>
        </div>
    </div>
</body>
</html>
