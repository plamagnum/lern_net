<?php
/**
 * Загальний шаблон "шапки" сторінки
 * Параметри: $pageTitle, $activePage (nav)
 */
$pageTitle  = $pageTitle  ?? 'LinuxTest';
$activePage = $activePage ?? '';

require_once __DIR__ . '/../../includes/auth.php';
startSession();
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="uk" data-theme="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Тестування знань про систему Linux — питання, відповіді, вікі.">
    <title><?= htmlspecialchars($pageTitle) ?> — LinuxTest</title>
    <link rel="stylesheet" href="/css/style.css">
    <!-- Встановлення теми до завантаження (уникнення миготіння) -->
    <script>
        (function(){
            var t=localStorage.getItem('theme');
            if(t==='dark'||(t===null&&window.matchMedia('(prefers-color-scheme: dark)').matches)){
                document.documentElement.setAttribute('data-theme','dark');
            }
        })();
    </script>
</head>
<body>

<!-- Навігаційна панель -->
<nav class="navbar">
    <a href="/" class="navbar-brand">🐧 LinuxTest</a>

    <!-- Бургер-кнопка для мобільних -->
    <button class="navbar-burger" aria-label="Меню">
        <span></span><span></span><span></span>
    </button>

    <!-- Меню -->
    <div class="navbar-menu">
        <a href="/" class="<?= $activePage==='home' ? 'active' : '' ?>">🏠 Головна</a>
        <a href="/quiz.php" class="<?= $activePage==='quiz' ? 'active' : '' ?>">📝 Тести</a>
        <a href="/wiki.php" class="<?= $activePage==='wiki' ? 'active' : '' ?>">📖 Вікі</a>
        <?php if ($currentUser): ?>
            <a href="/results.php" class="<?= $activePage==='results' ? 'active' : '' ?>">📊 Результати</a>
            <?php if ($currentUser['role'] === 'admin'): ?>
                <a href="/admin/" class="<?= $activePage==='admin' ? 'active' : '' ?>">⚙️ Адмін</a>
            <?php endif; ?>
            <a href="/logout.php">🚪 Вихід (<?= htmlspecialchars($currentUser['username']) ?>)</a>
        <?php else: ?>
            <a href="/login.php" class="<?= $activePage==='login' ? 'active' : '' ?>">🔑 Вхід</a>
            <a href="/register.php" class="<?= $activePage==='register' ? 'active' : '' ?>">📋 Реєстрація</a>
        <?php endif; ?>
        <!-- Перемикач теми -->
        <button id="theme-toggle" class="theme-toggle">🌙 Темна</button>
    </div>
</nav>

<!-- Flash-повідомлення (з сесії) -->
<?php if (!empty($_SESSION['flash'])): ?>
<div class="flash-container">
    <?php foreach ($_SESSION['flash'] as $f): ?>
        <div class="flash-msg <?= $f['type'] ?>"><?= htmlspecialchars($f['msg']) ?></div>
    <?php endforeach; ?>
</div>
<?php unset($_SESSION['flash']); endif; ?>

<main>
