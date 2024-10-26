document.addEventListener("DOMContentLoaded", function () {
  var linkElement = document.getElementById("oneqlick-checkout-link");
  var qrcodeElement = document.getElementById("oneqlick-status-qrcode");
  var oneqlickLogoElement = document.getElementById("oneqlick-logo");
  var logoURL = oneqlickLogoElement.src;
  var size = qrcodeElement.clientWidth;
  if (size < 200) {
    size = 200;
  }
  if (size > 320) {
    size = 320;
  }
  var qrcode = new QRCode(qrcodeElement, {
    text: linkElement.href,
    width: size,
    height: size,
    colorDark: "#FA7146",
    colorLight: "#FFFFFF",
    correctLevel: QRCode.CorrectLevel.H,
    dotScale: 1,
    logo: logoURL,
    crossOrigin: null,
  });
});
