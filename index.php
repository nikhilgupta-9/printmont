<?php
if($_SESSION['user_id']) {
    header("Location: dashboard.php");
    exit();
}else{
    header("Location: api/auth/login.php");
    exit();
}
?>