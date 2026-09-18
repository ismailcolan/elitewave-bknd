<?php
date_default_timezone_set('UTC');
$fh = fopen('/home/staging/public_html/web/automation/crontab.txt', 'a');
echo $fh;
echo fwrite($fh, date('Y-m-d H:i:s') . "\r\n");
fclose($fh);

?>