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
<html lang="en" >
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="<?php print_r(site_path); ?>images/elw_360_32_32-1.png">
<title>Elite Wave 360</title>

  <script>
  window.console = window.console || function(t) {};
  if (document.location.search.match(/type=embed/gi)) {
    window.parent.postMessage("resize", "*");
  }
</script>

<style>
  :root{
    /* Logo-extracted palette */
    --navy:#0B1437;
    --navy-2:#0D1A4A;
    --navy-3:#111E5C;
    --red:#C8232A;
    --red-dim:#A81C22;
    --red-glow:rgba(200,35,42,0.22);

    /* Form side */
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
  html{ height:100%; }

  body{
    min-height:100vh;
    width:100%;
    font-family:'Inter', sans-serif !important;
    color: var(--ink);
    display:grid;
    grid-template-columns: 1fr 1fr;
    line-height:1.5;
  }

  body *{ font-family:'Inter', sans-serif !important; }
  ul, li{ list-style:none; }

  /* ===== Left panel — logo-based gradient ===== */
  .panel-visual{
    position:relative;
    grid-column: 1;
    min-width:0;
    min-height:100vh;
    /* navy core with red warmth from logo */
    background:
      radial-gradient(ellipse at 80% 5%,  rgba(200,35,42,0.28), transparent 45%),
      radial-gradient(ellipse at 15% 90%, rgba(200,35,42,0.18), transparent 40%),
      radial-gradient(ellipse at 50% 50%, rgba(17,30,92,0.9),   transparent 80%),
      linear-gradient(165deg, var(--navy) 0%, var(--navy-2) 45%, var(--navy-3) 100%);
    overflow:hidden;
    display:flex;
    flex-direction:column;
    justify-content:center;
    padding:64px;
    color:#F4F5FC;
  }

  /* subtle dot-grid texture */
  .panel-visual::before{
    content:"";
    position:absolute;
    inset:0;
    background-image: radial-gradient(circle, rgba(255,255,255,0.07) 1px, transparent 1px);
    background-size: 28px 28px;
    pointer-events:none;
    z-index:1;
  }

  .route-stage{
    position:absolute;
    inset:0;
    z-index:2;
  }

  .route-stage svg{
    position:absolute;
    width:100%;
    height:100%;
  }

  .panel-inner{
    position:relative;
    z-index:3;
    max-width:480px;
  }

  .eyebrow{
    display:inline-flex;
    align-items:center;
    gap:8px;
    font-size:12px;
    font-weight:600;
    letter-spacing:1.4px;
    text-transform:uppercase;
    color: var(--red);
    margin-bottom:22px;
    filter: brightness(1.3);
  }

  .eyebrow::before{
    content:"";
    width:18px;
    height:1.5px;
    background: var(--red);
    filter: brightness(1.3);
  }

  .visual-copy h2{
    font-family:'Space Grotesk', sans-serif;
    font-weight:700;
    font-size:44px;
    line-height:1.15;
    margin:0 0 18px;
    letter-spacing:-0.6px;
  }

  .visual-copy h2 em{
    font-style:normal;
    background: linear-gradient(90deg, #F4606A, #FF9B6A);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
  }

  .visual-copy p{
    font-size:15.5px;
    line-height:1.65;
    color: rgba(244,245,252,0.65);
    margin:0 0 40px;
    max-width:400px;
  }

  /* ===== City ticker ===== */
  .ticker{
    position:relative;
    width:100%;
    overflow:hidden;
    mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent);
    -webkit-mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent);
    padding:16px 0;
    border-top:1px solid rgba(255,255,255,0.1);
    border-bottom:1px solid rgba(255,255,255,0.1);
  }

  .ticker-track{
    display:flex;
    width:max-content;
    animation: scrollTicker 32s linear infinite;
  }

  @keyframes scrollTicker{
    from{ transform: translateX(0); }
    to{ transform: translateX(-50%); }
  }

  .ticker-item{
    display:flex;
    align-items:center;
    gap:10px;
    font-size:13.5px;
    font-weight:500;
    color: rgba(244,245,252,0.8);
    white-space:nowrap;
    padding:0 22px;
  }

  .ticker-item .dot{
    width:4px;
    height:4px;
    border-radius:50%;
    background: var(--red);
    filter: brightness(1.5);
    flex:0 0 auto;
  }

  .ticker-count{
    margin-top:16px;
    font-size:12.5px;
    color: rgba(244,245,252,0.45);
  }

  .ticker-count strong{
    color: rgba(244,245,252,0.8);
    font-weight:600;
  }

  .panel-foot{
    position:absolute;
    bottom:32px;
    left:64px;
    z-index:3;
    font-size:12px;
    color: rgba(244,245,252,0.35);
    letter-spacing:0.3px;
  }

  /* ===== Right form panel ===== */
  .panel-form{
    grid-column: 2;
    min-width:0;
    min-height:100vh;
    background: var(--form-bg);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:40px;
  }

  .form-card{
    width:100%;
    max-width:400px;
    background:#fff;
    border-radius:24px;
    padding:44px 40px;
    box-shadow:
      0 24px 60px rgba(11,20,55,0.08),
      0 2px 8px rgba(11,20,55,0.04);
  }

  .form-logo{
    height:auto;
    width:80%;
    display:block;
    margin:0 auto 24px;
  }

  #login{ width:100%; }
  #login form{ width:100%; display:block; }

  #login h1{
    font-family:'Space Grotesk', sans-serif;
    font-weight:700;
    font-size:24px;
    line-height:1.3;
    margin:0 0 6px;
    color: var(--ink);
    text-align:center;
  }

  .subtitle{
    font-size:13.5px;
    color: var(--muted);
    margin-bottom:28px;
    text-align:center;
  }

  #logo{ display:none; }

  .input{ margin-bottom:18px; }

  .input label{
    display:block;
    font-size:12.5px;
    font-weight:600;
    color: var(--ink);
    margin-bottom:7px;
    letter-spacing:0.2px;
  }

  .input .field-wrap{
    position:relative;
    width:100%;
    display:flex;
    align-items:center;
  }

  .field-icon{
    position:absolute;
    left:14px;
    top:50%;
    transform: translateY(-50%);
    width:18px;
    height:18px;
    color:#9CA1BD;
    display:flex;
    align-items:center;
    justify-content:center;
    pointer-events:none;
    transition: color .15s ease;
  }

  /* icon turns red on focus */
  .input input:focus ~ .field-icon,
  .field-wrap:focus-within .field-icon{
    color: var(--red);
  }

  .input input{
    width:100%;
    height:50px;
    padding:0 52px 0 42px;
    border-radius:12px;
    border:1.5px solid var(--line);
    background: var(--tint);
    color: var(--ink);
    font-size:14px;
    font-family:'Inter', sans-serif;
    outline:none;
    display:block;
    transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
  }

  .input input::placeholder{ color:#A4A8C0; }

  /* red focus ring matching logo */
  .input input:focus{
    border-color: var(--red);
    background:#fff;
    box-shadow: 0 0 0 4px rgba(200,35,42,0.1);
  }

  .input.input-error input{
    border-color: var(--error);
    box-shadow: 0 0 0 4px rgba(216,69,90,0.08);
  }

  /* red arrow button matching logo */
  .btn{
    position:absolute;
    right:6px;
    top:50%;
    transform: translateY(-50%);
    width:38px;
    height:38px;
    border-radius:9px;
    cursor:pointer;
    border:none;
    background: var(--red);
    display:flex;
    align-items:center;
    justify-content:center;
    transition: background .15s ease, transform .1s ease, box-shadow .15s ease;
    box-shadow: 0 4px 14px rgba(200,35,42,0.35);
  }
  .btn:hover{
    background: var(--red-dim);
    transform: translateY(-50%);
    box-shadow: 0 6px 18px rgba(200,35,42,0.45);
  }
  .btn:active{ transform: translateY(-50%) scale(0.92); }

  .btn::before{
    content:"";
    width:8px;
    height:8px;
    border-top:2px solid #fff;
    border-right:2px solid #fff;
    transform: rotate(45deg) translateX(-1px);
  }

  #response{ display:none; width:100%; }
  #response.show{ display:block; }

  .message{
    font-size:13px;
    padding:10px 14px;
    border-radius:10px;
    margin-bottom:14px;
    width:100%;
  }
  .message.error{ background: var(--error-bg); color: var(--error); }
  .message.success{ background: var(--success-bg); color: var(--success); }

  .si-remember-password{
    display:flex;
    align-items:center;
    gap:8px;
    width:100%;
    margin: 6px 0 24px;
  }

  .si-remember-password input[type="checkbox"]{
    width:15px;
    height:15px;
    margin:0;
    accent-color: var(--red);
    cursor:pointer;
    flex:0 0 auto;
  }

  .si-remember-password .form-label{
    color: var(--muted);
    font-size:13px;
    cursor:pointer;
    flex:0 1 auto;
  }

  .separator{
    height:1px;
    width:100%;
    background: var(--line);
    margin:8px 0 0;
  }

  @media (max-width: 980px){
    body{ grid-template-columns: 1fr; }
    .panel-visual{ display:none; }
    .form-card{ box-shadow:none; padding:20px; }
  }
</style>

</head>

<body translate="no" >

  <div class="panel-visual">
    <div class="route-stage">
      <svg viewBox="0 0 700 900" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
        <!-- top dashed route — red tint -->
        <path d="M -50 120 C 120 90, 220 180, 380 130 S 650 60, 760 140"
              stroke="rgba(200,35,42,0.35)" stroke-width="1.5" fill="none" stroke-dasharray="2 6"/>
        <!-- bottom dashed route — navy lighter -->
        <path d="M -50 780 C 120 740, 250 820, 400 760 S 620 700, 760 770"
              stroke="rgba(255,255,255,0.12)" stroke-width="1.5" fill="none" stroke-dasharray="2 6"/>
        <!-- glowing node dots — red -->
        <circle cx="120" cy="100" r="3" fill="rgba(200,35,42,0.7)"/>
        <circle cx="120" cy="100" r="7" fill="rgba(200,35,42,0.12)"/>
        <circle cx="380" cy="130" r="3" fill="rgba(200,35,42,0.7)"/>
        <circle cx="380" cy="130" r="7" fill="rgba(200,35,42,0.12)"/>
        <circle cx="650" cy="80"  r="3" fill="rgba(200,35,42,0.7)"/>
        <circle cx="650" cy="80"  r="7" fill="rgba(200,35,42,0.12)"/>
        <!-- subtle bottom nodes — white -->
        <circle cx="250" cy="800" r="2.5" fill="rgba(255,255,255,0.25)"/>
        <circle cx="500" cy="730" r="2.5" fill="rgba(255,255,255,0.25)"/>
      </svg>
    </div>

    <div class="panel-inner">
      <div class="eyebrow">Pan-India Logistics Network</div>

      <div class="visual-copy">
        <h2>Every shipment.<br/>On <em>time</em>, every time.</h2>
        <p>From dispatch to doorstep, track your fleet, routes, and deliveries across India in one connected platform built for speed and certainty.</p>
      </div>

      <div class="ticker">
        <div class="ticker-track" id="cityTicker"></div>
      </div>

      <div class="ticker-count"><strong>70+</strong> cities served across India</div>
    </div>

    <div class="panel-foot">&copy; <?php echo date('Y'); ?> Elite Wave 360 Logistics. All rights reserved.</div>
  </div>

  <div class="panel-form">
    <div class="form-card">
      <div id="login">
        <img src="images/elitewave-light.png" alt="Elite Wave 360" class="form-logo" />
        <h1>Welcome back</h1>
        <div class="subtitle">Sign in to your Elite Wave 360 account</div>
        <form class="login100-form validate-form" method="post" id="login_form">
          <input type="hidden" name="form_name" id="from_name" value="login">
          <div id="logo"></div>

          <div class="input" id="mail">
            <label for="email">Email</label>
            <div class="field-wrap">
              <span class="field-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
              </span>
              <input type="email" name="email" id="email" placeholder="Enter your email" autocomplete="off" autofocus="" />
              <div class="btn" id="next" type="button"></div>
            </div>
          </div>

          <div class="input" id="pass" style="display: none;">
            <label for="password">Password</label>
            <div class="field-wrap">
              <span class="field-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
              </span>
              <input type="password" name="password" id="password" placeholder="Enter your password" autocomplete="off"/>
              <div class="btn" id="save" type="button"></div>
            </div>
          </div>

          <div id="response" class="alert alert-danger">
            <div class="message" style="text-align:center"></div>
          </div>

          <div class="si-remember-password">
            <input type="checkbox" name="remember" id="remember-me" class="form-choice form-choice-checkbox" value="1">
            <span id="remember-me-label" class="form-label" for="remember-me">Keep me signed in</span>
          </div>
          <div class="separator"></div>

          <!--<div id="footer">
              <label><span><a href="forgot_password.php">Forgot password?</a></span></label>
          </div>-->
        </form>
      </div>
    </div>
  </div>

  <script src="javascripts/jquery-1.10.2.min.js" type="text/javascript"></script>
  <script src="javascripts/jquery.validate.js" type="text/javascript"></script>
  <script type="text/javascript">

    (function(){
      var cities = [
        "New Delhi","Mumbai","Bangalore","Chennai","Hyderabad","Pune","Ahmedabad",
        "Kolkata","Jaipur","Surat","Amritsar","Agra","Lucknow","Ludhiana","Dehradun",
        "Faridabad","Indore","Coimbatore","Nagpur","Vadodara","Chandigarh","Bhopal",
        "Patna","Noida","Gurgaon","Rajkot"
      ];
      var track = document.getElementById('cityTicker');
      var html = '';
      for(var rep=0; rep<2; rep++){
        for(var i=0;i<cities.length;i++){
          html += '<span class="ticker-item"><span class="dot"></span>'+cities[i]+'</span>';
        }
      }
      track.innerHTML = html;
    })();

    jQuery(function($) {

      function showMessage(text, type){
        $("#response").addClass("show");
        $(".message").removeClass("error success").addClass(type).html(text);
      }

      $(document).on('click', '#next', function(event){
        event.preventDefault();
        var email = $("#email").val();
        var filter = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        if (!filter.test(email)) {
          $("#mail").addClass("input-error");
          showMessage('Please provide a valid email address', 'error');
          $("#email").focus();
          return false;
        } else {
          $.ajax({
            url:'fetch_details.php',
            type:"post",
            data:{cmd:"chck_users_email",email:email},
            success:function(result){

    console.log("Response:", result);
    console.log("Type:", typeof result);
    console.log("Trim:", $.trim(result));

    if($.trim(result) === "1"){

        $("#mail").hide(100);
        $("#pass").show().fadeIn(1000);
        $("#password").focus();

        $("#mail").removeClass("input-error");

        $("#response").removeClass("show");
        $(".message").removeClass("error").html('');

    }else{

        $("#mail").addClass("input-error");
        showMessage("Incorrect Email Id ..!!","error");

    }

}
          });
        }
      });

      $(document).on('keyup', '#email', function(event){
        event.preventDefault();
        if (event.keyCode === 13){ $("#next").trigger("click"); }
      });

      $(document).on('keyup', '#password', function(event){
        event.preventDefault();
        if (event.keyCode === 13){ $("#save").trigger("click"); }
      });

      $(document).on('click submit', '#save', function(ev){
        if($("#login_form").valid() == true){
          var data = $("#login_form").serialize();
          $.post('save_details.php', data, function(data) {
            console.log(data);
            if(data == 1){
              $("#pass").removeClass("input-error");
              showMessage('<img src="images/btn-ajax-loader.gif" height="10px" width="10px"/> &nbsp; Loading Please Wait ...', 'success');
              setTimeout(function(){ window.location.href='dashboard.php'; }, 2000);
            } else if(data==2){
              showMessage('<strong>Your Password has been already changed using this url</strong>', 'success');
              $('.message').fadeIn('slow').delay(200000).fadeOut('slow');
            } else {
              $("#pass").addClass("input-error");
              showMessage('Invalid Password !!!', 'error');
              $('.message').fadeIn('slow').delay(200000).fadeOut('slow');
            }
          });
        }
      });

    });

  </script>
</body>

</html>