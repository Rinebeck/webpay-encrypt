<?php

class AesCipher
{

    private const OPENSSL_CIPHER_NAME = "aes-128-ecb";
    private const CIPHER_KEY_LEN = 16; //128 bits

    private $keyHash;
    private $bankKey;
    private $data;

    function __construct($bankKey, $data = null)
    {
        $this->bankKey = $bankKey;
        # Generacion del hash a partir de la clave secreta del banco
        $this->keyHash = $this->createKeyhash();
        $this->data = $data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getKeyHash()
    {
        return $this->keyHash;
    }
    /**
     * Encripta datos en AES ECB de 128 bit key
     * 
     * @return string Hash en sha 256 de la clave enviada por el banco
     */
    public function createKeyhash()
    {
        return hash("sha256", $this->bankKey, true);
    }
    /**
     * Selecciona los primeros 16 byte del hash de la clave
     * 
     * @param string $key - Hash en sha 256 de la clave enviada por el banco
     * @return string 16 bytes de del hash de la clave enviada por el Banco
     */
    private function fixKey()
    {

        if (strlen($this->key) < AesCipher::CIPHER_KEY_LEN) {
            //0 pad to len 16
            return str_pad($this->key, AesCipher::CIPHER_KEY_LEN, "0");
        }

        if (strlen($this->key) > AesCipher::CIPHER_KEY_LEN) {
            //truncate to 16 bytes
            return substr($this->key, 0, AesCipher::CIPHER_KEY_LEN);
        }

        return $this->key;
    }
    /**
     * Encripta datos en AES ECB de 128 bit key
     * 
     * @param string $key - Clave enviada por el banco debe ser de 16 bytes en sha-256
     * @param string $data - Datos a ser cifrados
     * @return encrypted Datos cifrados
     */
    public function encrypt()
    {
        $fixedKey = $this->fixKey($this->keykey);
        $encodedEncryptedData = base64_encode(openssl_encrypt($this->data, AesCipher::OPENSSL_CIPHER_NAME, $fixedKey, OPENSSL_PKCS1_PADDING));
        return $encodedEncryptedData;
    }
    /**
     * Desencripta datos en AES ECB de 128 bit key
     * 
     * @param string $key - Clave enviada por el banco debe ser de 16 bytes en sha-256
     * @param string $data - Datos a ser cifrados
     * @return decrypted Datos Desencriptados
     */
    public function decrypt()
    {
        $fixedKey = $this->fixKey($this->keykey);
        $decryptedData = openssl_decrypt(base64_decode($this->data), AesCipher::OPENSSL_CIPHER_NAME, $fixedKey, OPENSSL_PKCS1_PADDING);
        return $decryptedData;
    }
};
