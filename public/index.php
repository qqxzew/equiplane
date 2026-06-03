<?php
declare(strict_types=1);
require_once __DIR__ . "/../app/bootstrap.php";
require_once __DIR__ . "/../app/database/db.php";
require_once __DIR__ . "/../views/header.php";
?>

<div class="max-w-5xl">
    <h1 class="text-2xl font-bold mb-2">Control panel</h1>
    <p class="text-gray-400 mb-8">Industrial maintenance ticketing system</p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <div class="bg gray-900 p-6 rounded-xl border bordere-gray-800">
            <div class="text-sm text-gray-400">Active accidents</div>
            <div class="text-3xl font-bold text-orange-500">0</div>
        </div>

        <div class="bg-gray-900 p-6 rounded-xl border norder-gray-800">
            <div class="text-sm text-gray-400 mb-1">Engeneers at the object</div>
            <div class="text-3xl font-bold text-blue-500">0</div>
        </div>

        <div class="bg-gray-900 p-6 rounded-xl border border-gray-800">
            <div class="text-sm text-gray-400 mb-1">Database status</div>
            <div class="text-sm font-semibold mt-2">
                <?php if (isset($pdo)): ?>
                    <span class="text-green-500 bg-green-500/10 px-2 py-1 rounded">Active</span>
                <?php else: ?>
                    <span class="text-red-500 bg-red-500/10 px-2 py-1 rounded">Inactive</span>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php
require_once __DIR__ . "/../views/footer.php";
?>