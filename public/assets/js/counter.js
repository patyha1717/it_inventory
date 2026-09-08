(function () {

    // Check if required elements exist; otherwise stop script
    const requiredIds = ["campaignid", "total", "sent", "failed", "queued", "status"];
    const missing = requiredIds.some(id => document.getElementById(id) === null);
    if (missing) return; // <-- FIX: Stop running on other pages

    let intervalId = null;

    async function loadProgress() {
        let campaignid = document.getElementById("campaignid").value;

        if (campaignid > 0) {
            let formData = new FormData();
            formData.append("campaignid", campaignid);

            const res = await fetch("mail_counter", {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            });

            const data = await res.json();

            // Update UI safely
            document.getElementById("total").innerText = data.total ?? "0";
            document.getElementById("sent").innerText = data.sent ?? "0";
            document.getElementById("failed").innerText = data.failed ?? "0";
            document.getElementById("queued").innerText = data.queue ?? "0";

            if (data.queue == 0) {
                const startBtn = document.querySelector(".startsending");
                const pauseBtn = document.querySelector(".pausesmail");

                if (startBtn) startBtn.style.display = "none";
                if (pauseBtn) pauseBtn.style.display = "none";

                if (data.status !== '') {
                    document.getElementById("status").innerText = data.status;
                }

                if (intervalId !== null) {
                    clearInterval(intervalId);
                    intervalId = null;
                }
            }
        }
    }

    let status = document.getElementById("status").innerText;

    if (status === 'queued' || status === 'sending') {
        intervalId = setInterval(loadProgress, 5000);
    }

})();
