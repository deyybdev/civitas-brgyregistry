<?php
// Quick check that PHP can talk to MySQL. Delete this file before the defense.
include "db.php";

$tables = ['Barangay', 'Purok', 'Household', 'Resident', 'DocumentType', 'DocumentRequest', 'Payment'];

echo "<h2>Connected to civitas_brgyregistry</h2><ul>";
foreach ($tables as $table) {
    $count = $conn->query("SELECT COUNT(*) AS n FROM $table")->fetch_assoc()['n'];
    echo "<li>$table: $count rows</li>";
}
echo "</ul>";

$view = $conn->query("SELECT COUNT(*) AS n FROM vw_request_balances")->fetch_assoc()['n'];
echo "<p>View vw_request_balances: $view rows</p>";
