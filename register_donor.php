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

// Fetch Data
$blood_groups = $conn->query("SELECT * FROM blood_groups");
$pending_donors = $conn->query("SELECT donors.id, donors.name, donors.phone, donors.address, blood_groups.name AS blood_group 
                                FROM donors JOIN blood_groups ON donors.blood_group_id = blood_groups.id 
                                WHERE donors.approved = 0");
$blood_requests = $conn->query("SELECT requests.id, requester_name, phone, address, blood_groups.name AS blood_group, status 
                                FROM requests JOIN blood_groups ON requests.blood_group_id = blood_groups.id
                                ORDER BY requests.id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register Blood Donor</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f6fa; padding: 20px; }
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

<h1>Register Blood Donor</h1>

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

</body>
</html>
