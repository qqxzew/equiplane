<?php
declare(strict_types=1);
/** @var PDO $pdo */
session_start();

require_once __DIR__ . '/../app/auth/check.php';
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/database/db.php';

$role = $_SESSION['user_role'];

if ($role !== 'Admin' && $role !== 'Engineer') {
    header('Location: tickets.php');
    exit();
}

$stmt = $pdo->query("
       SELEECT e.id, e.name, e.serial_number, c.name AS company_name
       FROM equipment e
       JOIN companies c ON e.company_id = c.id
       ORDER BY c.name, e.name
");
$equipments = $stmt->fetchAll();

require_once __DIR__ . '/../views/header.php';
?>

    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold tracking-wider text-orange-500">Equipment Registry</h1>
                <p class="text-sm text-gray-400 mt-1">List of all registered industrial machines and their corporate
                    owners.</p>
            </div>
        </div>

        <div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden shadow-xl">
            <?php if (empty($equipments)): ?>
                <div class="p-8 text-center text-gray-500">
                    No equipment found in the database.
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                        <tr class="border-b border-gray-800 bg-gray-950/50 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                            <th class="py-4 px-6">ID</th>
                            <th class="py-4 px-6">Company Owner</th>
                            <th class="py-4 px-6">Machine Name</th>
                            <th class="py-4 px-6">Serial Number</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800/60 text-sm text-gray-300">
                        <?php foreach ($equipments as $eq): ?>
                            <tr class="hover:bg-gray-850/40 transition">
                                <td class="py-4 px-6 font-mono text-xs text-gray-500">
                                    #<?= $eq['id'] ?>
                                </td>
                                <td class="py-4 px-6 font-medium text-white">
                                    <?= htmlspecialchars($eq['company_name'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="py-4 px-6">
                                    <?= htmlspecialchars($eq['name'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="py-4 px-6 font-mono text-gray-400">
                                    <?= htmlspecialchars($eq['serial_number'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php
require_once __DIR__ . '/../views/footer.php';
?>