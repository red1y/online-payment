<?php

    function aesEncrypt($key, $iv, $message) {
        $cipher = openssl_encrypt($message, "AES-256-CBC", $key, 0, $iv) or die("aes encrypt error ".openssl_error_string());
        return $cipher;
    }

    function aesDecrypt($key, $iv, $cipher) {
        $message = openssl_decrypt($cipher, "AES-256-CBC", $key, 0, $iv) or die("aes decrypt error ".openssl_error_string());
        return $message;
    }

    function pkEncrypt($pk, $message) {
        $cipher = "";
        openssl_public_encrypt($message, $cipher, $pk) or die("pk encrypt error ".openssl_error_string());
        $cipher = base64_encode($cipher) or die("base64 encode error");
        return $cipher;
    }

    function skDecrypt($sk, $cipher) {
        $message = "";
        $cipher = base64_decode($cipher, true) or die("base64 decode error");
        openssl_private_decrypt($cipher, $message, $sk) or die("sk decrypt error ".openssl_error_string());
        return $message;
    }

    function skEncrypt($sk, $message) {
        $cipher = "";
       openssl_private_encrypt($message, $cipher, $sk) or die("sk encrypt error ".openssl_error_string());
        $cipher = base64_encode($cipher);
        return $cipher;
    }
    
    function pkDecrypt($pk, $cipher) {
        $message = "";
        $cipher = base64_decode($cipher, true) or die("base64 decode error");
        openssl_public_decrypt($cipher, $message, $pk) or die("pk decrypt error ".openssl_error_string());
        return $message;
    }

    function loadKey($keyFile, $kind) {
        $key = file_get_contents($keyFile) or die("open key file error");
        if($kind == 0) {
            $key = openssl_pkey_get_private($key) or die("read sk error ".openssl_error_string());
        } else {
            $key = openssl_pkey_get_public($key) or die("read pk error ".openssl_error_string());
        }
        return $key;
    }
    
    function loadKeyStr($keyStr, $kind) {
        $key = $keyStr;
        if($kind == 0) {
            $key = openssl_pkey_get_private($key) or die("read sk error ".openssl_error_string());
        } else {
            $key = openssl_pkey_get_public($key) or die("read pk error ".openssl_error_string());
        }
        return $key;
    }

    // aes-256-cbc use 128bit iv
    function genIv128() {
        $strong  = false;
        $iv = openssl_random_pseudo_bytes(16, $strong) or die("iv gen error"); $strong or die("strong iv error");
        return $iv;
    }

    function genKey256() {
        $strong = false;
        $key = openssl_random_pseudo_bytes(32, $strong) or die("key gen error"); $strong or die("strong key error");
        return $key;
    }

?>