<?php
session_start();
include "../connect.php";

// Check if user is logged in and is a crew member
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: ../loginpage.php");
    exit();
}

// Simple query to fetch all pending, preparing, and out for delivery orders
$query = "SELECT * FROM orders WHERE status IN ('pending', 'preparing', 'out for delivery') ORDER BY order_time DESC";
$result = $conn->query($query);
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crew Dashboard - K-Food Delight</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --success-color: #22c55e;
            --warning-color: #eab308;
            --danger-color: #ef4444;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-700: #374151;
            --gray-800: #1f2937;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--gray-100);
        }

        .dashboard {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        .sidebar {
            background-color: white;
            padding: 1.5rem;
            border-right: 1px solid var(--gray-200);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .logo img {
            width: 40px;
            height: 40px;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--gray-700);
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            background-color: var(--gray-100);
            color: var(--primary-color);
        }

        .main-content {
            padding: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 350px));
            gap: 1.5rem;
            padding: 1.5rem;
            justify-content: start;
        }

        .order-card {
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
            height: fit-content;
            min-height: 320px;
            display: flex;
            flex-direction: column;
        }

        /* Pending - Light Yellow */
        .order-card[data-status="pending"] {
            background: #fff5d1;
            border: 1px solid #e6d5a7;
        }
        .order-card[data-status="pending"] .info-section {
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid #e6d5a7;
        }

        /* Preparing - Light Blue */
        .order-card[data-status="preparing"] {
            background: #e6f3ff;
            border: 1px solid #b3d7ff;
        }
        .order-card[data-status="preparing"] .info-section {
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid #b3d7ff;
        }

        /* Out for delivery - Light Green */
        .order-card[data-status="out for delivery"] {
            background: #e6ffe6;
            border: 1px solid #b3e6b3;
        }
        .order-card[data-status="out for delivery"] .info-section {
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid #b3e6b3;
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .order-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem;
            margin: -1rem -1rem 0.75rem -1rem;
            border-bottom: 1px solid;
            border-radius: 8px 8px 0 0;
        }

        /* Header styles for different statuses */
        .order-card[data-status="pending"] .order-header {
            background-color: #fff5d1;
            border-bottom-color: #e6d5a7;
        }

        .order-card[data-status="preparing"] .order-header {
            background-color: #e6f3ff;
            border-bottom-color: #b3d7ff;
        }

        .order-card[data-status="out for delivery"] .order-header {
            background-color: #e6ffe6;
            border-bottom-color: #b3e6b3;
        }

        .order-content {
            display: grid;
            grid-template-columns: 2fr 3fr 2fr;
            gap: 1.5rem;
            padding: 1.5rem;
        }

        .section-title {
            font-size: 0.875rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.75rem;
            font-weight: 600;
        }

        .order-row {
            background: #fff5d1;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .order-row td {
            padding: 1.5rem;
            border: 1px solid #e6d5a7;
            font-size: 0.9rem;
        }

        .order-row td:first-child {
            border-radius: 0.75rem 0 0 0.75rem;
        }

        .order-row td:last-child {
            border-radius: 0 0.75rem 0.75rem 0;
        }

        .order-row:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        /* Scrollbar styling */
        .order-card::-webkit-scrollbar {
            width: 6px;
        }

        .order-card::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.05);
            border-radius: 3px;
        }

        .order-card::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.2);
            border-radius: 3px;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e6d5a7;
            background: #fff5d1;
            flex-shrink: 0;
        }

        .order-id {
            font-weight: 600;
            font-family: monospace;
            font-size: 1.1rem;
            padding: 0.4rem 0.6rem;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.5);
        }

        .order-card[data-status="pending"] .order-id {
            color: #856404;
            background: rgba(255, 255, 255, 0.7);
        }

        .order-card[data-status="preparing"] .order-id {
            color: #004085;
            background: rgba(255, 255, 255, 0.7);
        }

        .order-card[data-status="out for delivery"] .order-id {
            color: #155724;
            background: rgba(255, 255, 255, 0.7);
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            color: white;
        }

        .status-pending {
            background-color: #ffc107;
        }

        .status-preparing {
            background-color: #0d6efd;
        }

        .status-out {
            background-color: #28a745;
        }

        .status-badge i {
            font-size: 0.875rem;
        }

        .info-section {
            background: rgba(255, 255, 255, 0.5);
            border-radius: 6px;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
            border: 1px solid #e6d5a7;
        }

        .items-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .item-entry {
            padding: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .info-section h4 {
            color: #664d03;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .customer-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .order-id {
            font-family: 'Monaco', monospace;
            color: #4f46e5;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .order-id i {
            color: #6366f1;
        }

        .status-pending {
            background-color: var(--warning-color);
            color: white;
        }

        .status-preparing {
            background-color: var(--primary-color);
            color: white;
        }

        .status-out {
            background-color: var(--success-color);
            color: white;
        }

        .customer-info {
            margin-bottom: 1rem;
            padding: 1rem;
            background-color: rgba(255, 255, 255, 0.7);
            border-radius: 0.75rem;
            border: 1px solid #e6d5a7;
            flex-shrink: 0;
        }

        .order-items {
            margin-bottom: 1rem;
            padding: 1rem;
            background-color: rgba(255, 255, 255, 0.7);
            border-radius: 0.75rem;
            border: 1px solid #e6d5a7;
            flex-shrink: 0;
        }

        .order-items p {
            margin: 0.5rem 0;
            font-size: 1rem;
        }

        .order-items h4 {
            margin-bottom: 0.5rem;
            color: #664d03;
            border-bottom: 1px dashed #e6d5a7;
            padding-bottom: 0.25rem;
        }

        .order-address {
            margin: 0.25rem 0;
            padding: 0.375rem;
            background-color: white;
            border-radius: 0.375rem;
        }

        .payment-method {
            margin: 0.25rem 0;
            padding: 0.375rem;
            background-color: white;
            border-radius: 0.375rem;
        }

        .order-total {
            font-size: 1.1rem;
            font-weight: 600;
            text-align: right;
            margin-top: 1rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.75rem;
            margin-top: auto;
            padding-top: 0.75rem;
            border-top: 1px solid #e6d5a7;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn i {
            font-size: 1rem;
        }

        .btn-primary {
            background: #4f46e5;
            color: white;
        }

        .btn-primary:hover {
            background: #4338ca;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .total-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            background: #f8f9fc;
            border-radius: 8px;
            margin-top: auto;
        }

        .total-label {
            font-size: 0.875rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .total-amount {
            font-size: 1.25rem;
            font-weight: 700;
            color: #4f46e5;
        }

        .btn {
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 0.375rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }

        /* Notifications */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 2rem;
            border-radius: 8px;
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transform: translateX(120%);
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification-success {
            border-left: 4px solid var(--success-color);
        }

        .notification-error {
            border-left: 4px solid var(--danger-color);
        }

        .notification i {
            font-size: 1.25rem;
        }

        .notification-success i {
            color: var(--success-color);
        }

        .notification-error i {
            color: var(--danger-color);
        }

        /* Table Styles */
        .orders-table th {
            text-align: left;
            padding: 1rem;
            color: #666;
            font-weight: 600;
            border-bottom: 2px solid #eee;
            white-space: nowrap;
        }

        .order-items-list {
            max-width: 300px;
        }

        .order-item {
            background: rgba(255, 255, 255, 0.7);
            padding: 0.5rem;
            margin: 0.25rem 0;
            border-radius: 4px;
            border: 1px solid #e6d5a7;
        }

        .delivery-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .delivery-info .address,
        .delivery-info .payment {
            background: rgba(255, 255, 255, 0.7);
            padding: 0.5rem;
            border-radius: 4px;
            border: 1px solid #e6d5a7;
        }

        .delivery-info i {
            margin-right: 0.5rem;
            color: #666;
        }

        .order-total {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--primary-color);
        }

        /* Responsive Table */
        @media (max-width: 1200px) {
            .orders-table-container {
                overflow-x: auto;
                margin: 0 -1rem;
                padding: 0 1rem;
            }

            .orders-table {
                min-width: 1000px;
            }
        }

        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: 1fr;
            }

            .sidebar {
                display: none;
            }

            .orders-table {
                min-width: 800px;
            }
        }
    </style>
    <script src="js/order-status.js" defer></script>
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="logo">
                <img src="../images/logo.png" alt="K-Food Delight">
                <h2>Crew Panel</h2>
            </div>
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="#" class="nav-link active">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Orders</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../logout.php" class="nav-link">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Order Processing</h1>
            </div>

            <div class="orders-grid">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card" data-status="<?php echo strtolower($order['status']); ?>">
                        <div class="order-header">
                            <span class="order-id">#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></span>
                            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>

                        <div class="info-section">
                            <h4><i class="fas fa-user"></i> Customer Details</h4>
                            <div class="customer-name"><?php echo htmlspecialchars($order['name']); ?></div>
                        </div>

                        <div class="info-section">
                            <h4><i class="fas fa-shopping-bag"></i> Order Items</h4>
                            <?php if (!empty($order['item_name'])): ?>
                                <div class="items-grid">
                                <?php 
                                $items = explode("\n", $order['item_name']);
                                foreach ($items as $item):
                                    $item = trim($item);
                                    if (!empty($item)):
                                ?>
                                    <div class="item-entry">
                                        <?php echo htmlspecialchars($item); ?>
                                    </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                                </div>
                            <?php else: ?>
                                <p>No items found</p>
                            <?php endif; ?>
                        </div>

                        <div class="info-section">
                            <h4><i class="fas fa-map-marker-alt"></i> Delivery Address</h4>
                            <div class="address-text"><?php echo htmlspecialchars($order['address']); ?></div>
                            <div class="payment-method">
                                <i class="fas fa-money-bill-wave"></i>
                                <?php echo ucfirst(htmlspecialchars($order['method'])); ?>
                            </div>
                        </div>

                        <div class="info-section">
                            <div class="total-amount">
                                Total: ₱<?php echo number_format($order['total_price'], 2); ?>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <?php if ($order['status'] == 'pending'): ?>
                                <button class="btn btn-primary" onclick="updateStatus(<?php echo $order['id']; ?>, 'preparing')">
                                    <i class="fas fa-utensils"></i> Start Preparing
                                </button>
                            <?php elseif ($order['status'] == 'preparing'): ?>
                                <button class="btn btn-success" onclick="updateStatus(<?php echo $order['id']; ?>, 'out for delivery')">
                                    <i class="fas fa-motorcycle"></i> Mark Out for Delivery
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- JavaScript loaded from order-status.js -->
</body>
</html>