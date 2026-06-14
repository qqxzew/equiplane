<?php
declare(strict_types=1);
/** @var PDO $pdo */
session_start();

require_once __DIR__ . '/../app/auth/check.php';
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/database/db.php';

$role = $_SESSION['user_role'];

if ($role !== 'Admin' && $role !== 'Engineer') {
    header('Location: tickets.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'Admin') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        setFlash('error', 'Security token expired.');
        header('Location: equipment.php');
        exit();
    }

    $companyId = (int)$_POST['company_id'];
    $name = trim($_POST['name'] ?? '');
    $serial = trim($_POST['serial_number'] ?? '');

    if (empty($companyId) || empty($name) || empty($serial)) {
        setFlash('error', 'All fields are required.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO equipment (company_id, name, serial_number) VALUES (?, ?, ?)");
            $stmt->execute([$companyId, $name, $serial]);
            setFlash('success', 'Equipment registered.');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                setFlash('error', 'Serial number already exists.');
            } else {
                logSystemError("DB error: " . $e->getMessage());
                setFlash('error', 'System error.');
            }
        }
    }
    header('Location: equipment.php');
    exit();
}

$stmt = $pdo->query("
    SELECT e.id, e.name, e.serial_number, c.name AS company_name
    FROM equipment e
    JOIN companies c ON e.company_id = c.id
    ORDER BY c.name ASC, e.name ASC
");
$equipments = $stmt->fetchAll();

$companies = [];
if ($role === 'Admin') {
    $stmtComp = $pdo->query("SELECT id, name FROM companies ORDER BY name ASC");
    $companies = $stmtComp->fetchAll();
}

require_once __DIR__ . '/../views/header.php';
?>

    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <h1 class="text-xl font-semibold text-white flex items-center gap-2">
                <i class="ph ph-nut text-orange-500"></i> Equipment Registry
            </h1>
            <?php if ($role === 'Admin'): ?>
                <button onclick="toggleModal('modal-add')"
                        class="bg-orange-600 hover:bg-orange-500 text-white text-xs font-medium py-2 px-4 rounded transition flex items-center gap-2">
                    <i class="ph ph-plus-bold"></i> Add Equipment
                </button>
            <?php endif; ?>
        </div>

        <?= displayFlash() ?>

        <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead>
                    <tr class="border-b border-gray-800 bg-gray-950/50 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="py-2.5 px-4 w-16">ID</th>
                        <th class="py-2.5 px-4">Owner Company</th>
                        <th class="py-2.5 px-4">Machine Name</th>
                        <th class="py-2.5 px-4 w-48">Serial Number</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/60 text-xs text-gray-300">
                    <?php if (empty($equipments)): ?>
                        <tr>
                            <td colspan="4" class="py-6 text-center text-gray-500">No equipment found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($equipments as $eq): ?>
                            <tr class="hover:bg-gray-850/40 transition">
                                <td class="py-2 px-4 font-mono text-gray-500"><?= $eq['id'] ?></td>
                                <td class="py-2 px-4 font-medium text-white"><?= htmlspecialchars($eq['company_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="py-2 px-4 text-gray-400"><?= htmlspecialchars($eq['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="py-2 px-4 font-mono text-gray-500"><?= htmlspecialchars($eq['serial_number'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php if ($role === 'Admin'): ?>
    <div id="modal-add" class="hidden fixed inset-0 bg-black/80 z-50 items-center justify-center backdrop-blur-sm">
        <div class="bg-gray-900 border border-gray-800 rounded-lg p-6 w-96">
            <h3 class="text-sm font-semibold text-white mb-4">Register Equipment</h3>
            <form action="equipment.php" method="POST" class="space-y-3">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Company</label>
                    <select name="company_id" required
                            class="w-full bg-gray-950 border border-gray-800 rounded px-3 py-2 text-white text-xs focus:outline-none focus:border-orange-500">
                        <option value="">-- Select --</option>
                        <?php foreach ($companies as $comp): ?>
                            <option value="<?= $comp['id'] ?>"><?= htmlspecialchars($comp['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Machine Name</label>
                    <input type="text" name="name" required
                           class="w-full bg-gray-950 border border-gray-800 rounded px-3 py-2 text-white text-xs focus:outline-none focus:border-orange-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Serial Number</label>
                    <input type="text" name="serial_number" required
                           class="w-full bg-gray-950 border border-gray-800 rounded px-3 py-2 text-white text-xs focus:outline-none focus:border-orange-500">
                </div>
                <div class="flex gap-2 mt-4">
                    <button type="button" onclick="toggleModal('modal-add')"
                            class="flex-1 bg-gray-800 text-gray-400 py-2 rounded text-xs">Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-orange-600 text-white py-2 rounded text-xs">Add</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

    <script>
        function toggleModal(id) {
            const el = document.getElementById(id);
            el.classList.contains('hidden') ? el.classList.replace('hidden', 'flex') : el.classList.replace('flex', 'hidden');
        }
    </script>

<?php require_once __DIR__ . '/../views/footer.php'; ?>