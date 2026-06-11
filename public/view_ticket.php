<?php
declare(strict_types= 1);

session_start();

require_once __DIR__ . '/../app/auth/check.php';
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/database/db.php';

$role = $_SESSION['user_role'];
$userId = $_SESSION['user_id'];

if(!isset($_GET['id']) || empty($_GET['id'])){
    echo "Invalid ticket Id.";
    exit();
}

$ticketId = (int)$_GET['id'];

if($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'Engineer' && isset($_POST['ticket_action'])){
    $action = $_POST['ticket_action'];

    if($action === 'start'){
        $stmtStatus = $pdo->prepare("UPDATE tickets SET status = 'in_progress' WHERE id = ?");
        $stmtStatus->execute([$ticketId]);

        $stmtLog = $pdo->prepare("INSERT_INTO ticket_logs (ticket_id, action_text) VALUES (?, ?)");
        $stmtLog->execute(['$ticketId, "Engineer started work on the ticket."']);
    }
    elseif($action === 'resolve'){
        $stmtStatus = $pdo->prepare("UPDATE tickets SET status = 'closed' WHERE id = ?");
        $stmtStatus->execute([$ticketId]);

        $stmtLog = $pdo->prepare("INSERT INTO ticket_logs (ticket_id, action_text) VALUES(?, ?)");
        $stmtLog->execute([$ticketId, "Engineer resolved and closed the ticket."]);
    }
    
    header("Location: view_ticket.php?id=" . $ticketId);
    exit();
}
//upd
if($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'Admin' && isset($_POST['assign_engineer'])){
    $engineerId = (int)$_POST['engineer_id'];
   if($engineerId > 0){
    $stmtUpdate = $pdo->prepare("UPDATE tickets SET engineer_id = ? WHERE id = ?");
    $stmtUpdate->execute([$engineerId, $ticketId]);

    $stmtEngName = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmtEngName->execute([$engineerId]);
    $engineerName = $stmtEngName->fetchColumn();


    $stmtLog = $pdo->prepare("INSERT INTO ticket_logs (ticket_id, action_text) VALUES (?, ?)");
    $stmtLog->execute([$ticketId, "Admin assigned engineer: " . $engineerName]);

    header("Location: view_ticket.php?id=" . $ticketId);
    exit();
   }
}
//upd
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
    $engineers = $stmtEng->fetchall();
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

    <div class="mt-6 space-y-4 text-sm">
        <div>
            <span class="text-gray-500">Status:</span>
            <span class="text-white font-medium ml-1"><?= ucfirst($ticket['status']) ?></span>
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

        <?php if ($role === 'Engineer' && (int)$ticket['engineer_id'] === $userId): ?>
            <div class="border-t border-gray-800 pt-4 mt-4">
                <form action="view_ticket.php?id=<?= $ticketId ?>" method="POST" class="flex gap-3">
                    <?php if ($ticket['status'] === 'new'): ?>
                        <button type="submit" name="ticket_action" value="start" class="bg-blue-600 hover:bg-blue-500 text-white text-xs px-4 py-2 rounded transition font-medium">
                            Start Work
                        </button>
                    <?php endif; ?>

                    <?php if ($ticket['status'] === 'in_progress'): ?>
                        <button type="submit" name="ticket_action" value="resolve" class="bg-green-600 hover:bg-green-500 text-white text-xs px-4 py-2 rounded transition font-medium">
                            Resolve Ticket
                        </button>
                    <?php endif; ?>
                </form>
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

<?php 
require_once __DIR__ . '/../views/footer.php'; 
?>