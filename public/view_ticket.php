<?php
declare(strict_types=1);
/** @var PDO $pdo */
session_start();

require_once __DIR__ . '/../app/auth/check.php';
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/database/db.php';

$role = $_SESSION['user_role'];
$userId = $_SESSION['user_id'];

if (empty($_GET['id'])) {
    header('Location: tickets.php');
    exit();
}

$ticketId = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        setFlash('error', 'Security token expired.');
        header("Location: view_ticket.php?id=" . $ticketId);
        exit();
    }

    if ($role === 'Engineer' && isset($_POST['ticket_action'])) {
        $action = $_POST['ticket_action'];

        try {
            if ($action === 'start') {
                $stmtStatus = $pdo->prepare("UPDATE tickets SET status = 'in_progress' WHERE id = ?");
                $stmtStatus->execute([$ticketId]);

                $stmtLog = $pdo->prepare("INSERT INTO ticket_logs (ticket_id, action_text) VALUES (?, ?)");
                $stmtLog->execute([$ticketId, "Engineer started work on the ticket."]);

                setFlash('success', 'Work started.');
            } elseif ($action === 'resolve') {
                $hoursSpent = isset($_POST['hours_spent']) ? (float)$_POST['hours_spent'] : 0.0;
                $costOfParts = isset($_POST['cost_of_parts']) ? (float)$_POST['cost_of_parts'] : 0.0;

                $stmtStatus = $pdo->prepare("UPDATE tickets SET status = 'closed', hours_spent = ?, cost_of_parts = ? WHERE id = ?");
                $stmtStatus->execute([$hoursSpent, $costOfParts, $ticketId]);

                $stmtLog = $pdo->prepare("INSERT INTO ticket_logs (ticket_id, action_text) VALUES (?, ?)");
                $stmtLog->execute([$ticketId, "Engineer closed the ticket. Hours: $hoursSpent, Cost: $costOfParts Kč"]);

                setFlash('success', 'Ticket closed.');
            } elseif ($action === 'backup') {
                $backupReason = trim($_POST['backup_reason'] ?? '');
                if (empty($backupReason)) {
                    setFlash('error', 'Provide a reason.');
                } else {
                    $stmtStatus = $pdo->prepare("UPDATE tickets SET status = 'backup_required' WHERE id = ?");
                    $stmtStatus->execute([$ticketId]);

                    $stmtLog = $pdo->prepare("INSERT INTO ticket_logs (ticket_id, action_text) VALUES (?, ?)");
                    $stmtLog->execute([$ticketId, "Backup requested: " . htmlspecialchars($backupReason, ENT_QUOTES, 'UTF-8')]);

                    setFlash('success', 'Backup requested.');
                }
            }
        } catch (PDOException $e) {
            logSystemError("DB error: " . $e->getMessage());
            setFlash('error', 'System error.');
        }

        header("Location: view_ticket.php?id=" . $ticketId);
        exit();
    }

    if ($role === 'Admin' && isset($_POST['assign_engineer'])) {
        $engineerId = (int)$_POST['engineer_id'];
        if ($engineerId > 0) {
            try {
                $stmtUpdate = $pdo->prepare("UPDATE tickets SET engineer_id = ? WHERE id = ?");
                $stmtUpdate->execute([$engineerId, $ticketId]);

                $stmtEngName = $pdo->prepare("SELECT name FROM users WHERE id = ?");
                $stmtEngName->execute([$engineerId]);
                $engineerName = $stmtEngName->fetchColumn();

                $stmtLog = $pdo->prepare("INSERT INTO ticket_logs (ticket_id, action_text) VALUES (?, ?)");
                $stmtLog->execute([$ticketId, "Admin assigned engineer: " . $engineerName]);

                setFlash('success', 'Engineer assigned.');
            } catch (PDOException $e) {
                logSystemError("Assignment error: " . $e->getMessage());
                setFlash('error', 'Assignment failed.');
            }
        }
        header("Location: view_ticket.php?id=" . $ticketId);
        exit();
    }

    if ($role === 'Admin' && isset($_POST['admin_backup_decision'])) {
        $decision = $_POST['admin_backup_decision'];
        try {
            if ($decision === 'approve') {
                $stmtStatus = $pdo->prepare("UPDATE tickets SET status = 'in_progress' WHERE id = ?");
                $stmtStatus->execute([$ticketId]);
                $stmtLog = $pdo->prepare("INSERT INTO ticket_logs (ticket_id, action_text) VALUES (?, ?)");
                $stmtLog->execute([$ticketId, "Admin approved backup."]);
                setFlash('success', 'Backup approved.');
            } elseif ($decision === 'reject') {
                $stmtStatus = $pdo->prepare("UPDATE tickets SET status = 'in_progress' WHERE id = ?");
                $stmtStatus->execute([$ticketId]);
                $stmtLog = $pdo->prepare("INSERT INTO ticket_logs (ticket_id, action_text) VALUES (?, ?)");
                $stmtLog->execute([$ticketId, "Admin rejected backup."]);
                setFlash('success', 'Backup rejected.');
            }
        } catch (PDOException $e) {
            logSystemError("Backup decision error: " . $e->getMessage());
            setFlash('error', 'Decision failed.');
        }
        header("Location: view_ticket.php?id=" . $ticketId);
        exit();
    }
}

$stmt = $pdo->prepare("
    SELECT t.*, u.name AS client_name, u.email AS client_email,
           eng.name AS engineer_name, e.name AS equipment_name, e.serial_number AS equipment_serial
    FROM tickets t
    JOIN users u ON t.client_id = u.id
    LEFT JOIN users eng ON t.engineer_id = eng.id
    JOIN equipment e ON t.equipment_id = e.id
    WHERE t.id = ?
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket || ($role === 'Client' && (int)$ticket['client_id'] !== $userId)) {
    header('Location: tickets.php');
    exit();
}

$engineers = [];
if ($role === 'Admin') {
    $stmtEng = $pdo->query("SELECT id, name FROM users WHERE role = 'Engineer' ORDER BY name");
    $engineers = $stmtEng->fetchAll();
}

$stmtLogs = $pdo->prepare("SELECT action_text, created_at FROM ticket_logs WHERE ticket_id = ? ORDER BY created_at DESC");
$stmtLogs->execute([$ticketId]);
$logs = $stmtLogs->fetchAll();

require_once __DIR__ . '/../views/header.php';
?>

    <div class="space-y-4 max-w-4xl">
        <div class="flex items-center gap-3">
            <a href="tickets.php"
               class="text-gray-500 hover:text-white transition p-1 bg-gray-900 rounded border border-gray-800">
                <i class="ph ph-arrow-left"></i>
            </a>
            <h1 class="text-xl font-semibold text-white">#<?= $ticket['id'] ?>
                - <?= htmlspecialchars($ticket['subject'], ENT_QUOTES, 'UTF-8') ?></h1>
        </div>

        <?= displayFlash() ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2 space-y-4">
                <div class="bg-gray-900 p-5 rounded-lg border border-gray-800">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <i class="ph ph-text-align-left"></i> Description
                    </h2>
                    <p class="text-sm text-gray-300 whitespace-pre-wrap"><?= htmlspecialchars($ticket['description'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <div class="bg-gray-900 p-5 rounded-lg border border-gray-800">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <i class="ph ph-clock-counter-clockwise"></i> Activity Logs
                    </h2>
                    <div class="space-y-2 text-xs font-mono text-gray-400">
                        <div class="flex gap-3">
                            <span class="text-gray-600"><?= date('d.m.y H:i', strtotime($ticket['created_at'])) ?></span>
                            <span class="text-gray-300">Ticket created by client.</span>
                        </div>
                        <?php foreach ($logs as $log): ?>
                            <div class="flex gap-3">
                                <span class="text-gray-600"><?= date('d.m.y H:i', strtotime($log['created_at'])) ?></span>
                                <span class="text-gray-300"><?= htmlspecialchars($log['action_text'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="bg-gray-900 p-5 rounded-lg border border-gray-800 space-y-4 text-sm">
                    <div>
                        <div class="text-xs text-gray-500 mb-1">Status</div>
                        <?= getStatusBadge($ticket['status']) ?>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 mb-1">Priority</div>
                        <?= getPriorityBadge($ticket['priority']) ?>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 mb-1">Equipment</div>
                        <div class="text-gray-200"><?= htmlspecialchars($ticket['equipment_name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="text-xs font-mono text-gray-500 mt-0.5">
                            S/N: <?= htmlspecialchars($ticket['equipment_serial'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 mb-1">Client</div>
                        <div class="text-gray-200"><?= htmlspecialchars($ticket['client_name'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 mb-1">Engineer</div>
                        <div class="text-gray-200"><?= $ticket['engineer_name'] ? htmlspecialchars($ticket['engineer_name'], ENT_QUOTES, 'UTF-8') : '<span class="text-gray-600">Unassigned</span>' ?></div>
                    </div>

                    <?php if ($ticket['status'] === 'closed'): ?>
                        <div class="pt-3 border-t border-gray-800">
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-gray-500">Hours spent</span>
                                <span class="text-white font-mono"><?= $ticket['hours_spent'] ?> h</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500">Parts cost</span>
                                <span class="text-white font-mono"><?= $ticket['cost_of_parts'] ?> Kč</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($role === 'Admin' && $ticket['status'] === 'backup_required'): ?>
                    <div class="bg-red-500/10 border border-red-500/20 p-4 rounded-lg">
                        <span class="block text-xs font-bold text-red-400 uppercase tracking-wider mb-3">Backup Requested</span>
                        <form action="view_ticket.php?id=<?= $ticketId ?>" method="POST" class="flex gap-2">
                            <input type="hidden" name="csrf_token"
                                   value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" name="admin_backup_decision" value="approve"
                                    class="flex-1 bg-red-600 hover:bg-red-500 text-white text-xs py-2 rounded transition">
                                Approve
                            </button>
                            <button type="submit" name="admin_backup_decision" value="reject"
                                    class="flex-1 bg-gray-800 hover:bg-gray-700 text-gray-400 text-xs py-2 rounded transition">
                                Reject
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if ($role === 'Engineer' && (int)$ticket['engineer_id'] === $userId): ?>
                    <div class="bg-gray-900 border border-gray-800 p-4 rounded-lg space-y-2">
                        <?php if ($ticket['status'] === 'new'): ?>
                            <form action="view_ticket.php?id=<?= $ticketId ?>" method="POST">
                                <input type="hidden" name="csrf_token"
                                       value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" name="ticket_action" value="start"
                                        class="w-full bg-blue-600 hover:bg-blue-500 text-white text-xs py-2 rounded transition flex items-center justify-center gap-2">
                                    <i class="ph ph-play"></i> Start Work
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($ticket['status'] === 'in_progress' || $ticket['status'] === 'backup_required'): ?>
                            <?php if ($ticket['status'] === 'in_progress'): ?>
                                <button onclick="toggleModal('modal-backup')"
                                        class="w-full bg-gray-800 hover:bg-gray-700 text-red-400 border border-gray-700 text-xs py-2 rounded transition flex items-center justify-center gap-2">
                                    <i class="ph ph-warning"></i> Request Backup
                                </button>
                            <?php endif; ?>
                            <button onclick="toggleModal('modal-resolve')"
                                    class="w-full bg-green-600 hover:bg-green-500 text-white text-xs py-2 rounded transition flex items-center justify-center gap-2">
                                <i class="ph ph-check-circle"></i> Resolve Ticket
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($role === 'Admin'): ?>
                    <button onclick="toggleModal('modal-assign')"
                            class="w-full bg-gray-900 hover:bg-gray-800 border border-gray-800 text-gray-300 text-xs py-2.5 rounded transition flex items-center justify-center gap-2">
                        <i class="ph ph-user-plus"></i> Assign Engineer
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php if ($role === 'Engineer'): ?>
    <div id="modal-backup" class="hidden fixed inset-0 bg-black/80 z-50 items-center justify-center backdrop-blur-sm">
        <div class="bg-gray-900 border border-gray-800 rounded-lg p-6 w-80">
            <h3 class="text-sm font-semibold text-white mb-4">Request Backup</h3>
            <form action="view_ticket.php?id=<?= $ticketId ?>" method="POST" class="space-y-3">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="text" name="backup_reason" required placeholder="Reason..."
                       class="w-full bg-gray-950 border border-gray-800 rounded px-3 py-2 text-white text-xs focus:outline-none focus:border-orange-500">
                <div class="flex gap-2">
                    <button type="button" onclick="toggleModal('modal-backup')"
                            class="flex-1 bg-gray-800 text-gray-400 py-2 rounded text-xs">Cancel
                    </button>
                    <button type="submit" name="ticket_action" value="backup"
                            class="flex-1 bg-red-600 text-white py-2 rounded text-xs">Submit
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="modal-resolve" class="hidden fixed inset-0 bg-black/80 z-50 items-center justify-center backdrop-blur-sm">
        <div class="bg-gray-900 border border-gray-800 rounded-lg p-6 w-80">
            <h3 class="text-sm font-semibold text-white mb-4">Resolve Ticket</h3>
            <form action="view_ticket.php?id=<?= $ticketId ?>" method="POST" class="space-y-3">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Hours spent</label>
                    <input type="number" step="0.1" name="hours_spent" required min="0"
                           class="w-full bg-gray-950 border border-gray-800 rounded px-3 py-2 text-white text-xs focus:outline-none focus:border-orange-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Parts cost (Kč)</label>
                    <input type="number" step="1" name="cost_of_parts" required min="0"
                           class="w-full bg-gray-950 border border-gray-800 rounded px-3 py-2 text-white text-xs focus:outline-none focus:border-orange-500">
                </div>
                <div class="flex gap-2 mt-4">
                    <button type="button" onclick="toggleModal('modal-resolve')"
                            class="flex-1 bg-gray-800 text-gray-400 py-2 rounded text-xs">Cancel
                    </button>
                    <button type="submit" name="ticket_action" value="resolve"
                            class="flex-1 bg-green-600 text-white py-2 rounded text-xs">Close Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($role === 'Admin'): ?>
    <div id="modal-assign" class="hidden fixed inset-0 bg-black/80 z-50 items-center justify-center backdrop-blur-sm">
        <div class="bg-gray-900 border border-gray-800 rounded-lg p-6 w-80">
            <h3 class="text-sm font-semibold text-white mb-4">Assign Engineer</h3>
            <form action="view_ticket.php?id=<?= $ticketId ?>" method="POST" class="space-y-3">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <select name="engineer_id" required
                        class="w-full bg-gray-950 border border-gray-800 rounded px-3 py-2 text-white text-xs focus:outline-none focus:border-orange-500">
                    <option value="">-- Select --</option>
                    <?php foreach ($engineers as $eng): ?>
                        <option value="<?= $eng['id'] ?>" <?= ((int)$ticket['engineer_id'] === (int)$eng['id']) ? 'selected' : '' ?>><?= htmlspecialchars($eng['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="flex gap-2 mt-4">
                    <button type="button" onclick="toggleModal('modal-assign')"
                            class="flex-1 bg-gray-800 text-gray-400 py-2 rounded text-xs">Cancel
                    </button>
                    <button type="submit" name="assign_engineer"
                            class="flex-1 bg-orange-600 text-white py-2 rounded text-xs">Save
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

    <script>
        function toggleModal(id) {
            const el = document.getElementById(id);
            if (el.classList.contains('hidden')) {
                el.classList.replace('hidden', 'flex');
            } else {
                el.classList.replace('flex', 'hidden');
            }
        }
    </script>

<?php require_once __DIR__ . '/../views/footer.php'; ?>