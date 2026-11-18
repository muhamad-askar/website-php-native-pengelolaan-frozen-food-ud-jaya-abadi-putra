<?php
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }
    require_once __DIR__.'/../config/koneksi.php';

    function require_login(){
        if(!isset($_SESSION['id_user'])){
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        }
    }

    function require_admin(){
        require_login();
        if(($_SESSION['role'] ?? '') !== 'admin'){
            header('Location: ' . BASE_URL . 'index.php');
            exit;
        }
    }
?>