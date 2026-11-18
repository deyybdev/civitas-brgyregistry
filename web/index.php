<?php
$title = "Dashboard";
$active = "index.php";
include "header.php";

// Sample data. Later these come from SQL queries.
$stats = [
    'Active residents' => 1293,
    'Pending requests' => 24,
    'Fees collected'   => 8450,
    'Unpaid balance'   => 1250,
];
$perDay = ['Mon' => 45, 'Tue' => 70, 'Wed' => 35, 'Thu' => 100, 'Fri' => 55, 'Sat' => 30, 'Sun' => 60];
$pending = [
    ['Reyes, Ana', 'Barangay Clearance', 'Pending'],
    ['Santos, Mark', 'Cert. of Residency', 'Processing'],
    ['Cruz, Liza', 'Cert. of Indigency', 'Pending'],
];
$max = max($perDay);
?>
<div class="row">

    <div class="col" style="flex:2; min-width:440px">
        <div class="card">
            <h3>Overview</h3>
            <div class="g">
                <?php foreach ($stats as $label => $number): ?>
                    <div class="stat"><span><?= $label ?></span><b><?= number_format($number) ?></b></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <h3>Fees collected per day</h3>
            <div class="bars">
                <?php foreach ($perDay as $value): ?>
                    <div class="<?= $value == $max ? 'hi' : '' ?>" style="height:<?= $value ?>%"></div>
                <?php endforeach; ?>
            </div>
            <div class="days">
                <?php foreach ($perDay as $day => $value): ?>
                    <span><?= $day ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col" style="flex:1; min-width:300px">
        <div class="card">
            <h3>Pending requests</h3>
            <?php foreach ($pending as [$name, $doc, $status]): ?>
                <div class="li">
                    <span class="av"><?= htmlspecialchars(substr($name, 0, 1)) ?></span>
                    <div><?= htmlspecialchars($name) ?><small><?= htmlspecialchars($doc) ?></small></div>
                    <?= badge($status) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>
<?php include "footer.php"; ?>
