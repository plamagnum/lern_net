<?php
/**
 * Допоміжні функції для LinuxTest
 */

require_once __DIR__ . '/db.php';

/**
 * Очищає рядок від HTML-тегів (захист від XSS)
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Повертає список всіх категорій
 */
function getCategories(): array {
    $db = getDb();
    return $db->query('SELECT * FROM categories ORDER BY name')->fetchAll();
}

/**
 * Повертає категорію за ID
 */
function getCategoryById(int $id): ?array {
    $db  = getDb();
    $stmt = $db->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Повертає запитання для тесту
 * Якщо $categoryId = null — з усіх категорій
 */
function getQuizQuestions(?int $categoryId = null, int $limit = 10): array {
    $db  = getDb();
    if ($categoryId) {
        $stmt = $db->prepare(
            'SELECT q.*, c.name AS category_name
             FROM questions q
             JOIN categories c ON c.id = q.category_id
             WHERE q.category_id = ?
             ORDER BY RAND()
             LIMIT ?'
        );
        $stmt->execute([$categoryId, $limit]);
    } else {
        $stmt = $db->prepare(
            'SELECT q.*, c.name AS category_name
             FROM questions q
             JOIN categories c ON c.id = q.category_id
             ORDER BY RAND()
             LIMIT ?'
        );
        $stmt->execute([$limit]);
    }
    $questions = $stmt->fetchAll();

    // Завантажуємо варіанти відповідей для кожного запитання
    foreach ($questions as &$q) {
        $aStmt = $db->prepare('SELECT * FROM answers WHERE question_id = ? ORDER BY RAND()');
        $aStmt->execute([$q['id']]);
        $q['answers'] = $aStmt->fetchAll();
    }
    return $questions;
}

/**
 * Повертає всі відповіді для запитання
 */
function getAnswersForQuestion(int $questionId): array {
    $db   = getDb();
    $stmt = $db->prepare('SELECT * FROM answers WHERE question_id = ? ORDER BY id');
    $stmt->execute([$questionId]);
    return $stmt->fetchAll();
}

/**
 * Перевіряє правильність відповіді та повертає правильну відповідь
 */
function checkAnswer(int $questionId, int $answerId): array {
    $db = getDb();

    // Отримуємо правильну відповідь
    $stmt = $db->prepare(
        'SELECT a.*, q.explanation
         FROM answers a
         JOIN questions q ON q.id = a.question_id
         WHERE a.question_id = ? AND a.is_correct = 1
         LIMIT 1'
    );
    $stmt->execute([$questionId]);
    $correct = $stmt->fetch();

    $isCorrect = ($correct && $correct['id'] == $answerId);

    return [
        'is_correct'     => $isCorrect,
        'correct_id'     => $correct ? (int)$correct['id'] : null,
        'correct_text'   => $correct ? $correct['answer_text'] : '',
        'explanation'    => $correct ? $correct['explanation'] : '',
    ];
}

/**
 * Зберігає результат тесту
 */
function saveQuizResult(int $userId, ?int $categoryId, int $total, int $correct): int {
    $db    = getDb();
    $score = $total > 0 ? round(($correct / $total) * 100, 2) : 0;
    $stmt  = $db->prepare(
        'INSERT INTO quiz_results (user_id, category_id, total, correct, score)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $categoryId, $total, $correct, $score]);
    return (int)$db->lastInsertId();
}

/**
 * Зберігає окрему відповідь у лозі тесту
 */
function saveQuizAnswerLog(int $resultId, int $questionId, ?int $answerId, bool $isCorrect): void {
    $db   = getDb();
    $stmt = $db->prepare(
        'INSERT INTO quiz_answer_log (result_id, question_id, answer_id, is_correct)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$resultId, $questionId, $answerId, (int)$isCorrect]);
}

/**
 * Повертає результати тестів користувача
 */
function getUserResults(int $userId, int $limit = 20): array {
    $db   = getDb();
    $stmt = $db->prepare(
        'SELECT r.*, c.name AS category_name
         FROM quiz_results r
         LEFT JOIN categories c ON c.id = r.category_id
         WHERE r.user_id = ?
         ORDER BY r.completed_at DESC
         LIMIT ?'
    );
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

/**
 * Видаляє результат тесту (тільки власника або адміна)
 */
function deleteQuizResult(int $resultId, int $userId, bool $isAdmin = false): bool {
    $db = getDb();
    if ($isAdmin) {
        $stmt = $db->prepare('DELETE FROM quiz_results WHERE id = ?');
        $stmt->execute([$resultId]);
    } else {
        $stmt = $db->prepare('DELETE FROM quiz_results WHERE id = ? AND user_id = ?');
        $stmt->execute([$resultId, $userId]);
    }
    return $stmt->rowCount() > 0;
}

/**
 * Повертає вікі-сторінку за slug
 */
function getWikiBySlug(string $slug): ?array {
    $db   = getDb();
    $stmt = $db->prepare(
        'SELECT w.*, u.username AS author_name
         FROM wiki_pages w
         LEFT JOIN users u ON u.id = w.author_id
         WHERE w.slug = ?'
    );
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

/**
 * Повертає всі вікі-сторінки
 */
function getAllWikiPages(): array {
    $db = getDb();
    return $db->query(
        'SELECT w.*, u.username AS author_name
         FROM wiki_pages w
         LEFT JOIN users u ON u.id = w.author_id
         ORDER BY w.title'
    )->fetchAll();
}

/**
 * Генерує slug зі заголовку сторінки
 */
function generateSlug(string $title): string {
    $slug = mb_strtolower($title, 'UTF-8');
    // Транслітерація кириличних символів
    $cyr  = ['а','б','в','г','д','е','є','ж','з','и','і','ї','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ь','ю','я'];
    $lat  = ['a','b','v','g','d','e','ye','zh','z','y','i','yi','y','k','l','m','n','o','p','r','s','t','u','f','kh','ts','ch','sh','shch','','yu','ya'];
    $slug = str_replace($cyr, $lat, $slug);
    $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

/**
 * Повертає загальну статистику для адмін-панелі
 */
function getAdminStats(): array {
    $db = getDb();
    return [
        'users'     => (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'questions' => (int)$db->query('SELECT COUNT(*) FROM questions')->fetchColumn(),
        'results'   => (int)$db->query('SELECT COUNT(*) FROM quiz_results')->fetchColumn(),
        'wiki'      => (int)$db->query('SELECT COUNT(*) FROM wiki_pages')->fetchColumn(),
    ];
}

/**
 * Форматує дату українською
 */
function formatDate(string $datetime): string {
    $ts = strtotime($datetime);
    return date('d.m.Y H:i', $ts);
}

/**
 * Повертає клас CSS для оцінки (score%)
 */
function scoreClass(float $score): string {
    if ($score >= 80) return 'score-excellent';
    if ($score >= 60) return 'score-good';
    if ($score >= 40) return 'score-average';
    return 'score-poor';
}
