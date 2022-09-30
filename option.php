<?php

    include_once("./cryptor.php");
    include_once("./tools/dbopt.php");
    
    function crtCrd($conn) {
        $ncrd = substr(hashData(genKey256()), 0, 32);
        $_SESSION["crd"][0]["cnt"]++;
        $_SESSION["crd"][1][$ncrd] = array("crd" => $ncrd, "mny" => 0, "tst" => time() * 1000);
        $_SESSION["crd"][1]["0"] = $_SESSION["crd"][1][$ncrd];
        pulActCrd($conn, $_SESSION["act"]["act"], $_SESSION["crd"]);
        return $ncrd;
    }

    function cioCrd($conn, $trdInf) {
        if(hashData($trdInf["ppwd"]) != $_SESSION["inf"]["ppwd"]) {
            return array("status" => "2", "message" => "pay pwd error");
        }
        $crd = $trdInf["crd"];
        $mny = $trdInf["mny"];
        if($mny + $_SESSION["crd"][1][$crd]["mny"] < 0) {
            return array("status" => "1", "message" => "no enougn money");
        }
        $tst = time();
        $_SESSION["crd"][1][$crd]["mny"] += $mny; 
        $nrcd = array("crd" => $crd, "pcrd" => "-", "mny" => $mny, "tst" => $tst);
        $_SESSION["rcd"][0]["cnt"]++;
        $_SESSION["rcd"][1][] = $nrcd;

        pulActCrd($conn, $_SESSION["act"]["act"], $_SESSION["crd"]);
        pulActRcd($conn, $_SESSION["act"]["act"], $_SESSION["rcd"]);
        return array("status" => "0");
    }

    function ctrCrd($conn, $trdInf) {
        if(hashData($trdInf["ppwd"]) != $_SESSION["inf"]["ppwd"]) {
            return array("status" => "4", "message" => "pay pwd error");
        }
        // 自己的卡号
        $mcrd = $trdInf["mcrd"];
        // 转入该账号
        $pact = $trdInf["pact"];
        // 转账金额
        $tmny = $trdInf["tmny"];
        // 金额被客户端恶意修改
        if($tmny <= 0) {
            return array("status" => "5", "message" => "money error");
        }
        // 卡上余额不足
        if(-$tmny + $_SESSION["crd"][1][$mcrd]["mny"] < 0) {
            return array("status" => "3", "message" => "no enougn money");
        }
        // 取出该账户的银行卡和交易记录
        if(($pcrd = fchActCrd($conn, $pact)) == null) {
            return array("status" => "2", "message" => "pact not existed");
        }
        if($pcrd["0"]["cnt"] == 0) {
            return array("status" => "6", "message" => "pact no card opened");
        }
        $prcd = fchActRcd($conn, $pact);

       // 获得时间戳
        $tst = time() * 1000;

        // 为两个账户新增交易记录
        $nmrcd = array("crd" => $mcrd, "pcrd" => $pcrd[1]["0"]["crd"], "mny" => -$tmny, "tst" => $tst);
        $nprcd = array("crd" => $pcrd[1]["0"]["crd"], "pcrd" => $mcrd, "mny" => $tmny, "tst" => $tst);

        // 自己的交易记录
        $_SESSION["rcd"][0]["cnt"]++;
        $_SESSION["rcd"][1][] = $nmrcd;

        // 对方的交易记录
        $prcd[0]["cnt"]++;
        $prcd[1][] = $nprcd;

        // 进行转账操作
        $pcrd[1][$pcrd[1]["0"]["crd"]]["mny"] += $tmny;
        $_SESSION["crd"][1][$mcrd]["mny"] -= $tmny;

        // 将对方的信息写回数据库
        pulActCrd($conn, $pact, $pcrd);
        pulActRcd($conn, $pact, $prcd);

        pulActCrd($conn, $_SESSION["act"]["act"], $_SESSION["crd"]);
        pulActRcd($conn, $_SESSION["act"]["act"], $_SESSION["rcd"]);

        return array("status" => "0");
    }

    function logout() {
        session_destroy();
        setcookie(session_name(), "", time() - 3600, "/", "localhost");
        return array("status" => "0");
    }

    function fshCrd($conn) {
        $crd = fchActCrd($conn, $_SESSION["act"]["act"]);
        $rcd = fchActRcd($conn, $_SESSION["act"]["act"]);
        $_SESSION["crd"] = $crd;
        $_SESSION["rcd"] = $rcd;
        return array("status" => "0");
    }

    function validTimeStamp($tst) {
        // 45s有效期
        return (time() - $tst / 1000 < 45) ? true : false;
    }

    session_name("marsbank");
    session_start();


    if($_SESSION["act"] == null) {
        session_destroy();
        setcookie(session_name(), "", time() - 3600, "/", "localhost");
        echo "<script>alert('您还未登录');window.location.href='login.html'</script>";
    } else {

        $conn = new mysqli("localhost", "root", "wave.com", "bank") or die("conn db error ".$conn->error);

        $pstData = decryptRequestDataByKeyF("./key/sk.pem", $_POST);

        $key = $pstData["key"];

        if(!validTimeStamp($pstData["payload"]["timestamp"])) {
            $rspData = array("status" => "z", "message" => "time error");
        } else {
            $opt = $pstData["payload"]["opt"];

            switch($opt) {
                case '0':
                    $rspData = $_SESSION["crd"]; break;
                case '1':
                    $rspData = $_SESSION["rcd"]; break;
                case '4':
                    $rspData = $_SESSION["inf"]; break;
                case '5':
                    $rspData = fshCrd($conn); break;
                case '9':
                    $rspData = logout(); break;
                case 'a':
                    $rspData = array("ncrd" => crtCrd($conn)); break;
                case 'b':
                    $rspData = cioCrd($conn, $pstData["payload"]); break;
                case 'c':
                    $rspData = ctrCrd($conn, $pstData["payload"]); break;
                default: break;
            }
            $conn->close();
        }
        $response = encryptResponseData($key, $rspData);
        echo $response;
    }
?>