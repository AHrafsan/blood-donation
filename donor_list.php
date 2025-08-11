<?php
$conn = new mysqli("localhost", "root", "", "healthbridge");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Handle request submission
if (isset($_POST['send_request'])) {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $blood_group_id = $_POST['blood_group_id'];
    $donor_id = $_POST['donor_id'];

    $stmt = $conn->prepare("INSERT INTO requests (requester_name, phone, address, blood_group_id, donor_id, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("sssii", $name, $phone, $address, $blood_group_id, $donor_id);
    $stmt->execute();
    $success = "✅ Your request has been sent. Please wait for admin approval.";
}

// Get all blood groups
$blood_groups_list = $conn->query("SELECT * FROM blood_groups");

// Filtering logic
$where = "WHERE donors.approved = 1";
$selected_bg = $_GET['blood_group'] ?? '';
$location = $_GET['location'] ?? '';
$eligibility = $_GET['eligibility'] ?? '';

if (!empty($selected_bg)) {
    $where .= " AND donors.blood_group_id = " . intval($selected_bg);
}
if (!empty($location)) {
    $where .= " AND donors.address LIKE '%" . $conn->real_escape_string($location) . "%'";
}

$donors = $conn->query("
    SELECT donors.id, donors.name, donors.image, donors.phone, donors.address, donors.blood_group_id, donors.last_donation_date, blood_groups.name AS blood_group
    FROM donors
    JOIN blood_groups ON donors.blood_group_id = blood_groups.id
    $where
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Blood Donors - HealthBridge</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 0;
        }
        /* Navbar */
        .navbar {
            background: linear-gradient(90deg, #d63031, #ff7675);
            padding: 14px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .navbar .brand {
            font-size: 22px;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }
        .nav-right a {
            background: rgba(255,255,255,0.2);
            padding: 8px 14px;
            border-radius: 6px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        .nav-right a:hover {
            background: white;
            color: #d63031;
        }
        /* Title */
        h1 {
            text-align: center;
            color: #d63031;
            margin-top: 25px;
        }
        .success {
            text-align: center;
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 6px;
            width: 60%;
            margin: 15px auto;
            font-weight: bold;
        }
        /* Filters */
        .filter-box {
            display: flex;
            justify-content: center;
            margin: 25px auto;
            gap: 15px;
            flex-wrap: wrap;
        }
        select, input[type=text] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            background: white;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }
        /* Donor Cards */
        .donor-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .donor-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.3s;
        }
        .donor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 18px rgba(0,0,0,0.15);
        }
        .donor-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .donor-card h3 {
            margin: 12px 0 5px;
            text-align: center;
        }
        .donor-card p {
            font-size: 14px;
            margin: 4px 0;
            text-align: center;
        }
        /* Center blood group & button */
        .donor-actions {
            text-align: center;
            margin-top: 10px;
        }
        .blood-group {
            display: inline-block;
            background: #d63031;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .btn {
            background: #0984e3;
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-block;
            font-size: 14px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #0768b3;
        }
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.4);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 12px;
            width: 350px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        input, button {
            width: 100%;
            padding: 10px;
            margin: 6px 0;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }
        button {
            background: #d63031;
            color: white;
            border: none;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #b72525;
        }
    </style>
    <script>
        function openRequestModal(bloodGroupId, donorName, donorId) {
            document.getElementById("blood_group_id").value = bloodGroupId;
            document.getElementById("donor_id").value = donorId;
            document.getElementById("donor_name_display").innerText = donorName;
            document.getElementById("requestModal").style.display = "flex";
        }
        function closeModal() {
            document.getElementById("requestModal").style.display = "none";
        }
    </script>
</head>
<body>

<div class="navbar">
    <a href="donor_list.php" class="brand">HealthBridge</a>
    <div class="nav-right">
        <a href="donor_list.php">Home</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<h1>Available Blood Donors</h1>
<?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>

<!-- Filter Form -->
<form method="GET" class="filter-box" id="filterForm">
    <select name="blood_group" onchange="document.getElementById('filterForm').submit();">
        <option value="">All Blood Groups</option>
        <?php mysqli_data_seek($blood_groups_list, 0);
        while($bg = $blood_groups_list->fetch_assoc()) { ?>
            <option value="<?= $bg['id'] ?>" <?= ($selected_bg == $bg['id']) ? 'selected' : '' ?>>
                <?= $bg['name'] ?>
            </option>
        <?php } ?>
    </select>

    <input type="text" name="location" placeholder="Enter location"
           value="<?= htmlspecialchars($location) ?>"
           onkeydown="if(event.key === 'Enter'){this.form.submit();}">

    <select name="eligibility" onchange="document.getElementById('filterForm').submit();">
        <option value="">All Donors</option>
        <option value="eligible" <?= ($eligibility == 'eligible') ? 'selected' : '' ?>>Eligible</option>
        <option value="not_eligible" <?= ($eligibility == 'not_eligible') ? 'selected' : '' ?>>Not Eligible</option>
    </select>
</form>

<!-- Donor Cards -->
<div class="donor-container">
<?php
if ($donors->num_rows > 0) {
    while($d = $donors->fetch_assoc()) {
        $can_donate = true;
        $next_eligible_date = '';

        if (!empty($d['last_donation_date'])) {
            $last_date = new DateTime($d['last_donation_date']);
            $next_date = clone $last_date;
            $next_date->modify('+3 months');
            $next_eligible_date = $next_date->format('Y-m-d');

            if (new DateTime() < $next_date) {
                $can_donate = false;
            }
        }

        if ($eligibility === 'eligible' && !$can_donate) continue;
        if ($eligibility === 'not_eligible' && $can_donate) continue;
?>
        <div class="donor-card">
            <img src="<?= $d['image'] ?>" alt="Donor">
            <div style="padding: 12px;">
                <h3><?= $d['name'] ?></h3>
                <p><strong>📞</strong> <?= $d['phone'] ?></p>
                <p><strong>📍</strong> <?= $d['address'] ?></p>
                <p><strong>🩸 Last Donation:</strong> <?= !empty($d['last_donation_date']) ? $d['last_donation_date'] : 'Never' ?></p>
                <?php if (!$can_donate): ?>
                    <p style="color:red;"><strong>Next Eligible:</strong> <?= $next_eligible_date ?></p>
                <?php endif; ?>
                <div class="donor-actions">
                    <span class="blood-group"><?= $d['blood_group'] ?></span><br>
                    <?php if ($can_donate): ?>
                        <span class="btn" onclick="openRequestModal('<?= $d['blood_group_id'] ?>', '<?= $d['name'] ?>', '<?= $d['id'] ?>')">Request Blood</span>
                    <?php else: ?>
                        <span class="btn" style="background:gray;cursor:not-allowed;">Not Eligible</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
<?php
    }
} else {
    echo "<p style='text-align:center;'>No donors found.</p>";
}
?>
</div>

<!-- Request Modal -->
<div class="modal" id="requestModal">
    <div class="modal-content">
        <h3>Request Blood from <span id="donor_name_display"></span></h3>
        <form method="POST">
            <input type="hidden" name="blood_group_id" id="blood_group_id">
            <input type="hidden" name="donor_id" id="donor_id">
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
