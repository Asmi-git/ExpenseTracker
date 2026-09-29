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

// Find out which optional columns actually exist on the users table,
// so this page works whether or not "username"/"phone"/timestamp columns were added.
$available_columns = [];
$columns_result = mysqli_query($conn, "SHOW COLUMNS FROM users");
if ($columns_result) {
    while ($col = mysqli_fetch_assoc($columns_result)) {
        $available_columns[] = $col['Field'];
    }
}
$has_username = in_array('username', $available_columns);
$has_phone = in_array('phone', $available_columns);
$has_created_at = in_array('created_at', $available_columns);
$has_password_updated_at = in_array('password_updated_at', $available_columns);

$user_query = "SELECT * FROM users WHERE id='$user_id'";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

if (!$user) {
    // Account no longer exists (e.g. was just deleted) - send back to login.
    session_destroy();
    header("Location:../auth/login.php");
    exit();
}

$display_username = $has_username && !empty($user['username']) ? $user['username'] : $user_name;
$display_phone = $has_phone ? ($user['phone'] ?? '') : '';
$member_since = $has_created_at && !empty($user['created_at'])
    ? date("F Y", strtotime($user['created_at']))
    : 'N/A';
$password_last_updated = $has_password_updated_at && !empty($user['password_updated_at'])
    ? date("d M Y", strtotime($user['password_updated_at']))
    : 'N/A';

// ---- Profile Statistics (lifetime totals, distinct from the Reports page charts) ----
$income_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM income WHERE user_id='$user_id'"))['cnt'] ?? 0;
$expense_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM expense WHERE user_id='$user_id'"))['cnt'] ?? 0;
$total_transactions = $income_count + $expense_count;

$total_income_all = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM income WHERE user_id='$user_id'"))['total'] ?? 0;
$total_expense_all = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM expense WHERE user_id='$user_id'"))['total'] ?? 0;
$lifetime_balance = $total_income_all - $total_expense_all;
$lifetime_balance_color = $lifetime_balance >= 0 ? 'rgb(51,224,51)' : 'red';

$profileSuccess = $_SESSION['profileSuccess'] ?? null;
$profileError = $_SESSION['profileError'] ?? null;
unset($_SESSION['profileSuccess'], $_SESSION['profileError']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | Expense Tracker</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
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
        <h2>Profile
            <span class="account">
                <a href="../profile/profile.php">
                    <span class="account-1st"><?php echo $first_letter; ?></span>
                    <span class="account-name"><?php echo ucfirst(strtolower($user_name)); ?></span>
                    <span class="account-arrow"><i class="fa-solid fa-angle-down" style="color: rgb(129, 109, 243);"></i></span>
                </a>
            </span>
        </h2>
        <p>Manage your account information and preferences</p>

        <?php if ($profileSuccess) { ?>
            <div class="form-message success"><?php echo htmlspecialchars($profileSuccess); ?></div>
        <?php } ?>
        <?php if ($profileError) { ?>
            <div class="form-message error"><?php echo htmlspecialchars($profileError); ?></div>
        <?php } ?>

        <div class="profile-layout">
            <div class="insight-card profile-summary">
                <div class="avatar-large"><?php echo htmlspecialchars($first_letter); ?></div>
                <h3><?php echo htmlspecialchars($user_name); ?></h3>
                <p class="email"><?php echo htmlspecialchars($user['email']); ?></p>
                <div class="info-row"><span>Member Since</span><strong><?php echo htmlspecialchars($member_since); ?></strong></div>
                <div class="info-row"><span>Account Status</span><span class="type-income">Active</span></div>
            </div>

            <div class="profile-details">
                <div class="insight-card form-card">
                    <div class="header">
                        <h4><i class="fa-solid fa-user-pen" style="color: rgb(76, 73, 255);font-size:16px;"></i> Account Information</h4>
                    </div>
                    <form action="profile_process.php" method="POST" id="profileForm">
                        <div class="form-grid">
                            <div class="field">
                                <label for="fullname">Full Name</label>
                                <input id="fullname" name="fullname" type="text" value="<?php echo htmlspecialchars($user_name); ?>" required minlength="3">
                            </div>
                            <div class="field">
                                <label for="email">Email Address</label>
                                <input id="email" name="email" type="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="field">
                                <label for="username">Username</label>
                                <input id="username" name="username" type="text" value="<?php echo htmlspecialchars($display_username); ?>"
                                    <?php echo $has_username ? '' : 'readonly'; ?>>
                                <?php if (!$has_username) { ?>
                                    <p class="field-hint">This account doesn't have a separate username yet - it mirrors your full name.</p>
                                <?php } ?>
                            </div>
                            <div class="field">
                                <label for="phone">Phone Number</label>
                                <input id="phone" name="phone" type="text" value="<?php echo htmlspecialchars($display_phone); ?>"
                                    placeholder="<?php echo $has_phone ? 'Enter phone number' : 'Not available for this account'; ?>"
                                    <?php echo $has_phone ? '' : 'readonly'; ?>>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="field full">
                                <label for="current-pass">Current Password</label>
                                <input id="current-pass" name="current_password" type="password" placeholder="Only needed to change your password">
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="field">
                                <label for="new-pass">New Password</label>
                                <input id="new-pass" name="new_password" type="password" placeholder="Enter new password">
                            </div>
                            <div class="field">
                                <label for="confirm-pass">Confirm New Password</label>
                                <input id="confirm-pass" name="confirm_password" type="password" placeholder="Confirm new password">
                                <p class="field-hint" id="passwordMismatchHint" style="display:none;color:red;">Passwords do not match.</p>
                            </div>
                        </div>

                        <button class="update-btn" type="submit" name="update_profile">
                            <i class="fa-solid fa-lock" style="color:white;"></i> Update Profile
                        </button>
                    </form>
                </div>

                <div class="insight-card form-card">
                    <div class="header">
                        <h4><i class="fa-solid fa-gear" style="color: rgb(129, 109, 243);font-size:16px;"></i> Account Settings</h4>
                    </div>
                    <div class="delete-row" id="deleteAccountTrigger" role="button" tabindex="0" aria-label="Delete account">
                        <div class="delete-icon"><i class="fa-solid fa-trash" style="color: rgb(255, 46, 46);"></i></div>
                        <div class="delete-text">
                            <div class="title">Delete Account</div>
                            <div class="sub">Permanently delete your account and all data</div>
                        </div>
                        <div class="chevron"><i class="fa-solid fa-angle-right"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <h4>Profile Overview</h4>
        <div class="insights-grid">
            <div class="insight-card">
                <div class="header">
                    <h4><i class="fa-solid fa-chart-pie" style="color: rgb(76, 73, 255);font-size:16px;"></i> Profile Statistics</h4>
                </div>
                <div class="info-row"><span>Total Transactions</span><strong><?php echo $total_transactions; ?></strong></div>
                <div class="info-row"><span>Income Entries</span><strong><?php echo $income_count; ?></strong></div>
                <div class="info-row"><span>Expense Entries</span><strong><?php echo $expense_count; ?></strong></div>
                <div class="info-row"><span>Lifetime Balance</span><strong style="color:<?php echo $lifetime_balance_color; ?>">₹<?php echo number_format($lifetime_balance, 2); ?></strong></div>
            </div>

            <div class="insight-card">
                <div class="header">
                    <h4><i class="fa-solid fa-shield-halved" style="color: rgb(51, 224, 51);font-size:16px;"></i> Account Security</h4>
                </div>
                <div class="info-row"><span>Password</span><span class="type-income">Protected</span></div>
                <div class="info-row"><span>Password Last Updated</span><strong><?php echo htmlspecialchars($password_last_updated); ?></strong></div>
                <p class="savings-sub">Use a strong password and update it periodically to keep your account secure.</p>
            </div>
        </div>
    </div>

    <dialog id="deleteAccountDialog">
        <h3>Delete Account</h3>
        <p>This will permanently delete your account and all of your income/expense records. This cannot be undone.</p>
        <form action="delete_account.php" method="POST">
            <div class="dialog-buttons">
                <button type="submit" name="confirm_delete" id="confirmDeleteBtn">Delete Account</button>
                <button type="button" id="cancelDeleteBtn">Cancel</button>
            </div>
        </form>
    </dialog>

    <dialog id="logoutDialog">
        <h3>Logout</h3>
        <p>Are you sure you want to logout?</p>
        <div class="dialog-buttons">
            <button id="logoutbtn">Logout</button>
            <button id="cancelbtn">Cancel</button>
        </div>
    </dialog>

    <script>

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
        const logoutLink = document.getElementById("logoutLink");
        const logoutDialog = document.getElementById("logoutDialog");

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

        const deleteAccountTrigger = document.getElementById("deleteAccountTrigger");
        const deleteAccountDialog = document.getElementById("deleteAccountDialog");
        deleteAccountTrigger.addEventListener("click", function() {
            deleteAccountDialog.showModal();
        });
        deleteAccountTrigger.addEventListener("keydown", function(e) {
            if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                deleteAccountDialog.showModal();
            }
        });
        document.getElementById("cancelDeleteBtn").addEventListener("click", function() {
            deleteAccountDialog.close();
        });

        const profileForm = document.getElementById("profileForm");
        const newPass = document.getElementById("new-pass");
        const confirmPass = document.getElementById("confirm-pass");
        const mismatchHint = document.getElementById("passwordMismatchHint");
        profileForm.addEventListener("submit", function(e) {
            if (newPass.value.length > 0 || confirmPass.value.length > 0) {
                if (newPass.value !== confirmPass.value) {
                    e.preventDefault();
                    mismatchHint.style.display = "block";
                    return;
                }
            }
            mismatchHint.style.display = "none";
        });
    </script>
</body>

</html>
