<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "healthbridge");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Add Donor
if (isset($_POST['add_donor'])) {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $blood_group_id = $_POST['blood_group'];
    $image = "uploads/" . basename($_FILES["image"]["name"]);
    move_uploaded_file($_FILES["image"]["tmp_name"], $image);

    $conn->query("INSERT INTO donors (name, image, phone, address, blood_group_id, approved) 
                  VALUES ('$name', '$image', '$phone', '$address', '$blood_group_id', 1)");
}

// Approve Donor
if (isset($_GET['approve_donor'])) {
    $id = $_GET['approve_donor'];
    $conn->query("UPDATE donors SET approved = 1 WHERE id = $id");
}

// Approve/Reject Requests
if (isset($_GET['approve_request'])) {
    $id = $_GET['approve_request'];
    $conn->query("UPDATE requests SET status = 'approved' WHERE id = $id");
}
if (isset($_GET['reject_request'])) {
    $id = $_GET['reject_request'];
    $conn->query("UPDATE requests SET status = 'rejected' WHERE id = $id");
}

// Get data
$blood_groups = $conn->query("SELECT * FROM blood_groups");

$pending_donors = $conn->query("
    SELECT donors.id, donors.name, donors.phone, donors.address, blood_groups.name AS blood_group 
    FROM donors 
    JOIN blood_groups ON donors.blood_group_id = blood_groups.id 
    WHERE donors.approved = 0
");

$blood_requests = $conn->query("
    SELECT requests.id, requests.requester_name, requests.phone AS requester_phone, requests.address, 
           blood_groups.name AS blood_group, requests.status,
           donors.name AS donor_name, donors.phone AS donor_phone
    FROM requests
    JOIN blood_groups ON requests.blood_group_id = blood_groups.id
    LEFT JOIN donors ON requests.donor_id = donors.id
    ORDER BY requests.id DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body { font-family: Arial; background: #f5f6fa; padding: 20px; }
        h1 { text-align: center; color: #d63031; margin-bottom: 30px; }
        .section { background: white; padding: 20px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0px 4px 8px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background: #d63031; color: white; }
        input, select { width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ccc; border-radius: 5px; }
        button { background: #d63031; color: white; padding: 10px; border: none; border-radius: 5px; cursor: pointer; }
        a { padding: 5px 10px; color: white; border-radius: 5px; text-decoration: none; }
        .approve { background: green; }
        .reject { background: red; }
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

<!-- Blood Requests -->
<div class="section">
    <h2>Blood Requests</h2>
    <?php if ($blood_requests->num_rows > 0) { ?>
        <table>
            <tr>
                <th>Requester Name</th>
                <th>Requester Phone</th>
                <th>Address</th>
                <th>Blood Group</th>
                <th>Donor Name</th>
                <th>Donor Phone</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php while($req = $blood_requests->fetch_assoc()) { ?>
                <tr>
                    <td><?= $req['requester_name'] ?></td>
                    <td><?= $req['requester_phone'] ?></td>
                    <td><?= $req['address'] ?></td>
                    <td><?= $req['blood_group'] ?></td>
                    <td><?= $req['donor_name'] ? $req['donor_name'] : '-' ?></td>
                    <td><?= $req['donor_phone'] ? $req['donor_phone'] : '-' ?></td>
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
