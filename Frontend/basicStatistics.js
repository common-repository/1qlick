document.addEventListener("DOMContentLoaded", function () {
  track1Qlick("buttonDisplayed");
  get1QlickButton().addEventListener("mouseenter", function (e) {
    track1Qlick("buttonHovered");
  });
  getExplanationButton().addEventListener("click", function (e) {
    track1Qlick("explanationClicked");
  });
});

function get1QlickButton() {
  return document.getElementById("oneqlick-checkout-link");
}

function getExplanationButton() {
  return document.getElementById("what-is-oneqlick-button");
}

function getShopAndSession() {
  return get1QlickButton().href.split("/").slice(-2);
}

function getRemoteURL() {
  return get1QlickButton().href.split("/").slice(0, 3).join("/");
}

function track1Qlick(event) {
  // event should be one of "buttonDisplayed", "buttonHovered", "explanationClicked"
  const [shopUrl, sessionId] = getShopAndSession();
  const url = getRemoteURL();
  const xhttp = new XMLHttpRequest();
  xhttp.onload = function () {
    const result = this.responseText;
    console.log(result);
  };
  xhttp.open("POST", url + "/api/statistics/", true);
  xhttp.setRequestHeader("content-type", "application/x-www-form-urlencoded");
  xhttp.send("event=" + event + "&shop=" + shopUrl + "&session=" + sessionId);
}
