const ENDPOINT_URL = "https://clickclack.mx/encrypt/";

const getPaymentURL = (amount, transaction_type, email) => {
  var formData = new FormData();
  formData.append("action", "get_url");
  formData.append("transaction_type", transaction_type);
  formData.append("amount", amount);
  formData.append("email", email);

  var requestOptions = {
    method: "POST",
    body: formData,
    redirect: "follow",
  };

  return fetch(ENDPOINT_URL, requestOptions)
    .then((response) => response.json())
    .then((result) => result.response)
    .catch((error) => console.log("error", error));
};

document.querySelector("#webpayplus").addEventListener("click", () => {
  const email = document.querySelector('[name="email"]').value;
  const amount = parseFloat(
    document
      .querySelector(".w-commerce-commercecheckoutsummarytotal")
      .innerText.replace(/[^0-9.]/g, "")
  );
  if (email && amount) {
    const response = getPaymentURL(amount, "deferred_charge", email);
    response.then((response) => {
      const iframeUrl = response.nb_url;
      // create a dialog with iframe inside
      const dialog = document.createElement("dialog");
      dialog.setAttribute("id", "paymentDialog");
      const iframe = document.createElement("iframe");
      iframe.src = iframeUrl;
      iframe.style = "overflow:hidden;height:100%;width:100%";
      iframe.setAttribute("frameborder", "0");
      iframe.setAttribute("width", "100%");
      iframe.setAttribute("height", "100%");

      dialog.appendChild(iframe);
      document.body.appendChild(dialog);
      loadCancelButton();
      dialog.showModal();
    });
  } else {
    alert("Please enter your email.");
  }
});

const loadCancelButton = () => {
  const cancelButton = document.createElement("button");
  const dialog = document.getElementById("paymentDialog");
  cancelButton.setAttribute("id", "cancelButton");
  cancelButton.innerText = "Cancelar";
  cancelButton.addEventListener("click", function () {
    dialog.close();
  });
  dialog.appendChild(cancelButton);
};
