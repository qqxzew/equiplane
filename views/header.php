<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <title>EquipLane</title>
</head>
<body class="bg-gray-950 text-gray-200 text-sm">
<div class="flex min-h-screen">
    <aside class="w-64 bg-gray-900 border-r border-gray-800 flex flex-col justify-between p-4">
        <div>
            <div class="text-xl font-bold tracking-wider text-orange-500 mb-6 px-2 flex items-center gap-2">
                <i class="ph-fill ph-engine"></i> EquipLane
            </div>
            <nav class="space-y-1">
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin'): ?>
                    <a href="index.php"
                       class="flex items-center gap-3 px-3 py-2 rounded-md text-gray-400 hover:bg-gray-800 hover:text-white transition">
                        <i class="ph ph-squares-four text-lg"></i> Dashboard
                    </a>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_role'])): ?>
                    <a href="tickets.php"
                       class="flex items-center gap-3 px-3 py-2 rounded-md text-gray-400 hover:bg-gray-800 hover:text-white transition">
                        <i class="ph ph-ticket text-lg"></i> Applications
                    </a>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin'): ?>
                    <a href="companies.php"
                       class="flex items-center gap-3 px-3 py-2 rounded-md text-gray-400 hover:bg-gray-800 hover:text-white transition">
                        <i class="ph ph-buildings text-lg"></i> Companies
                    </a>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'Admin' || $_SESSION['user_role'] === 'Engineer')): ?>
                    <a href="equipment.php"
                       class="flex items-center gap-3 px-3 py-2 rounded-md text-gray-400 hover:bg-gray-800 hover:text-white transition">
                        <i class="ph ph-nut text-lg"></i> Equipment
                    </a>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin'): ?>
                    <a href="users.php"
                       class="flex items-center gap-3 px-3 py-2 rounded-md text-gray-400 hover:bg-gray-800 hover:text-white transition">
                        <i class="ph ph-users text-lg"></i> Personnel
                    </a>
                <?php endif; ?>
            </nav>
        </div>

        <div class="text-xs text-gray-400 border-t border-gray-800 pt-4 mt-4 space-y-1 px-2">
            <?php if (isset($_SESSION['user_name'])): ?>
                <div class="text-gray-500">User:</div>
                <div class="text-white font-medium truncate"><?= htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="text-orange-500 font-medium"><?= htmlspecialchars($_SESSION['user_role'], ENT_QUOTES, 'UTF-8'); ?></div>

                <a href="profile.php"
                   class="flex items-center gap-2 text-gray-400 hover:text-white transition mt-3 py-1">
                    <i class="ph ph-user-circle text-base"></i> Profile
                </a>
                <a href="logout.php"
                   class="flex items-center gap-2 text-red-400 hover:text-red-300 transition py-1">
                    <i class="ph ph-sign-out text-base"></i> Log Out
                </a>
            <?php else: ?>
                <a href="login.php"
                   class="flex items-center justify-center gap-2 bg-orange-600 hover:bg-orange-500 text-white font-medium py-2 px-3 rounded transition">
                    <i class="ph ph-sign-in"></i> Sign In
                </a>
            <?php endif; ?>
        </div>
    </aside>
    <main class="flex-1 p-6 bg-gray-950">