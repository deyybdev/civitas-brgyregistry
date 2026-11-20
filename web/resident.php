<?php
include "db.php";

$id = (int) ($_GET['id'] ?? 0);

// ----- Resident, household, and barangay -----
$stmt = $conn->prepare("SELECT r.resident_id, r.last_name, r.first_name, r.middle_name, r.birth_date,
        TIMESTAMPDIFF(YEAR, r.birth_date, CURDATE()) AS age, r.contact_number, r.status, r.date_registered,
        h.household_id, h.household_name, h.house_number, h.street, p.purok_name,
        b.barangay_name, b.municipality, b.province
    FROM Resident r
    JOIN Household h ON h.household_id = r.household_id
    JOIN Purok p ON p.purok_id = h.purok_id
    JOIN Barangay b ON b.barangay_id = p.barangay_id
    WHERE r.resident_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

$members = [];
$requests = [];
$totalPaid = 0;
$totalBalance = 0;

if ($res) {
    // Other members of the same household
    $stmt = $conn->prepare("SELECT resident_id, last_name, first_name, middle_name, status
        FROM Resident WHERE household_id = ? AND resident_id <> ? ORDER BY birth_date, resident_id");
    $stmt->bind_param("ii", $res['household_id'], $id);
    $stmt->execute();
    $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Request history (reads the view)
    $stmt = $conn->prepare("SELECT request_id, type_name, purpose, status, request_date, fee_charged, total_paid, balance
        FROM vw_request_balances WHERE resident_id = ? ORDER BY request_date DESC, request_id DESC");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($requests as $rq) {
        $totalPaid += $rq['total_paid'];
        $totalBalance += $rq['balance'];
    }
}

$title = "Resident Profile";
$active = "residents.php";
include "header.php";
?>
<?php if (!$res): ?>
    <div class="note err msg">Resident not found. <a href="residents.php">Back to residents</a></div>
<?php else: ?>
    <?php $fullName = $res['first_name'] . ' ' . ($res['middle_name'] !== '' ? $res['middle_name'] . ' ' : '') . $res['last_name']; ?>

    <div class="card" style="margin-bottom:14px; display:flex; align-items:center; gap:14px; flex-wrap:wrap">
        <span class="av" style="width:56px; height:56px; font-size:18px"><?= h(substr($res['first_name'], 0, 1) . substr($res['last_name'], 0, 1)) ?></span>
        <div style="flex:1; min-width:200px">
            <div style="font-size:20px; font-weight:500"><?= h($fullName) ?></div>
            <div class="sub" style="margin:4px 0 0">Resident #<?= $res['resident_id'] ?> &middot; <?= h($res['household_name']) ?></div>
        </div>
        <?= badge($res['status']) ?>
        <?= badge($res['status'] === 'Active' ? 'Verified' : 'Not verified') ?>
        <a class="btn s" href="residents.php?edit=<?= $res['resident_id'] ?>">Edit</a>
        <?php if ($res['status'] === 'Active'): ?>
            <a class="btn" href="request_new.php?resident=<?= $res['resident_id'] ?>">New request</a>
        <?php endif; ?>
    </div>

    <div class="row">

        <div class="col" style="flex:2; min-width:440px">
            <div class="card">
                <h3>Personal information</h3>
                <div class="li"><div style="color:var(--muted)">Full name</div><span><?= h($fullName) ?></span></div>
                <div class="li"><div style="color:var(--muted)">Birth date</div><span><?= h($res['birth_date']) ?><?= $res['status'] === 'Deceased' ? '' : ' (' . (int) $res['age'] . ' years old)' ?></span></div>
                <div class="li"><div style="color:var(--muted)">Contact number</div><span><?= h($res['contact_number'] ?? '-') ?></span></div>
                <div class="li"><div style="color:var(--muted)">Status</div><span><?= h($res['status']) ?></span></div>
                <div class="li"><div style="color:var(--muted)">Registered on</div><span><?= h($res['date_registered']) ?></span></div>
            </div>

            <div class="card">
                <h3>Request history</h3>
                <p class="sub"><?= count($requests) ?> requests &middot; Paid <?= number_format($totalPaid, 2) ?> &middot; Balance <?= number_format($totalBalance, 2) ?></p>
                <?php if (count($requests) === 0): ?>
                    <p class="sub">No document requests yet.</p>
                <?php else: ?>
                    <div class="tw">
                        <table>
                            <tr><th>Req</th><th>Document</th><th>Purpose</th><th>Date</th><th>Status</th><th>Fee</th><th>Paid</th><th>Balance</th></tr>
                            <?php foreach ($requests as $rq): ?>
                                <tr>
                                    <td><?= $rq['request_id'] ?></td>
                                    <td><?= h($rq['type_name']) ?></td>
                                    <td><?= h($rq['purpose']) ?></td>
                                    <td><?= h($rq['request_date']) ?></td>
                                    <td><?= badge($rq['status']) ?></td>
                                    <td><?= number_format($rq['fee_charged'], 2) ?></td>
                                    <td><?= number_format($rq['total_paid'], 2) ?></td>
                                    <td><b><?= number_format($rq['balance'], 2) ?></b></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col" style="flex:1; min-width:290px">
            <div class="card">
                <h3>Household</h3>
                <div class="li"><div style="color:var(--muted)">Name</div><a href="households.php?view=<?= $res['household_id'] ?>" style="color:inherit; font-weight:500"><?= h($res['household_name']) ?></a></div>
                <div class="li"><div style="color:var(--muted)">Purok</div><span><?= h($res['purok_name']) ?></span></div>
                <div class="li"><div style="color:var(--muted)">Address</div><span><?= h($res['house_number'] . ' ' . $res['street']) ?></span></div>
                <div class="li"><div style="color:var(--muted)">Barangay</div><span><?= h($res['barangay_name'] . ', ' . $res['municipality'] . ', ' . $res['province']) ?></span></div>
            </div>

            <div class="card">
                <h3>Other household members</h3>
                <?php if (count($members) === 0): ?>
                    <p class="sub">No other members.</p>
                <?php endif; ?>
                <?php foreach ($members as $m): ?>
                    <div class="li">
                        <span class="av"><?= h(substr($m['first_name'], 0, 1) . substr($m['last_name'], 0, 1)) ?></span>
                        <div><a href="resident.php?id=<?= $m['resident_id'] ?>" style="color:inherit; font-weight:500"><?= h($m['last_name'] . ', ' . $m['first_name'] . ' ' . $m['middle_name']) ?></a></div>
                        <?= badge($m['status']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
<?php endif; ?>
<?php include "footer.php"; ?>
