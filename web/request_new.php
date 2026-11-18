<?php
$title = "New Request";
$active = "request_new.php";
include "header.php";

// Sample data. Later: SELECT from DocumentTypes.
$types = [
    'Barangay Clearance'       => 50.00,
    'Certificate of Residency' => 50.00,
    'Certificate of Indigency' => 0.00,
];

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Later: INSERT into DocumentRequests and copy the fee into fee_charged.
    $message = 'Saved (demo only).';
}
?>
<div class="card" style="max-width:640px">
    <h3>Request details</h3>

    <?php if ($message): ?>
        <div class="note ok"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <label for="resident">Resident</label>
        <input class="f" id="resident" name="resident" placeholder="Search by name" required>

        <label for="doctype">Document type</label>
        <select id="doctype" name="doctype">
            <?php foreach ($types as $name => $fee): ?>
                <option><?= htmlspecialchars($name) ?> (<?= number_format($fee, 2) ?>)</option>
            <?php endforeach; ?>
        </select>

        <label for="purpose">Purpose</label>
        <input class="f" id="purpose" name="purpose" required>

        <button class="btn" style="margin-top:16px">Save request</button>
    </form>
</div>
<?php include "footer.php"; ?>
