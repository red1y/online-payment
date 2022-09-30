<?php

    function verifyTst($tst) {
        return time() - $tst / 1000 < 45;
    }

    function verifyMD($OI, $PIMD, $POMD) {
        return $POMD == (hashData((hashData($OI)).$PIMD));
    }

    function verifyMDB($PI, $OIMD, $POMD) {
        return $POMD == (hashData($OIMD.hashData($PI)));
    }

    function verifyPK($pk, $POMD, $SPOMD) {
        return substr($POMD, 0, 100) == pkDecrypt($pk, $SPOMD);
    }
?>