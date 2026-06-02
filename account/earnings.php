<?php
session_start();
$page_title = "Earnings & Payments";
include "includes/merchant_header.php";

// Check merchant status
include "../includes/db.php";
$merchant_id = $_SESSION['merchant_id'];

// Include sidebar
include "includes/sidebar.php";

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get earnings summary
$earnings_sql = "SELECT 
                    DATE(transaction_date) as date,
                    SUM(gross_amount) as gross_amount,
                    SUM(commission_fee) as commission_fee,
                    SUM(delivery_fee_earned) as delivery_fee,
                    SUM(net_amount) as net_amount,
                    COUNT(*) as order_count
                FROM merchant_earnings 
                WHERE merchant_id = ? 
                AND transaction_date BETWEEN ? AND ?
                GROUP BY DATE(transaction_date)
                ORDER BY date DESC";
$stmt = mysqli_prepare($conn, $earnings_sql);
mysqli_stmt_bind_param($stmt, "iss", $merchant_id, $start_date, $end_date);
mysqli_stmt_execute($stmt);
$earnings_result = mysqli_stmt_get_result($stmt);

// Get payout history
$payouts_sql = "SELECT * FROM payout_history 
                WHERE merchant_id = ? 
                ORDER BY payout_date DESC";
$stmt = mysqli_prepare($conn, $payouts_sql);
mysqli_stmt_bind_param($stmt, "i", $merchant_id);
mysqli_stmt_execute($stmt);
$payouts_result = mysqli_stmt_get_result($stmt);

// Calculate totals
$totals_sql = "SELECT 
                    SUM(gross_amount) as total_gross,
                    SUM(commission_fee) as total_commission,
                    SUM(net_amount) as total_net,
                    COUNT(*) as total_orders
                FROM merchant_earnings 
                WHERE merchant_id = ?";
$stmt = mysqli_prepare($conn, $totals_sql);
mysqli_stmt_bind_param($stmt, "i", $merchant_id);
mysqli_stmt_execute($stmt);
$totals_result = mysqli_stmt_get_result($stmt);
$totals = mysqli_fetch_assoc($totals_result);
?>

<div class="main-content">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Earnings & Payments</h3>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" onclick="requestPayout()">
                <i class="bi bi-wallet2 me-2"></i> Request Payout
            </button>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success text-white">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <h4>$<?php echo number_format($totals['total_net'] ?? 0, 2); ?></h4>
                <p class="text-muted">Total Net Earnings</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-info text-white">
                    <i class="bi bi-currency-exchange"></i>
                </div>
                <h4>$<?php echo number_format($totals['total_gross'] ?? 0, 2); ?></h4>
                <p class="text-muted">Total Gross Sales</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning text-white">
                    <i class="bi bi-percent"></i>
                </div>
                <h4>$<?php echo number_format($totals['total_commission'] ?? 0, 2); ?></h4>
                <p class="text-muted">Total Commission</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary text-white">
                    <i class="bi bi-bag-check"></i>
                </div>
                <h4><?php echo $totals['total_orders'] ?? 0; ?></h4>
                <p class="text-muted">Total Orders</p>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter Earnings</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Earnings Breakdown -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Earnings Breakdown</h5>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary" onclick="exportEarnings()">
                            Export
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Orders</th>
                                    <th>Gross Sales</th>
                                    <th>Commission</th>
                                    <th>Delivery Fees</th>
                                    <th>Net Earnings</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($earnings_result && mysqli_num_rows($earnings_result) > 0): ?>
                                    <?php while($earning = mysqli_fetch_assoc($earnings_result)): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($earning['date'])); ?></td>
                                        <td><?php echo $earning['order_count']; ?></td>
                                        <td class="text-success">$<?php echo number_format($earning['gross_amount'], 2); ?></td>
                                        <td class="text-danger">-$<?php echo number_format($earning['commission_fee'], 2); ?></td>
                                        <td class="text-info">+$<?php echo number_format($earning['delivery_fee'], 2); ?></td>
                                        <td class="text-primary fw-bold">$<?php echo number_format($earning['net_amount'], 2); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="bi bi-wallet display-4 text-muted"></i>
                                            <p class="mt-3">No earnings data available</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Payout History -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Payout History</h5>
                </div>
                <div class="card-body">
                    <?php if ($payouts_result && mysqli_num_rows($payouts_result) > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while($payout = mysqli_fetch_assoc($payouts_result)): ?>
                            <div class="list-group-item border-0 px-0 py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">$<?php echo number_format($payout['amount'], 2); ?></h6>
                                        <small class="text-muted">
                                            <?php echo date('M j, Y', strtotime($payout['payout_date'])); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-<?php 
                                        switch($payout['status']) {
                                            case 'completed': echo 'success'; break;
                                            case 'processing': echo 'warning'; break;
                                            case 'pending': echo 'secondary'; break;
                                            default: echo 'danger';
                                        }
                                    ?>">
                                        <?php echo ucfirst($payout['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-wallet2 display-4 text-muted"></i>
                            <p class="mt-3">No payout history</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Available Balance -->
            <div class="card mt-3">
                <div class="card-body text-center">
                    <h5>Available Balance</h5>
                    <h2 class="text-success">$<?php echo number_format($totals['total_net'] ?? 0, 2); ?></h2>
                    <small class="text-muted">Ready for payout</small>
                    <div class="mt-3">
                        <button class="btn btn-success w-100" onclick="requestPayout()">
                            <i class="bi bi-wallet2 me-2"></i> Withdraw Funds
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Settings -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Payment Settings</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Bank Account</h6>
                    <?php
                    $bank_sql = "SELECT * FROM merchant_banking WHERE merchant_id = ?";
                    $stmt = mysqli_prepare($conn, $bank_sql);
                    mysqli_stmt_bind_param($stmt, "i", $merchant_id);
                    mysqli_stmt_execute($stmt);
                    $bank_result = mysqli_stmt_get_result($stmt);
                    $bank = mysqli_fetch_assoc($bank_result);
                    ?>
                    
                    <?php if ($bank): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        Bank account verified
                    </div>
                    <p>
                        <strong>Account:</strong> ****<?php echo substr($bank['account_number'], -4); ?><br>
                        <strong>Routing:</strong> ****<?php echo substr($bank['routing_number'], -4); ?><br>
                        <strong>Type:</strong> <?php echo ucfirst($bank['account_type']); ?>
                    </p>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        No bank account added
                    </div>
                    <?php endif; ?>
                    <button class="btn btn-outline-primary" onclick="updateBankAccount()">
                        Update Bank Details
                    </button>
                </div>
                <div class="col-md-6">
                    <h6>Payout Schedule</h6>
                    <div class="mb-3">
                        <label class="form-label">Frequency</label>
                        <select class="form-select">
                            <option>Daily</option>
                            <option selected>Weekly</option>
                            <option>Bi-weekly</option>
                            <option>Monthly</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Minimum Payout</label>
                        <input type="number" class="form-control" value="50" min="10">
                    </div>
                    <button class="btn btn-primary">Save Settings</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function requestPayout() {
    const amount = prompt('Enter amount to withdraw (Available: $<?php echo number_format($totals['total_net'] ?? 0, 2); ?>):');
    if (amount && amount > 0) {
        if (confirm(`Request payout of $${amount}?`)) {
            fetch('includes/request_payout.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({amount: amount})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Payout request submitted!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
    }
}

function exportEarnings() {
    // Similar to reports export function
    let csv = 'Date,Orders,Gross Sales,Commission,Delivery Fees,Net Earnings\n';
    <?php 
    mysqli_data_seek($earnings_result, 0);
    while ($row = mysqli_fetch_assoc($earnings_result)) {
        echo "csv += '" . $row['date'] . "'," . $row['order_count'] . "," . $row['gross_amount'] . "," . 
             $row['commission_fee'] . "," . $row['delivery_fee'] . "," . $row['net_amount'] . "\\n';";
    }
    ?>
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'earnings_<?php echo date('Y-m-d'); ?>.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

function updateBankAccount() {
    window.location.href = 'settings.php#banking';
}
</script>
</body>
</html>