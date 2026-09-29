<?php
session_start();
include '../config/db.php';
/** @var mysqli $conn */

if (!isset($_SESSION['user_id'])) {
    header("Location:../auth/login.php");
    exit();
}
$user_id=$_SESSION['user_id'];

$search=trim($_GET['search'] ?? "");
$type=trim($_GET['type'] ?? 'alltypes');
$category=trim($_GET['category'] ?? 'allcategories');
$month=trim($_GET['month'] ?? 'allmonths');

$income_type="";
$expense_type="";
if($type!='alltypes'){
    $income_type="AND '$type'='alltypes' or '$type'='income'";
    $expense_type="AND '$type'='alltypes' or '$type'='expense'";

}

$income_category="";
$expense_category="";
if($category!='allcategories'){
    $income_category="AND source='$category'";
    $expense_category="AND category='$category'";
}

$income_month="";
$expense_month="";
if($month!='allmonths'){
    $income_month="AND month(income_date)=$month";
    $expense_month="AND month(expense_date)=$month";
}

$export_query = "SELECT income_date as transaction_date,
                           source as category,
                           description,
                           amount,
                           'Income' as type 
                           from income 
                           where user_id='$user_id' 
                           $income_type
                           $income_category
                           $income_month
                           and (source like '%$search%' or description like '%$search%' OR amount LIKE '%$search%'
    OR 'Income' LIKE '%$search%')
    
                    union all 
                    select expense_date as transaction_date,
                           category,
                           description,
                           amount,
                           'Expense' as type 
                           from expense 
                           where user_id='$user_id'
                           $expense_type
                           $expense_category
                           $expense_month
                           and (category like '%$search%' or description like '%$search%' OR amount LIKE '%$search%'
    OR 'Expense' LIKE '%$search%')
                           order by transaction_date desc";

$export_result=mysqli_query($conn,$export_query);

header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=transactions.csv");

$output=fopen("php://output","w");
fputcsv($output,array(
    "Date",
    "Category",
    "Description",
    "Amount",
    "Type"
));

while($row=mysqli_fetch_assoc($export_result)){
    fputcsv($output,array(
        $row['transaction_date'],
        ucfirst($row['category']),
        $row['description'],
        $row['amount'],
        $row['type']
    ));
}

fclose($output);
exit();
?>