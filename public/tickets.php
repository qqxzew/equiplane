<?php
declare(strict_types=1);
/** @var PDO $pdo */
session_start();

require_once __DIR__ . '/../app/auth/check.php';
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/database/db.php';

$role = $_SESSION['user_role'];
$userId = $_SESSION['user_id'];

$filterStatus = $_GET['status'] ?? '';
$filterPriority = $_GET['priority'] ?? '';
$searchQuery = trim($_GET['search'] ?? '');

$whereSql = "WHERE 1=1";
$params = [];

if ($role === 'Client') {
    $whereSql .= " AND t.client_id = ?";
    $params[] = $userId;
} elseif ($role === 'Engineer') {
    $whereSql .= " AND t.engineer_id = ?";
    $params[] = $userId;
}

if (!empty($filterStatus)) {
    $whereSql .= " AND t.status = ?";
    $params[] = $filterStatus;
}

if (!empty($filterPriority)) {
    $whereSql .= " AND t.priority = ?";
    $params[] = $filterPriority;
}

if (!empty($searchQuery)) {
    $whereSql .= " AND (t.subject LIKE ? OR t.description LIKE ?)";
    $params[] = "%" . $searchQuery . "%";
    $params[] = "%" . $searchQuery . "%";
}

$countSql = "
    SELECT COUNT(t.id) 
    FROM tickets t
    JOIN users u ON t.client_id = u.id
    LEFT JOIN users eng ON t.engineer_id = eng.id
    JOIN equipment e ON t.equipment_id = e.id
    $whereSql
";
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalRecords = (int)$stmtCount->fetchColumn();

$limit = 15;
$page = max(1, (int)($_GET['page'] ?? 1));
$totalPages = max(1, (int)ceil($totalRecords / $limit));

if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $limit;

$sql = "
    SELECT t.*,
           u.name AS client_name,
           eng.name AS engineer_name,
           e.name AS equipment_name
    FROM tickets t
    JOIN users u ON t.client_id = u.id
    LEFT JOIN users eng ON t.engineer_id = eng.id
    JOIN equipment e ON t.equipment_id = e.id
    $whereSql
    ORDER BY t.created_at DESC
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

require_once __DIR__ . '/../views/header.php';
?>

    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-xl font-semibold text-white flex items-center gap-2">
                    <i class="ph ph-ticket text-orange-500"></i> Applications
                </h1>
            </div>
            <?php if ($role === 'Client'): ?>
                <a href="create_ticket.php"
                   class="bg-orange-600 hover:bg-orange-500 text-white text-xs font-medium py-2 px-4 rounded transition flex items-center gap-2">
                    <i class="ph ph-plus-bold"></i> New Application
                </a>
            <?php endif; ?>
        </div>

        <?= displayFlash() ?>

        <form method="GET" action="tickets.php"
              class="bg-gray-900 p-3 rounded-lg border border-gray-800 flex flex-wrap gap-3 items-end text-xs">
            <div class="flex flex-col gap-1 w-40">
                <label for="status" class="text-gray-500 font-medium">Status</label>
                <select name="status" id="status"
                        class="bg-gray-950 border border-gray-800 rounded px-2 py-1.5 text-white focus:outline-none focus:border-orange-500/50">
                    <option value="">All Statuses</option>
                    <option value="new" <?= $filterStatus === 'new' ? 'selected' : '' ?>>New</option>
                    <option value="in_progress" <?= $filterStatus === 'in_progress' ? 'selected' : '' ?>>In Progress
                    </option>
                    <option value="backup_required" <?= $filterStatus === 'backup_required' ? 'selected' : '' ?>>Backup
                        Required
                    </option>
                    <option value="closed" <?= $filterStatus === 'closed' ? 'selected' : '' ?>>Closed</option>
                </select>
            </div>

            <div class="flex flex-col gap-1 w-40">
                <label for="priority" class="text-gray-500 font-medium">Priority</label>
                <select name="priority" id="priority"
                        class="bg-gray-950 border border-gray-800 rounded px-2 py-1.5 text-white focus:outline-none focus:border-orange-500/50">
                    <option value="">All Priorities</option>
                    <option value="low" <?= $filterPriority === 'low' ? 'selected' : '' ?>>Low</option>
                    <option value="medium" <?= $filterPriority === 'medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="high" <?= $filterPriority === 'high' ? 'selected' : '' ?>>High</option>
                </select>
            </div>

            <div class="flex-1 min-w-[200px] flex flex-col gap-1">
                <label for="search" class="text-gray-500 font-medium">Search Keyword</label>
                <input type="text" name="search" id="search"
                       value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Subject or description..."
                       class="w-full bg-gray-950 border border-gray-800 rounded px-2 py-1.5 text-white focus:outline-none focus:border-orange-500/50">
            </div>

            <div class="flex gap-2">
                <button type="submit"
                        class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-1.5 rounded transition font-medium border border-gray-700 flex items-center gap-2">
                    <i class="ph ph-funnel"></i> Filter
                </button>
                <?php if (!empty($filterStatus) || !empty($filterPriority) || !empty($searchQuery)): ?>
                    <a href="tickets.php"
                       class="bg-gray-950 hover:bg-gray-900 text-gray-400 px-3 py-1.5 rounded transition flex items-center justify-center font-medium border border-gray-800">
                        <i class="ph ph-x"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden flex flex-col">
            <?php if (empty($tickets)): ?>
                <div class="p-6 text-center text-gray-500 text-xs">
                    No applications found.
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                        <tr class="border-b border-gray-800 bg-gray-950/50 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="py-2.5 px-4 w-16">ID</th>
                            <th class="py-2.5 px-4">Subject</th>
                            <th class="py-2.5 px-4">Equipment</th>
                            <?php if ($role !== 'Client'): ?>
                                <th class="py-2.5 px-4">Client</th>
                            <?php endif; ?>
                            <?php if ($role !== 'Engineer'): ?>
                                <th class="py-2.5 px-4">Engineer</th>
                            <?php endif; ?>
                            <th class="py-2.5 px-4 w-24">Priority</th>
                            <th class="py-2.5 px-4 w-32">Status</th>
                            <th class="py-2.5 px-4 w-12 text-center"></th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800/60 text-xs text-gray-300">
                        <?php foreach ($tickets as $ticket): ?>
                            <tr class="hover:bg-gray-850/40 transition group">
                                <td class="py-2 px-4 font-mono text-gray-500">
                                    <?= $ticket['id'] ?>
                                </td>
                                <td class="py-2 px-4 font-medium text-white truncate max-w-[200px]"
                                    title="<?= htmlspecialchars($ticket['subject'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($ticket['subject'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="py-2 px-4 text-gray-400 truncate max-w-[150px]"
                                    title="<?= htmlspecialchars($ticket['equipment_name'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($ticket['equipment_name'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <?php if ($role !== 'Client'): ?>
                                    <td class="py-2 px-4 text-gray-400 truncate max-w-[120px]">
                                        <?= htmlspecialchars($ticket['client_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                <?php endif; ?>
                                <?php if ($role !== 'Engineer'): ?>
                                    <td class="py-2 px-4">
                                        <?= $ticket['engineer_name'] ? '<span class="text-gray-300">' . htmlspecialchars($ticket['engineer_name'], ENT_QUOTES, 'UTF-8') . '</span>' : '<span class="text-gray-600">Unassigned</span>' ?>
                                    </td>
                                <?php endif; ?>
                                <td class="py-2 px-4">
                                    <?= getPriorityBadge($ticket['priority']) ?>
                                </td>
                                <td class="py-2 px-4">
                                    <?= getStatusBadge($ticket['status']) ?>
                                </td>
                                <td class="py-2 px-4 text-center">
                                    <a href="view_ticket.php?id=<?= $ticket['id'] ?>"
                                       class="inline-flex items-center justify-center text-gray-500 hover:text-orange-500 hover:bg-orange-500/10 p-1.5 rounded transition">
                                        <i class="ph ph-arrow-right text-base"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="px-4 py-3 border-t border-gray-800 flex justify-between items-center text-[11px] bg-gray-950/30">
                    <span class="text-gray-500">
                        <?= $offset + 1 ?> - <?= min($offset + $limit, $totalRecords) ?> of <?= $totalRecords ?>
                    </span>
                        <div class="flex gap-1">
                            <?php $queryParams = $_GET; ?>

                            <?php if ($page > 1): ?>
                                <?php $queryParams['page'] = $page - 1; ?>
                                <a href="?<?= http_build_query($queryParams) ?>"
                                   class="px-2 py-1 bg-gray-900 border border-gray-800 text-gray-400 rounded hover:text-white hover:border-gray-600 transition flex items-center">
                                    <i class="ph ph-caret-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php $queryParams['page'] = $i; ?>
                                <a href="?<?= http_build_query($queryParams) ?>"
                                   class="px-2.5 py-1 rounded border transition <?= $i === $page ? 'bg-orange-600/10 border-orange-500/50 text-orange-500' : 'bg-gray-900 border-gray-800 text-gray-400 hover:text-white hover:border-gray-600' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <?php $queryParams['page'] = $page + 1; ?>
                                <a href="?<?= http_build_query($queryParams) ?>"
                                   class="px-2 py-1 bg-gray-900 border border-gray-800 text-gray-400 rounded hover:text-white hover:border-gray-600 transition flex items-center">
                                    <i class="ph ph-caret-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>

<?php
require_once __DIR__ . '/../views/footer.php';
?>