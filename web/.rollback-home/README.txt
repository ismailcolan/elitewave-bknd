rollback-home — snapshot before Live Tracking redesign of admin dashboard.php
Created: 2026-11-03

Restore (from web/):
  cp .rollback-home/dashboard.php dashboard.php
  cp .rollback-home/cargo_ops_dashboard_app.php include/cargo_ops_dashboard_app.php
  cp .rollback-home/cargo-ops-dashboard.css stylesheets/cargo-ops-dashboard.css
  cp .rollback-home/cargo-ops-dashboard.js javascripts/cargo-ops-dashboard.js

Files:
  dashboard.php
  cargo_ops_dashboard_app.php  → include/
  cargo-ops-dashboard.css      → stylesheets/
  cargo-ops-dashboard.js       → javascripts/

Does NOT include CL/DR sections changes (those were left alone).
New files added during redesign (ajax/dashboard_tracking.php, include/city_coords.php)
are not in this snapshot — delete them on restore if present.
