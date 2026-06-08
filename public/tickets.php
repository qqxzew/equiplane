<?php
declare(strict_types= 1);

session_start();

require_once __DIR__ . '/../app/auth/check.php';
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/database/db.php';

$role =  $_SESSION['user_role'];
$userId = $_SESSION['user_id'];

$sql = "SELECT t.*,
                u.name AS client_name,
                eng.name AS engineer_name,
                e.name AS equipment_name
        FROM tickets t
        JOIN users u ON t.client_id = u.id
        LEFT JOIN users eng ON t.engineer_id = eng.id
        JOIN equipment e ON t.equipment_id = e.id";

if($role === 'Admin'){
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}elseif($role === 'Engineer'){
    $sql .= " WHERE t.engineer_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
}elseif($role === 'Client'){
    $sql .= " WHERE client_id =?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
}

$tickets = $stmt->fetchAll();

require_once __DIR__ . '/../views/header.php';
?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold tracking-wider text-orange-500">Maintenance Applications</h1>
            <p class="text-sm text-gray-400 mt-1">Manage and track industrial equipment repair requests.</p>
        </div>
        <?php if ($role === 'Client'): ?>
            <a href="create_ticket.php" class="bg-orange-600 hover:bg-orange-500 text-white text-sm font-medium py-2.5 px-5 rounded-lg transition shadow-lg shadow-orange-600/10">
                + New Application
            </a>
        <?php endif; ?>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden shadow-xl">
        <?php if (empty($tickets)): ?>
            <div class="p-8 text-center text-gray-500">
                No applications found.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-800 bg-gray-950/50 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                            <th class="py-4 px-6">ID</th>
                            <th class="py-4 px-6">Subject / Equipment</th>
                            <?php if ($role !== 'Client'): ?>
                                <th class="py-4 px-6">Client</th>
                            <?php endif; ?>
                            <?php if ($role !== 'Engineer'): ?>
                                <th class="py-4 px-6">Engineer</th>
                            <?php endif; ?>
                            <th class="py-4 px-6">Priority</th>
                            <th class="py-4 px-6">Status</th>
                            <th class="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/60 text-sm text-gray-300">
                        <?php foreach ($tickets as $ticket): ?>
                            <tr class="hover:bg-gray-850/40 transition">
                                <td class="py-4 px-6 font-mono text-xs text-gray-500">
                                    #<?= $ticket['id'] ?>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="font-medium text-white"><?= htmlspecialchars($ticket['subject'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($ticket['equipment_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <?php if ($role !== 'Client'): ?>
                                    <td class="py-4 px-6 text-gray-400">
                                        <?= htmlspecialchars($ticket['client_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                <?php endif; ?>
                                <?php if ($role !== 'Engineer'): ?>
                                    <td class="py-4 px-6 text-gray-400">
                                        <?= $ticket['engineer_name'] ? htmlspecialchars($ticket['engineer_name'], ENT_QUOTES, 'UTF-8') : '<span class="text-gray-600 italic text-xs">Unassigned</span>' ?>
                                    </td>
                                <?php endif; ?>
                                <td class="py-4 px-6">
                                    <?php
                                    $priorityColor = match($ticket['priority']) {
                                        'high' => 'bg-red-500/10 text-red-400 border-red-500/20',
                                        'medium' => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
                                        default => 'bg-green-500/10 text-green-400 border-green-500/20',
                                    };
                                    ?>
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-md border <?= $priorityColor ?>">
                                        <?= ucfirst($ticket['priority']) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <?php
                                    $statusColor = match($ticket['status']) {
                                        'in_progress' => 'bg-orange-500/10 text-orange-400 border-orange-500/20',
                                        'closed' => 'bg-gray-500/10 text-gray-400 border-gray-500/20',
                                        default => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                    };
                                    ?>
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-md border <?= $statusColor ?>">
                                        <?= str_replace('_', ' ', ucfirst($ticket['status'])) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <a href="view_ticket.php?id=<?= $ticket['id'] ?>" class="text-xs font-medium text-orange-500 hover:text-orange-400 transition">
                                        View Details →
                                    </a>
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