<?php
/**
 * Адмін: управління категоріями (CRUD)
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

startSession();
requireAdmin();

$db         = getDb();
$pageTitle  = 'Категорії — Адмін';
$activePage = 'admin';
$action     = $_GET['action'] ?? 'list';
$editId     = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;

// ------ Обробка POST ------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction  = $_POST['action'] ?? '';
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($postAction === 'delete') {
        $id = (int)$_POST['id'];
        $db->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
        $_SESSION['flash'][] = ['type' => 'success', 'msg' => 'Категорію видалено.'];
        header('Location: /admin/categories.php');
        exit;
    }

    if (in_array($postAction, ['create', 'update'])) {
        if (empty($name)) {
            $_SESSION['flash'][] = ['type' => 'error', 'msg' => 'Назва категорії не може бути порожньою.'];
        } else {
            if ($postAction === 'create') {
                $stmt = $db->prepare('INSERT INTO categories (name, description) VALUES (?, ?)');
                $stmt->execute([$name, $description]);
                $_SESSION['flash'][] = ['type' => 'success', 'msg' => 'Категорію додано.'];
            } else {
                $id   = (int)$_POST['id'];
                $stmt = $db->prepare('UPDATE categories SET name=?, description=? WHERE id=?');
                $stmt->execute([$name, $description, $id]);
                $_SESSION['flash'][] = ['type' => 'success', 'msg' => 'Категорію оновлено.'];
            }
            header('Location: /admin/categories.php');
            exit;
        }
    }
}

// ------ Дані для форм ------
$editCat = null;
if ($action === 'edit' && $editId) {
    $stmt = $db->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$editId]);
    $editCat = $stmt->fetch();
}

// ------ Список ------
$categories = $db->query(
    'SELECT c.*, COUNT(q.id) AS question_count
     FROM categories c LEFT JOIN questions q ON q.category_id = c.id
     GROUP BY c.id ORDER BY c.name'
)->fetchAll();

require __DIR__ . '/../templates/header.php';
?>

<div class="container">
<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div>
        <?php if (in_array($action, ['new', 'edit'])): ?>
            <div class="d-flex align-center justify-between mb-2">
                <h2><?= $action === 'new' ? '➕ Нова категорія' : '✏️ Редагувати категорію' ?></h2>
                <a href="/admin/categories.php" class="btn btn-outline btn-sm">← Назад</a>
            </div>

            <div class="card">
                <form method="POST" action="/admin/categories.php">
                    <input type="hidden" name="action" value="<?= $action === 'new' ? 'create' : 'update' ?>">
                    <?php if ($editCat): ?>
                        <input type="hidden" name="id" value="<?= $editCat['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label">Назва категорії</label>
                        <input type="text" name="name" class="form-control"
                               value="<?= htmlspecialchars($editCat['name'] ?? '') ?>"
                               placeholder="Файлова система" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Опис (необов'язково)</label>
                        <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($editCat['description'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex gap-1">
                        <button type="submit" class="btn btn-primary">
                            <?= $action === 'new' ? '➕ Додати' : '💾 Зберегти' ?>
                        </button>
                        <a href="/admin/categories.php" class="btn btn-outline">Скасувати</a>
                    </div>
                </form>
            </div>

        <?php else: ?>
            <div class="d-flex align-center justify-between mb-2">
                <h2>📁 Категорії (<?= count($categories) ?>)</h2>
                <a href="/admin/categories.php?action=new" class="btn btn-primary btn-sm">➕ Додати</a>
            </div>

            <div class="card">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>ID</th><th>Назва</th><th>Запитань</th><th>Опис</th><th>Дії</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td><?= $cat['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($cat['name']) ?></strong></td>
                                    <td><?= $cat['question_count'] ?></td>
                                    <td class="text-sm text-muted"><?= htmlspecialchars(mb_strimwidth($cat['description'] ?? '', 0, 50, '…')) ?></td>
                                    <td>
                                        <a href="/admin/categories.php?action=edit&id=<?= $cat['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                    data-confirm="Видалити «<?= htmlspecialchars($cat['name']) ?>»? Всі запитання цієї категорії також будуть видалені!">🗑️</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
