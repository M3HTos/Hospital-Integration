<?php
    include($_SERVER['DOCUMENT_ROOT'] . "/config.php");
    require_once($_SERVER['DOCUMENT_ROOT'] . "/classes/Database.php");
    require_once($_SERVER['DOCUMENT_ROOT'] . "/classes/Uploader.php");

    if ($debug) {
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
        error_reporting(E_ALL);
    }
    
    $database = new Database($host, $db, $user, $password);
    $uploader = new Uploader($database);
    $uploader->upload_xlsx("talons.xlsx");
?>