<?php
declare(strict_types=1);
/** @var PDO $pdo */
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header('Location: tickets.php');
    exit();
}

require_once __DIR__ . "/../app/auth/check.php";
require_once __DIR__ . "/../app/bootstrap.php";
require_once __DIR__ . "/../app/database/db.php";

$stmtActive = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status IN ('new', 'in_progress', 'backup_required')");
$activeTickets = $stmtActive->fetchColumn();

$stmtEngineers = $pdo->query("SELECT COUNT(DISTINCT engineer_id) FROM tickets WHERE status IN('in_progress', 'backup_required') AND engineer_id IS NOT NULL");
$activeEngineers = $stmtEngineers->fetchColumn();

$stmtRevenue = $pdo->query("
    SELECT t.hours_spent, t.cost_of_parts, u.hourly_rate 
    FROM tickets t 
    JOIN users u ON t.engineer_id = u.id 
    WHERE t.status = 'closed'
");
$closedTicketsData = $stmtRevenue->fetchAll();

$totalRevenue = 0.0;

foreach ($closedTicketsData as $row) {
    $rate = (float)$row['hourly_rate'];
    $totalRevenue += ((float)$row['hours_spent'] * $rate) + (float)$row['cost_of_parts'];
}

require_once __DIR__ . "/../views/header.php";
?>

    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-xl font-semibold text-white flex items-center gap-2">
                    <i class="ph ph-squares-four text-orange-500"></i> Dashboard
                </h1>
            </div>
            <a href="export.php"
               class="bg-gray-800 hover:bg-gray-700 text-white text-xs font-medium py-2 px-4 rounded transition border border-gray-700 flex items-center gap-2">
                <i class="ph ph-download-simple"></i> Export CSV
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-900 p-5 rounded-lg border border-gray-800 flex items-center gap-4">
                <div class="p-3 bg-orange-500/10 text-orange-500 rounded-lg">
                    <i class="ph ph-warning-circle text-2xl"></i>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Active Alerts</div>
                    <div class="text-2xl font-bold text-white"><?= $activeTickets ?></div>
                </div>
            </div>

            <div class="bg-gray-900 p-5 rounded-lg border border-gray-800 flex items-center gap-4">
                <div class="p-3 bg-blue-500/10 text-blue-500 rounded-lg">
                    <i class="ph ph-users text-2xl"></i>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Field Engineers</div>
                    <div class="text-2xl font-bold text-white"><?= $activeEngineers ?></div>
                </div>
            </div>

            <div class="bg-gray-900 p-5 rounded-lg border border-gray-800 flex items-center gap-4">
                <div class="p-3 bg-green-500/10 text-green-500 rounded-lg">
                    <i class="ph ph-currency-circle-dollar text-2xl"></i>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Revenue (w/o VAT)</div>
                    <div class="text-2xl font-bold text-white"><?= number_format($totalRevenue, 2, '.', ' ') ?> Kč</div>
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . "/../views/footer.php"; ?>