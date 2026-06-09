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

require_once __DIR__ . '/../views/header.php';
?>

<div class="max-w-2xl bg-gray-900 p-6 rounded-xl border border-gray-800">
    <a href="tickets.php" class="text-xs text-gray-500 hover:text-orange-500">Back to Applications</a>

    <h1 class="text-xl font-bold text-orange-500 mt-2">
        <?=  htmlspecialchars($ticket['subject'], ENT_QUOTES, 'UTF-8') ?> (#<?= $ticket['id'] ?>)
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

       <div class="border-t border-gray-800 pt-4">
            <span class="text-xs text-gray-400 block mb-1">Detailed Description:</span>
            <p class="text-gray-300 whitespace-pre-wrap leading-relaxed"><?= htmlspecialchars($ticket['description'], ENT_QUOTES, 'UTF-8') ?></p>
       </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../views/footer.php';
?>