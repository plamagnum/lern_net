<?php
/**
 * Функції автентифікації та управління сесіями
 */

require_once __DIR__ . '/db.php';

// Запускаємо сесію якщо ще не запущена
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('lernnet_session');
        session_start();
    }
}

/**
 * Реєстрація нового користувача
 * Повертає масив ['success' => bool, 'error' => string|null, 'user_id' => int|null]
 */
function registerUser(string $username, string $email, string $password): array {
    // Валідація вхідних даних
    if (strlen($username) < 3 || strlen($username) > 50) {
        return ['success' => false, 'error' => 'Логін має бути від 3 до 50 символів'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Невірний формат email'];
    }
    if (strlen($password) < 6) {
        return ['success' => false, 'error' => 'Пароль має бути не менше 6 символів'];
    }
    if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $username)) {
        return ['success' => false, 'error' => 'Логін може містити лише латинські літери, цифри, _, - та .'];
    }

    $db = getDb();

    // Перевіряємо чи вже існує користувач
    $stmt = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Користувач з таким логіном або email вже існує'];
    }

    // Хешуємо пароль
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    // Створюємо користувача
    $stmt = $db->prepare('INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, \'user\')');
    $stmt->execute([$username, $email, $passwordHash]);
    $userId = (int)$db->lastInsertId();

    return ['success' => true, 'error' => null, 'user_id' => $userId];
}

/**
 * Вхід користувача
 * Повертає масив ['success' => bool, 'error' => string|null]
 */
function loginUser(string $usernameOrEmail, string $password): array {
    $db = getDb();

    // Знаходимо користувача за логіном або email
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
    $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'error' => 'Невірний логін або пароль'];
    }

    // Зберігаємо дані користувача в сесії
    startSession();
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['logged_in'] = true;

    // Оновлюємо ID сесії для безпеки
    session_regenerate_id(true);

    return ['success' => true, 'error' => null];
}

/**
 * Вихід з системи
 */
function logoutUser(): void {
    startSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

/**
 * Перевіряє чи залогований користувач
 */
function isLoggedIn(): bool {
    startSession();
    return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
}

/**
 * Перевіряє чи є користувач адміном
 */
function isAdmin(): bool {
    startSession();
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

/**
 * Повертає дані поточного користувача або null
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    startSession();
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role'     => $_SESSION['role'],
    ];
}

/**
 * Перенаправляє на сторінку входу якщо не залогований
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/**
 * Перенаправляє на головну якщо не адмін
 */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /?error=access_denied');
        exit;
    }
}
