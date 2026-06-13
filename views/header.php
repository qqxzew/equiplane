<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>EquipLane</title>
</head>
<body class="bg-gray-900 text-gray-100">
<aside class="w-64 bg-gray-900 border-r border-gray-800 flex flex-col justify-between p-6">
    <div>
        <div class="text-xl font-bold tracking-wider text-orange-500 mb-8">
            EquipLane
        </div>

        <nav class="space-y-2">
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin'): ?>
                <a href="../public/index.php"
                   class="block px-4 py-2.5 rounded-lg text-gray-400 hover:bg-gray-800 hover:text-white transition">
                    Dashboard
                </a>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_role'])): ?>
                <a href="../public/tickets.php"
                   class="block px-4 py-2.5 rounded-lg text-gray-400 hover:bg-gray-800 hover:text-white transition">
                    Applications
                </a>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'Admin' || $_SESSION['user_role'] === 'Engineer')): ?>
                <a href="#"
                   class="block px-4 py-2.5 rounded-lg text-gray-400 hover:bg-gray-800 hover:text-white transition">
                    Equipment
                </a>
            <?php endif; ?>
        </nav>
    </div>

    <div class="text-sm text-gray-400 border-t border-gray-800 pt-4 space-y-1">
        <?php if (isset($_SESSION['user_name'])): ?>
        <div class="text-xs text-gray-500">User:</div>
        <div class="text-white font-medium">
            <?= htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="text-xs text-orange-500 font-medium">
            <?= htmlspecialchars($_SESSION['user_role'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
    </div>
    <a href="../public/logout.php" class="block text-xs text-blue-400 hover:text-blue-300 transition font-medium pt-1">
        Log Out
    </a>
    <?php else: ?>
        <a href="../public/login.php"
           class="block text-center bg-orange-600 hover:bg-orange-500 text-white text-xs font-medium py-2 px-3 rounded-md transition">
            Sign In
        </a>
    <?php endif; ?>
</aside>
<main class="flex-1 p-8">
</body>
</html>