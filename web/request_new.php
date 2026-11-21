<?php
include "db.php";

$message = "";
$messageType = "ok";
$selectedResident = (int) ($_GET['resident'] ?? 0);

// ----- File the request (calls the stored procedure) -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedResident = (int) $_POST['resident_id'];
    try {
        $row = callProcedure($conn, "CALL sp_create_request(?, ?, ?, ?)", "iiss", [
            $selectedResident, (int) $_POST['doctype_id'], trim($_POST['purpose']), $_POST['request_date'],
        ]);
        $message = "Request #" . $row['request_id'] . " filed.";
        $selectedResident = 0;
    } catch (mysqli_sql_exception $e) {
        $message = friendlyError($e);   // for example: Only Active residents can file a request
        $messageType = "err";
    }
}

$residents = $conn->query("SELECT r.resident_id, r.last_name, r.first_name, r.middle_name, r.status, p.purok_name
    FROM Resident r
    JOIN Household h ON h.household_id = r.household_id
    JOIN Purok p ON p.purok_id = h.purok_id
    ORDER BY r.last_name, r.first_name")->fetch_all(MYSQLI_ASSOC);
$types = $conn->query("SELECT doctype_id, type_name, fee FROM DocumentType ORDER BY doctype_id")->fetch_all(MYSQLI_ASSOC);
$recent = $conn->query("SELECT request_id, resident_name, type_name, status, fee_charged
    FROM vw_request_balances ORDER BY request_id DESC LIMIT 6");

$title = "New Request";
$active = "request_new.php";
include "header.php";
?>
<?php if ($message): ?>
    <div class="note <?= $messageType ?> msg"><?= h($message) ?></div>
<?php endif; ?>

<div class="row">

    <div class="card" style="flex:1; min-width:340px; max-width:560px">
        <h3>Request details</h3>
        <form method="post" action="request_new.php">
            <label for="resident_id">Resident</label>
            <select id="resident_id" name="resident_id" required data-search>
                <option value="">Select resident</option>
                <?php foreach ($residents as $r): ?>
                    <option value="<?= $r['resident_id'] ?>"<?= sel($selectedResident, $r['resident_id']) ?>>
                        <?= h($r['last_name'] . ', ' . $r['first_name'] . ' ' . $r['middle_name'] . ' (' . $r['purok_name'] . ')') ?>
                        <?= $r['status'] === 'Active' ? '' : ' - ' . h($r['status']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="doctype_id">Document type</label>
            <select id="doctype_id" name="doctype_id" required>
                <?php foreach ($types as $t): ?>
                    <option value="<?= $t['doctype_id'] ?>" data-fee="<?= $t['fee'] ?>"><?= h($t['type_name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="fee">Fee (copied to fee_charged when saved)</label>
            <input class="f" id="fee" value="<?= number_format($types[0]['fee'], 2) ?>" readonly>

            <label for="purpose">Purpose</label>
            <input class="f" id="purpose" name="purpose" maxlength="150" required>

            <label for="request_date">Request date</label>
            <input class="f" id="request_date" name="request_date" type="date" value="<?= date('Y-m-d') ?>" required>

            <button class="btn" style="margin-top:16px">Save request</button>
        </form>
    </div>

    <div class="card" style="flex:1; min-width:340px">
        <h3>Latest requests</h3>
        <div class="tw">
            <table>
                <tr><th>Req</th><th>Resident</th><th>Document</th><th>Status</th></tr>
                <?php while ($r = $recent->fetch_assoc()): ?>
                    <tr>
                        <td><?= $r['request_id'] ?></td>
                        <td><?= h($r['resident_name']) ?></td>
                        <td><?= h($r['type_name']) ?></td>
                        <td><?= badge($r['status']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
        <p class="sub" style="margin:12px 0 8px">Need to record a payment?</p>
        <a class="btn s" href="requests.php">Go to Requests &amp; Payments</a>
    </div>

</div>

<script>
    // Show the fee of the selected document type
    var typeSelect = document.getElementById("doctype_id");
    typeSelect.addEventListener("change", function () {
        var fee = parseFloat(typeSelect.selectedOptions[0].dataset.fee);
        document.getElementById("fee").value = fee.toFixed(2);
    });
</script>
<?php include "footer.php"; ?>