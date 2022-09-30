<?php
    include_once("./cryptor.php");

    include_once("./tools/UI.php");
    include_once("./tools/dbopt.php");
    include_once("./tools/verifier.php");



    $pstData = decryptRequestDataByKeyB("./key/sk.pem", $_POST);

    $key = $pstData["key"];
    $tobank = $pstData["payload"];

    // 银行私钥
    $sk = loadKey("./key/sk.pem", 0);
    
    $bkey = base64_decode(skDecrypt($sk, $tobank["key"]));
    $biv = base64_decode(skDecrypt($sk, $tobank["iv"]));

    $binf = json_decode(aesDecrypt($bkey, $biv, $tobank["inf"]), true) or die("json decode error ".json_last_error_msg());
    
    $pk = $tobank["pk"];

    $PI = $binf["PI"];
    $OIMD = $binf["OIMD"];
    $POMD = $binf["POMD"];
    $SIGN = $binf["SIGN"];

    $TST = $binf["TST"];

    if(!verifyTst($TST)) {
        $rspData = array("status" => "z", "message" => "time error");
    } else if(!verifyMDB($PI, $OIMD, $POMD)) {
        $rspData = array("status" => "a", "message" => "hash error");
    } else if(!verifyPK($pk, $POMD, $SIGN)) {
        $rspData = array("status" => "b", "message" => "sign error");
    } else {

        $conn = new mysqli("localhost", "root", "wave.com", "bank");

        $PI = json_decode($PI, true);

        setcookie("marsbank", $PI["bid"]);
        session_name("marsbank");
        session_start();
        var_dump($_SESSION);

        if($_SESSION["act"] == null) {
            $rspData = array("status" => "1", "message" => "usr not login");
        } else {
            $trdInf = array(
                "opt" => "c",
                "mcrd" => $_SESSION["crd"][1][0]["crd"],
                "pact" => $PI["act"],
                "tmny" => $PI["mny"],
                "ppwd" => $PI["pwd"],
                "timestamp" => time() * 1000
            );
            $rspData = ctrCrd($conn, $trdInf);
        }
    }

    $response = encryptResponseData($key, $rspData);
    echo $response;
?>