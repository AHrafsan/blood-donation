<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "healthbridge");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Handle request submission
if (isset($_POST['send_request'])) {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $blood_group_id = $_POST['blood_group_id'];

    $stmt = $conn->prepare("INSERT INTO requests (requester_name, phone, address, blood_group_id, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->bind_param("sssi", $name, $phone, $address, $blood_group_id);
    $stmt->execute();
    $success = "Your request has been sent. Please wait for admin approval.";
}

// Get all blood groups for filter
$blood_groups_list = $conn->query("SELECT * FROM blood_groups");

// Filtering logic
$where = "WHERE donors.approved = 1";
if (isset($_GET['filter'])) {
    $selected_bg = $_GET['blood_group'];
    $location = $_GET['location'];

    if (!empty($selected_bg)) {
        $where .= " AND donors.blood_group_id = " . intval($selected_bg);
    }
    if (!empty($location)) {
        $where .= " AND donors.address LIKE '%" . $conn->real_escape_string($location) . "%'";
    }
}

// Fetch filtered donors
$donors = $conn->query("
    SELECT donors.id, donors.name, donors.image, donors.phone, donors.address, donors.blood_group_id, blood_groups.name AS blood_group
    FROM donors
    JOIN blood_groups ON donors.blood_group_id = blood_groups.id
    $where
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Blood Donors</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f6fa; margin: 0; padding: 20px; }
        h1 { text-align: center; color: #d63031; }
        .success { text-align: center; color: green; }
        .filter-box { display: flex; justify-content: center; margin: 20px 0; gap: 10px; }
        select, input[type=text] { padding: 8px; border: 1px solid #ccc; border-radius: 5px; }
        button.filter-btn { background: #0984e3; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; }
        .donor-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-top: 30px; }
        .donor-card { background: white; border-radius: 10px; box-shadow: 0px 4px 8px rgba(0,0,0,0.1); padding: 15px; text-align: center; }
        .donor-card img { width: 100%; height: 200px; object-fit: cover; border-bottom: 3px solid #d63031; }
        .donor-card h3 { margin: 10px 0; }
        .blood-group { background: #d63031; color: white; padding: 5px 10px; border-radius: 20px; }
        .btn { background: #0984e3; color: white; padding: 8px 15px; border-radius: 5px; cursor: pointer; display: inline-block; margin-top: 10px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 20px; border-radius: 10px; width: 350px; }
        input { width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ccc; border-radius: 5px; }
        button { background: #d63031; color: white; padding: 10px; border: none; border-radius: 5px; cursor: pointer; width: 100%; }
    </style>
    <script>
        function openRequestModal(bloodGroupId, donorName) {
            document.getElementById("blood_group_id").value = bloodGroupId;
            document.getElementById("donor_name_display").innerText = donorName;
            document.getElementById("requestModal").style.display = "flex";
        }
        function closeModal() {
            document.getElementById("requestModal").style.display = "none";
        }
    </script>
</head>
<body>

<h1>Available Blood Donors</h1>
<?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>

<!-- Filter Form -->
<form method="GET" class="filter-box">
    <select name="blood_group">
        <option value="">All Blood Groups</option>
        <?php while($bg = $blood_groups_list->fetch_assoc()) { ?>
            <option value="<?= $bg['id'] ?>" <?= (isset($selected_bg) && $selected_bg == $bg['id']) ? 'selected' : '' ?>>
                <?= $bg['name'] ?>
            </option>
        <?php } ?>
    </select>
    <input type="text" name="location" placeholder="Enter location" value="<?= isset($location) ? htmlspecialchars($location) : '' ?>">
    <button type="submit" name="filter" class="filter-btn">Filter</button>
</form>

<!-- Donor Cards -->
<div class="donor-container">
    <?php if ($donors->num_rows > 0) {
        while($d = $donors->fetch_assoc()) { ?>
            <div class="donor-card">
                <img src="<?= $d['image'] ?>" alt="Donor">
                <h3><?= $d['name'] ?></h3>
                <p><strong>Phone:</strong> <?= $d['phone'] ?></p>
                <p><strong>Address:</strong> <?= $d['address'] ?></p>
                <span class="blood-group"><?= $d['blood_group'] ?></span><br>
                <span class="btn" onclick="openRequestModal('<?= $d['blood_group_id'] ?>', '<?= $d['name'] ?>')">Request Blood</span>
            </div>
    <?php } } else { echo "<p style='text-align:center;'>No donors found.</p>"; } ?>
</div>

<!-- Request Modal -->
<div class="modal" id="requestModal">
    <div class="modal-content">
        <h3>Request Blood from <span id="donor_name_display"></span></h3>
        <form method="POST">
            <input type="hidden" name="blood_group_id" id="blood_group_id">
            <input type="text" name="name" placeholder="Your Name" required>
            <input type="text" name="phone" placeholder="Your Phone" required>
            <input type="text" name="address" placeholder="Your Address" required>
            <button type="submit" name="send_request">Send Request</button>
        </form>
        <br>
        <button style="background:#636e72" onclick="closeModal()">Cancel</button>
    </div>
</div>

</body>
</html>
