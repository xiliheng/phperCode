<?php
/**
 * Created by PhpStorm.
 * User: lee
 * Date: 17/12/4
 * Time: 下午6:21
 */
include "../../lib/crawler/StockInfo.php";

Class getAllKlines
{
    public static $fqArr = [
        1 => '前复权',
        2 => '后复权',
        0 => '不复权',
    ];

    public static $returnPerArr = [
        'day' => 0,
        'month' => 1,
        'week' => 2,
        //'minute' => 3,
        'quarter' => 4,
        'year' => 5,
    ];

    public static function getKline()
    {
        $nowRedis = StockInfo::getNowRedis();
        $redis = StockInfo::getAllRedis();
        $result = $nowRedis->get("stockAll");
        if(empty($result))
        {
            Tool::log_print("Fatal", "获取股票实时行情，返回的股票所有ID未空");
            exit;
        }
        $stockAll = json_decode($result,true);
        $klineUrl = Db::$wstockUrl['kline'];

        foreach($stockAll as $k=>$v)
        {
            foreach(self::$returnPerArr as $perName=>$per)
            {
                foreach(self::$fqArr as $fq=>$fname)
                {
                    //只记录2000年之后的数据
                    $etime = "2018-01-01";
                    $stime = "2000-01-01";

                    $url = $klineUrl. "symbol=" . $k . "&stime=" .$stime.
                        "&etime=" .$etime. "&fq=" .$fq.  "&return_t=" . $per;
                    $output = Tool::getHttpForGzip($url, 10, "", false, true);

                    $lineArr = [];
                    if(strpos($output,"errcode") === false)
                    {
                        $lineArr = self::mergeDateByYear($output);
                        $key = strtolower($k)."_".$perName."_".$fq;
                        var_dump($lineArr);
                        //Tool::log_print("Notice", $key);
                        //self::insertRedis($redis, $key, $lineArr);
                    }
                    else
                    {
                        //Tool::log_print("Warn", "K线获取为空，url为：". $url);
                    }
                }
            }
        }
    }

    public static function mergeDateByYear($output)
    {
        $result = [];
        $lineArr = explode("\n",$output);
        foreach($lineArr as $line)
        {
	    if(empty($line))
		continue;
            $arr = explode(",",$line);
            $year = date("Y",strtotime($arr[2]));
            if(!array_key_exists($year,$result))
            {
                $result[$year] = null;
            }
            $date = date("Ymd",strtotime($arr[2]));
            $result[$year][$date] = [$arr[3],$arr[4],$arr[5],$arr[6],$arr[7],$arr[8]];
        }
        return $result;
    }

    public static function insertRedis($redis, $key, $result)
    {
        if(!empty($result))
        {
            $strArr = [];
            foreach($result as $year=>$arr)
            {
                $strArr[$year] = json_encode($arr);
            }
            $redis->hMset($key, $strArr);
        }
    }
}
