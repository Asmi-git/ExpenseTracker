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

$search = "";
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

$type = trim($_GET['type'] ?? 'alltypes');

$category = trim($_GET['category'] ?? 'allcategories');
$income_category = "";
$expense_category = "";
if ($category != 'allcategories') {
    $income_category = "AND source='$category'";
    $expense_category = "AND category='$category'";
}

$month = trim($_GET['month'] ?? 'allmonths');
$income_month = "";
$expense_month = "";
if ($month != 'allmonths') {
    $income_month = "AND month(income_date)=$month";
    $expense_month = "AND month(expense_date)=$month";
}

$total_income_query = "SELECT sum(amount) as total_income from income where user_id='$user_id'";
$total_result = mysqli_query($conn, $total_income_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_income = $total_row['total_income'] ?? 0;

$total_expense_query = "SELECT sum(amount) as total_expense from expense where user_id='$user_id'";
$total_result = mysqli_query($conn, $total_expense_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_expense = $total_row['total_expense'] ?? 0;

$current_balance = $total_income - $total_expense;


$total_transactions_query = "SELECT (SELECT count(*) from income where user_id='$user_id' and ('$type'='alltypes' or '$type'='income')
                           $income_category
                           $income_month
                           and (source like '%$search%' or description like '%$search%' OR amount LIKE '%$search%'
    OR 'Income' LIKE '%$search%'))+(select count(*) from expense where user_id='$user_id' and ('$type'='alltypes' or '$type'='expense')
                           $expense_category
                           $expense_month
                           and (category like '%$search%' or description like '%$search%' OR amount LIKE '%$search%'
    OR 'Expense' LIKE '%$search%')) as total_transactions";
$total_transactions_result = mysqli_query($conn, $total_transactions_query);
$total_transactions_row = mysqli_fetch_assoc($total_transactions_result);
$total_transactions = $total_transactions_row['total_transactions'];

$records_per_page = 10;
$page = 1;
if (isset($_GET['page'])) {
    $page = (int)$_GET['page'];
}
$offset = ($page - 1) * $records_per_page;
$total_pages = ceil($total_transactions / $records_per_page);
$start_record = $offset + 1;
$end_record = $offset + $records_per_page;

$transaction_query = "SELECT id,income_date as transaction_date,
                           source as category,
                           description,
                           amount,
                           'Income' as type 
                           from income 
                           where user_id='$user_id' 
                           and ('$type'='alltypes' or '$type'='income')
                           $income_category
                           $income_month
                           and (source like '%$search%' or description like '%$search%' OR amount LIKE '%$search%'
    OR 'Income' LIKE '%$search%')
    
                    union all 
                    select id,expense_date as transaction_date,
                           category,
                           description,
                           amount,
                           'Expense' as type 
                           from expense 
                           where user_id='$user_id'
                           and ('$type'='alltypes' or '$type'='expense')
                           $expense_category
                           $expense_month
                           and (category like '%$search%' or description like '%$search%' OR amount LIKE '%$search%'
    OR 'Expense' LIKE '%$search%')
    
                           order by transaction_date desc
                           LIMIT $offset,$records_per_page";

$transaction_result = mysqli_query($conn, $transaction_query);
if (!$transaction_result) {
    die(mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Transactions | Expense Tracker</title>
    <link rel="stylesheet" href="../assets/css/transaction.css">
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
                <li><a href="../dashboard/dashboard.php"><i class="fa-solid fa-house" style="color:rgb(11, 57, 183);"></i>Dashboard</a></li>
                <li><a href="../income/income.php"><i class="fa-solid fa-circle-arrow-up" style="color: rgb(52, 231, 21);"></i>Income</a>
                </li>
                <li><a href="../expenses/expense.php"><i class="fa-solid fa-circle-arrow-down"
                            style="color: rgb(255, 43, 43);"></i>Expenses</a></li>
                <li class="active"><a href="../transactions/transaction.php"><i class="fa-solid fa-table-list" style="color: rgb(208, 27, 228);"></i>Transactions</a>
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
        <h2>Transactions
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
            <a href="../transactions/transaction.php">Transactions</a>
        </nav>
        <div class="three-box">
            <div class="box1">
                <div class="img">
                    <i class="fa-solid fa-arrow-up"></i>
                </div>
                <div class="content">
                    <p class="one">Total Income</p>
                    <p class="two">₹<?php echo number_format($total_income, 2); ?></p>
                    <p class="three">All time</p>
                </div>
            </div>
            <div class="box2" style="width: max-content;">
                <div class="img" style="background-color: rgb(253, 228, 228);">
                    <i class="fa-solid fa-arrow-down" style="color: rgb(255, 46, 46);"></i>
                </div>
                <div class="content">
                    <p class="one">Total Expenses</p>
                    <p class="two" style="color:red;">₹<?php echo number_format($total_expense, 2); ?></p>
                    <p class="three">All time</p>
                </div>
            </div>
            <div class="box3">
                <div class="img" style="background-color: rgb(220, 220, 255);">
                    <i class="fa-solid fa-wallet" style="color: rgb(76, 73, 255);"></i>
                </div>
                <div class="content">
                    <p class="one">Current Balance</p>
                    <p class="two" style="color: blue">₹<?php echo number_format($current_balance, 2); ?></p>
                    <p class="three">All time</p>
                </div>
            </div>
            <div class="box3">
                <div class="img" style="background-color: rgb(252, 226, 232);">
                    <i class="fa-solid fa-chart-pie" style="color: rgb(237, 101, 101)"></i>
                </div>
                <div class="content">
                    <p class="one">Total Transactions</p>
                    <p class="two" style="color: rgb(253, 4, 29);"><?php echo $total_transactions; ?></p>
                    <p class="three">All time</p>
                </div>
            </div>
        </div>
        <div class="transaction">
            <h4>All Transactions</h4>
            <form action="transaction.php" method="get" id="filter-form">
                <div class="search-container">
                    <div class="search">
                        <input type="text" name="search" placeholder="Search transactions..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ""; ?>">
                        <button type="submit" class="search-icon-btn">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>
                    <div class="type-filter">
                        <select name="type" onchange="this.form.submit()">
                            <option value="alltypes" <?php if ($type == "alltypes") echo "selected"; ?>>All types</option>
                            <option value="income" <?php if ($type == "income") echo "selected"; ?>>Income</option>
                            <option value="expense" <?php if ($type == "expense") echo "selected"; ?>>Expense</option>
                        </select>
                    </div>
                    <div class="category-filter">
                        <select name="category" onchange="this.form.submit()">
                            <option value="allcategories" <?php if ($category == "allcategories") echo "selected"; ?>>All Categories</option>
                            <option value="salary" <?php if ($category == "salary") echo "selected"; ?>>Salary</option>
                            <option value="freelance" <?php if ($category == "freelance") echo "selected"; ?>>Freelance</option>
                            <option value="business" <?php if ($category == "business") echo "selected"; ?>>Business</option>
                            <option value="investment" <?php if ($category == "investment") echo "selected"; ?>>Investment</option>
                            <option value="food" <?php if ($category == "food") echo "selected"; ?>>Food</option>
                            <option value="travel" <?php if ($category == "travel") echo "selected"; ?>>Travel</option>
                            <option value="shopping" <?php if ($category == "shopping") echo "selected"; ?>>Shopping</option>
                            <option value="bills" <?php if ($category == "bills") echo "selected"; ?>>Bills</option>
                            <option value="healthcare" <?php if ($category == "healthcare") echo "selected"; ?>>Healthcare</option>
                            <option value="education" <?php if ($category == "education") echo "selected"; ?>>Education</option>
                            <option value="entertainment" <?php if ($category == "entertainment") echo "selected"; ?>>Entertainment</option>
                            <option value="others" <?php if ($category == "others") echo "selected"; ?>>Others</option>
                        </select>
                    </div>
                    <div class="month-filter">
                        <select name="month" onchange="this.form.submit()">
                            <option value="allmonths" <?php if ($month == "allmonths") echo "selected"; ?>>All Months</option>
                            <option value="1" <?php if ($month == "1") echo "selected"; ?>>January</option>
                            <option value="2" <?php if ($month == "2") echo "selected"; ?>>February</option>
                            <option value="3" <?php if ($month == "3") echo "selected"; ?>>March</option>
                            <option value="4" <?php if ($month == "4") echo "selected"; ?>>April</option>
                            <option value="5" <?php if ($month == "5") echo "selected"; ?>>May</option>
                            <option value="6" <?php if ($month == "6") echo "selected"; ?>>June</option>
                            <option value="7" <?php if ($month == "7") echo "selected"; ?>>July</option>
                            <option value="8" <?php if ($month == "8") echo "selected"; ?>>August</option>
                            <option value="9" <?php if ($month == "9") echo "selected"; ?>>September</option>
                            <option value="10" <?php if ($month == "10") echo "selected"; ?>>October</option>
                            <option value="11" <?php if ($month == "11") echo "selected"; ?>>November</option>
                            <option value="12" <?php if ($month == "12") echo "selected"; ?>>December</option>
                        </select>
                    </div>
                    <div class="reset">
                        <button class="reset-btn" type="button" onclick="window.location='transaction.php'">Reset Filters</button>
                    </div>
            </form>
            <div class="export">
                <a href="export_transaction.php?search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>&category=<?php echo urlencode($category); ?>&month=<?php echo urlencode($month); ?>"
                    class="export-btn"
                    style="text-decoration:none;font-size:13px;" id="exportLink">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>Export</span>
                </a>
            </div>
        </div>
        <table>
            <tr>
                <th>Date</th>
                <th>Category</th>
                <th>Description</th>
                <th>Amount</th>
                <th>Type</th>
                <th>Action</th>
            </tr>
            <?php
            if (mysqli_num_rows($transaction_result) > 0) {
                while ($row = mysqli_fetch_assoc($transaction_result)) {
            ?>
                    <tr>
                        <td>
                            <div class="date">
                                <i class="fa-regular fa-calendar"
                                    style="color: rgb(129, 129, 129);background-color: rgb(234, 234, 234);border-radius: 50%;padding: 5px;"></i>
                                <span><?php echo date('d/m/y', strtotime($row['transaction_date'])); ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="category">
                                <?php
                                if ($row['category'] == 'salary') {
                                    echo '<img src="../assets/images/salary.png" alt="food" height="20" width="20">';
                                } else if ($row['category'] == 'freelance') {
                                    echo '<img src="../assets/images/freelance.png" alt="food" height="20" width="20">';
                                } else if ($row['category'] == 'business') {
                                    echo '<img src="../assets/images/business.png" alt="food" height="20" width="20">';
                                } else if ($row['category'] == 'investment') {
                                    echo '<img src="../assets/images/investment.png" alt="food" height="20" width="20">';
                                } else if ($row['category'] == 'others') {
                                    echo '<img src="../assets/images/others.png" alt="food" height="20" width="20">';
                                } else if ($row['category'] == 'food') {
                                    echo '<img src="../assets/images/food.png" alt="food" height="20" width="20">';
                                } else if ($row['category'] == 'travel') {
                                    echo '<img src="../assets/images/travel.png" alt="food" height="20" width="20">';
                                } else if ($row['category'] == 'shopping') {
                                    echo '<img src="../assets/images/shopping.png" alt="food" height="20" width="20">';
                                } else if ($row['category'] == 'bills') {
                                    echo '<img src="../assets/images/bill.png" alt="food" height="20" width="20">';
                                } else if ($row['category'] == 'healthcare') {
                                    echo '<img src="../assets/images/health.png" alt="food" height="20" width="20">';
                                } else if ($row['category'] == 'education') {
                                    echo '<img src="../assets/images/education.png" alt="food" height="20" width="20">';
                                } else if ($row['category'] == 'entertainment') {
                                    echo '<img src="../assets/images/entertainment.png" alt="food" height="20" width="20">';
                                } else {
                                    echo '<img src="../assets/images/other.png" alt="food" height="20" width="20">';
                                }
                                ?>
                                <span><?php echo ucfirst($row['category']); ?></span>
                            </div>
                        </td>
                        <td><?php echo !empty($row['description']) ? $row['description'] : '-'; ?></td>
                        <td>
                            <?php
                            if ($row['type'] == 'Income') {
                                echo '<span style="color: rgb(51, 224, 51);font-weight: bold;">+₹' . number_format($row['amount'], 2) . '</span>';
                            } else {
                                echo '<span style="color: red;font-weight: bold;">-₹' . number_format($row['amount'], 2) . '</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if ($row['type'] == 'Income') {
                                echo '<span class="type-income">Income</span>';
                            } else {
                                echo '<span class="type-expense">Expense</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="delete_transaction.php?id=<?php echo $row['id']; ?>&type=<?php echo strtolower(($row['type'])); ?>" class="deleteLink" aria-label="Delete transaction">
                                    <img src="../assets/images/delete.png" alt="delete" width="25" height="25">
                                </a>
                            </div>
                        </td>

                    </tr>

                <?php
                }
            } else {
                ?>
                <tr>
                    <td colspan="5">No transactions match your search yet. Try adjusting your filters or add a new income/expense entry.</td>
                </tr>
            <?php
            }
            ?>
        </table>
        <div class="pagination">
            <?php if ($total_transactions == 0) {
                $start_record = 0;
                $end_record = 0;
            }
            if ($end_record > $total_transactions) {
                $end_record = $total_transactions;
            }
            ?>
            <p>Showing <?php echo $start_record; ?> to <?php echo $end_record; ?> of <?php echo $total_transactions; ?> transactions</p>
            <div class="pagination-btn">
                <?php
                if ($page > 1) { ?>
                    <a href="transaction.php?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $type; ?>&category=<?php echo $category; ?>&month=<?php echo $month; ?>">
                        <button class="page-btn">&laquo; Previous</button>
                    </a>
                <?php
                }
                ?>

                <?php
                for ($i = 1; $i <= $total_pages; $i++) { ?>
                    <a href="transaction.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $type; ?>&category=<?php echo $category; ?>&month=<?php echo $month; ?>">
                        <button class="page-number <?php if ($page == $i) echo 'active'; ?>"><?php echo $i; ?>
                        </button>
                    </a>
                <?php
                }
                ?>

                <?php
                if ($page < $total_pages) { ?>
                    <a href="transaction.php?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $type; ?>&category=<?php echo $category; ?>&month=<?php echo $month; ?>">
                        <button class="page-btn">&raquo; Next</button>
                    </a>
                <?php
                }
                ?>

            </div>
        </div>
    </div>
    </div>
    <dialog id="exportDialog">
        <h3>Export Transactions</h3>
        <p>Do you want to export your transaction records?</p>
        <div class="dialog-buttons">
            <button id="downloadbtn">Download</button>
            <button id="cancelbtn">Cancel</button>
        </div>
    </dialog>
    <dialog id="deleteDialog">
        <h3>Delete Transaction</h3>
        <p>Do you want to delete this transaction record?</p>
        <div class="dialog-buttons">
            <button id="deletebtn">Delete</button>
            <button id="notDeletebtn">Cancel</button>
        </div>
    </dialog>
    <dialog id="logoutDialog">
        <h3>Logout</h3>
        <p>Are you sure you want to logout?</p>
        <div class="dialog-buttons">
            <button id="logoutbtn">Logout</button>
            <button id="cancelBtn">Cancel</button>
        </div>
    </dialog>
    <script>
        const exportLink = document.getElementById("exportLink");
        const exportDialog = document.getElementById("exportDialog");

        exportLink.addEventListener("click", function(e) {
            e.preventDefault();
            exportDialog.showModal();
        });

        document.getElementById("cancelbtn").addEventListener("click", function() {
            exportDialog.close();
        });

        document.getElementById("downloadbtn").addEventListener("click", function() {
            exportDialog.close();
            window.location.href = exportLink.href;
        });

        const deleteLink = document.querySelectorAll(".deleteLink");
        const deleteDialog = document.getElementById("deleteDialog");
        let deleteUrl = "";
        deleteLink.forEach(function(link) {
            link.addEventListener("click", function(e) {
                e.preventDefault();
                deleteUrl=this.href;
                deleteDialog.showModal();
            })
        });

        document.getElementById("notDeletebtn").addEventListener("click", function() {
            deleteDialog.close();
        });

        document.getElementById("deletebtn").addEventListener("click", function() {
            deleteDialog.close();
            window.location.href = deleteUrl;
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

        document.getElementById("cancelBtn").addEventListener("click", function() {
            logoutDialog.close();
        });

        document.getElementById("logoutbtn").addEventListener("click", function() {
            logoutDialog.close();
            window.location.href = logoutLink.href;
        });
    </script>
</body>

</html>