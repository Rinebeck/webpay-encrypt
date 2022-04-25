<?php

require_once("constants.php");
/**
 * @author Rinebeck
 * @version 1.1
 * @date 2022/04/18
 * 
 * En php.ini habilitar la linea extension=php_openssl.dll (o equivalente a linux)
 */

class AES
{
  private const CIPHER_KEY_LEN = 16;
  private const REQUEST_URL = "https://bc.mitec.com.mx/p/gen";
  private const CYPHER_ALGO = "AES-128-CBC";

  private $key128;
  private $xmlString;
  private $encryptedString;
  private $data;
  private $response;

  function __construct($key128)
  {
    $this->key128 = $key128;
  }
  /**
   * Permite cifrar una cadena a partir de un llave proporcionada
   * @param strToEncrypt
   * @param key
   * @return String con la cadena encriptada
   */

  public function encryptXml()
  {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(strtolower(static::CYPHER_ALGO)));
    $cipherText = openssl_encrypt($this->xmlString, static::CYPHER_ALGO, hex2bin($this->key128), 1, $iv);
    return base64_encode($iv . $cipherText);
  }

  /**
   * Permite descifrar una cadena a partir de un llave proporcionada
   * @param strToDecrypt
   * @param key
   * @return String con la cadena descifrada
   */

  public function decrypt()
  {
    $encodedInitialData = base64_decode($this->encryptedString);
    $iv = substr($encodedInitialData, 0, 16);
    $encodedInitialData = substr($encodedInitialData, 16);
    $decrypted = openssl_decrypt($encodedInitialData, static::CYPHER_ALGO, hex2bin($this->key128), 1, $iv);
    return $decrypted;
  }

  public static function getUniqId()
  {
    return strtoupper(uniqid("BLLB"));
  }

  public function setXMLString($xml)
  {
    $this->xmlString = $xml;
  }

  public function setEncryptedString($encrypted)
  {
    $this->encryptedString = $encrypted;
  }

  public function setData($data)
  {
    $this->data = $data;
  }

  public function validateXML()
  {
    $xml = new DOMDocument();
    $xml->loadXML($this->xmlString, LIBXML_NOBLANKS);
    return $xml->schemaValidate("xml/validate.xsd");
  }

  /**
   * Selecciona los primeros 16 byte del hash de la clave
   * 
   * @return string 16 bytes de del hash de la clave enviada por el Banco
   */
  private function getFixedKey()
  {
    if (strlen(DATA_ZERO) < static::CIPHER_KEY_LEN) {
      //0 pad to len 16
      return str_pad(DATA_ZERO, static::CIPHER_KEY_LEN, "0");
    }

    if (strlen(DATA_ZERO) > static::CIPHER_KEY_LEN) {
      //truncate to 16 bytes
      return substr(DATA_ZERO, 0, static::CIPHER_KEY_LEN);
    }

    return DATA_ZERO;
  }

  private function encrypt()
  {
    $ivlen = openssl_cipher_iv_length(static::CYPHER_ALGO);
    $iv = openssl_random_pseudo_bytes($ivlen);
    $encodedEncryptedData = base64_encode(openssl_encrypt($this->data, static::CYPHER_ALGO, static::getFixedKey(), OPENSSL_RAW_DATA, $iv));
    $encodedIV = base64_encode($iv);
    $encryptedPayload = $encodedEncryptedData . ":" . $encodedIV;

    return base64_encode($encryptedPayload);
  }

  public function send()
  {
    $payload = $this->encrypt();
    // var_dump($this->data);exit;
    // send the request
    $postfields = str_replace(
      ["{DATA_ZERO}", "{DATA}"],
      [DATA_ZERO, $payload],
      "xml=<pgs><data0>{DATA_ZERO}</data0><data>{DATA}</data></pgs>",
    );
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, static::REQUEST_URL);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $response = curl_exec($ch);
    curl_close($ch);
    return $this->response = $response;
  }

  public function getResponse()
  {
    return $this->response;
  }
}
