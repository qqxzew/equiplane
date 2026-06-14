<?php
declare(strict_types=1);
/** @var PDO $pdo */
session_start();

require_once __DIR__ . '/../app/auth/check.php';
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/database/db.php';

$stmt = $pdo->prepare("SELECT name, email, role, password_hash FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        setFlash('error', 'Security token expired. Please try again.');
        header('Location: profile.php');
        exit();
    }

    $oldPassword = trim($_POST['old_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');

    if (!password_verify($oldPassword, $user['password_hash'])) {
        setFlash('error', 'Current password is wrong.');
    } elseif ($newPassword === $oldPassword) {
        setFlash('error', 'New password cannot be the same as the old password.');
    } elseif (strlen($newPassword) < 8) {
        setFlash('error', 'Password must contain at least 8 characters.');
    } else {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$newHash, $_SESSION['user_id']]);
        setFlash('success', 'Password successfully updated.');
    }

    header('Location: profile.php');
    exit();
}

require_once __DIR__ . '/../views/header.php';
?>

    <div class="max-w-2xl bg-gray-900 border border-gray-800 rounded-2xl p-8 shadow-xl">
        <h1 class="text-2xl font-bold tracking-wider text-orange-500 mb-6">User Profile</h1>

        <?= displayFlash() ?>

        <div class="grid grid-cols-2 gap-6 mb-8 border-b border-gray-800 pb-6">
            <div>
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Full Name</div>
                <div class="text-white font-medium text-lg"><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Email Address</div>
                <div class="text-white font-medium text-lg"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">System Role</div>
                <div class="text-orange-500 font-medium text-lg"><?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>

        <form action="profile.php" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token"
                   value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">

            <h2 class="text-lg font-semibold text-white">Change Password</h2>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="old_password" class="block text-sm font-medium text-gray-400 mb-2">Current
                        Password</label>
                    <input type="password" name="old_password" id="old_password" required
                           class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-orange-500 transition">
                </div>
                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-400 mb-2">New Password</label>
                    <input type="password" name="new_password" id="new_password" required
                           class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-orange-500 transition">
                </div>
            </div>

            <button type="submit"
                    class="bg-orange-600 hover:bg-orange-500 text-white font-medium py-2.5 px-6 rounded-lg transition shadow-lg shadow-orange-600/10">
                Update Password
            </button>
        </form>
    </div>

<?php require_once __DIR__ . '/../views/footer.php'; ?>