<?php
session_start();

$_SESSION['host'] = $_REQUEST['host'];
$_SESSION['port'] = $_REQUEST['port'];
$_SESSION['passwd'] = $_REQUEST['passwd'];
$_SESSION['id'] = session_id();
$_SESSION['socket'] = '/tmp/vnc_' . $_SESSION['id'];

header('Location: vncview.php');
