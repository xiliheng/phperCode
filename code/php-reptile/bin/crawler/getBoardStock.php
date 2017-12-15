<?php
/**
 * Created by PhpStorm.
 * User: lee
 * Date: 17/12/5
 * Time: 下午7:35
 */

include "../../lib/crawler/StockInfo.php";

Class getBoardStock
{
    public static function getBoard()
    {
        if(!StockInfo::checkRunTime("../../"))
        {
            exit;
        }
        $nowRedis = StockInfo::getNowRedis();
        $redis = StockInfo::getAllRedis();
        $boardType = Db::$boardType;
        foreach($boardType as $k=>$v)
        {
            $url = $v['board'].time();
            $resArr = [];
            $arr = StockInfo::dongcaiParse($url);
            if(is_array($arr))
            {
                foreach($arr as $board)
                {
                    $board = str_replace(["[","]","\""],"",$board);
                    $boardArr = explode(",",$board);
                    $bpercent = $boardArr[3];
                    if($bpercent == '-')
                    {
                        $bpercent = "0.00";
                    }
                    $bid = $boardArr[1].$boardArr[0];
                    $tmpArr = [
                        $boardArr[2], //名称
                        $bpercent, //涨跌幅
                        $boardArr[4], //成交量
                        $boardArr[5],  //换手率
                        $boardArr[18],  //当前价格
                        $boardArr[19], //涨跌数
                        $boardArr[6], //涨跌股票数
                    ];

                    $resArr[$bid] = $tmpArr;

                    $key = $bid."_info";
                    StockInfo::insertIncrToRedis($redis, $key, $tmpArr);
                }
            }
            if(!empty($resArr))
            {
                $nowRedis->set($k, json_encode($resArr));
            }
        }
    }

    public static function getStockByBoard()
    {
        if(!StockInfo::checkRunTime("../../",false))
        {
            exit;
        }
        $redis = StockInfo::getAllRedis();
        $nowRedis = StockInfo::getNowRedis();
        $boardType = Db::$boardType;
        $resArr = [];
        foreach($boardType as $k=>$v)
        {
            $ret = $nowRedis->get($k);
            if(!empty($ret))
            {
                $boardArr = json_decode($ret,true);
                foreach($boardArr as $bid=>$bArr)
                {
                    $bidArr = [];
                    $url = $v['stock'].$bid."&_g=0.".time();
                    $arr = StockInfo::dongcaiParse($url);
                    if(is_array($arr))
                    {
                        foreach($arr as $stock)
                        {
                            $stock = str_replace(["[","]","\""],"",$stock);
                            $stockArr = explode(",",$stock);
                            $status = StockInfo::getStatus($stockArr[1]);
                            $stockId = $status . $stockArr[1];
                            $resArr[$stockId][] = $bid;
                            $bidArr[] = $stockId;
                        }
                    }
                    $redis->set($k."_".$bid."_stock", json_encode($bidArr));
                }
            }
        }
        if(!empty($resArr))
        {
            foreach($resArr as $stock=>$ar)
            {
                $redis->set(strtolower($stock)."_board", json_encode($ar));
            }
        }
    }
}
