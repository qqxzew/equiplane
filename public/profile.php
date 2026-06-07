<?php
declare(strict_types= 1);

session_start();

require_once __DIR__ . '/../app/auth/check.php';
require_once __DIR__ .'/../app/bootstrap.php';
require_once __DIR__ .'/../app/database/db.php';

$success = '';
$error = '';

$smtp = $pdo->prepare("SELECT name, email, role, password_hash FROM users WHERE id = ?");
$smtp->execute([$_SESSION['user_id']]);
$user = $smtp->fetch();

if (!$user){
    echo "User not found.";
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $oldPassword = trim($_POST['old_password']);
    $newPassword = trim($_POST['new_password']);
    if(password_verify($oldPassword, $user['password_hash']) == false){
        $error = "Password is wrong";
    }
    elseif($newPassword === $oldPassword){
        $error = "New password can be same as an old password.";
    }
    elseif($newPassword == ''|| strlen($newPassword) < 8){
        $error = 'Password must contain 8 symbols';
    }
    else{
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $smtp = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $smtp->execute([$newHash, $_SESSION['user_id']]);  
        $success = "Password updated.";
    }
}
require_once __DIR__ . '/../views/header.php';
?>

<div class="max-w-2xl bg-gray-900 border border-gray-800 rounded-2xl p-8 shadow-xl">
    <h1 class="text-2xl font-bold tracking-wider text-orange-500 mb-6">User Profile</h1>

    <?php if (!empty($error)): ?>
        <div class="bg-red-500/10 border border-red-500/20 text-red-400 text-sm p-4 rounded-lg mb-6">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="bg-green-500/10 border border-green-500/20 text-green-400 text-sm p-4 rounded-lg mb-6">
            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

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
        <h2 class="text-lg font-semibold text-white">Change Password</h2>
        
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="old_password" class="block text-sm font-medium text-gray-400 mb-2">Current Password</label>
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

<?php
require_once __DIR__ . '/../views/footer.php';
?>