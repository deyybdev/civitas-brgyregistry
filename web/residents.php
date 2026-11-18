<?php
$title = "Residents";
$active = "residents.php";
include "header.php";

// Sample data. Later: SELECT from Residents joined with Households and Puroks.
$residents = [
    [1, 'Reyes, Ana M.', '1990-03-14', 'Purok 1 / 12 Rizal St.', 'Active'],
    [2, 'Santos, Mark D.', '1985-11-02', 'Purok 1 / 12 Rizal St.', 'Active'],
    [3, 'Cruz, Liza P.', '1972-07-21', 'Purok 2 / 5 Mabini St.', 'Transferred'],
    [4, 'Garcia, Pedro L.', '1948-01-30', 'Purok 2 / 7 Mabini St.', 'Deceased'],
];
?>
<div class="row">

    <div class="card" style="flex:2; min-width:440px">
        <div class="tw">
            <table>
                <tr>
                    <th>ID</th><th>Name</th><th>Birth date</th><th>Purok / Household</th><th>Status</th><th></th>
                </tr>
                <?php foreach ($residents as [$id, $name, $birth, $address, $status]): ?>
                    <tr>
                        <td><?= $id ?></td>
                        <td><?= htmlspecialchars($name) ?></td>
                        <td><?= $birth ?></td>
                        <td><?= htmlspecialchars($address) ?></td>
                        <td><?= badge($status) ?></td>
                        <td><a class="btn s" href="#">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <div class="card" style="flex:1; min-width:290px">
        <h3>Add resident</h3>
        <form method="post">
            <label for="last_name">Last name</label>
            <input class="f" id="last_name" name="last_name" required>

            <label for="first_name">First name</label>
            <input class="f" id="first_name" name="first_name" required>

            <label for="birth_date">Birth date</label>
            <input class="f" id="birth_date" name="birth_date" type="date" required>

            <label for="purok_id">Purok</label>
            <select id="purok_id" name="purok_id"><option>Select purok</option></select>

            <label for="household_id">Household</label>
            <select id="household_id" name="household_id"><option>Select household</option></select>

            <button class="btn" style="margin-top:16px; width:100%">Save resident</button>
        </form>
    </div>

</div>
<?php include "footer.php"; ?>
