<?php

    session_name("marsbank");
    session_start();
    // var_dump($_SESSION);
    if($_SESSION["act"] == null) {
        session_destroy();
        setcookie(session_name(), "", time() - 3600, "/", "localhost");
        echo "<script>alert('您还未登录');window.location.href='login.html'</script>";
    } else {
        // var_dump($_SESSION);
        include_once("./index.html");
    }
?>