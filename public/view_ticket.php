<?php
declare(strict_types= 1);

session_start();

require_once __DIR__ . '/../app/auth/check.php';
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/database/db.php';

$role = $_SESSION['user_role'];
$userId = $_SESSION['user_id'];

if(!isset($_GET['id']) || empty($_GET['id'])){
    echo "Invalid ticket ID.";
    exit();
}

$ticketId = (int)$_GET['id'];
$error = "";

if($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'Engineer' && isset($_POST['ticket_action'])){
    $action = $_POST['ticket_action'];

    try {
        if($action === 'start'){
            $stmtStatus = $pdo->prepare("UPDATE tickets SET status = 'in_progress' WHERE id = ?");
            $stmtStatus->execute([$ticketId]);

            $stmtLog = $pdo->prepare("INSERT INTO ticket_logs (ticket_id, action_text) VALUES (?, ?)");
            $stmtLog->execute([$ticketId, "Engineer started work on the ticket."]);
        }
        elseif($action === 'resolve'){
            $hoursSpent = isset($_POST['hours_spent']) ? (float)$_POST['hours_spent'] : 0.0;
            $costOfParts = isset($_POST['cost_of_parts']) ? (float)$_POST['cost_of_parts'] : 0.0;

            $stmtStatus = $pdo->prepare("UPDATE tickets SET status = 'closed', hours_spent = ?, cost_of_parts = ? WHERE id = ?");
            $stmtStatus->execute([$hoursSpent, $costOfParts, $ticketId]);

            $stmtLog = $pdo->prepare("INSERT INTO ticket_logs (ticket_id, action_text) VALUES (?, ?)");
            $stmtLog->execute([$ticketId, "Engineer resolved and closed the ticket. Hours: {$hoursSpent}, Cost: {$costOfParts} Kč"]);
        }
        elseif($action === 'backup'){
            $backupReason = trim($_POST['backup_reason'] ?? '');
            if(empty($backupReason)){
                $error = "Please provide a reason for help.";
            } else {
                $stmtStatus = $pdo->prepare("UPDATE tickets SET status = 'backup_required' WHERE id = ?");
                $stmtStatus->execute([$ticketId]);

                $stmtLog = $pdo->prepare("INSERT INTO ticket_logs (ticket_id, action_text) VALUES (?, ?)");
                $stmtLog->execute([$ticketId, "Engineer requested backup. Reason: " . $backupReason]);

                header("Location: view_ticket.php?id=". $ticketId);
                exit();
            }
        }
        
        if (empty($error)) {
            header("Location: view_ticket.php?id=" . $ticketId);
            exit();
        }
    } catch (PDOException $e) {
        logSystemError("Engineer action database failure: " . $e->getMessage());
        $error = "System error occurred while updating ticket.";
    }
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'Admin' && isset($_POST['assign_engineer'])){
    $engineerId = (int)$_POST['engineer_id'];
    if($engineerId > 0){
        try {
            $stmtUpdate = $pdo->prepare("UPDATE tickets SET engineer_id = ? WHERE id = ?");
            $stmtUpdate->execute([$engineerId, $ticketId]);

            $stmtEngName = $pdo->prepare("SELECT name FROM users WHERE id = ?");
            $stmtEngName->execute([$engineerId]);
            $engineerName = $stmtEngName->fetchColumn();

            $stmtLog = $pdo->prepare("INSERT INTO ticket_logs (ticket_id, action_text) VALUES (?, ?)");
            $stmtLog->execute([$ticketId, "Admin assigned engineer: " . $engineerName]);

            header("Location: view_ticket.php?id=" . $ticketId);
            exit();
        } catch (PDOException $e) {
            logSystemError("Admin assignment failure: " . $e->getMessage());
            $error = "Failed to assign engineer.";
        }
    }
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'Admin' && isset($_POST['admin_backup_decision'])){
    $decision = $_POST['admin_backup_decision'];

    try {
        if($decision === 'approve'){
            $stmtStatus = $pdo->prepare("UPDATE tickets SET status = 'in_progress' WHERE id = ?");
            $stmtStatus->execute([$ticketId]);

            $stmtLog = $pdo->prepare("INSERT INTO ticket_logs (ticket_id, action_text) VALUES (?, ?)");
            $stmtLog->execute([$ticketId, "Admin APPROVED backup request."]);
        }
        elseif($decision === 'reject'){
            $stmtStatus = $pdo->prepare("UPDATE tickets SET status = 'in_progress' WHERE id = ?");
            $stmtStatus->execute([$ticketId]);

            $stmtLog = $pdo->prepare("INSERT INTO ticket_logs (ticket_id, action_text) VALUES (?, ?)");
            $stmtLog->execute([$ticketId, "Admin REJECTED backup request. Work must continue."]);
        }
        header("Location: view_ticket.php?id=". $ticketId);
        exit();
    } catch (PDOException $e) {
        logSystemError("Admin backup decision failure: " . $e->getMessage());
        $error = "Failed to process backup decision.";
    }
}

$stmt = $pdo->prepare("
    SELECT t.*,
        u.name AS client_name,
        u.email AS client_email,
        eng.name AS engineer_name,
        e.name AS equipment_name,
        e.serial_number AS equipment_serial
    FROM tickets t
    JOIN users u  ON t.client_id = u.id
    LEFT JOIN users eng ON t.engineer_id = eng.id
    JOIN equipment e ON t.equipment_id = e.id
    WHERE t.id = ?
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if(!$ticket){
    echo "Ticket not found.";
    exit();
}

if($role === 'Client' && (int)$ticket['client_id'] !== $userId){
    http_response_code(403);
    echo "403 Forbidden. You do not have permission to view this application.";
    exit();
}

$engineers = [];
if($role === 'Admin'){
    $stmtEng = $pdo->query("SELECT id, name FROM users WHERE role = 'Engineer' ORDER BY name ASC");
    $engineers = $stmtEng->fetchAll();
}

$stmtLogs = $pdo->prepare("SELECT action_text, created_at FROM ticket_logs WHERE ticket_id = ? ORDER BY created_at DESC");
$stmtLogs->execute([$ticketId]);
$logs = $stmtLogs->fetchAll();

require_once __DIR__ . '/../views/header.php';
?>

<div class="max-w-2xl bg-gray-900 p-6 rounded-xl border border-gray-800">
    <a href="tickets.php" class="text-xs text-gray-500 hover:text-orange-500">Back to Applications</a>

    <h1 class="text-xl font-bold text-orange-500 mt-2">
        <?= htmlspecialchars($ticket['subject'], ENT_QUOTES, 'UTF-8') ?> (#<?= $ticket['id'] ?>)
    </h1>

    <?php if (!empty($error)): ?>
        <div class="bg-red-500/10 border border-red-500/20 text-red-400 text-xs p-3 rounded mt-4">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="mt-6 space-y-4 text-sm">
        <div>
            <span class="text-gray-500">Status:</span>
            <span class="<?= $ticket['status'] === 'backup_required' ? 'text-red-500 font-bold' : 'text-white font-medium' ?> ml-1">
                <?= str_replace('_', ' ', ucfirst($ticket['status'])) ?>
            </span>
        </div>

        <div>
            <span class="text-gray-500">Priority:</span>
            <span class="text-white font-medium ml-1"><?= ucfirst($ticket['priority']) ?></span>
        </div>

        <div>
            <span class="text-gray-500">Equipment:</span>
            <span class="text-white ml-1">
                <?= htmlspecialchars($ticket['equipment_name'], ENT_QUOTES, 'UTF-8') ?>
                (S/N: <?= htmlspecialchars($ticket['equipment_serial'], ENT_QUOTES, 'UTF-8') ?>)
            </span>
        </div>
        
        <div>
            <span class="text-gray-500">Reported By:</span>
            <span class="text-white ml-1">
                <?= htmlspecialchars($ticket['client_name'], ENT_QUOTES, 'UTF-8') ?> 
                (<?= htmlspecialchars($ticket['client_email'], ENT_QUOTES, 'UTF-8') ?>)
            </span>
        </div>

        <div>
            <span class="text-gray-500">Assigned Engineer:</span>
            <span class="text-white ml-1">
                <?= $ticket['engineer_name'] ? htmlspecialchars($ticket['engineer_name'], ENT_QUOTES, 'UTF-8') : 'Unassigned' ?>
            </span>
        </div>

        <?php if ($ticket['status'] === 'closed'): ?>
            <div class="border-t border-gray-800 pt-4 mt-4 text-xs space-y-1">
                <div><span class="text-gray-500">Hours spent:</span> <span class="text-white font-mono"><?= $ticket['hours_spent'] ?> h</span></div>
                <div><span class="text-gray-500">Parts cost:</span> <span class="text-white font-mono"><?= $ticket['cost_of_parts'] ?> Kč</span></div>
            </div>
        <?php endif; ?>

        <?php if ($role === 'Admin' && $ticket['status'] === 'backup_required'): ?>
            <div class="border border-red-500/20 bg-red-500/5 p-4 rounded-lg mt-4 space-y-3">
                <span class="block text-xs font-bold text-red-400 uppercase tracking-wider">🚨 Backup Request Pending Approval</span>
                
                <form action="view_ticket.php?id=<?= $ticketId ?>" method="POST" class="flex gap-2">
                    <button type="submit" name="admin_backup_decision" value="approve" class="bg-green-600 hover:bg-green-500 text-white text-xs px-3 py-1.5 rounded transition font-medium">
                        Accept & Add Engineers
                    </button>
                    <button type="submit" name="admin_backup_decision" value="reject" class="bg-gray-800 hover:bg-gray-700 text-gray-400 text-xs px-3 py-1.5 rounded transition font-medium">
                        Reject Request
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($role === 'Engineer' && (int)$ticket['engineer_id'] === $userId): ?>
            <div class="border-t border-gray-800 pt-4 mt-4">
                <?php if ($ticket['status'] === 'new'): ?>
                    <form action="view_ticket.php?id=<?= $ticketId ?>" method="POST">
                        <button type="submit" name="ticket_action" value="start" class="bg-blue-600 hover:bg-blue-500 text-white text-xs px-4 py-2 rounded transition font-medium">
                            Start Work
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($ticket['status'] === 'in_progress' || $ticket['status'] === 'backup_required'): ?>
                    <div class="space-y-4 max-w-xs">
                        
                        <?php if ($ticket['status'] === 'in_progress'): ?>
                            <form action="view_ticket.php?id=<?= $ticketId ?>" method="POST" class="border-b border-gray-800 pb-4 space-y-2">
                                <label for="backup_reason" class="block text-xs text-gray-400 font-medium">Specify Backup Reason:</label>
                                <input type="text" name="backup_reason" id="backup_reason" required placeholder="e.g. Need second person for heavy lifting"
                                       class="w-full bg-gray-950 border border-gray-800 rounded px-2.5 py-1.5 text-white text-xs focus:outline-none">
                                <button type="submit" name="ticket_action" value="backup" class="bg-red-600 hover:bg-red-500 text-white text-xs px-3 py-1.5 rounded transition font-medium">
                                    🚨 Request Backup
                                </button>
                            </form>
                        <?php endif; ?>

                        <form action="view_ticket.php?id=<?= $ticketId ?>" method="POST" class="space-y-3 pt-2">
                            <span class="block text-xs text-gray-400 font-medium">Report Resources Before Closing:</span>
                            <div>
                                <label for="hours_spent" class="block text-xs text-gray-500 mb-1">Hours spent:</label>
                                <input type="number" step="0.1" name="hours_spent" id="hours_spent" required min="0" placeholder="e.g. 2.5"
                                       class="w-full bg-gray-950 border border-gray-800 rounded px-2.5 py-1.5 text-white text-xs focus:outline-none">
                            </div>

                            <div>
                                <label for="cost_of_parts" class="block text-xs text-gray-500 mb-1">Parts cost (Kč):</label>
                                <input type="number" step="1" name="cost_of_parts" id="cost_of_parts" required min="0" placeholder="e.g. 1500"
                                       class="w-full bg-gray-950 border border-gray-800 rounded px-2.5 py-1.5 text-white text-xs focus:outline-none">
                            </div>

                            <button type="submit" name="ticket_action" value="resolve" class="bg-green-600 hover:bg-green-500 text-white text-xs px-4 py-2 rounded transition font-medium">
                                Resolve and Close Ticket
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($role === 'Admin'): ?>
            <div class="border-t border-gray-800 pt-4 mt-4">
                <form action="view_ticket.php?id=<?= $ticketId ?>" method="POST" class="space-y-2">
                    <label for="engineer_id" class="block text-xs text-gray-400">Assign or Change Engineer:</label>
                    <div class="flex gap-2">
                        <select name="engineer_id" id="engineer_id" required class="bg-gray-950 border border-gray-800 rounded px-3 py-1.5 text-white focus:outline-none text-xs">
                            <option value="">-- Select Engineer --</option>
                            <?php foreach ($engineers as $eng): ?>
                                <option value="<?= $eng['id'] ?>" <?= ((int)$ticket['engineer_id'] === (int)$eng['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($eng['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="assign_engineer" class="bg-orange-600 hover:bg-orange-500 text-white text-xs px-4 py-1.5 rounded transition">
                            Assign
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="border-t border-gray-800 pt-4">
            <span class="text-xs text-gray-400 block mb-1">Detailed Description:</span>
            <p class="text-gray-300 whitespace-pre-wrap leading-relaxed"><?= htmlspecialchars($ticket['description'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <div class="border-t border-gray-800 pt-4">
            <span class="text-xs text-gray-400 block mb-2">Application History Logs:</span>
            <div class="space-y-1 text-xs font-mono text-gray-500">
                <div>[<?= date('d.m.Y H:i', strtotime($ticket['created_at'])) ?>] Ticket created by client.</div>
                <?php foreach ($logs as $log): ?>
                    <div>[<?= date('d.m.Y H:i', strtotime($log['created_at'])) ?>] <?= htmlspecialchars($log['action_text'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/footer.php'; ?>