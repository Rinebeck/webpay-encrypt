<?php
// turn on all errors
error_reporting(E_ALL);

require_once("AES.php");
require_once("constants.php");

//header('Content-Type: application/json');

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

    if ($aes->validateXML()) {
        $ciphertext = $aes->encryptXml();
        $aes->setData($ciphertext);
        $aes->send();
        echo json_encode(['response' => $aes->getResponse()]);
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
    $plaintext = $aes->decrypt();
    echo json_encode(["plain" => $plaintext]);
} else if (isset($_POST['accion']) && $_POST['accion'] == 'generar_key') {
/* $cvv = mb_convert_encoding("752", "UTF-8");

$bankKey = mb_convert_encoding("A9279120481620090622AA30", "UTF-8");

$aesCipher = new AesCipher($bankKey);

$cvvencrypt = $aesCipher->encrypt($cvv);

$decrypted = $aesCipher->decrypt($cvvencrypt);

var_dump(["cvv" => $cvv, "encrypt" => $cvvencrypt, "decrypt" => $decrypted]); */

} else {
    echo json_encode(["error" => "No action specified."]);
}
die();
