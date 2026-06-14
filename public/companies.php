<?php
declare(strict_types=1);
/** @var PDO $pdo */
session_start();

require_once __DIR__ . '/../app/auth/check.php';
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/database/db.php';

if ($_SESSION['user_role'] !== 'Admin') {
    header('Location: tickets.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        setFlash('error', 'Security token expired.');
        header('Location: companies.php');
        exit();
    }

    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $ico = trim($_POST['ico'] ?? '');

    if (empty($name) || empty($address) || empty($ico)) {
        setFlash('error', 'All fields are required.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO companies (name, address, ico) VALUES (?, ?, ?)");
            $stmt->execute([$name, $address, $ico]);
            setFlash('success', 'Company added successfully.');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                setFlash('error', 'Company with this ICO already exists.');
            } else {
                logSystemError("DB error adding company: " . $e->getMessage());
                setFlash('error', 'System error occurred.');
            }
        }
    }
    header('Location: companies.php');
    exit();
}

$stmt = $pdo->query("SELECT * FROM companies ORDER BY name ASC");
$companies = $stmt->fetchAll();

require_once __DIR__ . '/../views/header.php';
?>

    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <h1 class="text-xl font-semibold text-white flex items-center gap-2">
                <i class="ph ph-buildings text-orange-500"></i> Companies
            </h1>
            <button onclick="toggleModal('modal-add')"
                    class="bg-orange-600 hover:bg-orange-500 text-white text-xs font-medium py-2 px-4 rounded transition flex items-center gap-2">
                <i class="ph ph-plus-bold"></i> Add Company
            </button>
        </div>

        <?= displayFlash() ?>

        <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead>
                    <tr class="border-b border-gray-800 bg-gray-950/50 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="py-2.5 px-4 w-16">ID</th>
                        <th class="py-2.5 px-4">Name</th>
                        <th class="py-2.5 px-4">Address</th>
                        <th class="py-2.5 px-4 w-32">ICO</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/60 text-xs text-gray-300">
                    <?php if (empty($companies)): ?>
                        <tr>
                            <td colspan="4" class="py-6 text-center text-gray-500">No companies found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($companies as $company): ?>
                            <tr class="hover:bg-gray-850/40 transition">
                                <td class="py-2 px-4 font-mono text-gray-500"><?= $company['id'] ?></td>
                                <td class="py-2 px-4 font-medium text-white"><?= htmlspecialchars($company['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="py-2 px-4 text-gray-400"><?= htmlspecialchars($company['address'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="py-2 px-4 font-mono text-gray-500"><?= htmlspecialchars($company['ico'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="modal-add" class="hidden fixed inset-0 bg-black/80 z-50 items-center justify-center backdrop-blur-sm">
        <div class="bg-gray-900 border border-gray-800 rounded-lg p-6 w-96">
            <h3 class="text-sm font-semibold text-white mb-4">Add Company</h3>
            <form action="companies.php" method="POST" class="space-y-3">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Company Name</label>
                    <input type="text" name="name" required
                           class="w-full bg-gray-950 border border-gray-800 rounded px-3 py-2 text-white text-xs focus:outline-none focus:border-orange-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Address</label>
                    <input type="text" name="address" required
                           class="w-full bg-gray-950 border border-gray-800 rounded px-3 py-2 text-white text-xs focus:outline-none focus:border-orange-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">ICO</label>
                    <input type="text" name="ico" required
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

    <script>
        function toggleModal(id) {
            const el = document.getElementById(id);
            el.classList.contains('hidden') ? el.classList.replace('hidden', 'flex') : el.classList.replace('flex', 'hidden');
        }
    </script>

<?php require_once __DIR__ . '/../views/footer.php'; ?>