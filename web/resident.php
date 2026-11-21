<?php
include "db.php";

// ----- Load the resident from ?id= -----
$id = (int) ($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT r.resident_id, r.last_name, r.first_name, r.middle_name, r.birth_date,
        r.contact_number, r.status, r.date_registered, h.household_id, h.household_name,
        h.house_number, h.street, p.purok_name
    FROM Resident r
    JOIN Household h ON h.household_id = r.household_id
    JOIN Purok p ON p.purok_id = h.purok_id
    WHERE r.resident_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

$requests = [];
$totals = ['count' => 0, 'fees' => 0, 'paid' => 0, 'balance' => 0];
$age = null;

if ($res) {
    // Age from birth date
    $age = (new DateTime($res['birth_date']))->diff(new DateTime('today'))->y;

    // Document requests with payment balances
    $stmt = $conn->prepare("SELECT request_id, type_name, purpose, status, request_date, release_date,
            fee_charged, total_paid, balance
        FROM vw_request_balances WHERE resident_id = ?
        ORDER BY request_date DESC, request_id DESC");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($requests as $rq) {
        $totals['count']++;
        $totals['fees'] += $rq['fee_charged'];
        $totals['paid'] += $rq['total_paid'];
        $totals['balance'] += $rq['balance'];
    }
}

// Payment state shown as a badge.
function payState($row)
{
    if ($row['fee_charged'] == 0) return 'No fee';
    if ($row['balance'] <= 0) return 'Paid';
    if ($row['total_paid'] > 0) return 'Partial';
    return 'Unpaid';
}

$title = $res ? "Resident Profile" : "Resident not found";
$active = "residents.php";
include "header.php";
?>
<?php if (!$res): ?>
    <div class="card">
        <h3>Resident not found</h3>
        <p class="sub">There is no resident with ID <?= (int) $id ?>.</p>
        <a class="btn" href="residents.php">Back to residents</a>
    </div>
<?php else: ?>

<div class="row">

    <div class="col" style="flex:1; min-width:290px">
        <div class="card">
            <div class="li" style="margin-bottom:12px">
                <span class="av"><?= h(mb_substr($res['first_name'], 0, 1) . mb_substr($res['last_name'], 0, 1)) ?></span>
                <div>
                    <b><?= h($res['last_name'] . ', ' . $res['first_name'] . ' ' . $res['middle_name']) ?></b>
                    <small>Resident #<?= (int) $res['resident_id'] ?></small>
                </div>
                <?= badge($res['status']) ?>
            </div>

            <table>
                <tr><th>Birth date</th><td><?= h($res['birth_date']) ?> (<?= (int) $age ?> yrs old)</td></tr>
                <tr><th>Contact</th><td><?= h($res['contact_number'] ?? '-') ?></td></tr>
                <tr><th>Household</th><td><a href="households.php?view=<?= (int) $res['household_id'] ?>" style="color:inherit; font-weight:500"><?= h($res['household_name']) ?></a></td></tr>
                <tr><th>Address</th><td><?= h($res['purok_name'] . ', ' . $res['house_number'] . ' ' . $res['street']) ?></td></tr>
                <tr><th>Registered</th><td><?= h($res['date_registered']) ?></td></tr>
            </table>

            <div style="display:flex; gap:10px; margin-top:16px">
                <a class="btn" href="request_new.php">New request</a>
                <a class="btn s" style="height:38px" href="residents.php?edit=<?= (int) $res['resident_id'] ?>">Edit</a>
                <a class="btn s" style="height:38px" href="residents.php">Back</a>
            </div>
        </div>
    </div>

    <div class="col" style="flex:2; min-width:440px">
        <div class="card">
            <h3>Summary</h3>
            <div class="g">
                <div class="stat"><span>Requests</span><b><?= $totals['count'] ?></b></div>
                <div class="stat"><span>Fees charged</span><b><?= number_format($totals['fees'], 2) ?></b></div>
                <div class="stat"><span>Paid</span><b><?= number_format($totals['paid'], 2) ?></b></div>
                <div class="stat"><span>Balance</span><b><?= number_format($totals['balance'], 2) ?></b></div>
            </div>
        </div>

        <div class="card">
            <h3>Document requests</h3>
            <?php if (count($requests) === 0): ?>
                <p class="sub">This resident has no document requests yet.</p>
            <?php else: ?>
                <div class="tw">
                    <table>
                        <tr>
                            <th>ID</th><th>Document</th><th>Purpose</th><th>Requested</th><th>Status</th>
                            <th>Fee</th><th>Paid</th><th>Balance</th><th>Payment</th>
                        </tr>
                        <?php foreach ($requests as $rq): ?>
                            <tr>
                                <td><?= (int) $rq['request_id'] ?></td>
                                <td><?= h($rq['type_name']) ?></td>
                                <td><?= h($rq['purpose']) ?></td>
                                <td><?= h($rq['request_date']) ?></td>
                                <td><?= badge($rq['status']) ?></td>
                                <td><?= number_format($rq['fee_charged'], 2) ?></td>
                                <td><?= number_format($rq['total_paid'], 2) ?></td>
                                <td><?= number_format($rq['balance'], 2) ?></td>
                                <td><?= badge(payState($rq)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
<?php endif; ?>
<?php include "footer.php"; ?>