function showWhatIsoneqlick(e) {
  e.preventDefault();
  document.getElementById("what-is-oneqlick-button").style.display = "none";
  document.getElementById("what-is-oneqlick-text").style.display = "";
}

function hideWhatIsoneqlick(e) {
  e.preventDefault();
  document.getElementById("what-is-oneqlick-button").style.display = "";
  document.getElementById("what-is-oneqlick-text").style.display = "none";
}

document.addEventListener("DOMContentLoaded", function() {
  document.getElementById("what-is-oneqlick-button").addEventListener("click", showWhatIsoneqlick)
  document.getElementById("what-is-oneqlick-text").addEventListener("click", hideWhatIsoneqlick)
});

function oneqlickStatus() {
  const xhttp = new XMLHttpRequest();
  xhttp.onload = function() {
    const result = JSON.parse(this.responseText);
    const qrcode = document.getElementById("oneqlick-status-qrcode");
    const oneqlick = document.getElementById("oneqlick-status-1qlick");
    const payment = document.getElementById("oneqlick-status-payment");

    console.log(result);
    console.log(result.status);
    switch (result.status) {
      case "paid":
        window.location.href = result.redirect;
        break;
      case "1qlick":
        if (oneqlick.style.display === "none") {
          qrcode.style.display = "none";
          oneqlick.style.display = ""
          payment.style.display = "none"
        }
        break;
      case "payment":
        if (payment.style.display === "none") {
          qrcode.style.display = "none";
          oneqlick.style.display = "none";
          payment.style.display = ""
        }
        break;
      case "unset":
        if (qrcode.style.display === "none") {
          qrcode.style.display = "";
          oneqlick.style.display = "none";
          payment.style.display = "none";
        }
        break;
    }
    window.setTimeout(oneqlickStatus, 5000);
  }
  xhttp.open("GET", "/wc-api/1qlick_status", true);
  xhttp.send();
}

oneqlickStatus();