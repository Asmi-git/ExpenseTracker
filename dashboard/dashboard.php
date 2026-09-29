<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location:../auth/login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$user_name=$_SESSION['user_name'];
$first_letter=strtoupper(substr($user_name,0,1));
include '../config/db.php';
/** @var mysqli $conn */

$total_income_query = "SELECT sum(amount) as total_income from income where user_id='$user_id'";
$total_result = mysqli_query($conn, $total_income_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_income = $total_row['total_income'] ?? 0;

$total_expense_query = "SELECT sum(amount) as total_expense from expense where user_id='$user_id'";
$total_result = mysqli_query($conn, $total_expense_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_expense = $total_row['total_expense'] ?? 0;

$current_balance = $total_income - $total_expense;

// ---- Expense Trend: daily expenses for the current month ----
$days_in_month = (int)date('t');
$dailyExpenseLabels = range(1, $days_in_month);
$dailyExpenseData = array_fill(0, $days_in_month, 0);
$daily_expense_query = "SELECT DAY(expense_date) as day, SUM(amount) as total
                        FROM expense
                        WHERE user_id='$user_id'
                        AND MONTH(expense_date)=MONTH(CURDATE())
                        AND YEAR(expense_date)=YEAR(CURDATE())
                        GROUP BY DAY(expense_date)";
$daily_expense_result = mysqli_query($conn, $daily_expense_query);
while ($row = mysqli_fetch_assoc($daily_expense_result)) {
    $dailyExpenseData[$row['day'] - 1] = (float) $row['total'];
}
$hasDailyTrend = array_sum($dailyExpenseData) > 0;

// ---- Weekly Spending Analysis: Monday to Sunday of the current week ----
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));
$weekLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$weekData = array_fill(0, 7, 0);
// MySQL WEEKDAY(): Monday=0 ... Sunday=6, which lines up with $weekLabels above.
$weekly_expense_query = "SELECT WEEKDAY(expense_date) as wd, SUM(amount) as total
                          FROM expense
                          WHERE user_id='$user_id'
                          AND expense_date BETWEEN '$week_start' AND '$week_end'
                          GROUP BY WEEKDAY(expense_date)";
$weekly_expense_result = mysqli_query($conn, $weekly_expense_query);
while ($row = mysqli_fetch_assoc($weekly_expense_result)) {
    $weekData[(int) $row['wd']] = (float) $row['total'];
}
$hasWeeklyData = array_sum($weekData) > 0;

// ---- Monthly Savings Progress: this month's income vs expense ----
$month_income_query = "SELECT SUM(amount) as total FROM income
                        WHERE user_id='$user_id'
                        AND MONTH(income_date)=MONTH(CURDATE())
                        AND YEAR(income_date)=YEAR(CURDATE())";
$month_income_result = mysqli_query($conn, $month_income_query);
$month_income = mysqli_fetch_assoc($month_income_result)['total'] ?? 0;

$month_expense_query = "SELECT SUM(amount) as total FROM expense
                         WHERE user_id='$user_id'
                         AND MONTH(expense_date)=MONTH(CURDATE())
                         AND YEAR(expense_date)=YEAR(CURDATE())";
$month_expense_result = mysqli_query($conn, $month_expense_query);
$month_expense_total = mysqli_fetch_assoc($month_expense_result)['total'] ?? 0;

$monthly_savings = $month_income - $month_expense_total;
$savings_percent = ($month_income > 0) ? round(($monthly_savings / $month_income) * 100) : 0;
$savings_bar_width = max(0, min(100, $savings_percent));
$savings_color = ($monthly_savings >= 0) ? 'rgb(51,224,51)' : 'red';

// ---- Highest Expense Day (current month) ----
$highest_day_query = "SELECT expense_date, SUM(amount) as total
                       FROM expense
                       WHERE user_id='$user_id'
                       AND MONTH(expense_date)=MONTH(CURDATE())
                       AND YEAR(expense_date)=YEAR(CURDATE())
                       GROUP BY expense_date
                       ORDER BY total DESC
                       LIMIT 1";
$highest_day_result = mysqli_query($conn, $highest_day_query);
$highest_day_row = mysqli_fetch_assoc($highest_day_result);
$highest_day_date = $highest_day_row['expense_date'] ?? null;
$highest_day_amount = $highest_day_row['total'] ?? 0;

// ---- Quick Stats: fills the empty space beside Recent Activity ----
$income_count_query = "SELECT COUNT(*) as cnt FROM income
                        WHERE user_id='$user_id'
                        AND MONTH(income_date)=MONTH(CURDATE())
                        AND YEAR(income_date)=YEAR(CURDATE())";
$income_count_month = mysqli_fetch_assoc(mysqli_query($conn, $income_count_query))['cnt'] ?? 0;

$expense_count_query = "SELECT COUNT(*) as cnt FROM expense
                         WHERE user_id='$user_id'
                         AND MONTH(expense_date)=MONTH(CURDATE())
                         AND YEAR(expense_date)=YEAR(CURDATE())";
$expense_count_month = mysqli_fetch_assoc(mysqli_query($conn, $expense_count_query))['cnt'] ?? 0;
$month_transactions_count = $income_count_month + $expense_count_month;

$days_elapsed = (int) date('j');
$avg_daily_spend = $days_elapsed > 0 ? $month_expense_total / $days_elapsed : 0;

$biggest_expense_query = "SELECT category, amount FROM expense
                           WHERE user_id='$user_id'
                           AND MONTH(expense_date)=MONTH(CURDATE())
                           AND YEAR(expense_date)=YEAR(CURDATE())
                           ORDER BY amount DESC LIMIT 1";
$biggest_expense_row = mysqli_fetch_assoc(mysqli_query($conn, $biggest_expense_query));

$transaction_query = "SELECT income_date as transaction_date,
                           source as category,
                           description,
                           amount,
                           'Income' as type 
                           from income 
                           where user_id='$user_id' 
                    union all 
                    select expense_date as transaction_date,
                           category,
                           description,
                           amount,
                           'Expense' as type 
                           from expense 
                           where user_id='$user_id'
                           order by transaction_date desc
                           limit 5";
$transaction_result=mysqli_query($conn,$transaction_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Expense Tracker</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="icon" type="image/png" href="../assets/images/android-chrome-192x192.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <!-- Hamburger toggle (visible on mobile) -->
    <button class="hamburger" id="hamburgerBtn" aria-label="Open menu" aria-expanded="false">
        <span></span><span></span><span></span>
    </button>
    <!-- Overlay to close sidebar on mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="left" id="sidebar">
        <div class="logo-container">
            <img src="../assets/images/mainlogo.jpeg" alt="Logo">
            <h1>Expense Tracker</h1>
        </div>
        <div class="navbar">
            <ul>
                <li class="active"><a href="../dashboard/dashboard.php"><i class="fa-solid fa-house" style="color:rgb(11, 57, 183);"></i>Dashboard</a></li>
                <li><a href="../income/income.php"><i class="fa-solid fa-circle-arrow-up" style="color: rgb(52, 231, 21);"></i>Income</a>
                </li>
                <li><a href="../expenses/expense.php"><i class="fa-solid fa-circle-arrow-down"
                            style="color: rgb(255, 43, 43);"></i>Expenses</a></li>
                <li><a href="../transactions/transaction.php"><i class="fa-solid fa-table-list" style="color: rgb(208, 27, 228);"></i>Transactions</a>
                </li>
                <li><a href="../reports/reports.php"><i class="fa-solid fa-chart-simple" style="color: rgb(243, 120, 49);"></i>Reports</a>
                </li>
            </ul>
            <hr style="margin:8px 10px">
            <ul class="navbar">
                <li id="logout"><a href="../auth/logout.php" id="logoutLink"><i class="fa-solid fa-right-from-bracket"
                            style="color: rgb(255, 8, 8);font-size:18px;"></i>Logout</a></li>
            </ul>
        </div>
    </div>
    <div class="right">
        <h2>Welcome, <?php echo ucfirst(strtolower($user_name)); ?> <span><img src="../assets/images/wave.png" width="25" height="25"></span>
            <span class="account">
                <a href="../profile/profile.php">
                    <span class="account-1st"><?php echo $first_letter; ?></span>
                    <span class="account-name"><?php echo ucfirst(strtolower($user_name)); ?></span>
                    <span class="account-arrow"><i class="fa-solid fa-angle-down" style="color: rgb(129, 109, 243);"></i></span>
                </a>
            </span>
        </h2>
        <p>Here's your financial overview</p>
        <div class="three-box">
            <div class="box1">
                <div class="img">
                    <i class="fa-solid fa-arrow-up"></i>
                </div>
                <div class="content">
                    <p id="one">Total Income</p>
                    <p id="two">₹<?php echo number_format($total_income, 2); ?></p>
                    <p id="three">All time</p>
                </div>
            </div>
            <div class="box2">
                <div class="img" style="background-color: rgb(253, 228, 228);">
                    <i class="fa-solid fa-arrow-down" style="color: rgb(255, 46, 46);"></i>
                </div>
                <div class="content">
                    <p id="one">Total Expenses</p>
                    <p id="two" style="color:red">₹<?php echo number_format($total_expense, 2); ?></p>
                    <p id="three">All time</p>
                </div>
            </div>
            <div class="box3">
                <div class="img" style="background-color: rgb(220, 220, 255);">
                    <i class="fa-solid fa-wallet" style="color: rgb(76, 73, 255);"></i>
                </div>
                <div class="content">
                    <p id="one">Current Balance</p>
                    <p id="two" style="color: blue;">₹<?php echo number_format($current_balance, 2); ?></p>
                    <p id="three">All time</p>
                </div>
            </div>
        </div>
        <h4>Quick Actions</h4>
        <div class="two-box">
            <div class="income-btn">
                <button onclick="window.location.href='../income/income.php'"><span><i class="fa-solid fa-circle-plus" style="color: white;font-size: 25px;"></i></span> Add
                    Income</button>
            </div>
            <div class="expense-btn">
                <button  onclick="window.location.href='../expenses/expense.php'" style="background: linear-gradient(to right,rgb(252, 104, 71),rgb(234, 43, 0));"><span><i
                            class="fa-solid fa-circle-plus" style="color: white;font-size: 25px;"></i></span> Add
                    Expenses</button>
            </div>
        </div>

        <h4>Dashboard Insights</h4>
        <div class="insights-grid">
            <div class="insight-card chart-card">
                <div class="header">
                    <h4><i class="fa-solid fa-chart-line" style="color: rgb(76, 73, 255);font-size:16px;"></i> Expense Trend (This Month)</h4>
                </div>
                <?php if ($hasDailyTrend) { ?>
                    <canvas id="expenseTrendChart"></canvas>
                <?php } else { ?>
                    <p class="no-insight-data">No expenses recorded yet this month.</p>
                <?php } ?>
            </div>

            <div class="insight-card chart-card">
                <div class="header">
                    <h4><i class="fa-solid fa-chart-simple" style="color: rgb(255, 46, 46);font-size:16px;"></i> Weekly Spending (Mon - Sun)</h4>
                </div>
                <?php if ($hasWeeklyData) { ?>
                    <canvas id="weeklySpendChart"></canvas>
                <?php } else { ?>
                    <p class="no-insight-data">No expenses recorded yet this week.</p>
                <?php } ?>
            </div>

            <div class="insight-card savings-card">
                <div class="header">
                    <h4><i class="fa-solid fa-piggy-bank" style="color: rgb(51, 224, 51);font-size:16px;"></i> Monthly Savings Progress</h4>
                </div>
                <p class="savings-amount" style="color:<?php echo $savings_color; ?>">₹<?php echo number_format($monthly_savings, 2); ?></p>
                <div class="savings-bar">
                    <div class="savings-fill" style="width:<?php echo $savings_bar_width; ?>%;"></div>
                </div>
                <p class="savings-sub">
                    <?php if ($month_income > 0) { ?>
                        <?php echo $savings_percent; ?>% of this month's income saved so far
                    <?php } else { ?>
                        No income recorded yet this month
                    <?php } ?>
                </p>
            </div>

            <div class="insight-card highest-day-card">
                <div class="header">
                    <h4><i class="fa-solid fa-calendar-day" style="color: rgb(255, 46, 46);font-size:16px;"></i> Highest Expense Day</h4>
                </div>
                <?php if ($highest_day_date) { ?>
                    <p class="highest-day-date"><?php echo date('l, d M Y', strtotime($highest_day_date)); ?></p>
                    <p class="highest-day-amount">₹<?php echo number_format($highest_day_amount, 2); ?></p>
                <?php } else { ?>
                    <p class="no-insight-data">No expenses recorded yet this month.</p>
                <?php } ?>
            </div>

            <div class="insight-card recent-activity-card">
                <div class="header">
                    <h4><i class="fa-solid fa-clock-rotate-left" style="color: rgb(129, 109, 243);font-size:16px;"></i> Recent Activity</h4>
                </div>
                <?php
                mysqli_data_seek($transaction_result, 0);
                if (mysqli_num_rows($transaction_result) > 0) {
                ?>
                    <ul class="activity-list">
                        <?php while ($row = mysqli_fetch_assoc($transaction_result)) { ?>
                            <li class="activity-item">
                                <div class="activity-left">
                                    <div class="activity-icon">
                                        <i class="fa-solid <?php echo $row['type'] == 'Income' ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"
                                           style="color:<?php echo $row['type'] == 'Income' ? 'rgb(51,224,51)' : 'red'; ?>;font-size:13px;"></i>
                                    </div>
                                    <div>
                                        <div class="activity-name"><?php echo ucfirst($row['category']); ?></div>
                                        <div class="activity-date"><?php echo date('d M', strtotime($row['transaction_date'])); ?></div>
                                    </div>
                                </div>
                                <div class="activity-amount <?php echo $row['type'] == 'Income' ? 'income' : 'expense'; ?>">
                                    <?php echo $row['type'] == 'Income' ? '+' : '-'; ?>₹<?php echo number_format($row['amount'], 2); ?>
                                </div>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } else { ?>
                    <p class="no-insight-data">No recent activity yet. Add your first income or expense to get started.</p>
                <?php }
                mysqli_data_seek($transaction_result, 0);
                ?>
            </div>

            <div class="insight-card">
                <div class="header">
                    <h4><i class="fa-solid fa-gauge-high" style="color: rgb(76, 73, 255);font-size:16px;"></i> Quick Stats</h4>
                </div>
                <div class="info-row"><span>Transactions This Month</span><strong><?php echo $month_transactions_count; ?></strong></div>
                <div class="info-row"><span>Avg Daily Spend</span><strong>₹<?php echo number_format($avg_daily_spend, 2); ?></strong></div>
                <div class="info-row">
                    <span>Biggest Expense</span>
                    <strong>
                        <?php if ($biggest_expense_row) { ?>
                            ₹<?php echo number_format($biggest_expense_row['amount'], 2); ?> (<?php echo ucfirst($biggest_expense_row['category']); ?>)
                        <?php } else { ?>
                            &mdash;
                        <?php } ?>
                    </strong>
                </div>
            </div>
        </div>

        <div class="transaction">
            <h4>Recent transactions <span><a href="../transactions/transaction.php">View all</a></span></h4>
            <table>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Type</th>
                </tr>
                <?php
                if(mysqli_num_rows($transaction_result)>0){
                    while($row=mysqli_fetch_assoc($transaction_result)){
                ?>
                <tr>
                    <td>
                        <div class="date">
                            <i class="fa-regular fa-calendar"
                                style="color: rgb(129, 129, 129);background-color: rgb(234, 234, 234);border-radius: 50%;padding: 5px;"></i>
                            <span><?php echo date('d/m/y',strtotime($row['transaction_date'])); ?></span>
                        </div>
                    </td>
                    <td>
                        <div class="category">
                            <?php 
                            if($row['category']=='salary'){
                                echo '<img src="../assets/images/salary.png" alt="food" height="20" width="20">';
                            }
                            else if($row['category']=='freelance'){
                                echo '<img src="../assets/images/freelance.png" alt="food" height="20" width="20">';
                            }
                            else if($row['category']=='business'){
                                echo '<img src="../assets/images/business.png" alt="food" height="20" width="20">';
                            }
                            else if($row['category']=='investment'){
                                echo '<img src="../assets/images/investment.png" alt="food" height="20" width="20">';
                            }
                            else if($row['category']=='others'){
                                echo '<img src="../assets/images/others.png" alt="food" height="20" width="20">';
                            }
                            else if($row['category']=='food'){
                                echo '<img src="../assets/images/food.png" alt="food" height="20" width="20">';
                            }
                            else if($row['category']=='travel'){
                                echo '<img src="../assets/images/travel.png" alt="food" height="20" width="20">';
                            }
                            else if($row['category']=='shopping'){
                                echo '<img src="../assets/images/shopping.png" alt="food" height="20" width="20">';
                            }
                            else if($row['category']=='bills'){
                                echo '<img src="../assets/images/bill.png" alt="food" height="20" width="20">';
                            }
                            else if($row['category']=='healthcare'){
                                echo '<img src="../assets/images/health.png" alt="food" height="20" width="20">';
                            }
                            else if($row['category']=='education'){
                                echo '<img src="../assets/images/education.png" alt="food" height="20" width="20">';
                            }
                            else if($row['category']=='entertainment'){
                                echo '<img src="../assets/images/entertainment.png" alt="food" height="20" width="20">';
                            }
                            else{
                                echo '<img src="../assets/images/other.png" alt="food" height="20" width="20">';
                            }
                            ?>
                            <span><?php echo ucfirst($row['category']); ?></span>
                        </div>
                    </td>
                    <td><?php echo !empty($row['description']) ? $row['description'] : '-'; ?></td>
                    <td>
                        <?php
                        if($row['type']=='Income'){
                            echo '<span style="color: rgb(51, 224, 51);font-weight: bold;">+₹' .number_format($row['amount'],2). '</span>';
                        }
                        else{
                            echo '<span style="color: red;font-weight: bold;">-₹' .number_format($row['amount'],2). '</span>';
                        }
                        ?>
                    </td>
                    <td
                    >
                        <?php
                        if($row['type']=='Income'){
                            echo '<span class="type-income">Income</span>';
                        }
                        else{
                            echo '<span class="type-expense">Expense</span>';
                        }
                        ?>
                    </td>
                </tr> 
                <?php
                    }
                }
                else{
                ?>
                <tr>
                    <td colspan="5">No transactions yet. Add your first income or expense to see it here.</td>
                </tr>
                <?php
                }
                ?>
            </table>
            <button id="transaction-btn"><a href="../transactions/transaction.php">View all transactions</a></button>
        </div>
    </div>
    <dialog id="logoutDialog">
        <h3>Logout</h3>
        <p>Are you sure you want to logout?</p>
        <div class="dialog-buttons">
            <button id="logoutbtn">Logout</button>
            <button id="cancelbtn">Cancel</button>
        </div>
    </dialog>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>

        // ---- Hamburger / sidebar toggle ----
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        function openSidebar() {
            sidebar.classList.add('open');
            overlay.classList.add('active');
            hamburgerBtn.setAttribute('aria-expanded', 'true');
        }
        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            hamburgerBtn.setAttribute('aria-expanded', 'false');
        }

        hamburgerBtn.addEventListener('click', function() {
            sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
        });
        overlay.addEventListener('click', closeSidebar);
        // Close sidebar if viewport grows past mobile breakpoint
        window.addEventListener('resize', function() {
            if (window.innerWidth > 900) { closeSidebar(); }
        });

        const logoutLink=document.getElementById("logoutLink");
        const logoutDialog=document.getElementById("logoutDialog");

        logoutLink.addEventListener("click", function(e) {
            e.preventDefault();
            logoutDialog.showModal();
        });

        document.getElementById("cancelbtn").addEventListener("click", function() {
            logoutDialog.close();
        });

        document.getElementById("logoutbtn").addEventListener("click", function() {
            logoutDialog.close();
            window.location.href = logoutLink.href;
        });

        const expenseTrendCanvas = document.getElementById("expenseTrendChart");
        if (expenseTrendCanvas) {
            new Chart(expenseTrendCanvas.getContext("2d"), {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($dailyExpenseLabels); ?>,
                    datasets: [{
                        label: 'Expense',
                        data: <?php echo json_encode($dailyExpenseData); ?>,
                        borderColor: 'rgb(76, 73, 255)',
                        backgroundColor: 'rgba(76, 73, 255, 0.1)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false } },
                        y: { grid: { display: false }, beginAtZero: true }
                    }
                }
            });
        }

        const weeklySpendCanvas = document.getElementById("weeklySpendChart");
        if (weeklySpendCanvas) {
            new Chart(weeklySpendCanvas.getContext("2d"), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($weekLabels); ?>,
                    datasets: [{
                        label: 'Expense',
                        data: <?php echo json_encode($weekData); ?>,
                        backgroundColor: 'rgb(255, 99, 99)',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false } },
                        y: { grid: { display: false }, beginAtZero: true }
                    }
                }
            });
        }
    </script>
</body>

</html>