<?php

class EwayNicCrypto
{
    /**
     * RSA encrypt with NIC public key (Base64 DER SubjectPublicKeyInfo).
     */
    public static function rsaEncrypt($plainText, $publicKeyBase64)
    {
        $publicKeyBase64 = trim(preg_replace('/\s+/', '', $publicKeyBase64));
        if ($publicKeyBase64 === '') {
            throw new RuntimeException('NIC public key is not configured.');
        }
        $der = base64_decode($publicKeyBase64, true);
        if ($der === false) {
            throw new RuntimeException('NIC public key is not valid Base64.');
        }
        $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
        $key = openssl_pkey_get_public($pem);
        if ($key === false) {
            throw new RuntimeException('Unable to load NIC public key.');
        }
        $encrypted = '';
        $ok = openssl_public_encrypt($plainText, $encrypted, $key, OPENSSL_PKCS1_PADDING);
        if (!$ok) {
            throw new RuntimeException('RSA encryption failed.');
        }
        return base64_encode($encrypted);
    }

    /**
     * AES-256-ECB encrypt (NIC: input is Base64 of UTF-8 JSON; output Base64 ciphertext).
     */
    public static function aesEncryptWithSek($base64Utf8Payload, $sekBase64)
    {
        $key = base64_decode($sekBase64, true);
        if ($key === false || strlen($key) === 0) {
            throw new RuntimeException('Invalid session encryption key.');
        }
        $data = base64_decode($base64Utf8Payload, true);
        if ($data === false) {
            throw new RuntimeException('Invalid payload encoding.');
        }
        $cipher = openssl_encrypt($data, 'AES-256-ECB', $key, OPENSSL_RAW_DATA);
        if ($cipher === false) {
            throw new RuntimeException('AES encryption failed.');
        }
        return base64_encode($cipher);
    }

    /**
     * AES-256-ECB decrypt NIC response data field; returns Base64 of inner JSON.
     */
    public static function aesDecryptWithSek($encryptedBase64, $sekBase64)
    {
        $key = base64_decode($sekBase64, true);
        if ($key === false || strlen($key) === 0) {
            throw new RuntimeException('Invalid session encryption key.');
        }
        $cipher = base64_decode($encryptedBase64, true);
        if ($cipher === false) {
            throw new RuntimeException('Invalid encrypted response.');
        }
        $plain = openssl_decrypt($cipher, 'AES-256-ECB', $key, OPENSSL_RAW_DATA);
        if ($plain === false) {
            throw new RuntimeException('AES decryption failed.');
        }
        return base64_encode($plain);
    }

    /**
     * Decrypt SEK from auth response using 32-byte app key string.
     */
    public static function decryptSekFromAuth($encryptedSekBase64, $appKey32)
    {
        $key = $appKey32;
        if (strlen($key) !== 32) {
            $key = substr(str_pad($key, 32, "\0"), 0, 32);
        }
        $cipher = base64_decode($encryptedSekBase64, true);
        if ($cipher === false) {
            throw new RuntimeException('Invalid SEK in auth response.');
        }
        $plain = openssl_decrypt($cipher, 'AES-256-ECB', $key, OPENSSL_RAW_DATA);
        if ($plain === false) {
            throw new RuntimeException('Failed to decrypt SEK.');
        }
        return base64_encode($plain);
    }

    public static function decodeInnerJson($base64Inner)
    {
        $json = base64_decode($base64Inner, true);
        if ($json === false) {
            throw new RuntimeException('Invalid inner Base64.');
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Invalid JSON in API response.');
        }
        return $decoded;
    }

    public static function generateAppKey32()
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $out = '';
        for ($i = 0; $i < 32; $i++) {
            $out .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $out;
    }
}
