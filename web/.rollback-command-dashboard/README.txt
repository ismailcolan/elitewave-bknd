rollback-command-dashboard — snapshot before Operations command center redesign
Created: 2026-11-03

Restore (from web/):
  cp .rollback-command-dashboard/dashboard.php dashboard.php
  cp .rollback-command-dashboard/cargo_ops_dashboard_app.php include/cargo_ops_dashboard_app.php
  cp .rollback-command-dashboard/cargo-ops-dashboard.css stylesheets/cargo-ops-dashboard.css
  cp .rollback-command-dashboard/cargo-ops-dashboard.js javascripts/cargo-ops-dashboard.js
  cp .rollback-command-dashboard/dashboard_tracking_data.php include/dashboard_tracking_data.php
  cp .rollback-command-dashboard/dashboard_ops_data.php include/dashboard_ops_data.php

Keeps Live tracking KPIs (incl. Cancelled booking) as of this snapshot.
