<?php
include "db.php";

$q = trim($_GET['q'] ?? '');
$birth = trim($_GET['birth'] ?? '');
$searched = $q !== '';
$results = [];

// ----- Search the registry by name (any word order), optionally by birth date -----
if ($searched) {
    $words = preg_split('/[\s,]+/', $q, -1, PREG_SPLIT_NO_EMPTY);
    $pattern = '%' . implode('%', $words) . '%';

    $stmt = $conn->prepare("SELECT r.resident_id, r.last_name, r.first_name, r.middle_name, r.birth_date,
            r.status, r.date_registered, p.purok_name, h.household_name, h.house_number, h.street
        FROM Resident r
        JOIN Household h ON h.household_id = r.household_id
        JOIN Purok p ON p.purok_id = h.purok_id
        WHERE (CONCAT(r.last_name, ' ', r.first_name, ' ', r.middle_name) LIKE ?
               OR CONCAT(r.first_name, ' ', r.middle_name, ' ', r.last_name) LIKE ?)
          AND (? = '' OR r.birth_date = NULLIF(?, ''))
        ORDER BY (r.status = 'Active') DESC, r.last_name, r.first_name
        LIMIT 50");
    $stmt->bind_param("ssss", $pattern, $pattern, $birth, $birth);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$title = "Verify Resident";
$active = "verify.php";
include "header.php";
?>
<style>
    .mb { margin-bottom: 14px; }
    .note.big { margin: 0 0 14px; padding: 14px 16px; font-size: 15px; }
</style>
<div class="card mb" style="max-width:720px">
    <h3>Check if a person is a registered resident</h3>
    <p class="sub">Only residents with Active status are verified. Inactive, Transferred, and Deceased residents are not.</p>
    <form method="get" action="verify.php">
        <label for="q">Name</label>
        <input class="f" id="q" name="q" value="<?= h($q) ?>" placeholder="For example: Mark Asaytuno" required>

        <label for="birth">Birth date (optional, to tell people with the same name apart)</label>
        <input class="f" id="birth" name="birth" type="date" value="<?= h($birth) ?>">

        <button class="btn" style="margin-top:16px">Verify</button>
    </form>
</div>

<?php if ($searched): ?>
    <?php if (count($results) === 0): ?>
        <div class="note err big">No registered resident found for "<?= h($q) ?>". This person is not in the registry.</div>
    <?php else: ?>
        <?php if (count($results) === 1): ?>
            <?php $one = $results[0]; ?>
            <?php if ($one['status'] === 'Active'): ?>
                <div class="note ok big">Verified: <?= h($one['last_name'] . ', ' . $one['first_name']) ?> is an Active resident of <?= h($one['purok_name']) ?>.</div>
            <?php else: ?>
                <div class="note err big">Not verified: <?= h($one['last_name'] . ', ' . $one['first_name']) ?> is registered but the status is <?= h($one['status']) ?>.</div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="card">
            <p class="count"><?= count($results) ?> matching record<?= count($results) === 1 ? '' : 's' ?></p>
            <div class="tw">
                <table>
                    <tr>
                        <th>ID</th><th>Name</th><th>Birth date</th><th>Household</th><th>Registered on</th><th>Status</th><th>Result</th>
                    </tr>
                    <?php foreach ($results as $r): ?>
                        <tr>
                            <td><?= $r['resident_id'] ?></td>
                            <td><a href="resident.php?id=<?= $r['resident_id'] ?>" style="color:inherit; font-weight:500"><?= h($r['last_name'] . ', ' . $r['first_name'] . ' ' . $r['middle_name']) ?></a></td>
                            <td><?= h($r['birth_date']) ?></td>
                            <td><?= h($r['household_name'] . ' (' . $r['purok_name'] . ', ' . $r['house_number'] . ' ' . $r['street'] . ')') ?></td>
                            <td><?= h($r['date_registered']) ?></td>
                            <td><?= badge($r['status']) ?></td>
                            <td><?= badge($r['status'] === 'Active' ? 'Verified' : 'Not verified') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
<?php include "footer.php"; ?>