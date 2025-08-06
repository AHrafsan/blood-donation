<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "healthbridge");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Add donor
if (isset($_POST['add_donor'])) {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $blood_group_id = $_POST['blood_group'];
    $last_donation_date = !empty($_POST['last_donation_date']) ? $_POST['last_donation_date'] : NULL;
    $image = "uploads/" . basename($_FILES["image"]["name"]);
    move_uploaded_file($_FILES["image"]["tmp_name"], $image);

    $stmt = $conn->prepare("INSERT INTO donors (name, image, phone, address, blood_group_id, last_donation_date, approved) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("ssssss", $name, $image, $phone, $address, $blood_group_id, $last_donation_date);
    $stmt->execute();
}

// Update last donation date manually
if (isset($_POST['update_donation_date'])) {
    $donor_id = $_POST['donor_id'];
    $last_donation_date = $_POST['last_donation_date'];
    $conn->query("UPDATE donors SET last_donation_date='$last_donation_date' WHERE id=$donor_id");
}

// Approve donor
if (isset($_GET['approve_donor'])) {
    $id = $_GET['approve_donor'];
    $conn->query("UPDATE donors SET approved = 1 WHERE id = $id");
}

// Approve request & update donor last donation date
if (isset($_GET['approve_request'])) {
    $id = $_GET['approve_request'];

    // Mark request as approved
    $conn->query("UPDATE requests SET status = 'approved' WHERE id = $id");

    // Get donor_id from request
    $result = $conn->query("SELECT donor_id FROM requests WHERE id = $id AND donor_id IS NOT NULL");
    if ($result && $row = $result->fetch_assoc()) {
        $donor_id = $row['donor_id'];

        // Update donor's last donation date to today
        $today = date("Y-m-d");
        $conn->query("UPDATE donors SET last_donation_date = '$today' WHERE id = $donor_id");
    }
}

// Reject request
if (isset($_GET['reject_request'])) {
    $id = $_GET['reject_request'];
    $conn->query("UPDATE requests SET status = 'rejected' WHERE id = $id");
}

// Fetch data
$blood_groups = $conn->query("SELECT * FROM blood_groups");
$pending_donors = $conn->query("SELECT donors.*, blood_groups.name AS blood_group FROM donors JOIN blood_groups ON donors.blood_group_id = blood_groups.id WHERE donors.approved = 0");
$donors_list = $conn->query("SELECT donors.*, blood_groups.name AS blood_group FROM donors JOIN blood_groups ON donors.blood_group_id = blood_groups.id WHERE donors.approved = 1");
$blood_requests = $conn->query("SELECT r.*, bg.name AS blood_group, d.name AS donor_name, d.phone AS donor_phone FROM requests r JOIN blood_groups bg ON r.blood_group_id = bg.id LEFT JOIN donors d ON r.donor_id = d.id ORDER BY r.id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body { font-family: Arial; background: #f5f6fa; padding: 20px; }
        h1 { text-align: center; color: #d63031; }
        .section { background: white; padding: 20px; margin-bottom: 30px; border-radius: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        th { background: #d63031; color: white; }
        input, select { padding: 8px; width: 100%; }
        button { background: #d63031; color: white; padding: 10px; border: none; cursor: pointer; }
        .approve { background: green; padding: 5px 10px; color: white; text-decoration: none; border-radius: 5px; }
        .reject { background: red; padding: 5px 10px; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>

<h1>Admin Dashboard</h1>

<!-- Add Donor -->
<div class="section">
    <h2>Add New Donor</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="text" name="phone" placeholder="Phone Number" required>
        <input type="text" name="address" placeholder="Address" required>
        <select name="blood_group" required>
            <option value="">Select Blood Group</option>
            <?php while($bg = $blood_groups->fetch_assoc()) { ?>
                <option value="<?= $bg['id'] ?>"><?= $bg['name'] ?></option>
            <?php } ?>
        </select>
        <input type="file" name="image" accept="image/*" required>
        <label>Last Donation Date:</label>
        <input type="date" name="last_donation_date">
        <button type="submit" name="add_donor">Add Donor</button>
    </form>
</div>

<!-- Pending Donors -->
<div class="section">
    <h2>Pending Donors</h2>
    <?php if ($pending_donors->num_rows > 0) { ?>
    <table>
        <tr><th>Name</th><th>Phone</th><th>Address</th><th>Blood Group</th><th>Action</th></tr>
        <?php while($pd = $pending_donors->fetch_assoc()) { ?>
        <tr>
            <td><?= $pd['name'] ?></td>
            <td><?= $pd['phone'] ?></td>
            <td><?= $pd['address'] ?></td>
            <td><?= $pd['blood_group'] ?></td>
            <td><a class="approve" href="?approve_donor=<?= $pd['id'] ?>">Approve</a></td>
        </tr>
        <?php } ?>
    </table>
    <?php } else { echo "<p>No pending donors.</p>"; } ?>
</div>

<!-- Manage Approved Donors -->
<div class="section">
    <h2>Manage Donors (Update Last Donation Date)</h2>
    <table>
        <tr><th>Name</th><th>Phone</th><th>Blood Group</th><th>Last Donation Date</th><th>Update</th></tr>
        <?php while($dl = $donors_list->fetch_assoc()) { ?>
        <tr>
            <td><?= $dl['name'] ?></td>
            <td><?= $dl['phone'] ?></td>
            <td><?= $dl['blood_group'] ?></td>
            <td><?= $dl['last_donation_date'] ?: 'Never' ?></td>
            <td>
                <form method="POST" style="display:flex; gap:5px;">
                    <input type="hidden" name="donor_id" value="<?= $dl['id'] ?>">
                    <input type="date" name="last_donation_date" required>
                    <button type="submit" name="update_donation_date">Save</button>
                </form>
            </td>
        </tr>
        <?php } ?>
    </table>
</div>

<!-- Blood Requests -->
<div class="section">
    <h2>Blood Requests</h2>
    <?php if ($blood_requests->num_rows > 0) { ?>
    <table>
        <tr><th>Requester Name</th><th>Phone</th><th>Address</th><th>Blood Group</th><th>Donor Name</th><th>Donor Phone</th><th>Status</th><th>Action</th></tr>
        <?php while($req = $blood_requests->fetch_assoc()) { ?>
        <tr>
            <td><?= $req['requester_name'] ?></td>
            <td><?= $req['phone'] ?></td>
            <td><?= $req['address'] ?></td>
            <td><?= $req['blood_group'] ?></td>
            <td><?= $req['donor_name'] ?: '-' ?></td>
            <td><?= $req['donor_phone'] ?: '-' ?></td>
            <td><?= ucfirst($req['status']) ?></td>
            <td>
                <?php if ($req['status'] == 'pending') { ?>
                    <a class="approve" href="?approve_request=<?= $req['id'] ?>">Approve</a>
                    <a class="reject" href="?reject_request=<?= $req['id'] ?>">Reject</a>
                <?php } else { echo "-"; } ?>
            </td>
        </tr>
        <?php } ?>
    </table>
    <?php } else { echo "<p>No blood requests.</p>"; } ?>
</div>

</body>
</html>
