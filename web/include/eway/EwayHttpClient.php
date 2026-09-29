<?php

class EwayHttpClient
{
    public static function postJson($url, array $headers, $bodyJson, $timeout = 60)
    {
        $ch = curl_init($url);
        $hdr = array('Content-Type: application/json', 'Accept: application/json');
        foreach ($headers as $k => $v) {
            $hdr[] = $k . ': ' . $v;
        }
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $bodyJson,
            CURLOPT_HTTPHEADER => $hdr,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
        ));
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($errno) {
            throw new RuntimeException('GST API connection failed: ' . $err);
        }
        return array('http' => $http, 'body' => $body === false ? '' : $body);
    }

    public static function get($url, array $headers, $timeout = 60)
    {
        $ch = curl_init($url);
        $hdr = array('Accept: application/json');
        foreach ($headers as $k => $v) {
            $hdr[] = $k . ': ' . $v;
        }
        curl_setopt_array($ch, array(
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => $hdr,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
        ));
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($errno) {
            throw new RuntimeException('GST API connection failed: ' . $err);
        }
        return array('http' => $http, 'body' => $body === false ? '' : $body);
    }

    public static function apiHeaders(array $settings, $authtoken)
    {
        return array(
            'client-id' => trim($settings['client_id'] ?? ''),
            'client-secret' => trim($settings['client_secret'] ?? ''),
            'gstin' => trim($settings['gstin'] ?? ''),
            'authtoken' => trim($authtoken),
        );
    }
}
