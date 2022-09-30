<?php

    include_once "./tools/crypto.php";

    // 前端发来的http请求, key和iv进行了base64编码
    function decryptRequestDataByKeyF($keyPath, $postData) {
        $postData["mac"] == hashData($postData["key"].$postData["iv"].$postData["payload"]) or die("check hash error");
        $sk = loadKey($keyPath, 0);
        $key = skDecrypt($sk, $postData["key"]); $key = base64_decode($key ,true) or die("base64 decode error");
        $iv = skDecrypt($sk, $postData["iv"]); $iv = base64_decode($iv, true) or die("base64 decode error");
        $payload = decryptData($key, $iv, $postData["payload"]);
        return array("key" => $key, "iv" => $iv, "payload" => $payload);
    }

    // 后端发送的http请求, key和iv直接用来解密
    function decryptRequestDataByKeyB($keyPath, $postData) {
        $postData["mac"] == hashData($postData["key"].$postData["iv"].$postData["payload"]) or die("check hash error");
        $sk = loadKey($keyPath, 0);
        $key = skDecrypt($sk, $postData["key"]); 
        $iv = skDecrypt($sk, $postData["iv"]); 
        $payload = decryptData($key, $iv, $postData["payload"]);
        return array("key" => $key, "iv" => $iv, "payload" => $payload);
    }

    // 使用接收方的公钥加密混合加密要发送的请求
    function encryptRequestDataByKey($pk, $postData) {
        // $pk = loadKey("./key/pk8.pem", 1);
        // $pk = loadKey($keyPath, 1);
        $key = pkEncrypt($pk, $postData["key"]);
        $iv = pkEncrypt($pk, $postData["iv"]);
        $payload = encryptData($postData["key"], $postData["iv"], $postData["payload"]);
        return array("mac" => hashData($key.$iv.$payload), "key" => $key, "iv" => $iv, "payload" => $payload);
    }

        // 使用接收方的公钥加密混合加密要发送的请求
    function encryptRequestDataByKeyFile($keyPath, $postData) {
        $pk = loadKey($keyPath, 1);
        // var_dump($pk);
        $key = pkEncrypt($pk, $postData["key"]);
        $iv = pkEncrypt($pk, $postData["iv"]);
        $payload = encryptData($postData["key"], $postData["iv"], $postData["payload"]);
        return array("mac" => hashData($key.$iv.$payload), "key" => $key, "iv" => $iv, "payload" => $payload);
    }

    // 解密服务器返回的响应
    function decryptResponseData($key, $rspsData) {
        $rspsData = json_decode($rspsData, true) or die("response decode json error" ).json_last_error_msg();
        $rspsData["mac"] == hashData($rspsData["iv"].$rspsData["payload"]) or die("check hash error");
        $iv = base64_decode($rspsData["iv"]);
        $result = decryptData($key, $iv, $rspsData["payload"]);
        return $result;
    }

    // 加密返回给客户端的响应
    function encryptResponseData($key, $result) {
        $iv = genIv128();
        $payload = encryptData($key, $iv, $result);
        $iv = base64_encode($iv);
        $rspsData = array("mac" => hashData($iv.$payload), "iv" => $iv, "payload" => $payload);
        $rspsData = json_encode($rspsData) or die("encode json error");
        return $rspsData;
    }

    // aes解密出传递的数据, 返回对象
    function decryptData($key, $iv, $payload) {
        $payload = aesDecrypt($key, $iv, $payload);
        $payload = json_decode($payload, true) or die("decode json error ".json_last_error());
        return $payload;
    }

    // aes加密序列化后的post数据
    function encryptData($key, $iv, $payload) {
        $payload = json_encode($payload) or die("encode json error ".json_last_error());
        $payload = aesEncrypt($key, $iv, $payload);
        return $payload;
    }

    // 返回哈希值
    function hashData($data) {
        $md = hash("sha512", $data) or die("hash data error");
        return $md;
    }
?>