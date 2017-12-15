<?php
/**
 * Created by PhpStorm.
 * User: mengyuan
 * Date: 17/12/8
 * Time: 下午3:35
 * explain: php run.php  getStockOutfit getOutfit
 * explain: php run.php  getStockOutfit getOutfitPosition
 */

include "../../lib/crawler/StockInfo.php";

Class getStockOutfit
{
    public static function getOutfit()
    {
        $redis = StockInfo::getNowRedis();
        $key = "stockAll";
        $strock = $redis->get($key);
        $arrStrock = json_decode($strock, true);
        //var_dump($strock);
        $redis = StockInfo::getAllRedis();
        foreach($arrStrock as $k=>$v)
        {
            $k = strtolower($k);
            if(strpos($k,"sh60") === false && strpos($k,"sz00") === false
                && strpos($k,"sz30") === false)
            {
                continue;
            }
            $link = Db::$outfitUrl['outfit'];
            $stockId = substr($k, 2);
            $url = str_replace('{{code}}', $stockId, $link);
            $resArr = [];
            $output = Tool::getHttpForGzip($url, 10, "", false, true);
            $output = trim($output, '(');
            $output = trim($output, ')');
            $arrOutfit = json_decode($output, true);
            if(count($arrOutfit) > 0)
            {
                foreach($arrOutfit as $outfit)
                {
                    $tmpArr = [
                        $outfit['insName'], //名称
                        $outfit['secuFullCode'],//strock
                        $outfit['rate'], //rate
                        $outfit['datetime'],  //日期
                    ];

                    $resArr[] = $tmpArr;
                }
                $key = $k."_outfit";
                $redis->Set($key, json_encode($resArr));
                $res[$stockId] = $resArr;
            }
        }
        if(!empty($res))
        {
            $k = 'allStrockOutfit';
            $redis->set($k, json_encode($res));
        }
    }

    public static function getOutfitPosition()
    {
        $arrRet = [];
        $nowRedis = StockInfo::getNowRedis();
        $redis = StockInfo::getAllRedis();
        $date = [
            '-03-31',
            '-06-30',
            '-09-30',
            '-12-31',
        ];
        foreach($date as $fd) {
            $fd = date("Y").$fd;
            if(date("Ymd") < date("Ymd",strtotime($fd)))
            {
                continue;
            }
            $k = str_replace("-","",$fd);
            $link = Db::$outfitUrl['position'];
            $url = str_replace('{{date}}', $fd, $link);
            $output = Tool::getHttpForGzip($url, 10, "", true, true);
            $start = strpos($output, "data:");
            $end = strpos($output, ",dataUrl");
            $output = substr($output, $start + 5, $end - $start - 5);
            $arr = json_decode($output, true);
            $resArr = [];
            if (is_array($arr)) {
                foreach ($arr as $stock) {
                    $stockid = $stock['SCode'];
                    $tmpArr = [
                        $stock['SName'], //股票名称
                        $stock['SCode'], //股票ID
                        $stock['Count'], //机构数
                        $stock['ShareHDNum'], //持股总数
                        $stock['VPosition'], //持股市值
                        $stock['CGChange'], //持股
                        $stock['ShareHDNumChange'], //持股变动数值
                        $stock['RateChange'], //持股变动比例


                    ];
                    $key = StockInfo::getStatus($stockid) . $stockid . "_position";
                    $ret = $redis->get($key);
                    if (!empty($ret)) {
                        $arrRet = json_decode($ret, true);
                    }

                    $arrRet[$k] = $tmpArr;
                    $redis->Set($key, json_encode($arrRet));
                    $resArr[$stockid] = $arrRet;
                }
            }
        }
        //var_dump($resArr); die;
        if(!empty($resArr))
        {
            $k = 'alloutfitdirection';
            $redis->set($k, json_encode($resArr));
        }
    }

}
