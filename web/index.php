<?php
include "db.php";

// ----- Overview numbers -----
$activeResidents = $conn->query("SELECT COUNT(*) FROM Resident WHERE status = 'Active'")->fetch_row()[0];
$openRequests = $conn->query("SELECT COUNT(*) FROM DocumentRequest WHERE status <> 'Released'")->fetch_row()[0];
$feesCollected = $conn->query("SELECT COALESCE(SUM(amount_paid), 0) FROM Payment")->fetch_row()[0];
$unpaidBalance = $conn->query("SELECT COALESCE(SUM(balance), 0) FROM vw_request_balances")->fetch_row()[0];

$stats = [
    'Active residents' => number_format($activeResidents),
    'Open requests'    => number_format($openRequests),
    'Fees collected'   => number_format($feesCollected, 2),
    'Unpaid balance'   => number_format($unpaidBalance, 2),
];

// ----- Fees per day for the 7 days ending at the latest payment -----
$last = $conn->query("SELECT MAX(payment_date) FROM Payment")->fetch_row()[0] ?? date('Y-m-d');
$perDay = [];
for ($i = 6; $i >= 0; $i--) {
    $perDay[date('Y-m-d', strtotime($last . " -" . $i . " day"))] = 0;
}
$from = date('Y-m-d', strtotime($last . " -6 day"));
$stmt = $conn->prepare("SELECT payment_date, SUM(amount_paid) AS total FROM Payment
    WHERE payment_date BETWEEN ? AND ? GROUP BY payment_date");
$stmt->bind_param("ss", $from, $last);
$stmt->execute();
$rows = $stmt->get_result();
while ($row = $rows->fetch_assoc()) {
    $perDay[$row['payment_date']] = (float) $row['total'];
}
$max = max($perDay);

// ----- Lists -----
$pending = $conn->query("SELECT resident_name, type_name, status FROM vw_request_balances
    WHERE status <> 'Released' ORDER BY request_date DESC, request_id DESC LIMIT 5");
$newResidents = $conn->query("SELECT last_name, first_name, date_registered FROM Resident
    ORDER BY date_registered DESC, resident_id DESC LIMIT 5");

$title = "Dashboard";
$active = "index.php";
include "header.php";
?>
<div class="row">

    <div class="col" style="flex:2; min-width:440px">
        <div class="card">
            <h3>Overview</h3>
            <div class="g">
                <?php foreach ($stats as $label => $value): ?>
                    <div class="stat"><span><?= $label ?></span><b><?= $value ?></b></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <h3>Fees collected per day</h3>
            <div class="bars">
                <?php foreach ($perDay as $day => $total): ?>
                    <?php $pct = $max > 0 ? max(4, round($total / $max * 100)) : 4; ?>
                    <div class="<?= $total == $max && $max > 0 ? 'hi' : '' ?>" style="height:<?= $pct ?>%"
                         title="<?= $day ?>: <?= number_format($total, 2) ?>"></div>
                <?php endforeach; ?>
            </div>
            <div class="days">
                <?php foreach ($perDay as $day => $total): ?>
                    <span><?= date('D', strtotime($day)) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col" style="flex:1; min-width:300px">
        <div class="card">
            <h3>Open requests</h3>
            <?php while ($r = $pending->fetch_assoc()): ?>
                <div class="li">
                    <span class="av"><?= h(substr($r['resident_name'], 0, 1)) ?></span>
                    <div><?= h($r['resident_name']) ?><small><?= h($r['type_name']) ?></small></div>
                    <?= badge($r['status']) ?>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="card">
            <h3>Recently registered</h3>
            <?php while ($r = $newResidents->fetch_assoc()): ?>
                <div class="li">
                    <span class="av"><?= h(substr($r['first_name'], 0, 1) . substr($r['last_name'], 0, 1)) ?></span>
                    <div><?= h($r['last_name'] . ', ' . $r['first_name']) ?><small><?= h($r['date_registered']) ?></small></div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

</div>
<?php include "footer.php"; ?>
