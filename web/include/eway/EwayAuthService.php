<?php

require_once __DIR__ . '/EwayNicCrypto.php';
require_once __DIR__ . '/EwayHttpClient.php';
require_once __DIR__ . '/eway_schema.php';

class EwayAuthService
{
    private $conn;
    private $settings;

    public function __construct($conn, array $settings)
    {
        $this->conn = $conn;
        $this->settings = $settings;
    }

    public function getSession($forceRefresh = false)
    {
        $gstin = trim($this->settings['gstin'] ?? '');
        if ($gstin === '') {
            throw new RuntimeException('GSTIN is not configured in E-Way API settings.');
        }

        if (!$forceRefresh) {
            $cached = $this->loadCache($gstin);
            if ($cached) {
                return $cached;
            }
        }

        return $this->authenticate($gstin);
    }

    private function loadCache($gstin)
    {
        $esc = mysqli_real_escape_string($this->conn, $gstin);
        $res = mysqli_query($this->conn, "SELECT * FROM eway_auth_cache WHERE gstin='$esc' LIMIT 1");
        if (!$res || mysqli_num_rows($res) === 0) {
            return null;
        }
        $row = mysqli_fetch_assoc($res);
        if (empty($row['authtoken']) || empty($row['sek_enc'])) {
            return null;
        }
        if (!empty($row['token_expiry'])) {
            $exp = strtotime($row['token_expiry']);
            if ($exp && $exp <= time() + 120) {
                return null;
            }
        }
        $sek = ew_data_decrypt($row['sek_enc']);
        if ($sek === '') {
            return null;
        }
        return array(
            'authtoken' => $row['authtoken'],
            'sek' => $sek,
        );
    }

    private function authenticate($gstin)
    {
        $base = rtrim(trim($this->settings['api_base_url'] ?? ''), '/');
        if ($base === '') {
            throw new RuntimeException('API base URL is not configured.');
        }
        $authPath = $this->settings['auth_path'] ?? '/authenticate';
        if ($authPath[0] !== '/') {
            $authPath = '/' . $authPath;
        }
        $url = $base . $authPath;

        $username = trim($this->settings['api_username'] ?? '');
        $password = trim($this->settings['api_password'] ?? '');
        $pubKey = trim($this->settings['nic_public_key'] ?? '');
        if ($username === '' || $password === '' || $pubKey === '') {
            throw new RuntimeException('API username, password, and NIC public key are required.');
        }

        $appKey = EwayNicCrypto::generateAppKey32();
        $payload = array(
            'action' => 'ACCESSTOKEN',
            'username' => $username,
            'password' => EwayNicCrypto::rsaEncrypt($password, $pubKey),
            'app_key' => EwayNicCrypto::rsaEncrypt($appKey, $pubKey),
        );
        $bodyJson = json_encode($payload);

        $headers = EwayHttpClient::apiHeaders($this->settings, '');
        unset($headers['authtoken']);
        $resp = EwayHttpClient::postJson($url, $headers, $bodyJson);

        $parsed = json_decode($resp['body'], true);
        if (!is_array($parsed)) {
            throw new RuntimeException('Invalid authentication response from GST.');
        }

        $status = (string) ($parsed['status'] ?? $parsed['Status'] ?? '0');
        if ($status !== '1') {
            $msg = $this->extractError($parsed);
            throw new RuntimeException($msg ?: 'GST authentication failed.');
        }

        $authtoken = $parsed['authtoken'] ?? $parsed['AuthToken'] ?? '';
        if ($authtoken === '' && isset($parsed['data']['AuthToken'])) {
            $authtoken = $parsed['data']['AuthToken'];
        }
        $sekEnc = $parsed['sek'] ?? $parsed['Sek'] ?? '';
        if ($sekEnc === '' && isset($parsed['data']['Sek'])) {
            $sekEnc = $parsed['data']['Sek'];
        }
        if ($authtoken === '' || $sekEnc === '') {
            throw new RuntimeException('Authentication response missing token or session key.');
        }

        $sek = EwayNicCrypto::decryptSekFromAuth($sekEnc, $appKey);

        $expirySql = 'NULL';
        $expiryRaw = $parsed['tokenExpiry'] ?? $parsed['TokenExpiry'] ?? '';
        if ($expiryRaw !== '') {
            $ts = strtotime($expiryRaw);
            if ($ts) {
                $expirySql = "'" . mysqli_real_escape_string($this->conn, date('Y-m-d H:i:s', $ts)) . "'";
            }
        } else {
            $expirySql = "'" . mysqli_real_escape_string($this->conn, date('Y-m-d H:i:s', time() + 360 * 60)) . "'";
        }

        $escGstin = mysqli_real_escape_string($this->conn, $gstin);
        $escToken = mysqli_real_escape_string($this->conn, $authtoken);
        $escSek = mysqli_real_escape_string($this->conn, ew_data_encrypt($sek));
        $escApp = mysqli_real_escape_string($this->conn, ew_data_encrypt($appKey));
        $now = mysqli_real_escape_string($this->conn, date('Y-m-d H:i:s'));

        mysqli_query($this->conn, "INSERT INTO eway_auth_cache (gstin, authtoken, sek_enc, app_key_enc, token_expiry, updated_at)
            VALUES ('$escGstin','$escToken','$escSek','$escApp',$expirySql,'$now')
            ON DUPLICATE KEY UPDATE authtoken='$escToken', sek_enc='$escSek', app_key_enc='$escApp', token_expiry=$expirySql, updated_at='$now'");

        return array('authtoken' => $authtoken, 'sek' => $sek);
    }

    private function extractError(array $parsed)
    {
        if (!empty($parsed['error']['message'])) {
            return (string) $parsed['error']['message'];
        }
        if (!empty($parsed['ErrorDetails'][0]['ErrorMessage'])) {
            return (string) $parsed['ErrorDetails'][0]['ErrorMessage'];
        }
        if (!empty($parsed['error'])) {
            return is_string($parsed['error']) ? $parsed['error'] : json_encode($parsed['error']);
        }
        return '';
    }

    public function clearCache($gstin = null)
    {
        $gstin = $gstin ?: trim($this->settings['gstin'] ?? '');
        if ($gstin === '') {
            return;
        }
        $esc = mysqli_real_escape_string($this->conn, $gstin);
        mysqli_query($this->conn, "DELETE FROM eway_auth_cache WHERE gstin='$esc'");
    }
}
