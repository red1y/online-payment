<?php
    include_once("./cryptor.php");

    include_once("./tools/dbopt.php");

    $conn = new mysqli("localhost", "root", "wave.com", "bank") or die("conn db error ".$conn->error);


    $pstData = decryptRequestDataByKeyF("./key/sk.pem", $_POST);

    $key = $pstData["key"];
    $iv = $pstData["iv"];
    $inf = $pstData["payload"];

    session_name("marsbank");
    session_start();

    // var_dump($_SESSION["cap"]);
    // var_dump($inf["cap"]);
    // var_dump($_SESSION["cap"] == $inf["cap"]);

    if($_SESSION["cap"] != $inf["cap"]) {
        $reqData = array("status" => "2", "message" => "verify code error");
    } else {
        // var_dump($act);
        $act = $inf["act"];
        $pwd = hashData($inf["pwd"]) ;
    
    
        $selUsr = "select `eid` from `usr` where `act`='{$act}' and `pwd`='{$pwd}';";
        $usr = $conn->query($selUsr) or die("select usr error ".$conn->error);
    
        if($usr->num_rows == 0) {
            $reqData = array("status" => "1", "message" => "login failed");
        } else {
            // TODO
            unset($_SESSION["cap"]);

            $inf = fchUsrInf($conn, $act);
            $crd = fchActCrd($conn, $act);
            $rcd = fchActRcd($conn, $act);
            $_SESSION["inf"] = $inf;
            $_SESSION["crd"] = $crd;
            $_SESSION["rcd"] = $rcd;
            $_SESSION["act"] = array("act" => $act, "eid" => $usr->fetch_assoc()["eid"]);
            $reqData = array("status" => "0", "message" => "login succes");
            // var_dump($_SESSION);
        }
    }


    

    // var_dump($reqData);

    // 生成的iv必须进行base64编码才能json化

    $response = encryptResponseData($key, $reqData);
    echo $response;
?>