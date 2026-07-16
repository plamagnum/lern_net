<?php
/**
 * Конфігурація програми LinuxTest
 * Читає налаштування з змінних середовища Docker або використовує значення за замовчуванням
 */

// Налаштування бази даних (з env-змінних Docker або defaults)
define('DB_HOST', getenv('DB_HOST') ?: 'mysql');
define('DB_NAME', getenv('DB_NAME') ?: 'lernnet');
define('DB_USER', getenv('DB_USER') ?: 'lernnet');
define('DB_PASS', getenv('DB_PASS') ?: 'lernnet_pass');

// Секретний ключ для сесій
define('SESSION_SECRET', getenv('SESSION_SECRET') ?: 'lernnet_session_secret_key');

// API-ключ для захисту API-ендпоінтів
define('ADMIN_API_KEY', getenv('ADMIN_API_KEY') ?: 'super_secret_admin_key_2024');

// Кількість запитань за замовчуванням у тесті
define('QUIZ_QUESTIONS_COUNT', 10);

// Версія програми
define('APP_VERSION', '1.0.0');
define('APP_NAME', 'LinuxTest');
