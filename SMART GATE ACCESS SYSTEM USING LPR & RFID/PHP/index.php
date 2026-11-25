
<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Smart Gate | Home</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
        }

        header {
            background: #0f172a;
            color: white;
            padding: 22px 7%;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .logo {
            background: #2563eb;
            padding: 13px;
            border-radius: 12px;
            font-size: 25px;
        }

        header h1 { margin: 0; font-size: 25px; }
        header p { margin: 6px 0 0; color: #cbd5e1; }

        main {
            max-width: 1000px;
            width: 90%;
            margin: 55px auto;
        }

        .intro { text-align: center; margin-bottom: 35px; }
        .intro h2 { font-size: 32px; margin-bottom: 10px; }
        .intro p { color: #64748b; line-height: 1.6; }

        .portals {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 5px 18px rgba(15, 23, 42, .07);
        }

        .icon { font-size: 35px; }
        .card h3 { margin-bottom: 10px; }
        .card p {
            color: #64748b;
            line-height: 1.6;
            min-height: 50px;
        }

        .btn {
            display: block;
            text-align: center;
            text-decoration: none;
            padding: 13px;
            border-radius: 8px;
            color: white;
            font-weight: bold;
            margin-top: 20px;
        }

        .admin { background: #2563eb; }
        .resident { background: #059669; }
        .btn:hover { opacity: .88; }

        .features {
            margin-top: 30px;
            padding: 22px;
            border-radius: 12px;
            background: #e2e8f0;
            text-align: center;
            line-height: 1.9;
        }

        footer {
            text-align: center;
            padding: 25px;
            color: #64748b;
            font-size: 13px;
        }

        @media (max-width: 650px) {
            .portals { grid-template-columns: 1fr; }
            .intro h2 { font-size: 26px; }
            main { margin-top: 35px; }
        }
    </style>
</head>
<body>

<header>
    <div class="brand">
        <div class="logo">🛡️</div>
        <div>
            <h1>SMART GATE</h1>
            <p>Smart Access Management System</p>
        </div>
    </div>
</header>

<main>
    <section class="intro">
        <h2>Welcome to Smart Gate</h2>
        <p>
            A smart access management system using License Plate
            Recognition, QR verification and RFID technology.
        </p>
        <h3>Choose Your Portal</h3>
    </section>

    <section class="portals">
        <div class="card">
            <div class="icon">🛡️</div>
            <h3>Admin Portal</h3>
            <p>
                Manage vehicle plate records and access the
                administration dashboard.
            </p>
            <a class="btn admin" href="admin/login.php">
                Admin Login
            </a>
        </div>

        <div class="card">
            <div class="icon">🏠</div>
            <h3>Resident Portal</h3>
            <p>
                Register visitors, create event invitations
                and manage guest approvals.
            </p>
            <a class="btn resident" href="resident/resident_login.php">
                Resident Login
            </a>
        </div>
    </section>

    <section class="features">
        <strong>SMART GATE FEATURES</strong><br>
        License Plate Recognition (LPR) · QR Code Verification · RFID Access
    </section>
</main>

<footer>
    Smart Gate Access System
</footer>

</body>
</html>
