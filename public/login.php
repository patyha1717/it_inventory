<?php
//  error_reporting(E_ALL);
// ini_set('display_errors',1);
require_once __DIR__ . '/../app/db.php';
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

if (!empty($_SESSION['user_id'])) {
    header("Location: /itms.arukustech.com/public/dashboard.php");
    exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';

        $pdo = db();
        $st = $pdo->prepare("
            SELECT * FROM users 
            WHERE email = ? AND status = 1
            AND role IN ('superadmin','admin','user')
            LIMIT 1
        ");
        $st->execute([$email]);
        $u = $st->fetch();

        if ($u && password_verify($pass, $u['password'])) {

    // ================= LOGIN ACTIVITY LOG =================
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

    $log = $pdo->prepare("
        INSERT INTO login_logs (user_id, action, ip_address, user_agent)
        VALUES (?, 'login', ?, ?)
    ");
    $log->execute([
        $u['id'],
        $ip,
        $ua
    ]);
    // ======================================================

    $_SESSION['user_id'] = $u['id'];
    $_SESSION['name']    = $u['name'];
    $_SESSION['email']   = $u['email'];
    $_SESSION['role']    = $u['role'];

    header("Location: /itms.arukustech.com/public/dashboard.php");
    exit;
}
 else {
            $msg = "Invalid email or password!";
        }
    }

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>IT Inventory Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<style>
*{box-sizing:border-box;font-family:'Inter',sans-serif}
html,body{margin:0;height:100%}

/* ===== LAYOUT ===== */
.wrapper{
    display:flex;
    height:100vh;
    width:100%;
}

/* ===== LEFT BACKGROUND ===== */
.left{
    flex:1;
    position:relative;
    background:url('/itms.arukustech.com/public/assets/login/coverimg.png') center center no-repeat;
    background-size:100% 100%;          /* FIX: fills full area */
    overflow:hidden;
}

/* subtle dark overlay (improves contrast) */
.left::after{
    content:'';
    position:absolute;
    inset:0;
    background:rgba(0,0,0,.15);
}

/* ===== SLIDER ===== */
.slider-wrapper{
    position:absolute;
    inset:0;
    display:flex;
    align-items:center;
    justify-content:center;
    z-index:2;
}

.slider{
    width:760px;
    height:420px;
    position:relative;
    border-radius:18px;

    /* GLASS EFFECT */
    background:rgba(255,255,255,.18); /* more transparent */
    backdrop-filter:blur(6px);        /* less blur */
    -webkit-backdrop-filter:blur(6px);
    box-shadow:0 25px 70px rgba(0,0,0,.35);
    overflow:hidden;
}

.slide{
    position:absolute;
    inset:0;
    opacity:0;
    transition:opacity 1s ease;
}

.slide.active{opacity:1}

.slide img{
    width:100%;
    height:100%;
    object-fit:contain;
}

/* dots */
.dots{
    position:absolute;
    bottom:14px;
    width:100%;
    text-align:center;
}
.dots span{
    width:7px;
    height:7px;
    border-radius:50%;
    display:inline-block;
    margin:0 4px;
    background:rgba(255,255,255,.6);
}
.dots span.active{
    background:#fff;
}

/* ===== RIGHT LOGIN (UNCHANGED) ===== */
.right{
    width:420px;
    background:#fff;
    padding:70px 55px;
    display:flex;
    flex-direction:column;
    justify-content:flex-start;
    min-height:100vh;  
    /* justify-content:center; */
}

.brand{
    font-size:22px;
    font-weight:700;
    color:#0d6efd;
    margin-bottom:22px;
}
h2{font-size:20px;margin:0}
p{font-size:13px;color:#6c757d;margin-bottom:24px}

input{
    width:100%;
    padding:9px 12px;
    border-radius:6px;
    border:1px solid #ced4da;
    font-size:13px;
    margin-bottom:12px;
}
input:focus{outline:none;border-color:#0d6efd}

.forgot{
    font-size:12px;
    color:#0d6efd;
    text-decoration:none;
    margin-bottom:18px;
    display:inline-block;
}

button{
    width:120px;
    padding:8px;
    background:#0d6efd;
    color:#fff;
    border:none;
    border-radius:20px;
    cursor:pointer;
}

.alert{
    background:#f8d7da;
    color:#842029;
    padding:10px;
    border-radius:6px;
    margin-bottom:14px;
    font-size:13px;
}

footer{
    position: fixed;
    /* margin-top:auto; */
    font-size:12px;
     bottom: 12px;
       right: 20px;
    color:#999;
    text-align:right;
    z-index:5;
}

/* ===== MOBILE ===== */
/* @media(max-width:900px){
    .left{display:none}
    .right{width:100%}

    footer{
        right:50%;
        transform:translateX(50%);
        text-align:center;
    }
} */

 @media(max-width:900px){

    .wrapper{
        flex-direction:column;
        height:auto;
        min-height:100vh;
    }

    .left{
        display:block;
        width:100%;
        height:220px;                 
        background-size:cover;
        background-position:center;
    }

   
    .slider-wrapper{
        display:flex;
    }

     .slider{
        width:90%;
        height:160px;         
        border-radius:12px;
    }

    .slide img{
        object-fit:contain;
    }


    .right{
        width:100%;
        min-height:auto;
        padding:35px 22px 130px;       
        justify-content:flex-start;
    }

    footer{
        position:fixed;
        bottom:12px;
        left:50%;
        transform:translateX(-50%);
        text-align:center;
    }
}

</style>
</head>

<body>

<div class="wrapper">

    <!-- LEFT -->
    <div class="left">
        <div class="slider-wrapper">
            <div class="slider">
                <div class="slide active"><img src="/itms.arukustech.com/public/assets/login/slide1.png"></div>
                <div class="slide"><img src="/itms.arukustech.com/public/assets/login/slide2.png"></div>
                <div class="slide"><img src="/itms.arukustech.com/public/assets/login/slide3.png"></div>

                <div class="dots">
                    <span class="active"></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT (UNCHANGED) -->
    <div class="right">
        <div class="brand">IT Inventory</div>

        <h2>Log in to get started</h2>
        <p>Please enter the following information to access your account</p>

        <?php if ($msg): ?>
            <div class="alert"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="email" name="email" placeholder="User ID" required>
            <input type="text" name="password" placeholder="Password" required>
            <a href="/forgot.php" class="forgot">Forgot Password</a><br>
            <button type="submit">Sign in</button>
        </form>

        <footer>Copyright ©<?= date('Y') ?> Arukus Technologies.</footer>
    </div>

</div>

<script>
let slides=document.querySelectorAll('.slide');
let dots=document.querySelectorAll('.dots span');
let i=0;

setInterval(()=>{
    slides[i].classList.remove('active');
    dots[i].classList.remove('active');
    i=(i+1)%slides.length;
    slides[i].classList.add('active');
    dots[i].classList.add('active');
},4000);
</script>

 <script>
    // 1. Disable Right-click
    document.addEventListener('contextmenu', event => event.preventDefault());

    // 2. Disable Keyboard Shortcuts
    document.onkeydown = function(e) {
        // F12
        if (e.keyCode == 123) return false;

        // Ctrl+Shift+I (Inspect)
        if (e.ctrlKey && e.shiftKey && e.keyCode == 'I'.charCodeAt(0)) return false;

        // Ctrl+Shift+J (Console)
        if (e.ctrlKey && e.shiftKey && e.keyCode == 'J'.charCodeAt(0)) return false;

        // Ctrl+Shift+C (Element Selector)
        if (e.ctrlKey && e.shiftKey && e.keyCode == 'C'.charCodeAt(0)) return false;

        // Ctrl+U (View Source)
        if (e.ctrlKey && e.keyCode == 'U'.charCodeAt(0)) return false;
    };

    // 3. Constant Debugger Loop (Optional)
    // This makes it extremely difficult to use the console even if opened
    setInterval(function() {
        debugger;
    }, 100);
</script> 


</body>
</html>

