<?php
/**
 * Бічна панель адмін-навігації
 * Підключається через include у всіх адмін-сторінках
 */
$currentFile = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <p class="text-muted text-sm mb-1" style="padding:.3rem .8rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Адмін</p>
    <a href="/admin/" class="<?= $currentFile==='index.php' ? 'active' : '' ?>">🏠 Дашборд</a>
    <a href="/admin/questions.php" class="<?= $currentFile==='questions.php' ? 'active' : '' ?>">❓ Запитання</a>
    <a href="/admin/categories.php" class="<?= $currentFile==='categories.php' ? 'active' : '' ?>">📁 Категорії</a>
    <a href="/admin/wiki.php" class="<?= $currentFile==='wiki.php' ? 'active' : '' ?>">📖 Вікі</a>
    <a href="/admin/users.php" class="<?= $currentFile==='users.php' ? 'active' : '' ?>">👥 Користувачі</a>
    <hr style="border:none;border-top:1px solid var(--border-color);margin:.5rem 0;">
    <a href="/">← На сайт</a>
</aside>
