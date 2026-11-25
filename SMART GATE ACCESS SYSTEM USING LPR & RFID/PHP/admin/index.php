
<?php
session_start();

if (
    !isset($_SESSION["admin_id"]) ||
    ($_SESSION["admin_role"] ?? "") !== "admin"
) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../db.php";

date_default_timezone_set("Asia/Kuala_Lumpur");

$sql = "SELECT * FROM visitor ORDER BY id DESC";
$result = $conn->query($sql);

$insideSql = "SELECT * FROM visitor WHERE time_out IS NULL";
$insideResult = $conn->query($insideSql);

$totalRecords = $result->num_rows;
$currentInside = $insideResult->num_rows;
$completed = $totalRecords - $currentInside;
?>


<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Smart Gate Access Management</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #f4f6f9;
    color: #222;
}

/* HEADER */

.header {
    background: linear-gradient(135deg, #111827, #1f2937);
    color: white;
    padding: 25px 50px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    display: flex;
    align-items: center;
    gap: 15px;
}

.logo-icon {
    font-size: 40px;
}

.logo h1 {
    margin: 0;
    font-size: 25px;
}

.logo p {
    margin: 5px 0 0;
    color: #cbd5e1;
    font-size: 14px;
}

.header-right {
    text-align: right;
}

.header-right .system {
    font-size: 14px;
    color: #cbd5e1;
}

.online {
    margin-top: 6px;
    color: #4ade80;
    font-weight: bold;
}

/* MAIN */

.container {
    width: 90%;
    max-width: 1200px;
    margin: 35px auto;
}

/* WELCOME */

.welcome {
    margin-bottom: 25px;
}

.welcome h2 {
    margin: 0;
    font-size: 28px;
}

.welcome p {
    margin-top: 8px;
    color: #64748b;
}

/* STATISTICS */

.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.card {
    background: white;
    padding: 25px;
    border-radius: 14px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-info h3 {
    margin: 0;
    color: #64748b;
    font-size: 14px;
    text-transform: uppercase;
}

.card-info .number {
    font-size: 32px;
    font-weight: bold;
    margin-top: 8px;
}

.card-icon {
    font-size: 38px;
}

/* SEARCH */

.toolbar {
    background: white;
    padding: 20px;
    border-radius: 14px 14px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.toolbar h2 {
    margin: 0;
    font-size: 20px;
}

.search-box input {
    width: 280px;
    padding: 12px 15px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    outline: none;
}

.search-box input:focus {
    border-color: #2563eb;
}

/* TABLE */

.table-container {
    background: white;
    padding: 0 20px 20px;
    border-radius: 0 0 14px 14px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #f8fafc;
    color: #475569;
    font-size: 13px;
    text-transform: uppercase;
    padding: 16px;
    text-align: left;
    border-bottom: 2px solid #e2e8f0;
}

td {
    padding: 16px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 14px;
}

tr:hover {
    background: #f8fafc;
}

/* RFID */

.rfid {
    font-family: monospace;
    font-weight: bold;
    letter-spacing: 1px;
}

/* PLATE */

.plate {
    font-weight: bold;
}

.plate-form {
    display: flex;
    align-items: center;
    gap: 6px;
}

.plate-input {
    width: 120px;
    padding: 8px 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 13px;
    font-weight: bold;
    text-transform: uppercase;
    outline: none;
}

.plate-input:focus {
    border-color: #2563eb;
}

.save-btn {
    padding: 8px 10px;
    border: none;
    border-radius: 6px;
    background: #2563eb;
    color: white;
    font-size: 12px;
    font-weight: bold;
    cursor: pointer;
}

.save-btn:hover {
    background: #1d4ed8;
}

/* STATUS */

.status {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.inside {
    background: #dcfce7;
    color: #166534;
}

.completed {
    background: #e2e8f0;
    color: #475569;
}

/* FOOTER */

.footer {
    text-align: center;
    padding: 30px;
    color: #64748b;
    font-size: 13px;
}

@media (max-width: 600px) {
    .stats {
        grid-template-columns: 1fr;
    }

    .card {
        padding: 16px;
    }

    .card-icon {
        font-size: 28px;
    }
}

</style>

</head>

<body>

<!-- HEADER -->

<div class="header">

    <div class="logo">

        <div class="logo-icon">🚗</div>

        <div>
            <h1>SMART GATE</h1>
            <p>Access Management System</p>
        </div>

    </div>

    <div class="header-right">

        <div class="system">
            Single Entrance Gate
        </div>

        <div class="online">
            ● System Online
        </div>

    </div>

</div>


<!-- MAIN -->

    <div class="container">

        <div class="welcome">

            <h2>Access Dashboard</h2>

            
            
    <div style="margin-top: 20px; display: flex; gap: 12px; flex-wrap: wrap;">

        <a href="register_plate.php"
        style="display: inline-block; padding: 12px 18px;
                background: #2563eb; color: white;
                text-decoration: none; border-radius: 8px;
                font-weight: bold;">
            + Register Resident Vehicle
        </a>

        <a href="manage_rfid.php"
        style="display: inline-block; padding: 12px 18px;
                background: #16a34a; color: white;
                text-decoration: none; border-radius: 8px;
                font-weight: bold;">
            + RFID Card Management
        </a>

    </div>


        <p>
            Monitor vehicle access records and authentication activity.
        </p>

    </div>


    <!-- STATISTICS -->

    <div class="stats">

        <div class="card">

            <div class="card-info">
                <h3>Total Records</h3>
                <div class="number">
                    <span id="totalRecords"><?php echo $totalRecords; ?></span>
                </div>
            </div>

            <div class="card-icon">📋</div>

        </div>


        <div class="card">

            <div class="card-info">
                <h3>Currently Inside</h3>
                <div class="number">
                   <span id="currentInside"><?php echo $currentInside; ?></span>
                </div>
            </div>

            <div class="card-icon">🚘</div>

        </div>


        <div class="card">

            <div class="card-info">
                <h3>Completed</h3>
                <div class="number">
                    <span id="completed"><?php echo $completed; ?></span>
                </div>
            </div>

            <div class="card-icon">✓</div>

        </div>

    </div>


    <!-- ACCESS RECORDS -->

    <div class="toolbar">

        <h2>Access Records</h2>

        <div class="search-box">

            <input
                type="text"
                id="searchInput"
                placeholder="🔍 Search RFID or plate..."
                onkeyup="searchTable()"
            >

        </div>

    </div>


    <div class="table-container">

        <table id="accessTable">

            <thead>

                <tr>

                    <th>ID</th>
                    <th>RFID UID</th>
                    <th>Plate No.</th>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Status</th>

                </tr>

            </thead>

            <tbody>

            <?php

            if ($result->num_rows > 0) {

                while ($row = $result->fetch_assoc()) {

                    echo "<tr>";

                    echo "<td>" . $row["id"] . "</td>";

                    echo "<td class='rfid'>" .
                         $row["rfid_uid"] .
                         "</td>";

                   echo "<td class='plate'>";

                        echo "<form action='../update_plate.php' method='POST' class='plate-form'>";

                        echo "<input type='hidden' name='id' value='" .
                            $row["id"] .
                            "'>";

                        echo "<input 
                                type='text' 
                                name='plate_no' 
                                value='" . htmlspecialchars($row["plate_no"]) . "'
                                placeholder='Enter Plate No.'
                                class='plate-input'
                                required
                            >";

                        echo "<button type='submit' class='save-btn'>Save</button>";

                        echo "</form>";

                        echo "</td>";

                    echo "<td>" .
                         $row["date"] .
                         "</td>";

                    echo "<td>" .
                         $row["time_in"] .
                         "</td>";

                    echo "<td>";

                    if ($row["time_out"] == NULL) {

                        echo "-";

                    } else {

                        echo $row["time_out"];

                    }

                    echo "</td>";

                    echo "<td>";

                    if ($row["time_out"] == NULL) {

                        echo "<span class='status inside'>
                              Currently Inside
                              </span>";

                    } else {

                        echo "<span class='status completed'>
                              Completed
                              </span>";

                    }

                    echo "</td>";

                    echo "</tr>";

                }

            } else {

                echo "<tr>";
                echo "<td colspan='7' style='text-align:center'>";
                echo "No access records found";
                echo "</td>";
                echo "</tr>";

            }

            ?>

            </tbody>

        </table>

    </div>

</div>

<!-- LPR Detection Records -->
<section style="margin: 40px 0 30px; padding: 0 8px;">
    <h2 style="margin:0 0 18px 0;">
    License Plate Recognition (LPR)
    </h2>

    <table id="lprTable"
        style="width:100%; border-collapse:collapse; margin-top:15px;">
        <thead>
            <tr>
                <th style="padding:10px; border:1px solid #ccc;">No.</th>
                <th style="padding:10px; border:1px solid #ccc;">Plate Number</th>
                <th style="padding:10px; border:1px solid #ccc;">Status</th>
                <th style="padding:10px; border:1px solid #ccc;">Confidence</th>
                <th style="padding:10px; border:1px solid #ccc;">Detected At</th>
            </tr>
        </thead>

        <tbody id="lprTableBody">
            <tr>
                <td colspan="5" style="padding:10px;">
                    Loading LPR records...
                </td>
            </tr>
        </tbody>
    </table>
</section>


<!-- FOOTER -->

<div class="footer">

    Smart Gate Access System © 2026  
    <br>
    License Plate Recognition (LPR) + RFID

</div>


<script>

function searchTable() {
    let input = document.getElementById("searchInput").value.toUpperCase();
    let table = document.getElementById("accessTable");
    let rows = table.getElementsByTagName("tr");

    for (let i = 1; i < rows.length; i++) {
        let text = rows[i].textContent.toUpperCase();

        if (text.includes(input)) {
            rows[i].style.display = "";
        } else {
            rows[i].style.display = "none";
        }
    }
}


// AUTO UPDATE DATABASE
setInterval(function() {

    fetch("../get_records.php")
        .then(response => response.json())
        .then(data => {

            console.log("Database updated");

            // Update statistics
            document.getElementById("totalRecords").innerText = data.totalRecords;
            document.getElementById("currentInside").innerText = data.currentInside;
            document.getElementById("completed").innerText = data.completed;

            // Update table
            let tbody = document.querySelector("#accessTable tbody");

            // Get existing row IDs
            let existingRows = {};

            tbody.querySelectorAll("tr").forEach(row => {
                let firstCell = row.querySelector("td");

                if (firstCell) {
                    let id = firstCell.innerText.trim();

                    if (!isNaN(id)) {
                        existingRows[id] = row;
                    }
                }
            });

            



            // Add / update records
            data.records.forEach(record => {

                let row = existingRows[record.id];

                // If new record
                if (!row) {

                    row = document.createElement("tr");

                    row.innerHTML = `
                        <td>${record.id}</td>

                        <td class="rfid">
                            ${record.rfid_uid}
                        </td>

                        <td class="plate">
                            <form action="../update_plate.php"method="POST" class="plate-form">

                                <input type="hidden"
                                       name="id"
                                       value="${record.id}">

                                <input type="text"
                                       name="plate_no"
                                       value="${record.plate_no || ''}"
                                       placeholder="Enter Plate No."
                                       class="plate-input"
                                       required>

                                <button type="submit"
                                        class="save-btn">
                                    Save
                                </button>

                            </form>
                        </td>

                        <td>${record.date}</td>

                        <td>${record.time_in}</td>

                        <td>
                            ${record.time_out || "-"}
                        </td>

                        <td>
                            ${
                                record.time_out === null
                                ?
                                '<span class="status inside">Currently Inside</span>'
                                :
                                '<span class="status completed">Completed</span>'
                            }
                        </td>
                    `;

                    // Newest record goes to top
                    tbody.insertBefore(row, tbody.firstChild);

                } else {

                    // Update existing row
                    // BUT don't disturb Plate No. if user is currently typing

                    let plateInput = row.querySelector(".plate-input");

                    if (
                        plateInput &&
                        document.activeElement === plateInput
                    ) {
                        return;
                    }

                    // Update Time Out
                    let cells = row.querySelectorAll("td");

                    if (cells.length >= 7) {

                        cells[5].innerText =
                            record.time_out || "-";

                        if (record.time_out === null) {

                            cells[6].innerHTML =
                                '<span class="status inside">Currently Inside</span>';

                        } else {

                            cells[6].innerHTML =
                                '<span class="status completed">Completed</span>';
                        }
                    }
                }
            });

            // Apply search again
            searchTable();

        })
        .catch(error => {
            console.log("Fetch error:", error);
        });

}, 3000);

</script>

<script>
    async function loadLPRRecords() {
        const tbody = document.getElementById("lprTableBody");

        try {
            const response = await fetch("../get_lpr_logs.php")
            if (!response.ok) {
                throw new Error("HTTP " + response.status);
            }

            const logs = await response.json();
            tbody.replaceChildren();

            if (logs.length === 0) {
                const row = tbody.insertRow();
                const cell = row.insertCell();
                cell.colSpan = 5;
                cell.textContent = "No LPR records found.";
                return;
            }

            logs.forEach(log => {
                const row = tbody.insertRow();

                [
                    log.id,
                    log.plate_no,
                    log.status,
                    log.confidence !== null
                        ? (Number(log.confidence) * 100).toFixed(1) + "%"
                        : "-",
                    log.detected_at
                ].forEach(value => {
                    const cell = row.insertCell();
                    cell.textContent = value ?? "-";
                    cell.style.padding = "10px";
                    cell.style.border = "1px solid #ccc";
                });
            });

        } catch (error) {
            console.error("LPR loading error:", error);
            tbody.replaceChildren();

            const row = tbody.insertRow();
            const cell = row.insertCell();
            cell.colSpan = 5;
            cell.textContent = "Unable to load LPR records.";
        }
    }

    loadLPRRecords();
    setInterval(loadLPRRecords, 3000);
</script>

</body>

</html>

<?php

$conn->close();

?>