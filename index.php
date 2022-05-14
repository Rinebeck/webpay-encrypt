<?php
require_once("AES.php");
require_once("constants.php");

header('Content-Type: application/json');

$aes = new AES();

if (isset($_POST['action']) && $_POST['action'] == 'get_url') {
    
    if (isset($_POST["transaction_type"])) {
        $transaction_type = $_POST["transaction_type"];
    }

    try {
        $response = $aes->prepareTransaction($transaction_type, [
            'AMOUNT' => $_POST["amount"],
            'CUSTOMER_EMAIL' => $_POST["email"],
        ])
        ->postRequest()
        ->decrypt()
        ->toObject();
        echo json_encode(['response' => $response]);
    } catch (Exception $e) {
        echo json_encode(array(
            'error' => $e->getMessage(),
        ));
    }
    die;
} else if (isset($_POST['accion']) && $_POST['accion'] == 'desencriptar') {
    if (!isset($_POST['cadena'])) {
        echo json_encode(array('error' => 'Missing required parameters.'));
        exit;
    }
    $aes->setEncryptedString($_POST['cadena']);
    $key128 = $_POST['key128'];
    $plaintext = $aes->decrypt();
    echo json_encode(["plain" => $plaintext]);
} else if (isset($_POST['strResponse']) && $strResponse = $_POST['strResponse']) {
    // TODO: decide where to store the strResponse
} else {
    echo json_encode(["error" => "No action specified."]);
}
die();
