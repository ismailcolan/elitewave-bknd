RBAC rollout — REVERTED 2026-11-02 (again)

Production PHP restored from this folder:
  connect.php → include/connect.php
  header.php  → include/header.php
  menu.php    → include/menu.php
  save_details.php → save_details.php
  index.php, login.php → web/

Also restored manually:
  user_list.php, users.php, fetch_details.php (legacy AD/USER/DR)

Removed RBAC PHP:
  include/rbac.php, rbac_config.php, rbac_schema.php, rbac_matrix.php,
  rbac_save_guard.php, rbac_permissions.js, rbac_ui.js
  rbac_install.php, role_permissions.php

Dropped MySQL tables:
  ew_app_role, ew_app_module, ew_app_screen, ew_role_permission,
  ew_role_screen_permission, ew_super_page, ew_permission_audit

Static design preview kept (not wired):
  web/rbac_ui_preview.html
  web/rbac_ui_preview.data.js

App menu/access is back to legacy $_SESSION['role'] checks (AD / USER / DR / CL).
