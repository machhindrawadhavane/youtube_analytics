<?php
echo "<pre>";
session_start();
ini_set('max_execution_time',0);
date_default_timezone_set('Asia/Calcutta');
require "database_connection.php";
require "db_config.php";
$conn = new Database(DB_SERVER, DB_USER, DB_PASS,"athena_live");
$link_live = $conn->connect();

$query_users_list = "select distinct assign_script_writer_id from scripts where script_stop_time != '0' ";
$users = mysqli_fetch_array(mysqli_query($link_live,$query_users_list));

print_r($users);



  
