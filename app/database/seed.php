<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/db.php';
/** @var PDO $pdo */

$hashAdmin = password_hash('admin', PASSWORD_DEFAULT);
$hashEngineer = password_hash('engineer', PASSWORD_DEFAULT);
$hashClient = password_hash('client', PASSWORD_DEFAULT);

$stmtCompany = $pdo->prepare("INSERT INTO companies (name, address, ico) VALUES (?, ?, ?)");

$stmtCompany->execute(['TechNova s.r.o.', 'Dlouhá 123/45, 110 00 Praha 1', '12345678']);
$idTechNova = $pdo->lastInsertId();

$stmtCompany->execute(['AgroPlus a.s.', 'Zelená 44, 602 00 Brno', '87654321']);
$idAgroPlus = $pdo->lastInsertId();

$stmtCompany->execute(['StavbaCZ s.r.o.', 'Průmyslová 7, 702 00 Ostrava', '11223344']);
$idStavba = $pdo->lastInsertId();


$stmtUser = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, hourly_rate) VALUES (?, ?, ?, ?, ?)");
$stmtUser->execute(["Admin", "admin@gmail.com", $hashAdmin, "Admin", 0]);
$stmtUser->execute(["Engineer", "engineer@gmail.com", $hashEngineer, "Engineer", 600]);
$stmtUser->execute(["Engineer1", "engineer1@gmail.com", $hashEngineer, "Engineer", 850]);
$stmtUser->execute(["Client", "client@gmail.com", $hashClient, "Client", 0]);
$stmtUser = $pdo->lastInsertId();


$stmtEquipment = $pdo->prepare("INSERT INTO equipment (company_id, name, serial_number) VALUES (?, ?, ?)");
$stmtEquipment->execute([$idTechNova, "White Icecream car", "965986564"]);
$stmtEquipment->execute([$idAgroPlus, "Black Icecream car", "324234424"]);
$stmtEquipment->execute([$idStavba, "Orange Icecream car", "654867436"]);
$stmtEquipment = $pdo->lastInsertId();

echo "Database seeded successfully.";
