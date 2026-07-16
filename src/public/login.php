<?php
/**
 * Сторінка входу в систему
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

startSession();

// Якщо вже залогований — перенаправляємо
if (isLoggedIn()) {
    header('Location: /');
    exit;
}

$redirect = '/';
$rawRedirect = filter_input(INPUT_GET, 'redirect', FILTER_UNSAFE_RAW) ?? '';
// Дозволяємо лише відносні шляхи (без зовнішніх URL) для запобігання відкритим перенаправленням
if ($rawRedirect && preg_match('#^/[^/\\\\]#', $rawRedirect)) {
    $redirect = $rawRedirect;
}

// Обробка форми входу
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = trim($_POST['username'] ?? '');
    $password        = $_POST['password'] ?? '';

    if (empty($usernameOrEmail) || empty($password)) {
        $error = 'Будь ласка, заповніть усі поля.';
    } else {
        $result = loginUser($usernameOrEmail, $password);
$error = '';

        if ($result['success']) {
            // Перенаправляємо після успішного входу (лише на відносні шляхи)
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = $result['error'];
        }
    }
}

$pageTitle = 'Вхід';
?>
<!DOCTYPE html>
<html lang="uk" data-theme="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вхід — LinuxTest</title>
    <link rel="stylesheet" href="/css/style.css">
    <script>(function(){var t=localStorage.getItem('theme');if(t==='dark'||(t===null&&window.matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.setAttribute('data-theme','dark');}})()</script>
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">🐧 LinuxTest</div>

        <div class="card">
            <h2 class="card-title text-center">Вхід в систему</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="/login.php?redirect=<?= htmlspecialchars(urlencode($redirect)) ?>">
                <div class="form-group">
                    <label class="form-label" for="username">Логін або Email</label>
                    <input type="text" id="username" name="username" class="form-control"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           placeholder="admin або email@example.com"
                           autocomplete="username" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Пароль</label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="••••••••"
                           autocomplete="current-password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">🔑 Увійти</button>
            </form>

            <p class="text-center text-sm mt-2">
                Немає акаунту? <a href="/register.php">Зареєструватись</a>
            </p>
        </div>

        <!-- Перемикач теми -->
        <div class="text-center mt-2">
            <button id="theme-toggle" class="theme-toggle" style="background:rgba(0,0,0,.06);border:1px solid rgba(0,0,0,.12);border-radius:20px;padding:.3rem .7rem;font-size:.85rem;cursor:pointer;">🌙 Темна</button>
        </div>
    </div>
</div>
<script src="/js/app.js"></script>
</body>
</html>
