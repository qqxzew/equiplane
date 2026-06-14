<?php
declare(strict_types=1);
/** @var PDO $pdo */
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header('Location: tickets.php');
    exit();
}

require_once __DIR__ . '/../app/auth/check.php';
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/database/db.php';

$stmt = $pdo->query("
    SELECT 
        t.id, 
        t.subject, 
        e.name AS equipment_name, 
        u.name AS client_name, 
        t.hours_spent, 
        t.cost_of_parts, 
        t.created_at
    FROM tickets t
    JOIN equipment e ON t.equipment_id = e.id
    JOIN users u ON t.client_id = u.id
    WHERE t.status = 'closed'
    ORDER BY t.created_at DESC
");
$tickets = $stmt->fetchAll();

$filename = "financial_report_" . date('Y-m-d_H-i') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, ['Ticket ID', 'Subject', 'Equipment', 'Client Company', 'Hours Spent', 'Parts Cost (Kč)', 'Created At']);

foreach ($tickets as $ticket) {
    fputcsv($output, [
        $ticket['id'],
        $ticket['subject'],
        $ticket['equipment_name'],
        $ticket['client_name'],
        $ticket['hours_spent'],
        $ticket['cost_of_parts'],
        $ticket['created_at']
    ]);
}

fclose($output);
exit();