<?php
/**
 * Quiz API — ендпоінти для JavaScript-клієнта
 * GET  /api/quiz.php?action=questions[&category=ID]  — список запитань
 * GET  /api/quiz.php?action=get_question&id=ID        — одне запитання (для адміна)
 * POST /api/quiz.php?action=check                     — перевірка відповіді
 * POST /api/quiz.php?action=finish                    — завершення тесту, збереження результату
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Встановлюємо JSON-відповідь
header('Content-Type: application/json; charset=utf-8');

startSession();

// Всі quiz-дії вимагають авторизації
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Необхідна авторизація']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {

        // -------------------------------------------
        // Отримати список запитань для тесту
        // -------------------------------------------
        case 'questions':
            $categoryId = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) ?: null;
            $questions  = getQuizQuestions($categoryId, QUIZ_QUESTIONS_COUNT);

            // Прибираємо поле is_correct — клієнт не повинен знати правильну відповідь
            foreach ($questions as &$q) {
                foreach ($q['answers'] as &$a) {
                    unset($a['is_correct']);
                }
            }

            echo json_encode(['success' => true, 'questions' => $questions]);
            break;

        // -------------------------------------------
        // Отримати одне запитання (для адміна)
        // -------------------------------------------
        case 'get_question':
            requireAdmin();
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Невірний ID']);
                break;
            }
            $db   = getDb();
            $stmt = $db->prepare('SELECT * FROM questions WHERE id = ?');
            $stmt->execute([$id]);
            $question = $stmt->fetch();
            if (!$question) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Запитання не знайдено']);
                break;
            }
            $question['answers'] = getAnswersForQuestion($id);
            echo json_encode(['success' => true, 'question' => $question]);
            break;

        // -------------------------------------------
        // Перевірити відповідь
        // -------------------------------------------
        case 'check':
            $body       = json_decode(file_get_contents('php://input'), true) ?? [];
            $questionId = (int)($body['question_id'] ?? 0);
            $answerId   = (int)($body['answer_id']   ?? 0);

            if (!$questionId || !$answerId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Невірні параметри']);
                break;
            }

            $result = checkAnswer($questionId, $answerId);
            echo json_encode(['success' => true] + $result);
            break;

        // -------------------------------------------
        // Завершити тест та зберегти результат
        // -------------------------------------------
        case 'finish':
            $body       = json_decode(file_get_contents('php://input'), true) ?? [];
            $categoryId = isset($body['category_id']) ? (int)$body['category_id'] : null;
            $answers    = $body['answers'] ?? [];

            if (empty($answers) || !is_array($answers)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Немає відповідей']);
                break;
            }

            $total   = count($answers);
            $correct = array_sum(array_column($answers, 'is_correct'));

            $user     = getCurrentUser();
            $resultId = saveQuizResult($user['id'], $categoryId ?: null, $total, $correct);

            // Зберігаємо лог відповідей
            foreach ($answers as $a) {
                saveQuizAnswerLog(
                    $resultId,
                    (int)($a['question_id'] ?? 0),
                    isset($a['answer_id']) ? (int)$a['answer_id'] : null,
                    !empty($a['is_correct'])
                );
            }

            echo json_encode([
                'success'   => true,
                'result_id' => $resultId,
                'total'     => $total,
                'correct'   => $correct,
                'score'     => $total > 0 ? round(($correct / $total) * 100, 2) : 0,
            ]);
            break;

        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Невідома дія']);
    }

} catch (Exception $e) {
    error_log('Quiz API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Внутрішня помилка сервера']);
}
