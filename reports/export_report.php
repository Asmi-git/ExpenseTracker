<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location:../auth/login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

include '../config/db.php';
/** @var mysqli $conn */

$selectedCategory = trim($_GET['category'] ?? 'allcategories');
$selectedMonth = trim($_GET['month'] ?? 'allmonths');
$selectedYear = trim($_GET['year'] ?? 'allyears');
$selectedSource = trim($_GET['source'] ?? 'allsources');

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

header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=Expense_Report.csv");

$output=fopen("php://output","w");
fputcsv($output, [
    "Type",
    "Date",
    "Category / Source",
    "Description",
    "Amount"
]);
$income_query = "
SELECT
    income_date AS trans_date,
    source,
    description,
    amount
FROM income
$income_condition
ORDER BY income_date DESC
";
$income_result = mysqli_query($conn, $income_query);
while ($row = mysqli_fetch_assoc($income_result)) {
    fputcsv($output, [
        "Income",
        $row['trans_date'],
        ucfirst($row['source']),
        $row['description'],
        $row['amount']
    ]);

}
$expense_query = "
SELECT
    expense_date AS trans_date,
    category,
    description,
    amount
FROM expense
$expense_condition
ORDER BY expense_date DESC
";
$expense_result = mysqli_query($conn, $expense_query);
while ($row = mysqli_fetch_assoc($expense_result)) {
    fputcsv($output, [
        "Expense",
        $row['trans_date'],
        ucfirst($row['category']),
        $row['description'],
        $row['amount']
    ]);
}
fclose($output);
exit();
?>