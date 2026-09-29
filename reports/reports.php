<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location:../auth/login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$first_letter = strtoupper(substr($user_name, 0, 1));
include '../config/db.php';
/** @var mysqli $conn */



$selectedCategory = trim($_GET['category'] ?? 'allcategories');
$selectedMonth = trim($_GET['month'] ?? 'allmonths');
$selectedYear = trim($_GET['year'] ?? 'allyears');
$selectedSource = trim($_GET['source'] ?? 'allsources');
$reportPeriod = 'All Time';

if ($selectedMonth != 'allmonths' && $selectedYear != 'allyears') {
    $reportPeriod = date("F", mktime(0, 0, 0, $selectedMonth, 1)) . " " . $selectedYear;
} else if ($selectedMonth != 'allmonths') {
    $reportPeriod = date("F", mktime(0, 0, 0, $selectedMonth, 1));
} else if ($selectedYear != 'allyears') {
    $reportPeriod = "Year " . $selectedYear;
}

$income_condition = "WHERE user_id='$user_id'";
$expense_condition = "WHERE user_id='$user_id'";

if ($selectedMonth != "allmonths") {
    $income_condition .= " AND MONTH(income_date)='$selectedMonth'";
    $expense_condition .= " AND MONTH(expense_date)='$selectedMonth'";
}

if ($selectedYear != "allyears") {
    $income_condition .= " AND YEAR(income_date)='$selectedYear'";
    $expense_condition .= " AND YEAR(expense_date)='$selectedYear'";
}

if ($selectedSource != "allsources") {
    $income_condition .= " AND source='$selectedSource'";
}

if ($selectedCategory != "allcategories") {
    $expense_condition .= " AND category='$selectedCategory'";
}

$total_income_query = "SELECT sum(amount) as total_income from income $income_condition";
$total_result = mysqli_query($conn, $total_income_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_income = $total_row['total_income'] ?? 0;

$total_expense_query = "SELECT sum(amount) as total_expense from expense $expense_condition";
$total_result = mysqli_query($conn, $total_expense_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_expense = $total_row['total_expense'] ?? 0;

$current_balance = $total_income - $total_expense;
$current_balance_color = ($current_balance >= 0) ? 'blue' : 'red';

$total_transactions_query = "SELECT((SELECT count(*) from income $income_condition)+(select count(*) from expense $expense_condition)) as total_transactions";
$total_transactions_result = mysqli_query($conn, $total_transactions_query);
$total_transactions_row = mysqli_fetch_assoc($total_transactions_result);
$total_transactions = $total_transactions_row['total_transactions'];

$monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$incomeData = array_fill(0, 12, 0);
$expenseData = array_fill(0, 12, 0);


$income_query = "SELECT month(income_date) as month,sum(amount) as total from income $income_condition group by month(income_date) order by month(income_date)";
$income_result = mysqli_query($conn, $income_query);
while ($row = mysqli_fetch_assoc($income_result)) {
    $incomeData[$row['month'] - 1] = $row['total'];
}

$expense_query = "SELECT month(expense_date) as month,sum(amount) as total from expense $expense_condition group by month(expense_date) order by month(expense_date)";
$expense_result = mysqli_query($conn, $expense_query);
while ($row = mysqli_fetch_assoc($expense_result)) {
    $expenseData[$row['month'] - 1] = $row['total'];
}


$categoryLabels = [];
$categoryData = [];
$categoryColors = [
    "food" => "#f17d2a",
    "travel" => "#1962f4",
    "shopping" => "#01d22b",
    "bills" => "#be04e7",
    "healthcare" => "#a00e03",
    "education" => "#6ba9ff",
    "entertainment" => "#ffbaba",
    "others" => "#ff66b8"
];
$background_colors = [];
$category_query = "SELECT category,sum(amount) as total from expense $expense_condition group by category order by total desc limit 5";
$category_result = mysqli_query($conn, $category_query);
while ($row = mysqli_fetch_assoc($category_result)) {
    $category = $row['category'];
    $categoryLabels[] = ucfirst($category);
    $categoryData[] = $row['total'];
    $background_colors[] = $categoryColors[$category] ?? "#cccccc";
}

$monthlyIncome = [];
$income = "
SELECT
    YEAR(income_date) AS year,
    MONTH(income_date) AS month,
    SUM(amount) AS total_income
FROM income
$income_condition
GROUP BY YEAR(income_date), MONTH(income_date)
";
$incomeResult = mysqli_query($conn, $income);
while ($row = mysqli_fetch_assoc($incomeResult)) {
    $key = $row['year'] . "-" . $row['month'];
    $monthlyIncome[$key] = $row['total_income'];
}

$balanceColor = "";

$monthlyExpense = [];
$expense = "
SELECT
    YEAR(expense_date) AS year,
    MONTH(expense_date) AS month,
    SUM(amount) AS total_expense
FROM expense
$expense_condition
GROUP BY YEAR(expense_date), MONTH(expense_date)
";
$expenseResult = mysqli_query($conn, $expense);
while ($row = mysqli_fetch_assoc($expenseResult)) {
    $key = $row['year'] . "-" . str_pad($row['month'], 2, "0", STR_PAD_LEFT);
    $monthlyExpense[$key] = $row['total_expense'];
}
$monthlySummary = [];
foreach ($monthlyIncome as $key => $income) {
    $monthlySummary[$key]['income'] = $income;
}
foreach ($monthlyExpense as $key => $expense) {
    $monthlySummary[$key]['expense'] = $expense;
}
foreach ($monthlySummary as $key => &$month) {
    $income_month = $month['income'] ?? 0;
    $expense_month = $month['expense'] ?? 0;
    $month['balance'] = $income_month - $expense_month;
    $month['balanceColor'] = ($month['balance'] >= 0) ? "blue" : "red";
}
unset($month);

krsort($monthlySummary);
$hasMonthlySummary = !empty($monthlySummary);
$hasBarChartData = (array_sum($incomeData) > 0 || array_sum($expenseData) > 0);
$hasPieData = array_sum($categoryData) > 0;


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Reports | Expense Tracker</title>
    <link rel="icon" type="image/png" href="../assets/images/android-chrome-192x192.png">
    <link rel="stylesheet" href="../assets/css/reports.css">
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
                <li><a href="../dashboard/dashboard.php"><i class="fa-solid fa-house" style="color:rgb(11, 57, 183);"></i>Dashboard</a></li>
                <li><a href="../income/income.php"><i class="fa-solid fa-circle-arrow-up" style="color: rgb(52, 231, 21);"></i>Income</a>
                </li>
                <li><a href="../expenses/expense.php"><i class="fa-solid fa-circle-arrow-down"
                            style="color: rgb(255, 43, 43);"></i>Expenses</a></li>
                <li><a href="../transactions/transaction.php"><i class="fa-solid fa-table-list" style="color: rgb(208, 27, 228);"></i>Transactions</a>
                </li>
                <li class="active"><a href="../reports/reports.php"><i class="fa-solid fa-chart-simple" style="color: rgb(243, 120, 49);"></i>Reports</a>
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
        <h2>Reports
            <span class="account">
                <a href="../profile/profile.php">
                    <span class="account-1st"><?php echo $first_letter; ?></span>
                    <span class="account-name"><?php echo ucfirst(strtolower($user_name)); ?></span>
                    <span class="account-arrow"><i class="fa-solid fa-angle-down" style="color: rgb(129, 109, 243);"></i></span>
                </a>
            </span>
        </h2>
        <nav class="breadcrumbs">
            <a href="../dashboard/dashboard.php">Dashboard</a>
            <span style="color: rgb(145, 144, 144);;">/</span>
            <a href="../reports/reports.php">Reports</a>
        </nav>
        <div class="three-box">
            <div class="box1">
                <div class="img">
                    <i class="fa-solid fa-arrow-up"></i>
                </div>
                <div class="content">
                    <p class="one">Total Income</p>
                    <p class="two">₹<?php echo number_format($total_income, 2); ?></p>
                    <p class="three"><?php echo $reportPeriod; ?></p>
                </div>
            </div>
            <div class="box2" style="width: max-content;">
                <div class="img" style="background-color: rgb(253, 228, 228);">
                    <i class="fa-solid fa-arrow-down" style="color: rgb(255, 46, 46);"></i>
                </div>
                <div class="content">
                    <p class="one">Total Expenses</p>
                    <p class="two" style="color:red;">₹<?php echo number_format($total_expense, 2); ?></p>
                    <p class="three"><?php echo $reportPeriod; ?></p>
                </div>
            </div>
            <div class="box3">
                <div class="img" style="background-color: rgb(220, 220, 255);">
                    <i class="fa-solid fa-wallet" style="color: rgb(76, 73, 255);"></i>
                </div>
                <div class="content">
                    <p class="one">Current Balance</p>
                    <p class="two" style="color: <?php echo $current_balance_color; ?>">₹<?php echo number_format($current_balance, 2); ?></p>
                    <p class="three"><?php echo $reportPeriod; ?></p>
                </div>
            </div>
            <div class="box3">
                <div class="img" style="background-color: rgb(252, 226, 232);">
                    <i class="fa-solid fa-chart-pie" style="color: rgb(237, 101, 101)"></i>
                </div>
                <div class="content">
                    <p class="one">Total Transactions</p>
                    <p class="two" style="color: rgb(253, 4, 29);"><?php echo $total_transactions; ?></p>
                    <p class="three"><?php echo $reportPeriod; ?></p>
                </div>
            </div>
        </div>
        <form action="" method="get">
            <div class="filters-div">
                <div class="left-filter">
                    <div class="month-filter">
                        <select name="month">
                            <option value="allmonths" <?php if ($selectedMonth == "allmonths") echo "selected"; ?>>All Months</option>
                            <option value="1" <?php if ($selectedMonth == "1") echo "selected"; ?>>January</option>
                            <option value="2" <?php if ($selectedMonth == "2") echo "selected"; ?>>February</option>
                            <option value="3" <?php if ($selectedMonth == "3") echo "selected"; ?>>March</option>
                            <option value="4" <?php if ($selectedMonth == "4") echo "selected"; ?>>April</option>
                            <option value="5" <?php if ($selectedMonth == "5") echo "selected"; ?>>May</option>
                            <option value="6" <?php if ($selectedMonth == "6") echo "selected"; ?>>June</option>
                            <option value="7" <?php if ($selectedMonth == "7") echo "selected"; ?>>July</option>
                            <option value="8" <?php if ($selectedMonth == "8") echo "selected"; ?>>August</option>
                            <option value="9" <?php if ($selectedMonth == "9") echo "selected"; ?>>September</option>
                            <option value="10" <?php if ($selectedMonth == "10") echo "selected"; ?>>October</option>
                            <option value="11" <?php if ($selectedMonth == "11") echo "selected"; ?>>November</option>
                            <option value="12" <?php if ($selectedMonth == "12") echo "selected"; ?>>December</option>
                        </select>
                    </div>
                    <div class="year-filter">
                        <select name="year">
                            <option value="allyears" <?php if ($selectedYear == "allyears") echo "selected"; ?>>All Years</option>
                            <option value="2024" <?php if ($selectedYear == "2024") echo "selected"; ?>>2024</option>
                            <option value="2025" <?php if ($selectedYear == "2025") echo "selected"; ?>>2025</option>
                            <option value="2026" <?php if ($selectedYear == "2026") echo "selected"; ?>>2026</option>
                            <option value="2027" <?php if ($selectedYear == "2027") echo "selected"; ?>>2027</option>
                        </select>
                    </div>
                    <div class="source-filter">
                        <select name="source">
                            <option value="allsources" <?php if ($selectedSource == "allsources") echo "selected"; ?>>All Sources</option>
                            <option value="salary" <?php if ($selectedSource == "salary") echo "selected"; ?>>Salary</option>
                            <option value="freelance" <?php if ($selectedSource == "freelance") echo "selected"; ?>>Freelance</option>
                            <option value="business" <?php if ($selectedSource == "business") echo "selected"; ?>>Business</option>
                            <option value="investment" <?php if ($selectedSource == "investment") echo "selected"; ?>>Investment</option>
                            <option value="other" <?php if ($selectedSource == "other") echo "selected"; ?>>Others</option>
                        </select>
                    </div>
                    <div class="category-filter">
                        <select name="category">
                            <option value="allcategories" <?php if ($selectedCategory == "allcategories") echo "selected"; ?>>All Categories</option>
                            <option value="food" <?php if ($selectedCategory == "food") echo "selected"; ?>>Food</option>
                            <option value="travel" <?php if ($selectedCategory == "travel") echo "selected"; ?>>Travel</option>
                            <option value="shopping" <?php if ($selectedCategory == "shopping") echo "selected"; ?>>Shopping</option>
                            <option value="bills" <?php if ($selectedCategory == "bills") echo "selected"; ?>>Bills</option>
                            <option value="healthcare" <?php if ($selectedCategory == "healthcare") echo "selected"; ?>>Healthcare</option>
                            <option value="education" <?php if ($selectedCategory == "education") echo "selected"; ?>>Education</option>
                            <option value="entertainment" <?php if ($selectedCategory == "entertainment") echo "selected"; ?>>Entertainment</option>
                            <option value="others" <?php if ($selectedCategory == "others") echo "selected"; ?>>Others</option>
                        </select>
                    </div>
                </div>
                <div class="right-filter">
                    <div class="reset">
                        <button class="reset-btn" type="button" onclick="window.location='reports.php'">Reset Filters</button>
                    </div>
                    <div class="export">
                        <button class="generate-btn" type="submit"><span><i class="fa-solid fa-chart-line"></i></span>
                            Generate Report</button>
                    </div>
                </div>
            </div>
        </form>
        <div class="charts-container">
            <div class="income-chart">
                <div class="header">
                    <h4><i class="fa-solid fa-chart-column" style="color: rgb(0, 166, 255);font-size:20px;"></i> Income
                        Vs Expense</h4>
                </div>
                <div class="chart-area">
                    <?php if ($hasBarChartData) { ?>
                        <canvas id="income-graph"></canvas>
                    <?php
                    } else { ?>
                        <div class="no_data_message">
                            <i class="fa-solid fa-chart-column"></i>
                            <p>No income or expense data available for the<br> selected filters.</p>
                        </div>
                    <?php
                    }
                    ?>
                </div>
            </div>
            <div class="expense-chart">
                <div class="header">
                    <h4><i class="fa-solid fa-chart-pie" style="color: rgb(243, 33, 72); font-size: 18px;"></i>Expense
                        By Category</h4>
                </div>
                <div class="chart-area">
                    <?php if ($hasPieData) { ?>
                        <canvas id="expense-graph"></canvas>
                    <?php } else { ?>
                        <div class="no_data_message">
                            <i class="fa-solid fa-chart-pie"></i>
                            <p>No expense data available for the selected filters.</p>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div class="bottom-container">
            <div class="monthly-summary">
                <div class="header">
                    <h4><i class="fa-solid fa-table-list" style="color: rgb(13, 88, 238);font-size: 18px;"></i>Monthly
                        Summary</h4>
                </div>
                <div class="summary-table">
                    <?php if ($hasMonthlySummary) { ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Total Income</th>
                                    <th>Total Expense</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <?php
                            foreach ($monthlySummary as $key => $month) {
                                list($year, $monthNo) = explode("-", $key);
                                $displayMonth = date("F", mktime(0, 0, 0, $monthNo, 1)) . " " . $year;
                            ?>
                                <tr>
                                    <td><?php echo $displayMonth; ?></td>
                                    <td style="color:rgb(7, 206, 7);">
                                        ₹<?php echo number_format($month['income'] ?? 0, 2); ?>
                                    </td>
                                    <td style="color:red;">
                                        ₹<?php echo number_format($month['expense'] ?? 0, 2); ?>
                                    </td>
                                    <td style="color:<?php echo $month['balanceColor']; ?>">
                                        ₹<?php echo number_format($month['balance'] ?? 0, 2); ?>
                                    </td>
                                </tr>
                            <?php
                            }
                            ?>
                        </table>
                    <?php } else { ?>
                        <div class="no_data_message">
                            <i class="fa-solid fa-table"></i>
                            <p>No monthly summary available for the selected filters.</p>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <div class="top-category">
                <div class="header">
                    <h4 style="margin-bottom: 0;">Top Spending Categories (Expense)</h4>
                </div>
                <div class="category-content">
                    <div class="category-card">
                        <?php if (mysqli_num_rows($category_result) > 0) { ?>
                            <?php
                            mysqli_data_seek($category_result, 0);
                            while ($row = mysqli_fetch_assoc($category_result)) {
                                $category = $row['category'];
                                $amount = $row['total'];
                                $percentage = ($total_expense > 0) ? ($amount / $total_expense) * 100 : 0; ?>
                                <div class="category-lists">
                                    <div class="category-icon">
                                        <?php
                                        if ($category == "food") {
                                            echo '<img src="../assets/images/food.png" width="20" height="20">';
                                        } elseif ($category == "travel") {
                                            echo '<img src="../assets/images/travel.png" width="20" height="20">';
                                        } elseif ($category == "shopping") {
                                            echo '<img src="../assets/images/shopping.png" width="20" height="20">';
                                        } elseif ($category == "bills") {
                                            echo '<img src="../assets/images/bill.png" width="20" height="20">';
                                        } elseif ($category == "healthcare") {
                                            echo '<img src="../assets/images/health.png" width="20" height="20">';
                                        } elseif ($category == "education") {
                                            echo '<img src="../assets/images/education.png" width="20" height="20">';
                                        } elseif ($category == "entertainment") {
                                            echo '<img src="../assets/images/entertainment.png" width="20" height="20">';
                                        } else {
                                            echo '<img src="../assets/images/other.png" width="20" height="20">';
                                        }
                                        ?>
                                    </div>
                                    <div class="category-details">
                                        <div class="top">
                                            <div class="left-detail">
                                                <h4><?php echo ucfirst($category); ?></h4>
                                            </div>
                                            <div class="right-detail">
                                                <h4>₹<?php echo number_format($amount, 2); ?></h4>
                                                <h4>(<?php echo number_format($percentage, 1); ?>%)</h4>
                                            </div>
                                        </div>
                                        <div class="bottom-line">
                                            <div
                                                class="progress-fill"
                                                style="width: <?php echo round($percentage); ?>%; background: <?php echo $categoryColors[$category] ?? '#cccccc'; ?>; ">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        <?php } else { ?>
                            <div class="no_data_message">
                                <i class="fa-solid fa-chart-pie"></i>
                                <p>No spending categories found for the selected filters.</p>
                            </div>
                        <?php } ?>
                    </div>
                    <?php if (mysqli_num_rows($category_result) > 0) { ?>
                        <div class="export-button">
                            <form action="export_report.php" method="GET">
                                <input type="hidden" name="month" value="<?php echo $selectedMonth; ?>">
                                <input type="hidden" name="year" value="<?php echo $selectedYear; ?>">
                                <input type="hidden" name="source" value="<?php echo $selectedSource; ?>">
                                <input type="hidden" name="category" value="<?php echo $selectedCategory; ?>">
                                <button type="button" id="exportBtn">
                                    <i class="fa-solid fa-download" style="color:rgb(167,27,255);"></i>
                                    Export Report (CSV)
                                </button>
                            </form>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    <dialog id="exportDialog">
        <h3>Export Report</h3>
        <p>Do you want to export this report as CSV?</p>
        <div class="dialog-buttons">
            <button type="button" id="cancelBtn">
                Cancel
            </button>
            <button type="button" id="downloadBtn">
                Export
            </button>
        </div>
    </dialog>
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
        const exportBtn = document.getElementById("exportBtn");
        const exportDialog = document.getElementById("exportDialog");
        const cancelBtn = document.getElementById("cancelBtn");
        const downloadBtn = document.getElementById("downloadBtn");
        exportBtn.addEventListener("click", () => {
            exportDialog.showModal();
        });
        cancelBtn.addEventListener("click", () => {
            exportDialog.close();
        });
        downloadBtn.addEventListener("click", () => {
            exportDialog.close();
            const params = new URLSearchParams(window.location.search);
            window.location.href =
                "export_report.php?" + params.toString();
        });


        // ---- Hamburger / sidebar toggle ----
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function openSidebar() {
            sidebar.classList.add('open');
            sidebarOverlay.classList.add('active');
            hamburgerBtn.setAttribute('aria-expanded', 'true');
        }
        function closeSidebar() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
            hamburgerBtn.setAttribute('aria-expanded', 'false');
        }

        hamburgerBtn.addEventListener('click', function() {
            sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
        });
        sidebarOverlay.addEventListener('click', closeSidebar);
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

        const incomeGraph = document.getElementById("income-graph");
        if (incomeGraph) {
            const ctx = incomeGraph.getContext("2d");
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($monthLabels); ?>,
                    datasets: [{
                            label: 'Income',
                            data: <?php echo json_encode($incomeData); ?>,
                            backgroundColor: 'rgb(51, 224, 51)'
                        },
                        {
                            label: 'Expense',
                            data: <?php echo json_encode($expenseData); ?>,
                            backgroundColor: 'red'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        const expenseGraph = document.getElementById("expense-graph");
        if (expenseGraph) {
            const ctxExpense = expenseGraph.getContext("2d");
            new Chart(ctxExpense, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($categoryLabels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($categoryData); ?>,
                        radius: '100%',
                        backgroundColor: <?php echo json_encode($background_colors) ?>
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRaio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'right',
                            align: 'center',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                boxWidth: 10,
                                padding: 10,
                                font: {
                                    size: 13
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>

</html>