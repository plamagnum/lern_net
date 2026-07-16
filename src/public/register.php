<?php
/**
 * Сторінка реєстрації нового користувача
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

startSession();

// Якщо вже залогований — перенаправляємо
if (isLoggedIn()) {
    header('Location: /');
    exit;
}

$error   = '';
$success = '';

// Обробка форми реєстрації
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($password !== $password2) {
        $error = 'Паролі не співпадають.';
    } else {
        $result = registerUser($username, $email, $password);
        if ($result['success']) {
            // Автоматично входимо після реєстрації
            loginUser($username, $password);
            $_SESSION['flash'][] = ['type' => 'success', 'msg' => 'Ласкаво просимо, ' . $username . '!'];
            header('Location: /');
            exit;
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="uk" data-theme="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Реєстрація — LinuxTest</title>
    <link rel="stylesheet" href="/css/style.css">
    <script>(function(){var t=localStorage.getItem('theme');if(t==='dark'||(t===null&&window.matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.setAttribute('data-theme','dark');}})()</script>
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">🐧 LinuxTest</div>

        <div class="card">
            <h2 class="card-title text-center">Реєстрація</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="/register.php">
                <div class="form-group">
                    <label class="form-label" for="username">Логін</label>
                    <input type="text" id="username" name="username" class="form-control"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           placeholder="myusername"
                           autocomplete="username" required>
                    <span class="form-hint">Від 3 до 50 символів. Лише латиниця, цифри, _ - .</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="you@example.com"
                           autocomplete="email" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Пароль</label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Мінімум 6 символів"
                           autocomplete="new-password" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password2">Підтвердити пароль</label>
                    <input type="password" id="password2" name="password2" class="form-control"
                           placeholder="Повторіть пароль"
                           autocomplete="new-password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">📋 Зареєструватись</button>
            </form>

            <p class="text-center text-sm mt-2">
                Вже маєте акаунт? <a href="/login.php">Увійти</a>
            </p>
        </div>

        <div class="text-center mt-2">
            <button id="theme-toggle" class="theme-toggle" style="background:rgba(0,0,0,.06);border:1px solid rgba(0,0,0,.12);border-radius:20px;padding:.3rem .7rem;font-size:.85rem;cursor:pointer;">🌙 Темна</button>
        </div>
    </div>
</div>
<script src="/js/app.js"></script>
</body>
</html>
