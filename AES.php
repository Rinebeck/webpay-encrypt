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
  private const REQUEST_URL = "https://bc.mitec.com.mx/p/gen";
  private const CYPHER_ALGO = 'AES-128-CBC';
  private const OPENSSL_CIPHER_NAME = 'aes-128-cbc';
  private const TRANSACTION_TYPES = [
    "3ds2",
    "campaign",
    "deferred_charge",
    "prepayment",
    "single_exhibition",
    "validate",
  ];

  private $key128;
  private $xmlString;
  private $xmlTemplate;
  private $encryptedString;
  private $data;
  private $response;

  function __construct($key128 = CYPHER_KEY)
  {
    $this->key128 = $key128;
  }

  /**
   * Permite cifrar una cadena a partir de un llave proporcionada
   * @param strToEncrypt
   * @param key
   * @return String con la cadena encriptada
   */

  public function encryptXML()
  {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::OPENSSL_CIPHER_NAME));
    $cipherText = openssl_encrypt($this->xmlString, self::CYPHER_ALGO, hex2bin($this->key128), 1, $iv);
    return base64_encode($iv . $cipherText);
  }

  /**
   * Permite descifrar una cadena a partir de un llave proporcionada
   * @param strToDecrypt
   * @param key
   * @return AES object instance
   */
  public function decrypt()
  {
    $encodedInitialData =  base64_decode($this->encryptedString);
    $iv = substr($encodedInitialData, 0, 16);
    $encodedInitialData = substr($encodedInitialData, 16);
    $this->response = openssl_decrypt($encodedInitialData, self::CYPHER_ALGO, hex2bin($this->key128), 1, $iv);
    return $this;
  }

  public function toObject() {
    if (!$this->response) {
      throw new Exception("No response found. Prepare and make a request first");
    } else {
      return simplexml_load_string($this->response);
    }
  }

  public static function getUniqId()
  {
    return strtoupper(uniqid("BLLB"));
  }

  /** @param array $params 
   * $params = [
   *      'COMPANY_ID' => (string) Defaults to COMPANY_ID global constant.
   *      'BRANCH_ID'  => (string) Defaults to BRANCH_ID global constant.
   *      'USER'       => (string) Defaults to USER global constant.
   *      'SECRET'     => (string) Defaults to SECRET global constant.
   *      'REFERENCE'  => (int) Defaults to a unique ID.
   *      'AMOUNT'     => (float) REQUIRED The transaction amount.
   *      'DATE'       => (string) Defaults to current date.
   *      'CUSTOMER_EMAIL' => (string) REQUIRED The customer email address.
   *    ]
   */
  public function prepareTransaction(string $transaction_type, array $params)
  {
    if ($params['AMOUNT'] == null) {
      throw new Exception("AMOUNT is required");
    }
    if ($params['CUSTOMER_EMAIL'] == null) {
      throw new Exception("CUSTOMER_EMAIL is required");
    }
    if (!$this->xmlTemplate) {
      $this->getXMLTemplate($transaction_type);
    }

    $next_day = new DateTime(date('Y-m-d'));
    $next_day->modify('+1 day');

    $search_array = [
      '{COMPANY_ID}',
      '{BRANCH_ID}',
      '{USER}',
      '{SECRET}',
      '{REFERENCE}',
      '{AMOUNT}',
      '{DATE}',
      '{CUSTOMER_EMAIL}',
    ];
    $replace_array = [
      $params['COMPANY_ID'] ?? COMPANY_ID,
      $params['BRANCH_ID'] ?? BRANCH_ID,
      $params['USER'] ?? USER,
      $params['SECRET'] ?? SECRET,
      $params['REFERENCE'] ?? self::getUniqId(),
      $params['AMOUNT'],
      $params['DATE'] ?? $next_day->format('d/m/Y'),
      $params['CUSTOMER_EMAIL'],
    ];
    $this->xmlString = str_replace($search_array, $replace_array, $this->xmlTemplate);
    return $this;
  }

  public function setXMLString($xml)
  {
    return $this->xmlString = $xml;
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
    $xml->loadXML($this->xmlString);
    return $xml->schemaValidate(__DIR__ . "/xml/validate.xsd");
  }

  public function getXMLTemplate(string $transaction_type)
  {
    if ($this->validateTransactionType($transaction_type)) {
      $this->xmlTemplate = file_get_contents(__DIR__ . "/xml/" . $transaction_type . ".xml");
    } else {
      throw new Exception("Invalid transaction type");
    }
  }

  /** 
   * @return AES| the AES object instance
   * @throws Exception
   */
  public function postRequest()
  {
    if ($this->validateXML()) {
      $this->setData($this->encryptXML());
      if (!empty($this->send())) {
        return $this;
      }
    } else {
      throw new Exception("Invalid XML.");
    }
  }

  private function validateTransactionType(string $transaction_type)
  {
    return in_array($transaction_type, static::TRANSACTION_TYPES);
  }

  public function send()
  {
    $requestBody = str_replace(
      ["{DATA_ZERO}", "{DATA}"],
      [DATA_ZERO, $this->data],
      "<pgs><data0>{DATA_ZERO}</data0><data>{DATA}</data></pgs>",
    );

    $payload = http_build_query(['xml' => $requestBody]) . "\n";
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => static::REQUEST_URL,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_POSTFIELDS => $payload,
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    $this->setEncryptedString($response);
    return $this->response = $response;
  }

  public function getResponse()
  {
    return $this->response;
  }
}
