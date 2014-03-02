<?php
session_start();

$host = $_SESSION['host'];
$port = $_SESSION['port'];
$passwd = $_SESSION['passwd'];
$socket = $_SESSION['socket'];
$sesid = $_COOKIE['PHPSESSID'];

?>
<html>
<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.0/jquery.min.js"></script>
<script src="js/keymap/kbmap.js"></script>
<script src="js/vncview.js.php"></script>

<button type="button" onclick="ctrlAltDel();">Ctrl+Alt+Del</button>
<button type="button" onclick="reconnect();">Reconnect</button>

<div id="vnccontainer">
  <canvas id="vncviewer" width="1" height="1" style="border: 1px solid; cursor:crosshair;"></canvas>
</div>

</html>
