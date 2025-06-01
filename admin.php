<?php
// Start session and handle database connection
require_once 'config.php';

  // Redirect to login if not authenticated
  if (!isset($_SESSION['admin_logged_in'])) {
      header("Location: login.php");
      exit();
  }

// Insert admin user if not exists (only do this once during setup)
$adminCheck = $conn->query("SELECT * FROM admins WHERE username = 'admin'");
if ($adminCheck->num_rows == 0) {
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO admins (username, password, full_name) VALUES ('admin', '$hashedPassword', 'Administrator')");
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="orders_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Write CSV headers
    fputcsv($output, array('ID', 'Reference', 'Network', 'Phone', 'Bundle', 'Price', 'Status', 'Date'));
    
    // Get all orders
    $result = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, array(
                $row['id'],
                $row['reference'],
                strtoupper($row['network']),
                $row['phone'],
                $row['bundle'],
                number_format($row['price'], 2),
                ucfirst($row['status']),
                date('Y-m-d H:i:s', strtotime($row['created_at']))
            ));
        }
    }
    
    fclose($output);
    exit;
}

// Handle order saving
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_order'])) {
    $reference = $_POST['reference'];
    $network = $_POST['network'];
    $phone = $_POST['phone'];
    $bundle = $_POST['bundle'];
    $price = $_POST['price'];
    
    $stmt = $conn->prepare("INSERT INTO orders (reference, network, phone, bundle, price) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssd", $reference, $network, $phone, $bundle, $price);
    $stmt->execute();
    
    // Store in session for success page
    $_SESSION['last_order'] = $reference;
    $_SESSION['delivery_phone'] = $phone;
    
    header("Location: index.php?success=true");
    exit;
}

// Get orders for admin dashboard
$orders = [];
$total_orders = 0;
$total_revenue = 0;
$pending_orders = 0;

// Get all orders
$result = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
if ($result) {
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    $total_orders = count($orders);
}

// Get total revenue
$result = $conn->query("SELECT SUM(price) as total FROM orders WHERE status = 'completed'");
if ($result) {
    $row = $result->fetch_assoc();
    $total_revenue = $row['total'] ?? 0;
}

// Get pending orders count
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
if ($result) {
    $row = $result->fetch_assoc();
    $pending_orders = $row['count'] ?? 0;
}

// Get daily totals for admin dashboard
$daily_totals = [];
$result = $conn->query("
    SELECT 
        DATE(created_at) as order_date, 
        COUNT(*) as total_orders, 
        SUM(price) as total_amount 
    FROM orders 
    GROUP BY DATE(created_at) 
    ORDER BY order_date DESC
");
if ($result) {
    $daily_totals = $result->fetch_all(MYSQLI_ASSOC);
}

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    $stmt->execute();
    
    header("Location: admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Bundle Sales - Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    body {
      background: linear-gradient(135deg, #1a2a6c, #b21f1f, #1a2a6c);
      color: #fff;
      min-height: 100vh;
      padding: 20px;
    }
    
    .container {
      max-width: 1200px;
      margin: 0 auto;
    }
    
    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 0;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      margin-bottom: 30px;
    }
    
    .logo {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 1.8rem;
      font-weight: bold;
    }
    
    .logo i {
      color: #4CAF50;
    }
    
    .card {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 15px;
      padding: 25px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
      margin-bottom: 30px;
    }
    
    .admin-stats {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 15px;
      margin-bottom: 25px;
    }

    .stat-card {
      background: rgba(255, 255, 255, 0.1);
      border-radius: 10px;
      padding: 20px;
      text-align: center;
      transition: all 0.3s;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    }

    .stat-value {
      font-size: 2.2rem;
      font-weight: bold;
      margin: 10px 0;
    }

    .total-orders .stat-value { color: #4CAF50; }
    .total-revenue .stat-value { color: #FFC107; }
    .pending-orders .stat-value { color: #2196F3; }

    .stat-label {
      font-size: 1rem;
      color: rgba(255, 255, 255, 0.8);
    }

    .stat-icon {
      font-size: 1.8rem;
      margin-bottom: 10px;
    }

    .export-btn {
      background: #17a2b8;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s;
    }

    .export-btn:hover {
      background: #138496;
      transform: translateY(-2px);
    }

    .admin-actions {
      display: flex;
      gap: 15px;
      margin-bottom: 20px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 10px;
      overflow: hidden;
    }
    
    th, td {
      padding: 15px;
      text-align: left;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    th {
      background: rgba(255,255,255,0.15);
      font-weight: 600;
    }
    
    tr:hover {
      background: rgba(255,255,255,0.05);
    }
    
    .status-pending {
      color: #FFC107;
      font-weight: bold;
    }
    
    .status-completed {
      color: #4CAF50;
      font-weight: bold;
    }
    
    .status-failed {
      color: #F44336;
      font-weight: bold;
    }
    
    .status-form {
      display: flex;
      gap: 10px;
    }
    
    .status-form select, .status-form button {
      padding: 8px 12px;
      border-radius: 5px;
      border: none;
    }
    
    .status-form button {
      background: #4CAF50;
      color: white;
      cursor: pointer;
    }
    
    .admin-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }
    
    .admin-header h2 {
      font-size: 1.8rem;
    }
    
    .logout-btn {
      background: #F44336;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s;
    }
    
    .logout-btn:hover {
      background: #d32f2f;
    }

    @media (max-width: 768px) {
      .admin-stats {
        grid-template-columns: 1fr;
      }
      
      .admin-actions {
        flex-direction: column;
      }
      
      .status-form {
        flex-direction: column;
      }
      
      th, td {
        padding: 10px 5px;
        font-size: 0.9rem;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="admin-section">
      <div class="admin-header">
        <h2><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>
        <div>
          <span style="margin-right: 15px; font-weight: 600;">Welcome, <?= htmlspecialchars($_SESSION['admin_username']) ?></span>
          <a href="index.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
      </div>
      
      <!-- Stats Cards -->
      <div class="admin-stats">
        <div class="stat-card total-orders">
          <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
          <div class="stat-value"><?= $total_orders ?></div>
          <div class="stat-label">Total Orders</div>
        </div>
        
        <div class="stat-card total-revenue">
          <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
          <div class="stat-value">GH₵ <?= number_format($total_revenue, 2) ?></div>
          <div class="stat-label">Total Revenue</div>
        </div>
        
        <div class="stat-card pending-orders">
          <div class="stat-icon"><i class="fas fa-clock"></i></div>
          <div class="stat-value"><?= $pending_orders ?></div>
          <div class="stat-label">Pending Orders</div>
        </div>
      </div>
      
      <div class="admin-actions">
        <a href="?export=csv" class="export-btn">
          <i class="fas fa-file-csv"></i> Export to CSV
        </a>
      </div>
      
      <!-- Daily Totals Card -->
      <div class="card">
        <h3><i class="fas fa-calendar-alt"></i> Daily Order Summary</h3>
        <?php if (count($daily_totals) > 0): ?>
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Orders</th>
              <th>Amount (GH₵)</th>
              <th>Avg. Order Value</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($daily_totals as $day): 
              $avg_order = $day['total_orders'] > 0 ? $day['total_amount'] / $day['total_orders'] : 0;
            ?>
            <tr>
              <td><?= date('M d, Y', strtotime($day['order_date'])) ?></td>
              <td><?= $day['total_orders'] ?></td>
              <td><?= number_format($day['total_amount'], 2) ?></td>
              <td><?= number_format($avg_order, 2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <p>No order data available.</p>
        <?php endif; ?>
      </div>
      
      <!-- Recent Orders Card -->
      <div class="card">
        <h3><i class="fas fa-list"></i> Order Management</h3>
        <?php if (count($orders) > 0): ?>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Reference</th>
              <th>Network</th>
              <th>Phone</th>
              <th>Bundle</th>
              <th>Price (GH₵)</th>
              <th>Date</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
              <td><?= $order['id'] ?></td>
              <td><?= $order['reference'] ?></td>
              <td><?= strtoupper($order['network']) ?></td>
              <td><?= $order['phone'] ?></td>
              <td><?= $order['bundle'] ?></td>
              <td><?= number_format($order['price'], 2) ?></td>
              <td><?= date('M d, H:i', strtotime($order['created_at'])) ?></td>
              <td class="status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></td>
              <td>
                <form method="POST" class="status-form">
                  <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                  <select name="status">
                    <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="failed" <?= $order['status'] == 'failed' ? 'selected' : '' ?>>Failed</option>
                  </select>
                  <button type="submit" name="update_status">Update</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <p>No orders found.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>