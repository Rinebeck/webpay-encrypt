<?php

require "AES.php";
require "constants.php";

header('Content-Type: application/json');

$aes = new AES(CYPHER_KEY);

if (isset($_POST['accion']) && $_POST['accion'] == 'encriptar') {
    
    if (isset($_POST["transaccion"]) && in_array($_POST["transaccion"], TRANSACTION_TYPES)) {
        $transaccion = $_POST["transaccion"];
    } else {
        echo json_encode(array("error" => "Invalid transaction type."));
    }

    // get xml based on transaction type
    $xml = file_get_contents("xml/".$transaccion.".xml");
    // edit xml
    $xml = str_replace("{COMPANY_ID}", COMPANY_ID, $xml);
    $xml = str_replace("{BRANCH_ID}", BRANCH_ID, $xml);
    $xml = str_replace("{USER}", USER, $xml);
    $xml = str_replace("{SECRET}", SECRET, $xml);
    $xml = str_replace("{REFERENCE}", AES::getUniqId(), $xml);
    $xml = str_replace("{AMOUNT}", $_POST["monto"], $xml);
    $xml = str_replace("{DATE}", date("d/m/Y"), $xml);
    $xml = str_replace("{CUSTOMER_EMAIL}", $_POST["email"], $xml);

    $aes->setXMLString($xml);

    $key128 = $_POST['key128'];
    if ($aes->validateXML()) {
        $ciphertext = $aes->encriptar();
        echo json_encode(["cipher" => $ciphertext]);
    } else {
        echo json_encode(["error" => "Invalid XML string."]);
    }

} else if (isset($_POST['accion']) && $_POST['accion'] == 'desencriptar') {
    if (!isset($_POST['cadena'])) {
        echo json_encode(array('error' => 'Missing required parameters.'));
        exit;
    }
    $aes->setEncryptedString($_POST['cadena']);
    $key128 = $_POST['key128'];
    $plaintext = $aes->desencriptar();
    echo json_encode(["plain" => $plaintext]);
} else {
    echo json_encode(["error" => "No action specified."]);
}
die();
