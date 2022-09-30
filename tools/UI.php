<?php

    function ctrCrd($conn, $trdInf) {

        // 自己的卡号
        $mcrd = $trdInf["mcrd"];
        // 转入该账号
        $pact = $trdInf["pact"];
        if($pact == $_SESSION["act"]["act"]) {
            return array("status" => "7", "message" => "pact error");
        }
        // 转账金额
        $tmny = $trdInf["tmny"];


        // 取出该账户的银行卡和交易记录
        if(($pcrd = fchActCrd($conn, $pact)) == null) {
            return array("status" => "2", "message" => "pact not existed");
        }
        if($pcrd["0"]["cnt"] == 0) {
            return array("status" => "6", "message" => "pact no card opened");
        }

        if(hashData($trdInf["ppwd"]) != $_SESSION["inf"]["ppwd"]) {
            return array("status" => "3", "message" => "pay pwd error");
        }

        // 金额被客户端恶意修改
        if($tmny <= 0) {
            return array("status" => "5", "message" => "money error");
        }
        // 卡上余额不足
        // var_dump($_SESSION["crd"]);
        if(-$tmny + $_SESSION["crd"][1][$mcrd]["mny"] < 0) {
            return array("status" => "4", "message" => "no enougn money");
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

?>