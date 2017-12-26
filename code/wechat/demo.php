<?php
/**
 * 微信Auth2.0授权 demo
 * @author mengyuan
 * @data 2017-12-26
 */
header("Content-type: text/html; charset=utf-8");
include "./lib/Wechat.php";

$appid = 'wx4fa9d386929d6eaa';
$appsecret = '95dae4499917ca30a45e31995eb9948e';

$code = $_GET['code'];
//echo $code; die;
$wechat = new Wechat($appid, $appsecret);
$objUserAuth = $wechat->getUserOpenId($code);
if(!empty($objUserAuth))
{
    $userInfo = $wechat->getUserInfo($objUserAuth->access_token, $objUserAuth->openid);
    var_dump($userInfo);

}
