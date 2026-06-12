<?php
declare(strict_types=1);

session_start();

if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin'){
    http_response_code(403);
    echo "403 Acces Denied. You do not have permission to acces this page.";
    exit();
}

require_once __DIR__ . "/../app/auth/check.php";

require_once __DIR__ . "/../app/bootstrap.php";
require_once __DIR__ . "/../app/database/db.php";

$stmtActive = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status IN ('new', 'in_progress', 'backup_required')");
$activeTickets = $stmtActive->fetchColumn();

$stmtEngineers = $pdo->query("SELECT COUNT(DISTINCT engineer_id) FROM tickets WHERE status IN('in_progress', 'backup_required') AND engineer_id IS NOT NULL");
$activeEngineers = $stmtEngineers->fetchColumn();

$stmtRevenue = $pdo->query("SELECT hours_spent, cost_of_parts FROM tickets WHERE status = 'closed'");
$closedTicketsData = $stmtRevenue->fetchAll();

#$stmtHourlyRate = $pdo->query("SELECT salary FROM users WHERE status IN('in_progress', 'backup_required') AND engineer_id IS NOT NULL");
#$stmtActiveEngHourlyRate = $stmtHourlyRate->fetchAll();
#$hourlyRate = $stmtActiveEngHourlyRate;

$totalRevenue = 0.0;
$hourlyRate = 600;

foreach($closedTicketsData as $row){
    $totalRevenue += ((float)$row['hours_spent'] * $hourlyRate) + (float)$row['cost_of_parts'];
}

require_once __DIR__ . "/../views/header.php";
?>

<div class="max-w-5xl">
    <h1 class="text-2xl font-bold mb-2">Control panel</h1>
    <p class="text-gray-400 mb-8">Industrial maintenance ticketing system</p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <div class="bg-gray-900 p-6 rounded-xl border border-gray-800">
            <div class="text-sm text-gray-400 mb-1">Active accidents</div>
            <div class="text-3xl font-bold text-orange-500"><?= $activeTickets ?></div>
        </div>

        <div class="bg-gray-900 p-6 rounded-xl border border-gray-800">
            <div class="text-sm text-gray-400 mb-1">Engineers at the object</div>
            <div class="text-3xl font-bold text-blue-500"><?= $activeEngineers ?></div>
        </div>

        <div class="bg-gray-900 p-6 rounded-xl border border-gray-800">
            <div class="text-sm text-gray-400 mb-1">Total Revenue (w/o VAT)</div>
            <div class="text-3xl font-bold text-green-500"><?= number_format($totalRevenue, 2, '.', ' ') ?> Kč</div>
        </div>

    </div>
</div>

<?php
require_once __DIR__ . "/../views/footer.php";
?>