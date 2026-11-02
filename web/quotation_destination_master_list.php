<?php
require_once('include/ew_quotation_module_flag.php');
ew_quotation_module_deny_web();
header('Location: quotation_destination_master.php');
exit;
