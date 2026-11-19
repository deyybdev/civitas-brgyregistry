<?php
include "db.php";

$message = "";
$messageType = "ok";
$form = [
    'resident_id' => 0, 'last_name' => '', 'first_name' => '', 'middle_name' => '',
    'birth_date' => '', 'contact_number' => '', 'household_id' => '', 'status' => 'Active',
];

// ----- Save or delete (form submitted) -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save') {
            $id = (int) ($_POST['resident_id'] ?? 0);
            $last = properCase($_POST['last_name']);
            $first = properCase($_POST['first_name']);
            $middle = properCase($_POST['middle_name']);
            $birth = $_POST['birth_date'];
            $contact = trim($_POST['contact_number']) === '' ? null : trim($_POST['contact_number']);
            $household = (int) $_POST['household_id'];
            $status = $_POST['status'];

            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE Resident SET household_id = ?, last_name = ?, first_name = ?,
                    middle_name = ?, birth_date = ?, contact_number = ?, status = ? WHERE resident_id = ?");
                $stmt->bind_param("issssssi", $household, $last, $first, $middle, $birth, $contact, $status, $id);
                $stmt->execute();
                $message = "Resident updated.";
            } else {
                $stmt = $conn->prepare("INSERT INTO Resident (household_id, last_name, first_name, middle_name,
                    birth_date, contact_number, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssss", $household, $last, $first, $middle, $birth, $contact, $status);
                $stmt->execute();
                $message = "Resident saved.";
            }
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM Resident WHERE resident_id = ?");
            $id = (int) $_POST['resident_id'];
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $message = "Resident deleted.";
        }
    } catch (mysqli_sql_exception $e) {
        $message = friendlyError($e);
        $messageType = "err";
        if ($action === 'save') {   // keep what the user typed
            $form = [
                'resident_id' => $id, 'last_name' => $last, 'first_name' => $first, 'middle_name' => $middle,
                'birth_date' => $birth, 'contact_number' => $contact ?? '', 'household_id' => $household, 'status' => $status,
            ];
        }
    }
}

// ----- Load a resident into the form (Edit link) -----
if ((int) $form['resident_id'] === 0 && isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $conn->prepare("SELECT resident_id, last_name, first_name, middle_name, birth_date,
        COALESCE(contact_number, '') AS contact_number, household_id, status FROM Resident WHERE resident_id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    if ($found) {
        $form = $found;
    }
}

// ----- Resident list with filters -----
$q = trim($_GET['q'] ?? '');
$purokFilter = (int) ($_GET['purok'] ?? 0);
$statusFilter = $_GET['status'] ?? '';
$like = "%" . $q . "%";

$stmt = $conn->prepare("SELECT r.resident_id, r.last_name, r.first_name, r.middle_name, r.birth_date,
        r.contact_number, r.status, p.purok_name, h.house_number, h.street
    FROM Resident r
    JOIN Household h ON h.household_id = r.household_id
    JOIN Purok p ON p.purok_id = h.purok_id
    WHERE CONCAT(r.last_name, ' ', r.first_name) LIKE ?
      AND (? = 0 OR p.purok_id = ?)
      AND (? = '' OR r.status = ?)
    ORDER BY r.last_name, r.first_name");
$stmt->bind_param("siiss", $like, $purokFilter, $purokFilter, $statusFilter, $statusFilter);
$stmt->execute();
$residents = $stmt->get_result();

$puroks = $conn->query("SELECT purok_id, purok_name FROM Purok ORDER BY purok_id")->fetch_all(MYSQLI_ASSOC);
$households = $conn->query("SELECT h.household_id, p.purok_name, h.house_number, h.street
    FROM Household h JOIN Purok p ON p.purok_id = h.purok_id
    ORDER BY p.purok_id, h.street, h.house_number")->fetch_all(MYSQLI_ASSOC);

$title = "Residents";
$active = "residents.php";
include "header.php";
?>
<?php if ($message): ?>
    <div class="note <?= $messageType ?> msg"><?= h($message) ?></div>
<?php endif; ?>

<div class="row">

    <div class="card" style="flex:2; min-width:440px">
        <form class="filters" method="get" action="residents.php">
            <input class="f" name="q" value="<?= h($q) ?>" placeholder="Search by name" aria-label="Search by name">
            <select name="purok" aria-label="Purok">
                <option value="0">All puroks</option>
                <?php foreach ($puroks as $p): ?>
                    <option value="<?= $p['purok_id'] ?>"<?= sel($purokFilter, $p['purok_id']) ?>><?= h($p['purok_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" aria-label="Status">
                <option value="">All statuses</option>
                <?php foreach (['Active', 'Inactive', 'Transferred', 'Deceased'] as $s): ?>
                    <option<?= sel($statusFilter, $s) ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn">Filter</button>
        </form>
        <p class="count"><?= $residents->num_rows ?> residents</p>

        <div class="tw">
            <table>
                <tr>
                    <th>ID</th><th>Name</th><th>Birth date</th><th>Contact</th><th>Purok / Household</th><th>Status</th><th></th>
                </tr>
                <?php while ($r = $residents->fetch_assoc()): ?>
                    <tr>
                        <td><?= $r['resident_id'] ?></td>
                        <td><?= h($r['last_name'] . ', ' . $r['first_name'] . ' ' . $r['middle_name']) ?></td>
                        <td><?= h($r['birth_date']) ?></td>
                        <td><?= h($r['contact_number'] ?? '-') ?></td>
                        <td><?= h($r['purok_name'] . ' / ' . $r['house_number'] . ' ' . $r['street']) ?></td>
                        <td><?= badge($r['status']) ?></td>
                        <td>
                            <div class="actions">
                                <a class="btn s" href="residents.php?edit=<?= $r['resident_id'] ?>">Edit</a>
                                <form method="post" action="residents.php" onsubmit="return confirm('Delete this resident?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="resident_id" value="<?= $r['resident_id'] ?>">
                                    <button class="btn s danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

    <div class="card" style="flex:1; min-width:290px">
        <h3><?= (int) $form['resident_id'] > 0 ? 'Edit resident #' . (int) $form['resident_id'] : 'Add resident' ?></h3>
        <form method="post" action="residents.php">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="resident_id" value="<?= (int) $form['resident_id'] ?>">

            <label for="last_name">Last name</label>
            <input class="f" id="last_name" name="last_name" value="<?= h($form['last_name']) ?>" required>

            <label for="first_name">First name</label>
            <input class="f" id="first_name" name="first_name" value="<?= h($form['first_name']) ?>" required>

            <label for="middle_name">Middle name</label>
            <input class="f" id="middle_name" name="middle_name" value="<?= h($form['middle_name']) ?>">

            <label for="birth_date">Birth date</label>
            <input class="f" id="birth_date" name="birth_date" type="date" value="<?= h($form['birth_date']) ?>" required>

            <label for="contact_number">Contact number</label>
            <input class="f" id="contact_number" name="contact_number" value="<?= h($form['contact_number']) ?>" placeholder="09XXXXXXXXX">

            <label for="household_id">Household</label>
            <select id="household_id" name="household_id" required>
                <option value="">Select household</option>
                <?php foreach ($households as $hh): ?>
                    <option value="<?= $hh['household_id'] ?>"<?= sel($form['household_id'], $hh['household_id']) ?>>
                        <?= h($hh['purok_name'] . ' - ' . $hh['house_number'] . ' ' . $hh['street']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="status">Status</label>
            <select id="status" name="status">
                <?php foreach (['Active', 'Inactive', 'Transferred', 'Deceased'] as $s): ?>
                    <option<?= sel($form['status'], $s) ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>

            <div style="display:flex; gap:10px; margin-top:16px">
                <button class="btn">Save resident</button>
                <?php if ((int) $form['resident_id'] > 0): ?>
                    <a class="btn s" style="height:38px" href="residents.php">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

</div>
<?php include "footer.php"; ?>
