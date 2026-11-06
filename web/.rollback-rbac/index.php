<?php
require_once ('include/connect.php');

if (isset($_COOKIE['persistID'])) {
	$user_id = $_COOKIE['persistID'];

	$query = "select * from users where user_id='$user_id' and status=0";
	$result = mysqli_query($conn, $query) or die(mysqli_error());

	if (mysqli_num_rows($result) == 1) {
		$row = mysqli_fetch_array($result);
		$result = mysqli_query($conn, $query) or die(mysqli_error());
		$_SESSION['LAST_ACTIVITY'] = time();
		$_SESSION['role'] = $row['role'];
		$_SESSION['user_id'] = $row['user_id'];
		echo '<script> location.href="dashboard.php"; </script>';
	}
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="shortcut icon" href="<?php print_r(site_path); ?>images/elw_360_32_32-1.png">
  <title>Elite Wave 360</title>
<style>
  :root{
    --blue:#0A6EF5;
    --blue-hover:#0858C7;
    --blue-soft:#D9E8FA;
    --red:#C8232A;
    --ink:#111827;
    --muted:#64748B;
    --line:#CBD5E1;
    --error:#D8455A;
    --error-bg:#FCEAEC;
    --success:#1D9E75;
    --success-bg:#E8F8F1;
    --glass-fill:rgba(255,255,255,0.5);
    --glass-border:rgba(255,255,255,0.92);
    --glass-outer:rgba(255,255,255,0.38);
    --glass-highlight:rgba(255,255,255,0.98);
    /* Outer glass ring + light lift (no heavy drop shade) */
    --glass-shadow:
      0 0 0 1px var(--glass-outer),
      0 0 0 3px rgba(255,255,255,0.14),
      0 8px 24px rgba(15,23,42,0.06),
      inset 0 1px 0 var(--glass-highlight),
      inset 1px 0 0 rgba(255,255,255,0.45);
    --label-ink:#0F172A;
  }

  *, *::before, *::after{ box-sizing:border-box; margin:0; padding:0; }
  html, body{
    width:100%;
    height:100%;
    overflow:hidden;
    background:#040B14;
  }

  body{
    font-family:'Inter', sans-serif;
    color:var(--ink);
    -webkit-font-smoothing:antialiased;
    -moz-osx-font-smoothing:grayscale;
    text-rendering:optimizeLegibility;
  }

  .login-stage{
    position:fixed;
    inset:0;
    width:100%;
    height:100dvh;
    overflow:hidden;
  }

  .login-banner-frame{
    position:absolute;
    inset:0;
    background:#0a1a32;
    overflow:hidden;
    display:flex;
    align-items:center;
    justify-content:center;
    perspective:1400px;
    perspective-origin:58% 78%;
  }

  /* Full aspect ratio — no stretch; fills viewport as large as possible */
  .login-banner-frame img{
    display:block;
    width:100%;
    height:100%;
    /* object-fit:contain;
    object-position:center center; */
  }

  /* City row below the “Reliable Deliveries” icon strip (under the footer labels in the banner) */
  .marquee-bar{
    position:absolute;
    z-index:2;
    left:5%;
    right:34%;
    bottom:1.8%;
    top:auto;
    height:auto;
    pointer-events:none;
  }

  .ticker{
    width:100%;
    overflow:hidden;
    mask-image:linear-gradient(90deg, transparent, #000 4%, #000 96%, transparent);
    -webkit-mask-image:linear-gradient(90deg, transparent, #000 4%, #000 96%, transparent);
    padding:0;
    border:none;
  }

  .ticker-track{
    display:flex;
    flex-direction:row;
    align-items:center;
    width:max-content;
    animation:scrollTicker 34s linear infinite;
    will-change:transform;
  }

  @keyframes scrollTicker{
    from{ transform:translate3d(0, 0, 0); }
    to{ transform:translate3d(-50%, 0, 0); }
  }

  .ticker-item{
    display:flex;
    align-items:center;
    gap:10px;
    font-size:12px;
    font-weight:500;
    color:rgba(244,245,252,0.92);
    white-space:nowrap;
    padding:0 18px;
    line-height:1.2;
  }

  .ticker-item .dot{
    flex-shrink:0;
    width:4px;
    height:4px;
    border-radius:50%;
    background:var(--red);
  }

  .login-glass-panel{
    position:absolute;
    z-index:5;
    top:0;
    right:clamp(20px, 3.5vw, 52px);
    width:clamp(300px, 34vw, 430px);
    height:100%;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    padding:16px 0;
    /* overflow-y:auto; */
    /* isolation:isolate; */
  }

  .glass-card{
    position:relative;
    width:100%;
    max-width:400px;
    padding:30px 28px 18px;
    border-radius:24px;
    background:var(--glass-fill);
    backdrop-filter:blur(20px) saturate(1.35);
    -webkit-backdrop-filter:blur(20px) saturate(1.35);
    border:1px solid var(--glass-border);
    box-shadow:var(--glass-shadow);
  }

  /* Soft top-left inner bevel — no full-card gradient shade */
  .glass-card::before{
    content:"";
    position:absolute;
    inset:0;
    border-radius:inherit;
    background:linear-gradient(145deg, rgba(255,255,255,0.28) 0%, transparent 42%);
    pointer-events:none;
    z-index:0;
  }

  .glass-card > *{
    position:relative;
    z-index:1;
  }

  .login-card-header{
    margin-bottom:20px;
  }

  .form-logo{
    width:85%;
    max-width:280px;
    height:auto;
    display:block;
    margin:0 auto 14px;
  }

  #login h1{
    font-size:23px;
    font-weight:700;
    text-align:left;
    margin:0 0 8px;
    color:var(--ink);
  }

  .subtitle{
    font-size:12.5px;
    color:#475569;
    text-align:left;
    margin:0;
    line-height:1.55;
    max-width:36em;
  }

  .input{ margin-bottom:14px; }

  .field-label{
    display:block;
    font-size:13px;
    font-weight:700;
    margin-bottom:7px;
    color:var(--label-ink);
  }

  .field-wrap{
    position:relative;
    display:flex;
    align-items:center;
  }

  .field-icon{
    position:absolute;
    left:14px;
    top:50%;
    transform:translateY(-50%);
    color:#475569;
    pointer-events:none;
    z-index:1;
  }

  .field-icon svg{
    display:block;
    width:18px;
    height:18px;
    stroke-width:2.25;
  }

  .field-wrap:focus-within .field-icon{ color:var(--blue); }

  .input input{
    width:100%;
    height:46px;
    padding:0 44px 0 42px;
    border-radius:10px;
    border:1px solid rgba(255,255,255,0.65);
    background:#fff;
    color:var(--ink);
    font-size:14px;
    font-family:'Inter', sans-serif;
    outline:none;
    transition:border-color .15s ease, box-shadow .15s ease;
  }

  .input.input-password input{ padding-right:46px; }

  .input input::placeholder{ color:#94A3B8; }

  .input input:focus{
    border-color:var(--blue);
    background:rgba(255,255,255,0.95);
    box-shadow:0 0 0 3px rgba(10,110,245,0.15);
  }

  .input.input-error input{
    border-color:var(--error);
    box-shadow:0 0 0 3px rgba(216,69,90,0.1);
  }

  .toggle-password{
    position:absolute;
    right:10px;
    top:50%;
    transform:translateY(-50%);
    width:32px;
    height:32px;
    border:none;
    background:transparent;
    color:#94A3B8;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:6px;
  }

  .toggle-password:hover{ color:var(--blue); }

  #response{ display:none; margin-bottom:12px; }
  #response.show{ display:block; }

  .message{
    font-size:12.5px;
    padding:10px 12px;
    border-radius:8px;
    text-align:center;
  }

  .message.error{ background:var(--error-bg); color:var(--error); }
  .message.success{ background:var(--success-bg); color:var(--success); }

  .si-remember-password{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin:4px 0 16px;
    flex-wrap:wrap;
  }

  .remember-wrap{
    display:flex;
    align-items:center;
    gap:8px;
  }

  .si-remember-password input[type="checkbox"]{
    width:16px;
    height:16px;
    accent-color:var(--blue);
    cursor:pointer;
  }

  .si-remember-password .form-label{
    font-size:13px;
    color:var(--muted);
    cursor:pointer;
  }

  .forgot-link{
    font-size:13px;
    font-weight:600;
    color:var(--blue);
    text-decoration:none;
  }

  .forgot-link:hover{ text-decoration:underline; }

  .btn-login-primary{
    width:100%;
    height:46px;
    margin:0;
    border:none;
    border-radius:8px;
    background:var(--blue);
    color:#fff;
    font-size:15px;
    font-weight:600;
    font-family:'Inter', sans-serif;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    box-shadow:0 6px 16px rgba(10,110,245,0.25);
    transition:background .15s ease;
  }

  .btn-login-primary:hover{ background:var(--blue-hover); }

  .divider-or{
    display:flex;
    align-items:center;
    gap:12px;
    margin:16px 0;
    color:var(--muted);
    font-size:12px;
    font-weight:500;
  }

  .divider-or::before,
  .divider-or::after{
    content:"";
    flex:1;
    height:1px;
    background:rgba(148,163,184,0.45);
  }

  .btn-microsoft{
    width:100%;
    height:46px;
    border:1px solid rgba(203,213,225,0.9);
    border-radius:10px;
    background:rgba(255,255,255,0.92);
    backdrop-filter:blur(10px);
    -webkit-backdrop-filter:blur(10px);
    color:var(--ink);
    font-size:14px;
    font-weight:600;
    font-family:'Inter', sans-serif;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:10px;
  }

  .btn-microsoft:hover{ background:rgba(255,255,255,0.72); }

  .support-box{
    margin-top:18px;
    padding:12px 12px;
    border-radius:12px;
    background:linear-gradient(145deg, rgba(255,255,255,0.62) 0%, rgba(217,232,250,0.45) 100%);
    backdrop-filter:blur(16px) saturate(1.25);
    -webkit-backdrop-filter:blur(16px) saturate(1.25);
    border:1px solid rgba(255,255,255,0.72);
    box-shadow:
      0 0 0 1px rgba(255,255,255,0.28),
      inset 0 1px 0 rgba(255,255,255,0.75);
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
  }

  .support-box-icon{
    width:36px;
    height:36px;
    border-radius:8px;
    background:rgba(255,255,255,0.85);
    color:var(--blue);
    display:flex;
    align-items:center;
    justify-content:center;
    flex-shrink:0;
  }

  .support-box-text{
    flex:1;
    min-width:120px;
  }

  .support-box-text h3{
    font-size:14px;
    font-weight:700;
    margin:0 0 2px;
  }

  .support-box-text p{
    font-size:11px;
    color:var(--muted);
    margin:0;
    line-height:1.35;
  }

  .btn-support{
    display:inline-flex;
    align-items:center;
    gap:6px;
    height:32px;
    padding:0 12px;
    border-radius:7px;
    border:1px solid rgba(255,255,255,0.55);
    background:rgba(255,255,255,0.75);
    backdrop-filter:blur(8px);
    color:var(--blue);
    font-size:12px;
    font-weight:600;
    text-decoration:none;
  }

  .login-page-footer{
    width:100%;
    margin-top:10px;
    padding-top:10px;
    border-top:1px solid rgba(148,163,184,0.35);
    text-align:center;
    font-size:12px;
    color:#64748B;
    line-height:1.65;
  }

  .login-page-footer a{
    color:#475569;
    text-decoration:none;
    font-weight:600;
    font-size:12px;
  }

  .login-page-footer a:hover{ text-decoration:underline; }

  .footer-sep{ margin:0 6px; opacity:0.55; }

  .footer-links{
    display:block;
    white-space:nowrap;
    margin-bottom:8px;
    font-size:12px;
  }

  .footer-copy{
    display:block;
    font-size:11.5px;
    color:#64748B;
  }

  /* Tablet landscape & small laptop — keep side card, tune banner + ticker */
  @media (max-width: 1200px) and (min-width: 901px){
    .login-glass-panel{
      width:clamp(300px, 36vw, 400px);
      right:clamp(12px, 2vw, 28px);
    }
    .marquee-bar{
      right:38%;
      left:4%;
      bottom:2%;
    }
    .login-banner-frame img{
      object-position:42% center;
    }
    .glass-card{
      padding:26px 24px 16px;
    }
  }

  /* Tablet portrait & narrow — banner strip on top, login below */
  @media (max-width: 900px){
    html, body{
      overflow:auto;
      height:auto;
      min-height:100%;
    }

    .login-stage{
      position:relative;
      display:flex;
      flex-direction:column;
      min-height:100dvh;
      min-height:100svh;
      height:auto;
      overflow:visible;
    }

    .login-banner-frame{
      position:relative;
      width:100%;
      height:clamp(260px, 42vh, 400px);
      min-height:260px;
      flex-shrink:0;
      perspective:none;
    }

    .login-banner-frame img{
      object-fit:cover;
      object-position:center 22%;
    }

    .marquee-bar{
      left:3%;
      right:3%;
      bottom:2.5%;
    }

    .ticker-item{
      font-size:11px;
      padding:0 14px;
    }

    .login-glass-panel{
      position:relative;
      top:auto;
      right:auto;
      width:100%;
      min-width:0;
      height:auto;
      min-height:0;
      flex:1;
      justify-content:center;
      padding:20px 20px max(28px, env(safe-area-inset-bottom));
      background:linear-gradient(180deg, #e8eef6 0%, #f1f5f9 28%, #f8fafc 100%);
    }

    .glass-card{
      max-width:440px;
      margin:0 auto;
      padding:28px 24px 20px;
      box-shadow:var(--glass-shadow);
    }

    .login-page-footer{ color:var(--muted); text-shadow:none; }
    .login-page-footer a{ color:var(--muted); }

    .footer-links{
      white-space:normal;
      line-height:1.5;
    }
  }

  /* Large phones & small tablets in landscape */
  @media (max-width: 900px) and (max-height: 520px){
    .login-banner-frame{
      height:clamp(200px, 38vh, 280px);
      min-height:200px;
    }
    .login-glass-panel{
      padding:12px 16px max(16px, env(safe-area-inset-bottom));
    }
    .glass-card{
      padding:20px 18px 14px;
    }
    .form-logo{
      max-width:220px;
      margin-bottom:10px;
    }
    #login h1{ font-size:20px; }
    .subtitle{ font-size:12px; margin-bottom:0; }
    .login-card-header{ margin-bottom:14px; }
    .input{ margin-bottom:10px; }
  }

  @media (max-width: 480px){
    .login-banner-frame{
      height:clamp(220px, 36vh, 320px);
    }
    .login-glass-panel{
      padding:16px 14px max(24px, env(safe-area-inset-bottom));
    }
    .glass-card{
      padding:24px 18px 16px;
      border-radius:20px;
    }
    #login h1{ font-size:21px; }
    .si-remember-password{
      flex-direction:column;
      align-items:flex-start;
      gap:8px;
    }
  }
</style>
</head>
<body>

  <div class="login-stage">
    <div class="login-banner-frame">
      <img src="images/without-full-logo-banner.png" width="1821" height="864" alt="">
      <div class="marquee-bar">
        <div class="ticker">
          <div class="ticker-track" id="cityTicker"></div>
        </div>
      </div>
    </div>

    <aside class="login-glass-panel">
      <div class="glass-card">
        <div id="login">
          <div class="login-card-header">
            <img src="images/elitewave-light.png" alt="Elite Wave 360" class="form-logo" />
            <h1>Welcome Back</h1>
            <p class="subtitle">Login to your EliteWave360 portal to manage your shipments, invoices, payments and more.</p>
          </div>
          <form class="login100-form validate-form" method="post" id="login_form">
            <input type="hidden" name="form_name" id="from_name" value="login">

            <div id="response">
              <div class="message"></div>
            </div>

            <div class="input" id="mail">
              <label for="email" class="field-label">Username or Email</label>
              <div class="field-wrap">
                <span class="field-icon">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 1 0-16 0"/><circle cx="12" cy="8" r="4"/></svg>
                </span>
                <input type="email" name="email" id="email" placeholder="Enter your username or email" autocomplete="username" autofocus />
              </div>
            </div>

            <div class="input input-password" id="pass">
              <label for="password" class="field-label">Password</label>
              <div class="field-wrap">
                <span class="field-icon">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                </span>
                <input type="password" name="password" id="password" placeholder="Enter your password" autocomplete="current-password" />
                <button type="button" class="toggle-password" id="togglePassword" aria-label="Show password">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
            </div>

            <div class="si-remember-password">
              <div class="remember-wrap">
                <input type="checkbox" name="remember" id="remember-me" value="1">
                <label class="form-label" for="remember-me">Remember me</label>
              </div>
              <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
            </div>

            <button type="button" class="btn-login-primary" id="loginBtn">
              Login
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </button>

            <!-- Microsoft SSO + support — re-enable when ready
            <div class="divider-or">OR</div>

            <button type="button" class="btn-microsoft" id="microsoftLogin">
              <svg width="20" height="20" viewBox="0 0 21 21"><rect x="1" y="1" width="9" height="9" fill="#F25022"/><rect x="11" y="1" width="9" height="9" fill="#7FBA00"/><rect x="1" y="11" width="9" height="9" fill="#00A4EF"/><rect x="11" y="11" width="9" height="9" fill="#FFB900"/></svg>
              Login with Microsoft
            </button>

            <div class="support-box">
              <div class="support-box-icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5z"/><path d="M21 11h-3a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-5z"/><path d="M12 3a4 4 0 0 0-4 4v1h8V7a4 4 0 0 0-4-4z"/></svg>
              </div>
              <div class="support-box-text">
                <h3>Need Help?</h3>
                <p>Contact our support team for assistance.</p>
                <a href="https://elitewave360.in/" class="btn-support" target="_blank" rel="noopener noreferrer">Get Support →</a>
              </div>
            </div>
            -->
          </form>

          <footer class="login-page-footer">
            <span class="footer-links">
              <a href="https://elitewave360.in/" target="_blank" rel="noopener noreferrer">Privacy Policy</a>
              <span class="footer-sep">|</span>
              <a href="https://elitewave360.in/" target="_blank" rel="noopener noreferrer">Terms of Service</a>
              <span class="footer-sep">|</span>
              <a href="https://elitewave360.in/" target="_blank" rel="noopener noreferrer">Support</a>
            </span>
            <span class="footer-copy">&copy; <?php echo date('Y'); ?> EliteWave360 Logistics. All rights reserved.</span>
          </footer>
        </div>
      </div>
    </aside>
  </div>

  <script type="text/javascript">
    (function(){
      var cities = [
        "New Delhi","Mumbai","Bangalore","Chennai","Hyderabad","Pune","Ahmedabad",
        "Kolkata","Jaipur","Surat","Amritsar","Agra","Lucknow","Ludhiana","Dehradun",
        "Faridabad","Indore","Coimbatore","Nagpur","Vadodara","Chandigarh","Bhopal",
        "Patna","Noida","Gurgaon","Rajkot"
      ];
      var track = document.getElementById('cityTicker');
      if (!track) return;
      var html = '';
      for (var rep = 0; rep < 2; rep++) {
        for (var i = 0; i < cities.length; i++) {
          html += '<span class="ticker-item"><span class="dot"></span>' + cities[i] + '</span>';
        }
      }
      track.innerHTML = html;
    })();
  </script>
  <script src="javascripts/jquery-1.10.2.min.js"></script>
  <script src="javascripts/jquery.validate.js"></script>
  <script type="text/javascript">
    jQuery(function($) {
      var emailVerified = false;
      var emailFilter = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;

      function showMessage(text, type){
        $("#response").addClass("show");
        $(".message").removeClass("error success").addClass(type).html(text);
      }

      function clearMessage(){
        $("#response").removeClass("show");
        $(".message").removeClass("error success").html('');
      }

      $('#togglePassword').on('click', function(){
        var $pw = $('#password');
        var show = $pw.attr('type') === 'password';
        $pw.attr('type', show ? 'text' : 'password');
        $(this).attr('aria-label', show ? 'Hide password' : 'Show password');
      });

      $('#email').on('input', function(){ emailVerified = false; $("#mail").removeClass("input-error"); });

      function submitLogin(){
        if (!$("#login_form").valid()) return;
        $.post('save_details.php', $("#login_form").serialize(), function(data){
          if ($.trim(String(data)) === '1') {
            showMessage('<img src="images/btn-ajax-loader.gif" height="10" width="10" alt=""/> Loading…', 'success');
            setTimeout(function(){ window.location.href = 'dashboard.php'; }, 2000);
          } else if (data == 2) {
            showMessage('Your password has already been changed using this url', 'success');
          } else {
            $("#pass").addClass("input-error");
            showMessage('Invalid Password !!!', 'error');
          }
        });
      }

      function verifyEmailThenLogin(){
        var email = $("#email").val();
        if (!emailFilter.test(email)) {
          $("#mail").addClass("input-error");
          showMessage('Please provide a valid email address', 'error');
          return;
        }
        if (!$("#password").val()) {
          $("#pass").addClass("input-error");
          showMessage('Please enter your password', 'error');
          return;
        }
        if (emailVerified) {
          submitLogin();
          return;
        }
        $.ajax({
          url: 'fetch_details.php',
          type: 'post',
          data: { cmd: 'chck_users_email', email: email },
          success: function(result){
            if ($.trim(result) === '1') {
              emailVerified = true;
              $("#mail").removeClass("input-error");
              clearMessage();
              submitLogin();
            } else {
              $("#mail").addClass("input-error");
              showMessage('Incorrect Email Id ..!!', 'error');
            }
          }
        });
      }

      $('#loginBtn').on('click', function(e){ e.preventDefault(); verifyEmailThenLogin(); });
      $('#login_form input').on('keydown', function(e){
        if (e.keyCode === 13) { e.preventDefault(); verifyEmailThenLogin(); }
      });
      /* $('#microsoftLogin').on('click', function(){
        showMessage('Microsoft sign-in is not configured yet. Please use email and password.', 'error');
      }); */
    });
  </script>
</body>
</html>
