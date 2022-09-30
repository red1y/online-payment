var crypto = require("./crypt.js");
var pkf = "-----BEGIN PUBLIC KEY-----\
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCWqM8BLEbeSCWFmj62Db0A3FSP\
q4q/eZYQb7VfXT8Oz8UYpZNCM7OwRQLLnqB3e96u//Mind1ozxJgFEOssosg9hXA\
sIpkqXdYVEN1hrDMDDfvRgiSOAgXvsZnXKuA3IR4d/PPiBPPXjpsAyX+cs4tADD5\
yO6PGJxKR7Gmut/JTwIDAQAB\
-----END PUBLIC KEY-----";

var logForm = ["act", "pwd", "cap"];
var regForm = ["rnm", "sex", "age", "cid", "tid", "eid", "lpwd", "rlpw", "ppwd", "rppw"];
var infKey = {
    "rnm": "姓名",
    "sex": "性别",
    "age": "年龄",
    "cid": "证件",
    "tid": "电话",
    "timestamp": "注册时间"
};

function jmpIndex() {
    window.location.href = "index.php";
}

function jmpLogin() {
    window.location.href = "login.html";
}

function getFormData(form) {
    data = new Object();
    for(key of form) {
        ele = document.getElementById(key);
        if(ele.value == "" || ele.style.color == "red") return null;
        data[key] = ele.value;
    }
    return data;
}

function urlEncode(data) {
    return encodeURIComponent(data);
}

function encryptRequestData(key, pstData) {
    iv = crypto.genKey(16);
    payload = crypto.aesEncrypt(key, iv, JSON.stringify(pstData));

    key = crypto.pkEncrypt(pkf, key);
    iv = crypto.pkEncrypt(pkf, iv);
    mac = crypto.hash(key + iv + payload);
    
    return "mac=" + urlEncode(mac) + "&key=" + urlEncode(key) + "&iv=" + urlEncode(iv) + "&payload=" + urlEncode(payload);
}

function decryptResponseData(key, rspData) {
    rspData = JSON.parse(rspData);
    mac = rspData["mac"];
    iv = rspData["iv"];
    payload = rspData["payload"];
    if(mac != crypto.hash(iv + payload)) {
        alert("mac error");
        return ;
    }
    result = crypto.aesDecrypt(key, iv, payload);
    return JSON.parse(result);
}

function xmlRequest(id, url, data, callback) {
    key = crypto.genKey(32);
    req = new XMLHttpRequest();
    req.open("POST", url);
    req.setRequestHeader('content-type', 'application/x-www-form-urlencoded');
    req.onreadystatechange = function() {
        if(this.readyState == 4 && this.status == 200) {
            console.log(this.responseText);
            callback(id, decryptResponseData(key, this.responseText));
        }
    }

    data["timestamp"] = new Date().getTime();
    console.log(data);
    req.send(encryptRequestData(key, data));
}

function regCallback(id, result) {
    console.log(result);
    if(result["status"] == "0") {
        alert("注册成功，请保管好您的账号: " + result["act"]);
        document.getElementById("loginl").click();
    } else {
        alert("注册失败，" + result["message"]);
    }
}

function regRequest() {
    pstData = getFormData(regForm);
    if(pstData == null) {
        alert("请正确填写注册信息!");
        return ;
    }
    pstData["lpwd"] = crypto.hashB64(pstData["lpwd"]);
    console.log(pstData);
    xmlRequest("regst", "./register.php", pstData, regCallback);
}

function logCallBack(id, result) {
    console.log(result);
    switch(result["status"]) {
        case "0" : alert("登陆成功， 点击跳转"); setTimeout(jmpIndex, 500); break;
        case "1" : alert("登录失败，账号或密码错误"); document.getElementById("capimg").click(); break;
        case "2" : alert("登录失败，验证码错误"); document.getElementById("capimg").click(); break;
        default: break;
    }
}

function logRequest() {
    pstData = getFormData(logForm);
    if(pstData == null) {
        alert("请正确填写登录信息");
        return ;
    }
    pstData["pwd"] = crypto.hashB64(pstData["pwd"]);
    console.log(pstData);
    xmlRequest("login", "./login.php", pstData, logCallBack);
}

function outCallback(id, result) {
    console.log(result);
    if(result["status"] == 0) {
        alert("退出成功");
        setTimeout(jmpLogin, 500);
    } else {
        alert("退出失败");
    }
}

function outRequest() {
    if(!confirm("确认退出?")) return ;
    xmlRequest("logout", "./option.php", {"opt": 9}, outCallback);
}

function crtCallback(id, result) {
    console.log(result);
    alert("新开卡号为: " + result["ncrd"]);
    document.getElementById("acinfol").click();
}

function crtRequest() {
    if(!confirm("确认开卡?")) return ;
    xmlRequest("crtcrd", "./option.php", {"opt": "a"}, crtCallback);
}

function cioCallback(id, result) {
    console.log(result);
    if(result["status"] == 0) {
        alert("存取成功");
    } else {
        alert("存取失败, " + result["message"]);
    }
}

function cioRequest() {
    crd = event.currentTarget.previousSibling.innerText.substr(4, 32);
    mny = parseInt(prompt("请输入金额"));
    ppwd = prompt("请输入支付密码");
    xmlRequest("", "./option.php", {"opt": "b", "crd": crd, "mny": mny, "ppwd": ppwd}, cioCallback);
}

function ctoCallback(id, result) {
    console.log(result);
    if(result["status"] == 0) {
        alert("转账成功");
    } else {
        alert("转账失败, " + result["message"]);
    }
}

function ctoRequest() {
    mcrd = event.currentTarget.previousSibling.innerText.substr(4, 32);
    pact = prompt("请输入转账方");
    mny = parseInt(prompt("请输入转账金额"));
    if(mny <= 0) {
        alert("转账金额只能大于0");
        return ;
    }
    ppwd = prompt("请输入支付密码");
    xmlRequest("", "./option.php", {"opt": "c", "mcrd": mcrd, "pact": pact, "tmny": mny, "ppwd": ppwd}, ctoCallback);
}

function optCallback(opt, data) {
    console.log(data);
    if(opt == "perinfd") infAdapter(data);
    else resAdapter(opt, data[1]);
}

function optRequest() {
    target = event.currentTarget;    
    optd = target.id.replace('l', 'd');
    var opt;
    switch(target.id) {
        case "acinfol": opt = 0; break;
        case "cashiol": opt = 0; break;
        case "actranl": opt = 0; break;
        case "acrecdl": opt = 1; break;
        case "perinfl": opt = 4; break;
        default : break;
    }
    xmlRequest(optd, "./option.php", {"opt": opt}, optCallback);
}

// click 事件传递触发对象，根剧对象进行适配
function resAdapter(opt, data) {
    ul = document.getElementById(opt).childNodes[1];
    ul.innerText = "";
    for(var item in data) {
        // default card
        if(item == "0" && opt != "acrecdd") continue;
        item = data[item];
        // item container
        li = document.createElement("li"); li.setAttribute("class", "optitem");
        // card container
        cspan = document.createElement("span");
        cspan.setAttribute("class", "acard");
        cspan.innerText = "卡号: " + item["crd"];
        li.appendChild(cspan);
        // alter container
        switch(opt) {
            case "acinfod": 
                // money container
                mspan = document.createElement("span");
                mspan.setAttribute("class", "lmny");
                mspan.innerText = "余额: " + item["mny"];
                li.appendChild(mspan);
                // date container
                dspan = document.createElement("span");
                dspan.setAttribute("class", "ctime");
                tdata = new Date(item["tst"]);
                tyear = tdata.getFullYear();
                tmonh = tdata.getMonth() + 1;
                tdate = tdata.getDate();
                dspan.innerText = "开卡日期: " + tyear + "." + tmonh + "." + tdate;
                li.appendChild(dspan);
                break;
            case "cashiod": 
                cspan.setAttribute("class", "bcard");
                // cashio button container
                cspan = document.createElement("span");
                cspan.setAttribute("class", "option");
                cspan.innerText = "存取款";
                cspan.addEventListener("click", cioRequest);
                li.appendChild(cspan);
                break;
            case "actrand": 
                cspan.setAttribute("class", "bcard");
                // tran button container
                tspan = document.createElement("span");
                tspan.setAttribute("class", "option");
                tspan.innerText = "转账";
                tspan.addEventListener("click", ctoRequest);
                li.appendChild(tspan);
                break;
            case "acrecdd":
                cspan.setAttribute("class", "ccard");
                // partner container
                pspan = document.createElement("span");
                pspan.setAttribute("class", "patt")
                pspan.innerText = "交易方: " + item["pcrd"];
                li.appendChild(pspan);
                // money container
                mspan = document.createElement("span");
                mspan.setAttribute("class", "tmny")
                mspan.innerText = "金额: " + item["mny"];
                li.appendChild(mspan);
                break;
            default: break;
        }
        ul.appendChild(li);
    }
}


function infAdapter(inf) {
    ul = document.getElementById("perinfd").childNodes[1];
    ul.innerText = "";
    for(key in inf) {
        if(infKey[key] == null) continue;
        li = document.createElement("li");
        li.setAttribute("class", "optitem");
        kspan = document.createElement("span");
        kspan.setAttribute("class", "ikey");
        kspan.innerText = infKey[key] + ": ";
        if(key == "timestamp") {
            tdata = new Date(inf[key]);
            tyear = tdata.getFullYear();
            tmonh = tdata.getMonth() + 1;
            tdate = tdata.getDate();
            inf[key] = tyear + "." + tmonh + "." + tdate;
        }
        vspan = document.createElement("span");
        vspan.setAttribute("class", "ival");
        vspan.innerText = inf[key];
        li.appendChild(kspan);
        li.appendChild(vspan);
        ul.appendChild(li);
    }
}

function frhCallback(id, result) {
    if(result["status"] == 0) {
        alert("刷新成功");
        document.getElementById("acinfol").click();
    } else {
        alert("刷新失败, " + result["message"]);
    }
}

function frhRequest() {
    xmlRequest("crdfrh", "./option.php", {"opt": 5}, frhCallback);
}

function boundEvent1() {
    acinfol = document.getElementById("acinfol");
    if(acinfol == null) return ;
    cashiol = document.getElementById("cashiol");
    actranl = document.getElementById("actranl");
    acrecdl = document.getElementById("acrecdl");
    perinfl = document.getElementById("perinfl");

    acinfol.addEventListener("click", optRequest);
    cashiol.addEventListener("click", optRequest);
    actranl.addEventListener("click", optRequest);
    acrecdl.addEventListener("click", optRequest);
    perinfl.addEventListener("click", optRequest);

    crtcrd = document.getElementById("crtcrd");
    crtcrd.addEventListener("click", crtRequest);

    logout = document.getElementById("logout");
    logout.addEventListener("click", outRequest);

    crtcrd = document.getElementById("crtfrh");
    crtcrd.addEventListener("click", frhRequest);
}

function boundEvent2() {
    login = document.getElementById("login");
    if(login == null) return ;
    // fgpwd = document.getElementById("fgpwd");
    regst = document.getElementById("regst");

    login.addEventListener("click", logRequest);
    // fgpwd.addEventListener("click", logRequest);
    regst.addEventListener("click", regRequest);
}
boundEvent1();
boundEvent2();


// function boundEvent() {
//     // skf = document.getElementById("skf");
//     // console.log(skf);
//     // skf.addEventListener("change", readSK);

// }

// function readSK() {
//     if(this.files[0] == null) return ;
//     keyFile = this.files[0];
//     console.log(keyFile);
//     reader = new FileReader();
//     reader.onload = function () {
//         console.log(reader.result);
//         console.log(typeof(reader.result));
//         key = loadKey(reader.result);
//         console.log(typeof(key));
//         if(key.isPrivate()) seck = key;
//         else pubk = key;
//         pkskFTest();
//     };
//     reader.readAsText(keyFile);
// }
