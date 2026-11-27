GSTR-1 reporting rollback (2026-11-26)

New screens and services (live under web/):
  gstr1.php, gstr1_statistics.php, gstr1_not_relevant.php, gstr1_voucher_register.php,
  gstr1_b2b.php, gstr1_hsn.php, gstr1_data.php
  include/gstr1_*.php
  javascripts/gstr1-report.js

menu.php was backed up BEFORE GST rail + submenu were added.

Restore pre-GSTR-1 navigation:
  cp -a .rollback-gstr1/menu.php include/menu.php

Ship GSTR-1 menu (from feature branch):
  Use current include/menu.php with gst module entries.