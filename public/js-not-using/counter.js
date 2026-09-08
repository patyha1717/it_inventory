(function () {
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
        // Update UI
        document.getElementById("total").innerText = data.total;
        document.getElementById("sent").innerText = data.sent;
        document.getElementById("failed").innerText = data.failed;
        document.getElementById("queued").innerText = data.queue;
        if(data.queue==0){
          document.querySelector(".startsending").style.display = "none";
          document.querySelector(".pausesmail").style.display = "none";
          if(data.status !==''){
           document.getElementById("status").innerText=data.status;
          }
          if(intervalId !== null) {
                clearInterval(intervalId);
                intervalId = null;
            }
        }
    }
}
var status = document.getElementById("status").innerText;
if (status === 'queued' || status === 'sending') {
    intervalId = setInterval(loadProgress, 5000);
}
})();