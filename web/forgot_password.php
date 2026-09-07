<?php
require_once('include/connect.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="shortcut icon" href="<?php print_r(site_path); ?>images/elw_360_32_32-1.png">
  <title>Forgot Password | Elite Wave 360</title>
  <style>
    :root{
      --navy:#0B1437;
      --navy-2:#0D1A4A;
      --navy-3:#111E5C;
      --red:#C8232A;
      --red-dim:#A81C22;
      --form-bg:#F2F4FA;
      --tint:#EBEEf8;
      --ink:#0B1437;
      --muted:#6E7491;
      --line:#DDE0EF;
      --error:#D8455A;
      --error-bg:#FCEAEC;
      --success:#1D9E75;
      --success-bg:#E8F8F1;
    }
    *, *::before, *::after{ box-sizing:border-box; margin:0; padding:0; }
    body{
      min-height:100vh;
      font-family:'Inter', sans-serif;
      color:var(--ink);
      display:grid;
      grid-template-columns:1fr 1fr;
      background:var(--form-bg);
    }
    .panel-visual{
      position:relative;
      min-height:100vh;
      background:
        radial-gradient(ellipse at 80% 5%, rgba(200,35,42,0.28), transparent 45%),
        radial-gradient(ellipse at 15% 90%, rgba(200,35,42,0.18), transparent 40%),
        linear-gradient(165deg, var(--navy) 0%, var(--navy-2) 45%, var(--navy-3) 100%);
      display:flex;
      flex-direction:column;
      justify-content:center;
      padding:64px;
      color:#F4F5FC;
    }
    .panel-visual::before{
      content:"";
      position:absolute;
      inset:0;
      background-image: radial-gradient(circle, rgba(255,255,255,0.07) 1px, transparent 1px);
      background-size:28px 28px;
      pointer-events:none;
    }
    .panel-inner{ position:relative; z-index:1; max-width:460px; }
    .panel-inner h2{
      font-family:'Space Grotesk', sans-serif;
      font-size:36px;
      line-height:1.2;
      margin-bottom:14px;
    }
    .panel-inner p{
      color:rgba(244,245,252,0.7);
      line-height:1.65;
      font-size:15px;
    }
    .panel-foot{
      position:absolute;
      bottom:32px;
      left:64px;
      font-size:12px;
      color:rgba(244,245,252,0.35);
    }
    .panel-form{
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:40px;
    }
    .form-card{
      width:100%;
      max-width:420px;
      background:#fff;
      border-radius:24px;
      padding:44px 40px;
      box-shadow:0 24px 60px rgba(11,20,55,0.08), 0 2px 8px rgba(11,20,55,0.04);
    }
    .form-logo{
      width:80%;
      display:block;
      margin:0 auto 24px;
    }
    h1{
      font-family:'Space Grotesk', sans-serif;
      font-size:24px;
      text-align:center;
      margin-bottom:6px;
    }
    .subtitle{
      text-align:center;
      color:var(--muted);
      font-size:13.5px;
      margin-bottom:28px;
    }
    .input{ margin-bottom:18px; }
    .input label{
      display:block;
      font-size:12.5px;
      font-weight:600;
      margin-bottom:7px;
    }
    .field-wrap{ position:relative; }
    .field-icon{
      position:absolute;
      left:14px;
      top:50%;
      transform:translateY(-50%);
      color:#9CA1BD;
      pointer-events:none;
    }
    .input input{
      width:100%;
      height:50px;
      padding:0 16px 0 42px;
      border-radius:12px;
      border:1.5px solid var(--line);
      background:var(--tint);
      font-size:14px;
      outline:none;
    }
    .input input:focus{
      border-color:var(--red);
      background:#fff;
      box-shadow:0 0 0 4px rgba(200,35,42,0.1);
    }
    .btn-submit{
      width:100%;
      height:48px;
      border:none;
      border-radius:12px;
      background:var(--red);
      color:#fff;
      font-size:14px;
      font-weight:600;
      cursor:pointer;
      box-shadow:0 4px 14px rgba(200,35,42,0.35);
    }
    .btn-submit:hover{ background:var(--red-dim); }
    .btn-submit:disabled{ opacity:.7; cursor:not-allowed; }
    .back-link{
      display:block;
      text-align:center;
      margin-top:18px;
      font-size:13px;
      font-weight:600;
      color:var(--muted);
      text-decoration:none;
    }
    .back-link:hover{ color:var(--red); }
    #response{ display:none; margin-bottom:14px; }
    #response.show{ display:block; }
    .message{
      font-size:13px;
      padding:10px 14px;
      border-radius:10px;
      text-align:center;
    }
    .message.error{ background:var(--error-bg); color:var(--error); }
    .message.success{ background:var(--success-bg); color:var(--success); }
    @media (max-width:980px){
      body{ grid-template-columns:1fr; }
      .panel-visual{ display:none; }
      .form-card{ box-shadow:none; padding:20px; }
    }
  </style>
</head>
<body>
  <div class="panel-visual">
    <div class="panel-inner">
      <h2>Reset your password</h2>
      <p>Enter the email linked to your Elite Wave 360 account. We will send you a secure link to set a new password.</p>
    </div>
    <div class="panel-foot">&copy; <?php echo date('Y'); ?> Elite Wave 360 Logistics. All rights reserved.</div>
  </div>

  <div class="panel-form">
    <div class="form-card">
      <img src="images/elitewave-light.png" alt="Elite Wave 360" class="form-logo" />
      <h1>Forgot password</h1>
      <div class="subtitle">We will email you a password reset link</div>

      <form method="post" id="forgot_password">
        <input type="hidden" name="form_name" value="forgot_password">

        <div id="response"><div class="message"></div></div>

        <div class="input">
          <label for="email">Email</label>
          <div class="field-wrap">
            <span class="field-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
            </span>
            <input type="email" name="email" id="email" placeholder="Enter your email" required autocomplete="email">
          </div>
        </div>

        <button class="btn-submit" type="button" id="save">Send reset link</button>
        <a href="index.php" class="back-link">&larr; Back to sign in</a>
      </form>
    </div>
  </div>

  <script src="javascripts/jquery-1.10.2.min.js"></script>
  <script src="javascripts/jquery.validate.js"></script>
  <script>
    jQuery(function($) {
      function showMessage(text, type) {
        $('#response').addClass('show');
        $('.message').removeClass('error success').addClass(type).html(text);
      }

      $('#save').on('click', function() {
        if (!$('#forgot_password').valid()) {
          return;
        }
        var $btn = $(this);
        $btn.prop('disabled', true).text('Sending...');
        $.post('save_details.php', $('#forgot_password').serialize(), function(data) {
          $btn.prop('disabled', false).text('Send reset link');
          if (data == 1) {
            showMessage('Password reset link sent. Please check your email.', 'success');
            setTimeout(function(){ window.location.href = 'index.php'; }, 2500);
          } else if (data == 2) {
            showMessage('Could not send email. Please try again later.', 'error');
          } else {
            showMessage('No account found with that email address.', 'error');
          }
        }).fail(function() {
          $btn.prop('disabled', false).text('Send reset link');
          showMessage('Network error. Please try again.', 'error');
        });
      });

      $('#email').on('keyup', function(e) {
        if (e.keyCode === 13) {
          $('#save').trigger('click');
        }
      });
    });
  </script>
</body>
</html>
