<?php
declare(strict_types=1);
/** @var PDO $pdo */
session_start();

require_once __DIR__ . "/../app/bootstrap.php";
require_once __DIR__ . "/../app/database/db.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmtCheckUser = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmtCheckUser->execute([$email]);

    $user = $stmtCheckUser->fetch();

    if ($user && password_verify($password, $user['password_hash']) === true) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];

        header('Location: index.php');
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equiplane - login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-md bg-gray-900 border border-gray-800 rounded-2xl p-8 shadow-xl">
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold tracking-wider text-orange-500 mb-2">EquipLane</h1>
        <p class="text-sm text-gray-400">Industrial maintenance ticketing system</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="bg-red-500/10 border border-red-500/20 text-red-400 text-sm p-4 rounded-lg mb-6">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST" class="space-y-6">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-400 mb-2">Email</label>
            <input type="email" name="email" id="email" required
                   class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-orange-500 transition">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-400 mb-2">Password</label>
            <input type="password" name="password" id="password" required
                   class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-orange-500 transition">
        </div>

        <button type="submit"
                class="w-full bg-orange-600 hover:bg-orange-500 text-white font-medium py-3 px-4 rounded-lg transition shadow-lg shadow-orange-600/10">
            Enter the system
        </button>
    </form>
</div>

</body>
</html>
