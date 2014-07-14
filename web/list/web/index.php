<?php
session_start();
$TAB = 'WEB';

// Main include
include($_SERVER['DOCUMENT_ROOT']."/inc/main.php");

// Header
include($_SERVER['DOCUMENT_ROOT'].'/templates/header.html');

// Panel
top_panel($user,$TAB);

// Data
exec (VESTA_CMD."v-list-web-domains $user json", $output, $return_var);
$data = json_decode(implode('', $output), true);
$data = array_reverse($data,true);
//Application proxys
exec (VESTA_CMD."v-list-app-proxys $user json", $app_output, $app_return_var);
$applications = json_decode(implode('', $app_output), true);
$applications = array_reverse($applications,true);
if ($_SESSION['user'] == 'admin') {
    include($_SERVER['DOCUMENT_ROOT'].'/templates/admin/list_web.html');
} else {
    include($_SERVER['DOCUMENT_ROOT'].'/templates/user/list_web.html');
}

// Back uri
$_SESSION['back'] = $_SERVER['REQUEST_URI'];

// Footer
include($_SERVER['DOCUMENT_ROOT'].'/templates/footer.html');
