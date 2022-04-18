<?php

/**
 * @author Mercadotecnia, Ideas y Tecnologia
 * @version 1.0
 * @date 2017/10/10
 * 
 * En php.ini habilitar la linea extension=php_openssl.dll (o equivalente a linux)
 */

class AES
{
  private $key128;
  private $xmlString;
  private $encryptedString;

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

  public function encriptar()
  {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-128-cbc'));
    $cipherText = openssl_encrypt($this->xmlString, 'AES-128-CBC', hex2bin($this->key128), 1, $iv);
    return base64_encode($iv . $cipherText);
  }

  /**
   * Permite descifrar una cadena a partir de un llave proporcionada
   * @param strToDecrypt
   * @param key
   * @return String con la cadena descifrada
   */

  public function desencriptar()
  {
    $encodedInitialData = base64_decode($this->encryptedString);
    $iv = substr($encodedInitialData, 0, 16);
    $encodedInitialData = substr($encodedInitialData, 16);
    $decrypted = openssl_decrypt($encodedInitialData, 'AES-128-CBC', hex2bin($this->key128), 1, $iv);
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

  public function validateXML()
  {
    $xml = new DOMDocument();
    $xml->loadXML($this->xmlString, LIBXML_NOBLANKS);
    return $xml->schemaValidate("xml/validate.xsd");
  }

  public function send($key, $data)
  {
    $ivlen = openssl_cipher_iv_length('AES-128-CBC');
    $iv = openssl_random_pseudo_bytes($ivlen);
    $encodedEncryptedData = base64_encode(openssl_encrypt($data, 'aes-128-cbc', AesCipher::fixKey($key), OPENSSL_RAW_DATA, $iv));
    $encodedIV = base64_encode($iv);
    $encryptedPayload = $encodedEncryptedData . ":" . $encodedIV;

    return base64_encode($encryptedPayload);
  }
}
