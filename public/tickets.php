<?php
declare(strict_types= 1);

session_start();

require_once __DIR__ . '/../app/auth/check.php';
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/database/db.php';

$role =  $_SESSION['user_role'];
$userId = $_SESSION['user_id'];

$filterStatus = $_GET['status'] ?? '';
$filterPriority = $_GET['priority'] ?? '';
$searchQuery = trim($_GET['search'] ?? '');

$sql = "SELECT t.*,
                u.name AS client_name,
                eng.name AS engineer_name,
                e.name AS equipment_name
        FROM tickets t
        JOIN users u ON t.client_id = u.id
        LEFT JOIN users eng ON t.engineer_id = eng.id
        JOIN equipment e ON t.equipment_id = e.id
        WHERE 1=1
";

$params = [];

if($role === 'Client'){
    $sql .= " AND t.client_id = ?";
    $params[] = $userId;
} elseif($role === 'Engineer') {
    $sql .= " AND t.engineer_id = ?";
    $params[] = $userId;
}

if(!empty($filterStatus)){
    $sql .= " AND t.status = ?";
    $params[] = $filterStatus;
}

if(!empty($filterPriority)){
    $sql .= " AND t.priority = ?";
    $params[] = $filterPriority;
}

if(!empty($searchQuery)){
    $sql .= " AND (t.subject LIKE ? OR t.description LIKE ?)";
    $params[] = "%" . $searchQuery . "%";
    $params[] = "%" . $searchQuery . "%";
}

$sql .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
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

    <form method="GET" action="tickets.php" class="bg-gray-900 p-4 rounded-xl border border-gray-800 flex flex-wrap gap-4 items-end text-xs">
        <div class="flex flex-col gap-1">
            <label for="status" class="text-gray-500 font-medium">Status</label>
            <select name="status" id="status" class="bg-gray-950 border border-gray-800 rounded-md px-3 py-2 text-white focus:outline-none focus:border-orange-500/50">
                <option value="">All Statuses</option>
                <option value="new" <?= $filterStatus === 'new' ? 'selected' : '' ?>>New</option>
                <option value="in_progress" <?= $filterStatus === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="backup_required" <?= $filterStatus === 'backup_required' ? 'selected' : '' ?>>Backup Required</option>
                <option value="closed" <?= $filterStatus === 'closed' ? 'selected' : '' ?>>Closed</option>
            </select>
        </div>

        <div class="flex flex-col gap-1">
            <label for="priority" class="text-gray-500 font-medium">Priority</label>
            <select name="priority" id="priority" class="bg-gray-950 border border-gray-800 rounded-md px-3 py-2 text-white focus:outline-none focus:border-orange-500/50">
                <option value="">All Priorities</option>
                <option value="low" <?= $filterPriority === 'low' ? 'selected' : '' ?>>Low</option>
                <option value="medium" <?= $filterPriority === 'medium' ? 'selected' : '' ?>>Medium</option>
                <option value="high" <?= $filterPriority === 'high' ? 'selected' : '' ?>>High</option>
            </select>
        </div>

        <div class="flex-1 min-w-[200px] flex flex-col gap-1">
            <label for="search" class="text-gray-500 font-medium">Search Keyword</label>
            <input type="text" name="search" id="search" value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search by subject or description..."
                   class="w-full bg-gray-950 border border-gray-800 rounded-md px-3 py-2 text-white focus:outline-none focus:border-orange-500/50">
        </div>

        <div class="flex gap-2">
            <button type="submit" class="bg-orange-600 hover:bg-orange-500 text-white px-5 py-2 rounded-md transition font-medium">
                Apply Filters
            </button>
            <?php if (!empty($filterStatus) || !empty($filterPriority) || !empty($searchQuery)): ?>
                <a href="tickets.php" class="bg-gray-850 hover:bg-gray-800 text-gray-400 px-4 py-2 rounded-md transition flex items-center justify-center font-medium border border-gray-800">
                    Reset
                </a>
            <?php endif; ?>
        </div>
    </form>

    <div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden shadow-xl">
        <?php if (empty($tickets)): ?>
            <div class="p-8 text-center text-gray-500 italic">
                No applications found matching the selected criteria.
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
                                        'backup_required' => 'bg-red-500/10 text-red-400 border-red-500/20 font-bold',
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