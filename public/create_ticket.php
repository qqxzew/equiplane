<?php
declare(strict_types=1);
/** @var PDO $pdo */
session_start();

require_once __DIR__ . '/../app/auth/check.php';
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/database/db.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Client') {
    header('Location: tickets.php');
    exit();
}

$stmtEquip = $pdo->query("SELECT id, name FROM equipment ORDER BY name");
$equipments = $stmtEquip->fetchAll();

$savedSubject = '';
$savedDescription = '';
$savedEquipmentId = '';
$savedPriority = 'medium';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        setFlash('error', 'Security session expired. Please submit the form again.');
        header('Location: create_ticket.php');
        exit();
    }

    $savedEquipmentId = $_POST['equipment_id'] ?? '';
    $savedSubject = trim($_POST['subject'] ?? '');
    $savedDescription = trim($_POST['description'] ?? '');
    $savedPriority = $_POST['priority'] ?? 'medium';

    $equipmentId = (int)$savedEquipmentId;

    if (empty($savedSubject) || empty($savedDescription) || empty($equipmentId)) {
        setFlash('error', 'All fields are required.');
    } elseif (mb_strlen($savedSubject) > 255) {
        setFlash('error', 'Subject is too long (maximum 255 characters).');
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO tickets (client_id, equipment_id, subject, description, priority, status)
            VALUES (?, ?, ?, ?, ?, 'new')
        ");

        $stmt->execute([
                $_SESSION['user_id'],
                $equipmentId,
                htmlspecialchars($savedSubject, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($savedDescription, ENT_QUOTES, 'UTF-8'),
                $savedPriority
        ]);

        setFlash('success', 'Application successfully created and sent to engineers!');
        header('Location: tickets.php');
        exit();
    }
}

require_once __DIR__ . '/../views/header.php';
?>

    <div class="max-w-2xl bg-gray-900 border border-gray-800 rounded-2xl p-8 shadow-xl">
        <div class="mb-6">
            <a href="tickets.php" class="text-xs text-gray-500 hover:text-orange-500 transition">← Back to
                Applications</a>
            <h1 class="text-2xl font-bold tracking-wider text-orange-500 mt-2">Create New Application</h1>
        </div>

        <?= displayFlash() ?>

        <form action="create_ticket.php" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token"
                   value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">

            <div>
                <label for="equipment_id" class="block text-sm font-medium text-gray-400 mb-2">Select Equipment</label>
                <select name="equipment_id" id="equipment_id" required
                        class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-orange-500 transition">
                    <option value="">-- Choose machine --</option>
                    <?php foreach ($equipments as $equip): ?>
                        <option value="<?= $equip['id'] ?>" <?= ((string)$equip['id'] === $savedEquipmentId) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($equip['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="subject" class="block text-sm font-medium text-gray-400 mb-2">Application Subject</label>
                <input type="text" name="subject" id="subject" required placeholder="e.g., Engine overheating"
                       value="<?= htmlspecialchars($savedSubject, ENT_QUOTES, 'UTF-8') ?>"
                       class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-orange-500 transition">
            </div>

            <div>
                <label for="priority" class="block text-sm font-medium text-gray-400 mb-2">Priority Level</label>
                <select name="priority" id="priority" required
                        class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-orange-500 transition">
                    <option value="low" <?= $savedPriority === 'low' ? 'selected' : '' ?>>Low (Standard maintenance)
                    </option>
                    <option value="medium" <?= $savedPriority === 'medium' ? 'selected' : '' ?>>Medium (Needs attention
                        soon)
                    </option>
                    <option value="high" <?= $savedPriority === 'high' ? 'selected' : '' ?>>High (Operational
                        shutdown)
                    </option>
                </select>
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-400 mb-2">Detailed
                    Description</label>
                <textarea name="description" id="description" rows="5" required placeholder="Describe what happened..."
                          class="w-full bg-gray-950 border border-gray-800 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-orange-500 transition"><?= htmlspecialchars($savedDescription, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <button type="submit"
                    class="w-full bg-orange-600 hover:bg-orange-500 text-white font-medium py-2.5 rounded-lg transition shadow-lg shadow-orange-600/10">
                Submit Application
            </button>
        </form>
    </div>

<?php require_once __DIR__ . '/../views/footer.php'; ?>