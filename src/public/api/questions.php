<?php
/**
 * REST API для запитань — для зовнішніх парсерів
 *
 * POST /api/questions.php — додати нове запитання з відповідями
 * GET  /api/questions.php — список запитань (для перевірки)
 *
 * Авторизація: заголовок Authorization: ******
 * або параметр ?api_key=super_secret_admin_key_2024
 *
 * Приклад curl-запиту:
 * curl -X POST http://localhost/api/questions.php \
 *   -H "Authorization: ******" \
 *   -H "Content-Type: application/json" \
 *   -d '{
 *     "question":    "Яка команда виводить список файлів?",
 *     "category":    "Файлова система",
 *     "explanation": "Команда ls виводить вміст директорії",
 *     "answers": [
 *       {"text": "ls",  "correct": true},
 *       {"text": "dir", "correct": false},
 *       {"text": "cat", "correct": false},
 *       {"text": "pwd", "correct": false}
 *     ]
 *   }'
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

// ----- Перевірка API-ключа -----
function checkApiKey(): bool {
    // Перевіряємо заголовок Authorization: ******
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $m)) {
        if ($m[1] === ADMIN_API_KEY) return true;
    }

    // Також приймаємо ?api_key= у GET-параметрі
    $paramKey = $_GET['api_key'] ?? '';
    if ($paramKey === ADMIN_API_KEY) return true;

    // Перевіряємо api_tokens у БД (додаткові токени)
    $anyKey = $m[1] ?? $paramKey;
    if ($anyKey) {
        $db   = getDb();
        $stmt = $db->prepare('SELECT id FROM api_tokens WHERE token = ? AND is_active = 1');
        $stmt->execute([$anyKey]);
        if ($stmt->fetch()) return true;
    }

    return false;
}

if (!checkApiKey()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Невірний або відсутній API-ключ']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // -------------------------------------------
        // GET: список запитань
        // -------------------------------------------
        $categoryId = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) ?: null;
        $limit      = min((int)($_GET['limit'] ?? 20), 100);

        $db = getDb();
        if ($categoryId) {
            $stmt = $db->prepare(
                'SELECT q.id, q.question, q.explanation, c.name AS category
                 FROM questions q JOIN categories c ON c.id = q.category_id
                 WHERE q.category_id = ? LIMIT ?'
            );
            $stmt->execute([$categoryId, $limit]);
        } else {
            $stmt = $db->prepare(
                'SELECT q.id, q.question, q.explanation, c.name AS category
                 FROM questions q JOIN categories c ON c.id = q.category_id
                 LIMIT ?'
            );
            $stmt->execute([$limit]);
        }
        $questions = $stmt->fetchAll();

        // Для кожного питання — відповіді
        foreach ($questions as &$q) {
            $aStmt = $db->prepare('SELECT id, answer_text AS text, is_correct AS correct FROM answers WHERE question_id = ?');
            $aStmt->execute([$q['id']]);
            $q['answers'] = $aStmt->fetchAll();
        }

        echo json_encode(['success' => true, 'count' => count($questions), 'questions' => $questions]);

    } elseif ($method === 'POST') {
        // -------------------------------------------
        // POST: додати нове запитання
        // -------------------------------------------
        $body = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Невірний JSON']);
            exit;
        }

        // Валідація обов'язкових полів
        $question    = trim($body['question'] ?? '');
        $categoryStr = trim($body['category'] ?? '');
        $answers     = $body['answers']  ?? [];
        $explanation = trim($body['explanation'] ?? '');

        if (empty($question)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Поле "question" є обов\'язковим']);
            exit;
        }
        if (empty($categoryStr)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Поле "category" є обов\'язковим']);
            exit;
        }
        if (!is_array($answers) || count($answers) !== 4) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Необхідно надати рівно 4 варіанти відповідей у полі "answers"']);
            exit;
        }

        // Перевіряємо що є одна правильна відповідь
        $correctCount = 0;
        foreach ($answers as $a) {
            if (!empty($a['correct'])) $correctCount++;
            if (empty($a['text'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Кожна відповідь повинна мати поле "text"']);
                exit;
            }
        }
        if ($correctCount !== 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Рівно одна відповідь повинна бути правильною (correct: true)']);
            exit;
        }

        $db = getDb();

        // Знаходимо або створюємо категорію
        $stmt = $db->prepare('SELECT id FROM categories WHERE name = ?');
        $stmt->execute([$categoryStr]);
        $cat = $stmt->fetch();

        if ($cat) {
            $categoryId = (int)$cat['id'];
        } else {
            // Створюємо нову категорію
            $stmt = $db->prepare('INSERT INTO categories (name) VALUES (?)');
            $stmt->execute([$categoryStr]);
            $categoryId = (int)$db->lastInsertId();
        }

        // Зберігаємо запитання
        $stmt = $db->prepare('INSERT INTO questions (category_id, question, explanation) VALUES (?, ?, ?)');
        $stmt->execute([$categoryId, $question, $explanation]);
        $questionId = (int)$db->lastInsertId();

        // Зберігаємо варіанти відповідей
        $stmt = $db->prepare('INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)');
        foreach ($answers as $a) {
            $stmt->execute([$questionId, trim($a['text']), empty($a['correct']) ? 0 : 1]);
        }

        http_response_code(201);
        echo json_encode([
            'success'     => true,
            'question_id' => $questionId,
            'category_id' => $categoryId,
            'message'     => 'Запитання успішно додано',
        ]);

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Метод не підтримується. Дозволені: GET, POST']);
    }

} catch (Exception $e) {
    error_log('Questions API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Внутрішня помилка сервера']);
}
