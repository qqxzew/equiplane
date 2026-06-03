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

        <div class="text-xs text-gray-500 border-t border-gray-800 pt-4">
            Role: <span class="text-gray-300 font-medium">Admin</span>
        </div>
    </aside>
    <main class="flex-1 p-8">
</body>
</html>