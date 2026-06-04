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
                <a href="#" class="block px-4 py-2.5 rounded-lg bg-gray-800 text-white font-medium transition">
                    Dashboard
                </a>
                <a fref="#" class="block px-4 py-2.5 rounded-lg text-gray-400 hover:bg-gray-800 hover:text-white transition">
                    Applications
                </a>
                <a href="#" class="block px-4 py-2.5 rounded-lg text-gray-400 hover:bg-gray-800 hover:text-white transition">
                    Equipment
                </a>
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
            <?php else: ?>
                <a href="login.php" class="block text-center bg-orange-600 hover:bg-orange-500 text-white text-xs font-medium py-2 px-3 rounded-md transition">
                    Sign In
                </a>
            <?php endif; ?>
        </div>
    </aside>
    <main class="flex-1 p-8">
</body>
</html>