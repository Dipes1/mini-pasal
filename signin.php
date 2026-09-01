<?php
session_start();
require __DIR__ . '/db.php';

$redirectAfterLogin = $_POST['redirect'] ?? ($_GET['redirect'] ?? ($_SESSION['redirect_after_login'] ?? 'index.php'));
unset($_SESSION['redirect_after_login']);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginValue = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($loginValue === '' || $password === '') {
        $error = 'Please enter both email/username and password.';
    } else {
        $adminData = getAdminSeed();
        $adminUsername = strtolower((string)($adminData['username'] ?? 'admin'));
        $adminPassword = (string)($adminData['password'] ?? 'admin123');

        if (strtolower($loginValue) === $adminUsername && $password === $adminPassword) {
            $_SESSION['user'] = [
                'id' => 0,
                'name' => 'Admin',
                'email' => 'admin@mini-pasal.local',
                'role' => 'admin',
            ];
            $finalRedirect = $_POST['redirect'] ?? $redirectAfterLogin;
            $target = str_contains($finalRedirect, 'admin.php') ? $finalRedirect : 'admin.php';
            header('Location: ' . $target);
            exit;
        }

        $users = loadUsers();

        $matched = false;
        foreach ($users as $user) {
            $storedEmail = strtolower((string)($user['email'] ?? ''));
            $storedUsername = strtolower((string)($user['username'] ?? ''));
            $storedName = strtolower((string)($user['name'] ?? ''));
            $loginMatches = $storedEmail === strtolower($loginValue) || $storedUsername === strtolower($loginValue) || $storedName === strtolower($loginValue);
            $storedPassword = (string)($user['password'] ?? '');
            $passwordValid = password_verify($password, $storedPassword) || ($storedPassword !== '' && $storedPassword === $password);

            if ($loginMatches && $passwordValid) {
                $_SESSION['user'] = ['id' => (int)($user['id'] ?? 0), 'name' => (string)($user['name'] ?? ''), 'email' => (string)($user['email'] ?? ''), 'role' => (string)($user['role'] ?? 'user')];
                $matched = true;
                break;
            }
        }

        if ($matched) {
            $finalRedirect = $_POST['redirect'] ?? $redirectAfterLogin;
            header('Location: ' . $finalRedirect);
            exit;
        }

        $error = 'Invalid email/username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Mini Pasal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(180deg, #f7f1e8 0%, #fdfaf5 100%);
            font-family: 'Segoe UI', sans-serif;
        }
        .auth-card {
            max-width: 460px;
            margin: 100px auto;
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
            <h1 class="h3 mb-0 mt-2">Welcome back</h1>
        </div>
        <div class="auth-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectAfterLogin) ?>">
                <div class="mb-3">
                    <label class="form-label">Email or Username</label>
                    <input type="text" name="email" class="form-control" placeholder="admin or your@email.com" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary-custom text-white w-100">Sign In</button>
            </form>
            <p class="mb-0 mt-3 text-center">
                No account yet? <a href="signup.php">Create one</a>
            </p>
        </div>
    </div>
</body>
</html>
