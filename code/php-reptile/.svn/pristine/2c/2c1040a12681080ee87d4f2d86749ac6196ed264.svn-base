<?php
/**
 * Created by PhpStorm.
 * User: lee
 * Date: 17/12/6
 * Time: 下午6:16
 */
include "../../lib/crawler/StockInfo.php";

Class getStockIncome
{
    public static function getToken()
    {
        $redis = StockInfo::getAllRedis();
        $token = $redis->get(HENG_TOKEN);
        if(empty($token))
        {
            $heng = Db::$heng;
            $tokenUrl = $heng['token'];
            $key = $heng['key'];
            $secret = $heng['secret'];
            $code = base64_encode($key.":".$secret);

            $headerArr = [
                "Content-Type:application/x-www-form-urlencoded;charset=utf-8",
                "Authorization:Basic " . $code,
                "Host:".$heng['host'],
            ];

            $postArr = "grant_type=client_credentials";

            $output = Tool::getHttpsByHeng($tokenUrl,120,$headerArr,$postArr);

            $tokenArr = json_decode($output,true);

            if(is_array($tokenArr) && array_key_exists('access_token',$tokenArr))
            {
                $token = $tokenArr['token_type'] . " " . $tokenArr['access_token'];
                $redis->setex(HENG_TOKEN, HENG_TOKEN_EXPRIE, $token);
            }
        }
        return $token;
    }

    public static function getIncome()
    {
        if(!StockInfo::checkRunTime("../../"))
        {
            exit;
        }
        $redis = StockInfo::getAllRedis();
        $link = Db::$dongcaiUrl['income'];

        $date = [
                '-03-31',
                '-06-30',
                '-09-30',
                '-12-31',
            ];

        foreach($date as $fd)
        {
            $fd = date("Y").$fd;
            if(date("Ymd") < date("Ymd",strtotime($fd)))
            {
                continue;
            }
            $incomeAll = [];
            $key = str_replace("-","",$fd);
            $url = $link . "&fd=".$fd;
            $arr = StockInfo::dongcaiParse($url);
            if(is_array($arr) && count($arr)>1)
            {
                foreach ($arr as $s)
                {
                    $s = str_replace(["[","]","\""],"",$s);
                    $stock = explode(",",$s);
                    $stockId = StockInfo::getStatus($stock[0]).$stock[0];

                    $tmp = [
                        $stock[2], //每股收益
                        $stock[4], //营业收入，单位元
                        $stock[5], //同比，百分比
                        $stock[6], //环比，百分比
                        $stock[7], //净利润，单位元
                        $stock[8], //同比，百分比
                        $stock[9], //环比，百分比
                        $stock[10], //每股净资产
                        $stock[11], //资产收益率，百分比
                        $stock[12], //每股经营现金流量，百分比
                        $stock[13], //销售毛利率，百分比
                        $stock[15], //报告日期
                    ];

                    $incomeAll[$stockId] = $tmp;

                    $output = $redis->get($stockId.STOCK_INCOME);
                    $resArr = [];
                    if(!empty($output))
                    {
                        $resArr = json_decode($output,true);

                    }
                    $resArr[$key] = $tmp;

                    $redis->set($stockId.STOCK_INCOME,json_encode($resArr));
                }
                $redis->hset(INCOME_ALL,$key,json_encode($incomeAll));
            }
        }
    }
}
