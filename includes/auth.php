<?php
function check_role($required_role) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $required_role) {
        header('Location: unauthorized.php');
        exit;
    }
}