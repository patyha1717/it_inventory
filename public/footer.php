</div> <!-- END main-content -->

  <!-- Footer (fixed) -->
  <footer class="main-footer custom-footer text-center">
    <div class="footer-inner d-flex justify-content-between align-items-center">
        <div class="footer-left">
            <strong>IT Inventory Management </strong>
        </div>
        <div class="footer-right">
          <!-- <b>Send smarter. Deliver faster</b> -->
            © <?=date('Y')?> | Designed by <b>Arukus Technologies.</b>
        </div>
    </div>
</footer>


<!-- =======================
     Core JS Dependencies
======================= -->

<!-- jQuery MUST load first -->
<script src="/itms.arukustech.com/public/assets/js/jquery.min.js"></script>

<!-- Bootstrap -->
<script src="/itms.arukustech.com/public/assets/js/bootstrap.bundle.min.js"></script>

<!-- App scripts -->
<script src="/itms.arukustech.com/public/assets/js/app.js"></script>
<script src="/itms.arukustech.com/public/assets/js/main.js"></script>
<script src="/itms.arukustech.com/public/assets/js/counter.js"></script>
<script src="/itms.arukustech.com/public/assets/js/attachments.js"></script>
<script src="/itms.arukustech.com/public/assets/js/preview.js"></script>

<!-- Charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Auto-close User Menu -->
<script>
document.addEventListener("click", function(e){
    const dropdown = document.getElementById("userDropdown");
    const userMenu = document.querySelector(".user-menu");

    if (dropdown && userMenu && !userMenu.contains(e.target)) {
        dropdown.style.display = "none";
    }
});
</script>

<script>
setTimeout(() => {
    let alerts = document.querySelectorAll('.alert');
    alerts.forEach(a => a.style.display = 'none');
}, 3000);
</script>

<!-- Allow per-page JS injection -->
<?php
if (isset($pageScript)) {
    echo $pageScript;
}
?>

<script>
/**
 * Modern Clock & Greeting Engine
 * Optimized for scannability and performance
 */
(function() {
    function updateDashboardHeader() {
        const now = new Date();
        const hrs = now.getHours();
        
        // 1. Determine Greeting based on time
        let greetText = "Good Evening";
        if (hrs < 12) greetText = "Good Morning";
        else if (hrs < 17) greetText = "Good Afternoon";
        
        // 2. Select Elements
        const elGreet = document.getElementById('topbar_greet');
        const elClock = document.getElementById('topbar_clock');
        const elDate  = document.getElementById('topbar_date');

        // 3. Inject Content with fallback checks
        if(elGreet) elGreet.textContent = greetText;

        if(elClock) {
            elClock.textContent = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit', 
                hour12: true 
            });
        }

        if(elDate) {
            elDate.textContent = now.toLocaleDateString('en-US', { 
                weekday: 'long', 
                month: 'short', 
                day: 'numeric',
                year: 'numeric'
            });
        }
    }

    // Refresh every 1000ms (1 second)
    setInterval(updateDashboardHeader, 1000);
    
    // Trigger immediately so user doesn't see "00:00:00"
    if (document.readyState === "complete" || document.readyState === "interactive") {
        updateDashboardHeader();
    } else {
        document.addEventListener("DOMContentLoaded", updateDashboardHeader);
    }
})();
</script>


</body>


</html>
