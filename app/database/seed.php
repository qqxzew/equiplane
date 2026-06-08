<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/db.php';

$hashAdmin = password_hash('admin', PASSWORD_DEFAULT);
$hashEngineer = password_hash('engineer', PASSWORD_DEFAULT);
$hashClient = password_hash('client', PASSWORD_DEFAULT);

$stmtCompany = $pdo->prepare("INSERT INTO companies (name, address, ico) VALUES (?, ?, ?)");
$stmtCompany->execute(['RTSoft', 'Lobezská 99/39, 326 00 Plzeň-Lobzy', '29092540']);
$idRTSoft = $pdo->lastInsertId();
$stmtCompany->execute(['SIT Port', 'Koterovská 152, 326 00 Plzeň 2-Slovany', '66362717']);
$idSitPort = $pdo->lastInsertId();
$stmtCompany->execute(['ŠKODA TRANSPORTATION a.s.', 'Emila Škody 2922/1, Jižní Předměstí, 301 00 Plzeň', '62623753']);
$idSkoda = $pdo->lastInsertId();


$stmtUser = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
$stmtUser->execute(["Admin", "admin@gmail.com", $hashAdmin, "Admin"]);
$stmtUser->execute(["Engineer1", "engineer1@gmail.com", $hashEngineer, "Engineer"]);
$stmtUser->execute(["Engineer2", "engineer2@gmail.com", $hashEngineer, "Engineer"]);
$stmtUser->execute(["Client", "windowsclient@gmail.com", $hashClient, "Client"]);
$stmtUser = $pdo->lastInsertId();


$stmtEquipment =$pdo->prepare("INSERT INTO equipment (company_id, name, serial_number) VALUES (?, ?, ?)");
$stmtEquipment->execute([$idRTSoft, "White Icecream car", "965986564"]);
$stmtEquipment->execute([$idSitPort, "Black Icecream car", "324234424"]);
$stmtEquipment->execute([$idSkoda, "Orange Icecream car", "654867436"]);
$stmtEquipment = $pdo->lastInsertId();

echo "Database seeded successfully.";
