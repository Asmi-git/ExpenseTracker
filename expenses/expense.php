<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location:../auth/login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$user_name=$_SESSION['user_name'];
$first_letter=strtoupper(substr($user_name,0,1));
include '../config/db.php';
/** @var mysqli $conn */

$expense_query = "SELECT * from expense where user_id='$user_id' order by expense_date desc";
$expense_result = mysqli_query($conn, $expense_query);

$total_expense_query = "SELECT sum(amount) as total_expense from expense where user_id='$user_id'";
$total_result = mysqli_query($conn, $total_expense_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_expense = $total_row['total_expense'] ?? 0;

$this_month_query = "SELECT sum(amount) as month_expense 
                    from expense 
                    where user_id='$user_id' 
                    and month(expense_date)=month(curdate()) 
                    and year(expense_date)=year(curdate())";
$month_result = mysqli_query($conn, $this_month_query);
$month_row = mysqli_fetch_assoc($month_result);
$month_expense = $month_row['month_expense'] ?? 0;

$category_query = "SELECT count(distinct category) as total_categories from expense where user_id='$user_id'";
$category_result = mysqli_query($conn, $category_query);
$category_row = mysqli_fetch_assoc($category_result);
$total_categories = $category_row['total_categories'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expenses | Expense Tracker</title>
    <link rel="stylesheet" href="../assets/css/income_expense.css">
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
                <li class="active"><a href="../expenses/expense.php"><i class="fa-solid fa-circle-arrow-down"
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
        <h2>Expense
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
            <a href="../expenses/expense.php">Expense</a>
        </nav>
        <div class="three-box">
            <div class="box1">
                <div class="img" style="background-color:rgb(253, 228, 228);">
                    <i class="fa-solid fa-arrow-down" style="color: rgb(255, 46, 46);"></i>
                </div>
                <div class="content">
                    <p class="one">Total Expenses</p>
                    <p class="two" style="color: red;">₹<?php echo number_format($total_expense,2); ?></p>
                    <p class="three">All time</p>
                </div>
            </div>
            <div class="box2">
                <div class="img" style="background-color: rgb(220, 220, 255);">
                    <i class="fa-solid fa-calendar-days" style="color: rgb(76, 73, 255);"></i>
                </div>
                <div class="content">
                    <p class="one">This Month Expense</p>
                    <p class="two" style="color:blue">₹<?php echo number_format($month_expense,2); ?></p>
                    <p class="three" id="currentDate"></p>
                </div>
            </div>
            <div class="box3">
                <div class="img" style="background-color: rgb(252, 226, 232);">
                    <i class="fa-solid fa-chart-pie" style="color: rgb(237, 101, 101)"></i>
                </div>
                <div class="content">
                    <p class="one">Expense Category</p>
                    <p class="two" style="color: rgb(253, 4, 29);"><?php echo $total_categories; ?></p>
                    <p class="three">Total categories</p>
                </div>
            </div>
        </div>
        <div class="two-box">
            <div class="left-box">
                <h4>Add New Expense</h4>
                <form action="expense_process.php" method="post" id="expense-form">
                    <div class="row">
                        <div class="field">
                            <label for="source">Expense Category <span style="color: red;">*</span></label>
                            <select id="source" name="category" required>
                                <option value="">--Select Category--</option>
                                <option value="food">Food</option>
                                <option value="travel">Travel</option>
                                <option value="shopping">Shopping</option>
                                <option value="bills">Bills</option>
                                <option value="healthcare">Healthcare</option>
                                <option value="education">Education</option>
                                <option value="entertainment">Entertainment</option>
                                <option value="others">Others</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="amount">Amount <span style="color: red;">*</span></label>
                            <input type="number" id="amount" name="amount" placeholder="Enter amount" required min="1">
                        </div>
                    </div>
                    <div class="field">
                        <label for="date">Date <span style="color: red;">*</span></label>
                        <input type="date" id="date" name="date" required>
                    </div>
                    <div class="field">
                        <label for="description">Description(optional)</label>
                        <textarea id="description" name="description" placeholder="Enter other details here..." rows="5"
                            cols="8" minlength="10" maxlength="50"></textarea>
                    </div>
                    <div class="field">
                        <button type="submit" id="submit-btn" name="add_expense" style="background: linear-gradient(to right,rgb(252, 104, 71),rgb(234, 43, 0));"><span><i class="fa-solid fa-circle-plus" style="color: white;font-size: 20px;"></i></span> Add
                    Expenses</button>
                    </div>
                </form>
            </div>
            <div class="right-box">
                <img src="../assets/images/expense-img.avif" alt="income-img" width="350" height="300">
                <p style="font-weight: bold;margin:auto 2px;">Track your all Expenses in one place</p>
                <p style="font-size: 13px;color: rgb(106, 105, 105);margin-top:12px;">Add your expense categories and<br>see where your money goes.</p>
            </div>
        </div>
        <div class="transaction">
            <h4>Recent Expense entries</h4>
            <table>
                <tr>
                    <th>Date</th>
                    <th>Source</th>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
                <?php
                if(mysqli_num_rows($expense_result)>0){
                    while($row=mysqli_fetch_assoc($expense_result)){
                ?>
                <tr>
                    <td>
                        <div class="date">
                            <i class="fa-regular fa-calendar"
                                style="color: rgb(129, 129, 129);background-color: rgb(234, 234, 234);border-radius: 50%;padding: 5px;"></i>
                            <span><?php echo $row['expense_date']; ?></span>
                        </div>
                    </td>
                    <td>
                        <div class="category">
                            <?php 
                            if($row['category']=='food'){
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
                    <td class="amount" style="color:red;font-weight: bold;">-₹<?php echo number_format($row['amount'],2); ?></td>
                </tr> 
                <?php
                    }
                }
                else{
                ?>
                <tr>
                    <td colspan="4">No expenses recorded yet. Use the form above to add your first one.</td>
                </tr>
                <?php
                }
                ?>
            </table>
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
    <script>
        const today = new Date();
        const month = today.toLocaleString('default', { month: 'long' });
        const year = today.getFullYear();
        document.getElementById("currentDate").innerHTML = month + " " + year;


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
    </script>
</body>

</html>