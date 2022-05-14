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

