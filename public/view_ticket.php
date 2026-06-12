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