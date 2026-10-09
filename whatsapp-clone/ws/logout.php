<?php require_once __DIR__.'/config.php'; if($_SERVER['REQUEST_METHOD']==='POST' && verify_csrf($_POST['csrf']??null)){$chat->logout();} header('Location:login.php'); exit;
