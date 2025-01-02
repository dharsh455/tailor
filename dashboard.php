<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "v2";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch Counts
$order_count_result = $conn->query("SELECT COUNT(*) as total_orders FROM orders");
$order_count = $order_count_result->fetch_assoc()['total_orders'];

$user_count_result = $conn->query("SELECT COUNT(*) as total_users FROM users");
$user_count = $user_count_result->fetch_assoc()['total_users'];

$total_revenue_result = $conn->query("SELECT SUM(total_amount) as total_revenue FROM orders");
$total_revenue = $total_revenue_result->fetch_assoc()['total_revenue'];

// Data for Graphs
$graph_data = [];
$graph_result = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS total_orders FROM orders GROUP BY month");
while ($row = $graph_result->fetch_assoc()) {
    $graph_data[] = $row;
}

// Fetch all orders
$orders_result = $conn->query("SELECT id, total_amount, full_name, email, address FROM orders");

// Fetch all order items
$order_items_result = $conn->query("SELECT id, order_id, product_name, product_price, product_quantity FROM order_items");

// Fetch all users
$users_result = $conn->query("SELECT measurement_id, name, email FROM users");

// Fetch all measurements
$measurements_result = $conn->query("SELECT id, shoulder_width, chest_width, waist_width, left_sleeve_length, right_sleeve_length, timestamp FROM measurements");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Sidebar */
        .sidebar {
            background-color: #343a40;
            color: white;
            height: 100vh;
            position: fixed;
            width: 240px;
            padding: 1.5rem;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            margin: 1rem 0;
            font-size: 1.1rem;
        }
        .sidebar a:hover {
            background-color: #495057;
            padding-left: 10px;
            border-radius: 5px;
        }
        .main-content {
            margin-left: 260px;
            padding: 2rem;
        }
        .card-custom {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border: none;
        }
table {
    width: 100%;
    margin-bottom: 2rem;
    border-collapse: collapse;
    background: linear-gradient(135deg, #f0f0f0, #d1e7dd); /* Gradient background */
    box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1), 0 4px 6px rgba(0, 0, 0, 0.1); /* Enhanced shadow for depth */
    border-radius: 10px; /* Rounded corners */
    overflow: hidden; /* Ensures border-radius is visible */
}

table th, table td {
    padding: 15px;
    text-align: left;
    border: 1px solid #ddd;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* Smooth, modern font */
}

table th {
    background-color: #28a745; /* Solid green background */
    color: white;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px; /* Slight spacing for a clean look */
    border-top-left-radius: 10px; /* Rounded corners on header */
    border-top-right-radius: 10px;
}

table tbody tr:nth-child(even) {
    background-color: #f4f9f4; /* Light greenish-gray for even rows */
}

table tbody tr:hover {
    background-color: #e0ffe0; /* Light green on hover */
    transform: scale(1.02); /* Slight scale effect on hover */
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1); /* Subtle shadow effect on hover */
    transition: transform 0.3s ease, background-color 0.3s ease, box-shadow 0.2s ease; /* Smooth transition */
}

table tbody tr {
    transition: background-color 0.3s ease;
}

table td {
    color: #333; /* Dark text color */
}

table th:hover {
    background-color: #218838; /* Darker green on hover for headers */
    transition: background-color 0.3s ease; /* Smooth transition */
}


        h1 {
            font-size: 2.5rem;
            font-weight: bold;
            color: black;
            text-transform: uppercase;
            margin-bottom: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
    
    <!-- Sidebar -->
    <div class="sidebar">
        <h4>Admin Panel</h4>
        <a href="#orders">Orders</a>
        <a href="#order-items">Order Items</a>
        <a href="#users">Users</a>
        <a href="#measurements">Measurements</a>
        <a href="#charts">Charts</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1>Dashboard</h1>
        <!-- Dashboard Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card card-custom text-white bg-primary p-3">
                    <h5>New Orders</h5>
                    <p><?= $order_count; ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-custom text-white bg-success p-3">
                    <h5>New Users</h5>
                    <p><?= $user_count; ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-custom text-white bg-warning p-3">
                    <h5>Total Revenue</h5>
                    <p>&#8377; <?= number_format($total_revenue, 2); ?></p>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <h3 id="orders">Orders</h3>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Total Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($order = $orders_result->fetch_assoc()) : ?>
                <tr>
                    <td><?= $order['id'] ?></td>
                    <td><?= $order['full_name'] ?></td>
                    <td><?= $order['email'] ?></td>
                    <td><?= $order['address'] ?></td>
                    <td>&#8377; <?= number_format($order['total_amount'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Order Items Table -->
        <h3 id="order-items">Order Items</h3>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Item ID</th>
                    <th>Order ID</th>
                    <th>Product Name</th>
                    <th>Product Price</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($order_item = $order_items_result->fetch_assoc()) : ?>
                <tr>
                    <td><?= $order_item['id'] ?></td>
                    <td><?= $order_item['order_id'] ?></td>
                    <td><?= $order_item['product_name'] ?></td>
                    <td>&#8377; <?= number_format($order_item['product_price'], 2); ?></td>
                    <td><?= $order_item['product_quantity'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Users Table -->
        <h3 id="users">Users</h3>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $users_result->fetch_assoc()) : ?>
                <tr>
                    <td><?= $user['measurement_id'] ?></td>
                    <td><?= $user['name'] ?></td>
                    <td><?= $user['email'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Measurements Table -->
        <h3 id="measurements">Measurements</h3>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>USER ID</th>
                    <th>Shoulder Width</th>
                    <th>Chest Width</th>
                    <th>Waist Width</th>
                    <th>Left Sleeve Length</th>
                    <th>Right Sleeve Length</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($measurement = $measurements_result->fetch_assoc()) : ?>
                <tr>
                    <td><?= $measurement['id'] ?></td>
                    <td><?= $measurement['shoulder_width'] ?></td>
                    <td><?= $measurement['chest_width'] ?></td>
                    <td><?= $measurement['waist_width'] ?></td>
                    <td><?= $measurement['left_sleeve_length'] ?></td>
                    <td><?= $measurement['right_sleeve_length'] ?></td>
                    <td><?= $measurement['timestamp'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Charts -->
        <h3 id="charts" class="mt-5">Order Trends</h3>
        <canvas id="orderChart" width="400" height="150"></canvas>
    </div>

    <!-- Chart.js Script -->
    <script>
        const ctx = document.getElementById('orderChart').getContext('2d');
        const chartData = <?php echo json_encode($graph_data); ?>;
        const labels = chartData.map(data => data.month);
        const dataValues = chartData.map(data => data.total_orders);

        const orderChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Orders Per Month',
                    data: dataValues,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    tension: 0.5,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true, position: 'top' }
                },
                scales: {
                    x: { title: { display: true, text: 'Months' } },
                    y: { title: { display: true, text: 'Total Orders' } }
                }
            }
        });
    </script>
</body>
</html>
