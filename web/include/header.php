<?php
if (isset($_SESSION['LAST_ACTIVITY'])) {
  if (empty($_SESSION['admin_id']) && empty($_SESSION['LAST_ACTIVITY']) or (time() - $_SESSION['LAST_ACTIVITY'] > 103600)) {
    echo '<script> location.href="index.php"; </script>';
    exit;
  }
} else if (!isset($_SESSION['admin_id'])) {
  echo '<script> location.href="index.php"; </script>';
  exit;
} else {
  echo '<script> location.href="index.php"; </script>';
  exit;
}
require_once ('include/function.php');
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
  --sidebar-width:     260px;
  --sidebar-collapsed: 64px;
  --top-bar-h:         64px;
  --ew-navy:           #06416F;
  --ew-navy-deep:      #042C4A;
  --ew-navy-light:     #0A5688;
  --ew-primary:        #06416F;
  --ew-accent:         #DD111E;
  --ew-accent-deep:    #A50D17;
  --ew-border:         #D8DDE5;
  --ew-text:           #1A2332;
  --ew-text-muted:     #6B7A8D;
  --font-display:      'Space Grotesk', sans-serif;
}

/* ========== TOP BAR ========== */
.top-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: var(--top-bar-h);
  background: #fff;
  border-bottom: 1px solid var(--ew-border);
  display: flex;
  align-items: center;
  padding: 0 20px 0 16px;
  z-index: 1100;
  gap: 0;
  box-shadow: 0 2px 10px rgba(6,65,111,.06);
}

body.page-header-fixed {
  padding-top: var(--top-bar-h) !important;
}

.navbar.navbar-fixed-top.scroll-hide {
  min-height: 0 !important;
  height: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
  border: 0 !important;
  background: transparent !important;
  box-shadow: none !important;
}

.main-content {
  margin-left: var(--sidebar-width) !important;
}

.main-content.collapsed {
  margin-left: var(--sidebar-collapsed) !important;
}

/* ---- Hamburger button ---- */
.hamburger-btn {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  border: none;
  background: transparent;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 5px;
  flex-shrink: 0;
  transition: background 0.2s ease;
  margin-right: 12px;
}
.hamburger-btn:hover { background: #EEF3F7; }

.hamburger-btn .hb-line {
  display: block;
  width: 20px;
  height: 2px;
  border-radius: 2px;
  background: var(--ew-navy);
  transition: transform 0.3s ease, opacity 0.2s ease;
  transform-origin: center;
}
.hamburger-btn.is-open .hb-line:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.hamburger-btn.is-open .hb-line:nth-child(2) { opacity: 0; transform: scaleX(0); }
.hamburger-btn.is-open .hb-line:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

/* ---- Logo ---- */
.top-bar .app-logo {
  width: auto;
  max-width: 150px;
  display: block;
  object-fit: contain;
  flex-shrink: 0;
}

/* ---- Spacer ---- */
.top-bar .tb-spacer { flex: 1; }

/* ---- Right cluster ---- */
.top-bar .top-right {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
}

/* notification bell */
.top-bar .notif-bell {
  position: relative;
  display: flex !important;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  border-radius: 10px;
  cursor: pointer;
  transition: background 0.2s ease, transform 0.15s ease;
}
.top-bar .notif-bell:hover { background: #EEF3F7; transform: scale(1.05); }
.top-bar .notif-bell i { font-size: 17px; color: var(--ew-navy); opacity: .7; }
.top-bar .notif-bell .notif-count {
  position: absolute;
  top: 2px; right: 2px;
  background: var(--ew-accent);
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  min-width: 16px;
  height: 16px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 3px;
  font-family: 'Inter', sans-serif;
  box-shadow: 0 0 0 2px #fff;
}
.top-bar .notif-bell .dropdown-menu1 {
  right: 0; left: auto;
  top: 48px;
  min-width: 300px;
  max-width: 360px;
  background: #fff;
  border: 1px solid var(--ew-border);
  border-radius: 14px;
  box-shadow: 0 14px 40px rgba(6,65,111,.16);
  padding: 6px;
  list-style: none;
}
.top-bar .notif-bell .dropdown-menu1 li a {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 14px;
  font-size: 0.85rem;
  color: var(--ew-text);
  border-radius: 8px;
  transition: background 0.15s;
}
.top-bar .notif-bell .dropdown-menu1 li a:hover { background: #E8EDF4; }

/* divider */
.top-bar .header-divider {
  width: 1px;
  height: 28px;
  background: var(--ew-border);
  flex-shrink: 0;
}

/* company name */
.top-bar .applicatoin-name {
  font-family: var(--font-display);
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--ew-navy);
  white-space: nowrap;
}

/* ---- User block ---- */
.top-bar .top-left {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  padding: 6px 12px 6px 6px;
  border-radius: 10px;
  transition: background 0.2s ease, border-color 0.2s ease;
  position: relative;
  border: 1px solid transparent;
}
.top-bar .top-left:hover { background: #EEF3F7; border-color: var(--ew-border); }

.top-bar .user-avatar {
  width: 36px; height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--ew-navy), var(--ew-navy-deep));
  color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700;
  font-family: var(--font-display);
  font-size: 13px;
  flex-shrink: 0;
  overflow: hidden;
  border: 2px solid #E8EDF4;
}
.top-bar .user-avatar img {
  width: 100%; height: 100%;
  object-fit: cover;
  border-radius: 50%;
}

.top-bar .user-meta {
  display: flex;
  flex-direction: column;
  line-height: 1.25;
  min-width: 0;
}
.top-bar .user-meta .user-name {
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--ew-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.top-bar .user-meta .user-role {
  font-size: 0.72rem;
  color: var(--ew-text-muted);
  font-weight: 500;
}
.top-bar .top-left .user-caret {
  font-size: 11px;
  color: var(--ew-text-muted);
  margin-left: 2px;
  flex-shrink: 0;
  transition: transform 0.2s ease;
}
.top-bar .top-left.menu-open .user-caret { transform: rotate(180deg); }

/* User dropdown */
.top-bar .top-left .user-dropdown-menu {
  display: none;
  position: absolute;
  top: 54px; right: 0;
  padding: 6px;
  left: auto;
  background: #fff;
  border: 1px solid var(--ew-border);
  border-radius: 14px;
  box-shadow: 0 14px 40px rgba(6,65,111,.16);
  min-width: 200px;
  z-index: 999;
  overflow: hidden;
  list-style: none;
}
.top-bar .top-left.menu-open .user-dropdown-menu { display: block; }

.top-bar .top-left .user-dropdown-menu li a {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 14px;
  font-size: 0.85rem;
  font-weight: 500;
  color: var(--ew-text);
  text-decoration: none;
  border-radius: 8px;
  transition: background 0.15s;
}
.top-bar .top-left .user-dropdown-menu li a:hover { background: #EEF3F7; color: var(--ew-navy); }
.top-bar .top-left .user-dropdown-menu li a i { color: var(--ew-text-muted); width: 16px; text-align: center; }

.top-bar .top-left .user-dropdown-menu li:last-child a { color: var(--ew-accent); }
.top-bar .top-left .user-dropdown-menu li:last-child a i { color: var(--ew-accent); }

@media (max-width: 767px) {
  .top-bar { padding: 0 10px 0 12px; }
  .top-bar .app-logo { max-width: 120px; }
  .top-bar .applicatoin-name { display: none !important; }
  .top-bar .header-divider { display: none; }
  .top-bar .user-meta { display: none; }
  body.page-header-fixed {
    padding-top: var(--top-bar-h) !important;
  }
  .main-content,
  .main-content.collapsed {
    margin-left: 0 !important;
    padding-top: 12px;
  }
  .new_dpt_bottom {
    margin-bottom: 16px !important;
  }
}
</style>

<div class="top-bar" id="topBar">

  <a href="dashboard.php" style="display:flex;align-items:center;text-decoration:none;">
    <img src="./images/elitewave-light.png" class="app-logo" alt="Elite Wave 360">
  </a>

  <div class="tb-spacer"></div>

  <button class="hamburger-btn is-open" id="hamburgerBtn" onclick="toggleSidebar()" title="Toggle sidebar" aria-label="Toggle sidebar">
    <span class="hb-line"></span>
    <span class="hb-line"></span>
    <span class="hb-line"></span>
  </button>

  <div class="top-right">

    <?php
    $user_inq = 'select * from user_inquiry_list where status=0';
    $res_inq = mysqli_query($conn, $user_inq);
    $inq_count = mysqli_num_rows($res_inq);
    if ($_SESSION['role'] == 'AD'):
      ?>
      <div class="dropdowns notifications hidden-xs notif-bell" data-toggle="dropdown">
        <i class="fa fa-bell" aria-hidden="true"></i>
        <span class="notif-count counter"></span>
        <ul class="dropdown-menu dropdown-menu1"></ul>
      </div>
    <?php endif; ?>

    <?php if ($_SESSION['role'] == 'CL'):
      $query33 = "select * from client where client_id IN (select company_name from users where user_id='" . $_SESSION['user_id'] . "')";
      $result33 = mysqli_query($conn, $query33);
      $row33 = mysqli_fetch_array($result33);
      echo '<span class="applicatoin-name" style="text-transform:uppercase;">' . $row33['client_company_name'] . '</span>';
    endif; ?>

    <div class="header-divider"></div>

    <div class="top-left" id="userMenuToggle">
      <?php $username = get_user($conn, $_SESSION['user_id']); ?>
      <div class="user-avatar">
        <img src="images/no_profile.png" alt="">
      </div>
      <div class="user-meta">
        <span class="user-name"><?php echo $username; ?></span>
        <span class="user-role">
          <?php
          if ($_SESSION['role'] == 'AD')
            echo 'Administrator';
          elseif ($_SESSION['role'] == 'DR')
            echo 'Driver';
          elseif ($_SESSION['role'] == 'CL')
            echo 'Client';
          else
            echo 'User';
          ?>
        </span>
      </div>
      <i class="fa fa-caret-down user-caret"></i>

      <ul class="user-dropdown-menu">
        <li><a href="change_password.php"><i class="fa fa-key"></i> Change Password</a></li>
        <li><a href="logout.php"><i class="fa fa-sign-out"></i> Logout</a></li>
      </ul>
    </div>

  </div>
</div>

<div class="loading-page"><img src="images/ajax_loader.gif" /></div>
<div class="form-data-saving" style="display:none;"><img src="images/loading.png" /></div>

<script>
$(document).ready(function(){

  $(document).on('mouseover click', function() {
    $.ajax({ url:"include/sessions_check.php", type:"POST",
      success:function(login){ if(login==0) window.location.href='logout.php'; }
    });
  });

  let idleTime = 0;
  let idleTimeout = 15 * 60 * 1000;
  $(document).on('mouseover', function(){ idleTime = 0; });

  setInterval(function(){
    idleTime += 5000;
    if(idleTime >= idleTimeout){
      if(typeof ewToast==='function') ewToast('Session expired. Please login again.','error');
      else alert('Session expired please login again');
      window.location.href = 'logout.php';
    } else {
      $.ajax({ url:"include/sessions_check.php", type:"POST",
        success:function(login){
          if(login==0){
            if(typeof ewToast==='function') ewToast('Session expired. Please login again.','error');
            else alert('Session expired please login again');
            window.location.href='logout.php';
          }
        }
      });
    }
  }, 5000);

  function load_unseen_notification(view=''){
    $.ajax({ url:"./fetch_details.php", type:"post",
      data:{ view:view, cmd:"get_notification_rfp" }, dataType:"json",
      success:function(data){
        $('.dropdown-menu1').html(data.notification);
        if(data.unseen_notification > 0) $('.counter').html(data.unseen_notification);
        else $('.counter').text("0");
      }
    });
  }
  load_unseen_notification();

  $(document).on('click', '.notif-bell', function(e){
    $('.counter').html('');
    load_unseen_notification('yes');
    $(this).find('.dropdown-menu1').toggle();
    e.stopPropagation();
  });

  setInterval(function(){ load_unseen_notification(); }, 5000);

  $(document).on('click', '#userMenuToggle', function(e){
    $(this).toggleClass('menu-open');
    e.stopPropagation();
  });
  $(document).on('click', function(){
    $('#userMenuToggle').removeClass('menu-open');
    $('.dropdown-menu1').hide();
  });

  var sidebarCollapsed = localStorage.getItem('sidebar_collapsed') === '1';
  if (window.matchMedia('(max-width: 767px)').matches) {
    $('#hamburgerBtn').removeClass('is-open');
  } else if(!sidebarCollapsed){
    $('#hamburgerBtn').addClass('is-open');
  } else {
    $('#hamburgerBtn').removeClass('is-open');
  }

  /* Fallback: always hide loading overlay — $(window).load() can be unreliable */
  $('.loading-page').hide();
  setTimeout(function(){ $('.loading-page').hide(); }, 1500);
  setTimeout(function(){ $('.loading-page').hide(); }, 4000);

});
$(window).load(function(){ $('.loading-page').hide(); });
</script>