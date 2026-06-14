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
        header('Location: users.php');
        exit();
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'Client';
    $companyId = !empty($_POST['company_id']) ? (int)$_POST['company_id'] : null;

    if (empty($name) || empty($email) || empty($password)) {
        setFlash('error', 'All fields required.');
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (company_id, name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$companyId, $name, $email, $hash, $role]);
            setFlash('success', 'User created.');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                setFlash('error', 'Email already exists.');
            } else {
                logSystemError("DB error: " . $e->getMessage());
                setFlash('error', 'System error.');
            }
        }
    }
    header('Location: users.php');
    exit();
}

$stmtUsers = $pdo->query("
    SELECT u.id, u.name, u.email, u.role, c.name AS company_name
    FROM users u
    LEFT JOIN companies c ON u.company_id = c.id
    ORDER BY u.role ASC, u.name ASC
");
$usersList = $stmtUsers->fetchAll();

$stmtComp = $pdo->query("SELECT id, name FROM companies ORDER BY name ASC");
$companies = $stmtComp->fetchAll();

require_once __DIR__ . '/../views/header.php';
?>

    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <h1 class="text-xl font-semibold text-white flex items-center gap-2">
                <i class="ph ph-users text-orange-500"></i> Personnel
            </h1>
            <button onclick="toggleModal('modal-add')"
                    class="bg-orange-600 hover:bg-orange-500 text-white text-xs font-medium py-2 px-4 rounded transition flex items-center gap-2">
                <i class="ph ph-user-plus"></i> Add User
            </button>
        </div>

        <?= displayFlash() ?>

        <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead>
                    <tr class="border-b border-gray-800 bg-gray-950/50 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="py-2.5 px-4">Name</th>
                        <th class="py-2.5 px-4">Email</th>
                        <th class="py-2.5 px-4 w-32">Role</th>
                        <th class="py-2.5 px-4">Company</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/60 text-xs text-gray-300">
                    <?php if (empty($usersList)): ?>
                        <tr>
                            <td colspan="4" class="py-6 text-center text-gray-500">No users found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usersList as $usr): ?>
                            <tr class="hover:bg-gray-850/40 transition">
                                <td class="py-2 px-4 font-medium text-white"><?= htmlspecialchars($usr['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="py-2 px-4 text-gray-400"><?= htmlspecialchars($usr['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="py-2 px-4">
                                    <?php if ($usr['role'] === 'Admin'): ?>
                                        <span class="text-red-400 font-medium">Admin</span>
                                    <?php elseif ($usr['role'] === 'Engineer'): ?>
                                        <span class="text-blue-400 font-medium">Engineer</span>
                                    <?php else: ?>
                                        <span class="text-green-400 font-medium">Client</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 px-4 text-gray-500"><?= $usr['company_name'] ? htmlspecialchars($usr['company_name'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="modal-add" class="hidden fixed inset-0 bg-black/80 z-50 items-center justify-center backdrop-blur-sm">
        <div class="bg-gray-900 border border-gray-800 rounded-lg p-6 w-[400px]">
            <h3 class="text-sm font-semibold text-white mb-4">Create New User</h3>
            <form action="users.php" method="POST" class="space-y-3">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <div class="grid grid-cols-2 gap-3">
                    <div class="col-span-2">
                        <label class="block text-xs text-gray-500 mb-1">Full Name</label>
                        <input type="text" name="name" required
                               class="w-full bg-gray-950 border border-gray-800 rounded px-3 py-2 text-white text-xs focus:outline-none focus:border-orange-500">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Email</label>
                        <input type="email" name="email" required
                               class="w-full bg-gray-950 border border-gray-800 rounded px-3 py-2 text-white text-xs focus:outline-none focus:border-orange-500">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Password</label>
                        <input type="password" name="password" required
                               class="w-full bg-gray-950 border border-gray-800 rounded px-3 py-2 text-white text-xs focus:outline-none focus:border-orange-500">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Role</label>
                        <select name="role" required
                                class="w-full bg-gray-950 border border-gray-800 rounded px-3 py-2 text-white text-xs focus:outline-none focus:border-orange-500">
                            <option value="Client">Client</option>
                            <option value="Engineer">Engineer</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Company (Clients)</label>
                        <select name="company_id"
                                class="w-full bg-gray-950 border border-gray-800 rounded px-3 py-2 text-white text-xs focus:outline-none focus:border-orange-500">
                            <option value="">-- Internal --</option>
                            <?php foreach ($companies as $comp): ?>
                                <option value="<?= $comp['id'] ?>"><?= htmlspecialchars($comp['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="flex gap-2 mt-4">
                    <button type="button" onclick="toggleModal('modal-add')"
                            class="flex-1 bg-gray-800 text-gray-400 py-2 rounded text-xs">Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-orange-600 text-white py-2 rounded text-xs">Create User
                    </button>
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