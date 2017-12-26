<?php

/**
 * @name  Wechat
 * @desc 微信服务号接口操作基础类
 *       获取用户openId
 *       生成二维码
 *       设置自定义菜单
 * @modefy mengyuan(mengyuan@baidu.com)
 */
class Wechat
{
    private $appId;
    private $appSecret;

    public function __construct($appid,$secret)
    {
        $this->appId = $appid ? $appid : 'wx4fa9d386929d6eaa';
        $this->appSecret = $secret ? $secret : '95dae4499917ca30a45e31995eb9948e';
    }

    /**
     * @see 获取access_token openId
     * @return string
     */
    public function getUserOpenId($code)
    {
        $res = [];
        if($code) {
            $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$this->appId&secret=$this->appSecret&code=$code&grant_type=authorization_code";
            $res = json_decode($this->httpGet($url));
        }
        return $res;
    }

    /**
     * @see 获取userInfo
     * @return string
     */
    public function getUserInfo($access_token, $openid)
    {
        $res = [];
        if($access_token && $openid) {
            $url = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid&lang=zh_CN";
            $res = json_decode($this->httpGet($url));
        }
        return $res;
    }



    /**
     * GET 请求
     * @param string $url
     */
    private function httpGet($url) {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }

    /**
     * @see fetchUrl
     * @param string $url
     * @param array $param
     * @return array
     */
    private function httpPost($url, $param)
    {
        $httpproxy = Orp_FetchUrl::getInstance(array('timeout' => 30000, 'conn_timeout' => 10000, 'max_response_size' => 1024000));
        $res = $httpproxy->post($url, $param);
        $var = json_decode($res);
        $err = $httpproxy->errmsg();
        $http_code = $httpproxy->http_code();
        if ($err) {
            Bd_log::debug('errmsg is ' . $err);
            return $err;
            exit(1);
        } else {
            Bd_log::debug('res is ' . $res);
            //echo $res;
            return $res;
            $header = $httpproxy->header();
            if ($http_code == 200) {
                exit(0);
            } else {
                exit($http_code);
            }
        }
    }

    /**
     * @see 获取ticket 生成二维码图片
     * @param string $channelType
     * @return array
     */
    public function getQrcodeTicket($channelType = 'test')
    {
        $accessToken = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=$accessToken";
        $qrData = array(
            'action_name' => 'QR_LIMIT_STR_SCENE',
            'action_info' => array(
                'scene' => array(
                    'scene_str' => $channelType
                )
            )
        );
        $strQrData = json_encode($qrData);
        $res = json_decode($this->httpPost($url, $strQrData));
        $ticket = UrlEncode($res->ticket);
        $url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$ticket;
        $result = $this->httpGet($url);
        return $result;
    }

    /**
     * @see 设置自定义菜单
     * @param string $jsonstr
     * @return array
     */
    public function setCustomerMenu($jsonstr = '')
    {
        $accessToken = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=$accessToken";
        $result = json_decode($this->httpPost($url, $jsonstr), true);

        return $result;
    }

    /**
     * @see 获取自定义菜单
     * @return array
     */
    public function getCustomerMenu()
    {
        $accessToken = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/menu/get?access_token=$accessToken";
        $result = json_decode($this->httpGet($url), true);

        return $result;
    }

}


