<?php
/**
 * Підключення до бази даних через PDO
 * Реалізує Singleton-патерн для уникнення множинних з'єднань
 */

require_once __DIR__ . '/config.php';

/**
 * Повертає єдине PDO-з'єднання з базою даних
 */
function getDb(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            DB_HOST,
            DB_NAME
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'",
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Логуємо помилку, але не показуємо деталі користувачу
            error_log('Помилка підключення до БД: ' . $e->getMessage());
            http_response_code(503);
            // Перевіряємо чи це API-запит (повертаємо JSON) або звичайна сторінка
            $isApi = (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false);
            if ($isApi) {
                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(['success' => false, 'error' => 'Сервіс тимчасово недоступний']));
            } else {
                die('<html><body><h1>503 — Сервіс тимчасово недоступний</h1><p>Будь ласка, спробуйте пізніше.</p></body></html>');
            }
        }
    }

    return $pdo;
}
