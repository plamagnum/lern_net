<?php
/**
 * Адмін: управління вікі-сторінками (CRUD)
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

startSession();
requireAdmin();

$db         = getDb();
$pageTitle  = 'Вікі — Адмін';
$activePage = 'admin';
$action     = $_GET['action'] ?? 'list';
$editId     = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;
$currentUser = getCurrentUser();

// ------ Обробка POST ------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'delete') {
        $id = (int)$_POST['id'];
        $db->prepare('DELETE FROM wiki_pages WHERE id = ?')->execute([$id]);
        $_SESSION['flash'][] = ['type' => 'success', 'msg' => 'Вікі-сторінку видалено.'];
        header('Location: /admin/wiki.php');
        exit;
    }

    if (in_array($postAction, ['create', 'update'])) {
        $title   = trim($_POST['title']   ?? '');
        $slug    = trim($_POST['slug']    ?? '');
        $content = $_POST['content'] ?? '';  // HTML-контент, довіряємо адміну

        if (empty($title) || empty($content)) {
            $_SESSION['flash'][] = ['type' => 'error', 'msg' => 'Заповніть заголовок та контент.'];
        } else {
            // Генеруємо slug якщо не вказано
            if (empty($slug)) {
                $slug = generateSlug($title);
            }
            // Унікальність slug
            $slugBase = $slug;
            $i = 1;
            while (true) {
                $checkSlug = $slug;
                if ($postAction === 'update') {
                    $stmt = $db->prepare('SELECT id FROM wiki_pages WHERE slug = ? AND id != ?');
                    $stmt->execute([$checkSlug, (int)$_POST['id']]);
                } else {
                    $stmt = $db->prepare('SELECT id FROM wiki_pages WHERE slug = ?');
                    $stmt->execute([$checkSlug]);
                }
                if (!$stmt->fetch()) break;
                $slug = $slugBase . '-' . $i++;
            }

            if ($postAction === 'create') {
                $stmt = $db->prepare('INSERT INTO wiki_pages (title, slug, content, author_id) VALUES (?,?,?,?)');
                $stmt->execute([$title, $slug, $content, $currentUser['id']]);
                $_SESSION['flash'][] = ['type' => 'success', 'msg' => 'Вікі-сторінку створено.'];
            } else {
                $id   = (int)$_POST['id'];
                $stmt = $db->prepare('UPDATE wiki_pages SET title=?, slug=?, content=? WHERE id=?');
                $stmt->execute([$title, $slug, $content, $id]);
                $_SESSION['flash'][] = ['type' => 'success', 'msg' => 'Вікі-сторінку оновлено.'];
            }
            header('Location: /admin/wiki.php');
            exit;
        }
    }
}

// ------ Дані для форм ------
$editPage = null;
if (in_array($action, ['edit']) && $editId) {
    $stmt = $db->prepare('SELECT * FROM wiki_pages WHERE id = ?');
    $stmt->execute([$editId]);
    $editPage = $stmt->fetch();
}

// ------ Список ------
$pages = $db->query(
    'SELECT w.*, u.username AS author_name
     FROM wiki_pages w LEFT JOIN users u ON u.id=w.author_id
     ORDER BY w.updated_at DESC'
)->fetchAll();

require __DIR__ . '/../templates/header.php';
?>

<div class="container">
<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div>
        <?php if (in_array($action, ['new', 'edit'])): ?>
            <div class="d-flex align-center justify-between mb-2">
                <h2><?= $action === 'new' ? '➕ Нова вікі-стаття' : '✏️ Редагувати статтю' ?></h2>
                <a href="/admin/wiki.php" class="btn btn-outline btn-sm">← Назад</a>
            </div>

            <div class="card">
                <form method="POST" action="/admin/wiki.php">
                    <input type="hidden" name="action" value="<?= $action === 'new' ? 'create' : 'update' ?>">
                    <?php if ($editPage): ?>
                        <input type="hidden" name="id" value="<?= $editPage['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label">Заголовок</label>
                        <input type="text" name="title" class="form-control"
                               value="<?= htmlspecialchars($editPage['title'] ?? '') ?>"
                               placeholder="iptables: основні команди" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Slug (URL)</label>
                        <input type="text" name="slug" class="form-control"
                               value="<?= htmlspecialchars($editPage['slug'] ?? '') ?>"
                               placeholder="iptables-basics (генерується автоматично)">
                        <span class="form-hint">Якщо залишити порожнім — генерується автоматично з заголовку</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Контент (HTML)</label>
                        <textarea name="content" class="form-control" rows="20"
                                  style="font-family:monospace;font-size:.85rem;"><?= htmlspecialchars($editPage['content'] ?? '') ?></textarea>
                        <span class="form-hint">Підтримується HTML. Теги &lt;pre&gt;&lt;code&gt; для блоків коду.</span>
                    </div>

                    <div class="d-flex gap-1">
                        <button type="submit" class="btn btn-primary">
                            <?= $action === 'new' ? '➕ Створити' : '💾 Зберегти' ?>
                        </button>
                        <a href="/admin/wiki.php" class="btn btn-outline">Скасувати</a>
                    </div>
                </form>
            </div>

        <?php else: ?>
            <div class="d-flex align-center justify-between mb-2">
                <h2>📖 Вікі (<?= count($pages) ?>)</h2>
                <a href="/admin/wiki.php?action=new" class="btn btn-primary btn-sm">➕ Нова стаття</a>
            </div>

            <div class="card">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Заголовок</th><th>Slug</th><th>Автор</th><th>Оновлено</th><th>Дії</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pages)): ?>
                                <tr><td colspan="5" class="text-center text-muted">Вікі-сторінок немає.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($pages as $p): ?>
                                <tr>
                                    <td>
                                        <a href="/wiki-article.php?slug=<?= urlencode($p['slug']) ?>" target="_blank">
                                            <?= htmlspecialchars($p['title']) ?>
                                        </a>
                                    </td>
                                    <td class="text-sm text-muted"><?= htmlspecialchars($p['slug']) ?></td>
                                    <td><?= htmlspecialchars($p['author_name'] ?? '—') ?></td>
                                    <td class="text-sm"><?= formatDate($p['updated_at']) ?></td>
                                    <td>
                                        <a href="/admin/wiki.php?action=edit&id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                    data-confirm="Видалити «<?= htmlspecialchars($p['title']) ?>»?">🗑️</button>
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
