<?

    $conn = new mysqli("localhost", "root", "wave.com", "bank") or die("connect db error ".$conn->error);

    include_once("./cryptor.php");

    $pstData = decryptRequestDataByKeyF("./key/sk.pem", $_POST);

    // var_dump($pstData);

    $key = $pstData["key"];
    $iv = $pstData["iv"];
    $inf = $pstData["payload"];

    // 取出邮箱
    $eid = $inf["eid"];
    unset($inf["eid"]);
    // 检查用户是否已注册
    $selEid = "select `act` from `usr` where `eid`='$eid';";
    // echo $selEid;
    $red = $conn->query($selEid) or die("select eid error ".$conn->error);

    if($red->num_rows != 0) {
        $reqData = array("status" => "1", "message" => "email already registered");
    } else {
        // 生成用户账号
        $act =  substr(hashData(json_encode($inf)), 0, 8); 

       

        // 取出登录密码
        $pwd = hashData($inf["lpwd"]) ;
        unset($inf["lpwd"]);
        // 取出支付密码
        // $ppwd =  hashData($inf["ppwd"]);
        $inf["ppwd"] =  hashData($inf["ppwd"]);

        // 插入数据表 user
        $insUsr = "insert into `usr` (`act`, `eid`, `pwd`) values ('$act', '$eid', '$pwd');";
        // $insUsr = "insert into `usr` (`act`, `eid`, `pwd`) values ('$act', '$eid', '$pwd');";
        // echo $insUsr;
        $conn->query($insUsr) or die("insert usr error ".$conn->error);

        // 生成对称密钥
        $ukey = genKey256();
        $uiv = genIv128();
        // 加密用户信息        
        $infd = aesEncrypt($ukey, $uiv, json_encode($inf));
        $infj = json_encode($inf);

        // 生成账户信息
        $crd = array(array("cnt" => 0), array());
        // 加密账户信息
        $crdd = aesEncrypt($ukey, $uiv, json_encode($crd));
        $crdj = json_encode($crd);

        // 生成交易记录
        $rcd = array(array("cnt" => 0), array());
        // 加密交易记录
        $rcdd = aesEncrypt($ukey, $uiv, json_encode($rcd));
        $rcdj = json_encode($rcd);

        // 加载公钥
        $pk = loadKey("./key/pk8.pem", 1);
        // 加密密钥
        $ukey = pkEncrypt($pk, $ukey);
        $uiv = pkEncrypt($pk, $uiv);
        // 插入数据表 userinfo
        $insInf = "insert into `usrinf` (`act`, `ukey`, `uiv`, `inf`, `msg`) values('$act', '$ukey', '$uiv', '$infd', '$infj');";
        // echo $insInf;
        $conn->query($insInf) or die("insert usrinf error ".$conn->error);

        // 插入账户表 usrcrd
        $insCrd = "insert into `usrcrd` (`act`, `crd`, `msg`) values('$act', '$crdd', '$crdj');";
        $conn->query($insCrd) or die("insert usrcrd error ".$conn->error);

        // 插入记录表 usrrcd
        $insRcd = "insert into `usrrcd` (`act`, `rcd`, `msg`) values('$act', '$rcdd', '$rcdj');";
        $conn->query($insRcd) or die("insert usrrcd error ".$conn->error);

        // 响应
        $reqData = array("status" => "0", "act" => $act);
    }

    
    // var_dump($reqData);
    $response = encryptResponseData($key, $reqData);
    echo $response;
?>