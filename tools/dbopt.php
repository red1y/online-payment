<?php

    function fchData($conn, $sql) {
        $res = $conn->query($sql) or die("sql error ".$conn->error);
        $data = array(); $i = 0;
        while(($rcd = $res->fetch_assoc())) $data[$i++] = $rcd;
        return $i == 0 ? null : $data;
    }

    function dcrData($conn, $act, $data) {
        $selKey = "select `ukey`, `uiv` from `usrinf` where `act`='{$act}';";
        $usrKey = fchData($conn, $selKey);
        if($usrKey == null) return null;
        else $usrKey = $usrKey[0];
        $ukey = $usrKey["ukey"]; $uiv = $usrKey["uiv"];
        $sk = loadKey("./key/sk.pem", 0);
        $ukey = skDecrypt($sk, $ukey); $uiv = skDecrypt($sk, $uiv);
        return aesDecrypt($ukey, $uiv, $data);
    }

    function fchActRcd($conn, $act) {
        $selRcd = "select `rcd` from `usrrcd` where `act`='{$act}';";
        $actRcd = fchData($conn, $selRcd);
        if($actRcd == null) return null;
        else $actRcd = $actRcd[0];
        $rcd = $actRcd["rcd"];
        $rcd = dcrData($conn, $act, $rcd);
        $rcd = json_decode($rcd, true) or die("json decode error ".json_last_error_msg());
        return $rcd;
    }

    function fchActCrd($conn, $act) {
        $selCrd = "select `crd` from `usrcrd` where `act`='{$act}';";
        $actCrd = fchData($conn, $selCrd);
        if($actCrd == null) return null;
        else $actCrd = $actCrd[0];
        $crd = $actCrd["crd"];
        $crd = dcrData($conn, $act, $crd);
        $crd = json_decode($crd, true) or die("json decode error ".json_last_error_msg());
        return $crd;
    }

    function fchUsrInf($conn, $act) {
        $selInf = "select `inf` from `usrinf` where `act`='{$act}';";
        $usrInf = fchData($conn, $selInf);
        if($usrInf == null) return null;
        else $usrInf = $usrInf[0];
        $inf = $usrInf["inf"];
        $inf = dcrData($conn, $act, $inf);
        $inf = json_decode($inf, true) or die("json decode error ".json_last_error_msg());
        return $inf;
    }

    function pulActCrd($conn, $act, $crd) {
        $selKey = "select `ukey`, `uiv` from `usrinf` where `act`='{$act}';";
        $usrKey = fchData($conn, $selKey)[0];
        $ukey = $usrKey["ukey"]; $uiv = $usrKey["uiv"];
        $sk = loadKey("./key/sk.pem", 0);
        $ukey = skDecrypt($sk, $ukey); $uiv = skDecrypt($sk, $uiv);
        $crdd = aesEncrypt($ukey, $uiv, json_encode($crd));
        $crdj = json_encode($crd);
        $updCrd = "update `usrcrd` set `crd`='{$crdd}', `msg`='{$crdj}' where `act`='{$act}';";
        $conn->query($updCrd) or die("upd crd error ".$conn->error);
    }

    function pulActRcd($conn, $act, $rcd) {
        $selKey = "select `ukey`, `uiv` from `usrinf` where `act`='{$act}';";
        $usrKey = fchData($conn, $selKey)[0];
        $ukey = $usrKey["ukey"]; $uiv = $usrKey["uiv"];
        $sk = loadKey("./key/sk.pem", 0);
        $ukey = skDecrypt($sk, $ukey); $uiv = skDecrypt($sk, $uiv);
        $rcdd = aesEncrypt($ukey, $uiv, json_encode($rcd));
        $rcdj = json_encode($rcd);
        $updRcd = "update `usrrcd` set `rcd`='{$rcdd}', `msg`='{$rcdj}' where `act`='{$act}';";
        $conn->query($updRcd) or die("upd crd error ".$conn->error);
    }

?>