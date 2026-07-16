<?php
/**
 * Адмін: управління користувачами
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

startSession();
requireAdmin();

$db          = getDb();
$pageTitle   = 'Користувачі — Адмін';
$activePage  = 'admin';
$currentUser = getCurrentUser();

// ------ Обробка POST ------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    $targetId   = (int)($_POST['user_id'] ?? 0);

    // Захист від видалення самого себе
    if ($targetId === (int)$currentUser['id'] && $postAction === 'delete') {
        $_SESSION['flash'][] = ['type' => 'error', 'msg' => 'Ви не можете видалити власний акаунт.'];
        header('Location: /admin/users.php');
        exit;
    }

    if ($postAction === 'delete' && $targetId) {
        $db->prepare('DELETE FROM users WHERE id = ?')->execute([$targetId]);
        $_SESSION['flash'][] = ['type' => 'success', 'msg' => 'Користувача видалено.'];
        header('Location: /admin/users.php');
        exit;
    }

    if ($postAction === 'toggle_role' && $targetId) {
        $stmt = $db->prepare('SELECT role FROM users WHERE id = ?');
        $stmt->execute([$targetId]);
        $user = $stmt->fetch();
        if ($user) {
            $newRole = $user['role'] === 'admin' ? 'user' : 'admin';
            $db->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$newRole, $targetId]);
            $_SESSION['flash'][] = ['type' => 'success', 'msg' => 'Роль змінено на ' . $newRole . '.'];
        }
        header('Location: /admin/users.php');
        exit;
    }

    // Зміна пароля користувача
    if ($postAction === 'change_password' && $targetId) {
        $newPassword = $_POST['new_password'] ?? '';
        if (strlen($newPassword) < 6) {
            $_SESSION['flash'][] = ['type' => 'error', 'msg' => 'Пароль повинен бути не менше 6 символів.'];
        } else {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $targetId]);
            $_SESSION['flash'][] = ['type' => 'success', 'msg' => 'Пароль змінено.'];
        }
        header('Location: /admin/users.php');
        exit;
    }
}

// ------ Список користувачів зі статистикою ------
$users = $db->query(
    'SELECT u.*, COUNT(r.id) AS result_count
     FROM users u
     LEFT JOIN quiz_results r ON r.user_id = u.id
     GROUP BY u.id
     ORDER BY u.created_at DESC'
)->fetchAll();

require __DIR__ . '/../templates/header.php';
?>

<div class="container">
<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div>
        <div class="d-flex align-center justify-between mb-2">
            <h2>👥 Користувачі (<?= count($users) ?>)</h2>
        </div>

        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Логін</th>
                            <th>Email</th>
                            <th>Роль</th>
                            <th>Тестів</th>
                            <th>Дата реєстрації</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= $u['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($u['username']) ?></strong>
                                    <?php if ($u['id'] == $currentUser['id']): ?>
                                        <span class="badge badge-admin" style="margin-left:.3rem;">Ви</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-sm"><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <span class="badge <?= $u['role'] === 'admin' ? 'badge-admin' : 'badge-user' ?>">
                                        <?= $u['role'] === 'admin' ? '👑 Адмін' : '👤 Юзер' ?>
                                    </span>
                                </td>
                                <td><?= $u['result_count'] ?></td>
                                <td class="text-sm"><?= formatDate($u['created_at']) ?></td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <!-- Зміна ролі -->
                                        <?php if ($u['id'] != $currentUser['id']): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_role">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn btn-warning btn-sm"
                                                        data-confirm="Змінити роль на <?= $u['role']==='admin'?'user':'admin' ?>?">
                                                    <?= $u['role'] === 'admin' ? '⬇️ user' : '⬆️ admin' ?>
                                                </button>
                                            </form>
                                            <!-- Видалення -->
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm"
                                                        data-confirm="Видалити користувача «<?= htmlspecialchars($u['username']) ?>»?">🗑️</button>
                                            </form>
                                        <?php endif; ?>
                                        <!-- Зміна пароля -->
                                        <button class="btn btn-outline btn-sm"
                                                onclick="showChangePassword(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')">
                                            🔑
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Модальне вікно зміни пароля -->
<div class="modal-overlay" id="change-pwd-modal">
    <div class="modal" onclick="event.stopPropagation()">
        <div class="modal-header">
            <span class="modal-title">🔑 Змінити пароль</span>
            <button class="modal-close" onclick="closeAllModals()">×</button>
        </div>
        <form method="POST" action="/admin/users.php">
            <input type="hidden" name="action" value="change_password">
            <input type="hidden" name="user_id" id="pwd-user-id">
            <div class="form-group">
                <label class="form-label">Новий пароль для: <strong id="pwd-username"></strong></label>
                <input type="password" name="new_password" class="form-control"
                       placeholder="Мінімум 6 символів" required>
            </div>
            <div class="d-flex gap-1">
                <button type="submit" class="btn btn-primary">Зберегти</button>
                <button type="button" class="btn btn-outline" onclick="closeAllModals()">Скасувати</button>
            </div>
        </form>
    </div>
</div>

<script>
function showChangePassword(userId, username) {
    document.getElementById('pwd-user-id').value  = userId;
    document.getElementById('pwd-username').textContent = username;
    openModal('change-pwd-modal');
}
</script>

<?php require __DIR__ . '/../templates/footer.php'; ?>
