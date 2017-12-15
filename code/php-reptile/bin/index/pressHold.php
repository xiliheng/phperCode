<?php
/**
 * Created by PhpStorm.
 * User: lee
 * Date: 17/12/9
 * Time: 下午11:23
 */
include "../../lib/crawler/StockInfo.php";
include "../../lib/LoadKline.php";
include "../../lib/Calculate.php";

Class pressHold
{

    public static $rise = 1;
    public static $fall = 2;
    public static $shake = 3;

    public static $amount = 30;

    public static function getLine()
    {
        $nowRedis = StockInfo::getNowRedis();

        $stockFull = $nowRedis->get("stockAll");
        if(empty($stockFull))
        {
            Tool::log_print("Fatal", "获取股票实时行情，返回的股票所有ID未空");
            exit;
        }
        $stockAll = json_decode($stockFull,true);
        foreach($stockAll as $k=>$v)
        {
            //$stock = "sh600036";
            $stock = strtolower($k);
            if(strpos($stock,"sh60") === false && strpos($stock,"sz00") === false
                && strpos($stock,"sz30") === false)
            {
                continue;
            }
            $totalLine = LoadKline::getStockKline($stock,$nowRedis,200);
            $totalCount = count($totalLine);

            if($totalCount > self::$amount)
            {
                $klineArr = array_slice($totalLine, 0-self::$amount, null, false);
                $startKey = $totalCount - self::$amount - 1;
            }
            else
            {
                $klineArr = $totalLine;
                $startKey = 0;
            }

            $trend = self::getTrend($klineArr);

            if($trend == self::$shake)
            {
                $result = self::shakePH($klineArr);
            }
            if($trend == self::$fall)
            {
                $ma = self::frPH($klineArr,$totalLine,$startKey,self::$fall);
                $shake = self::shakePH($klineArr);
                if($ma == 0)
                {
                    $result = $shake;
                }
                else
                {
                    $result['max'] = [
                        'type' => 'ma',
                        'value' => $ma,
                    ];
                    $result['min'] = $shake['min'];
                }
            }
            if($trend == self::$rise)
            {
                $ma = self::frPH($klineArr,$totalLine,$startKey,self::$rise);
                $shake = self::shakePH($klineArr);
                if($ma == 0)
                {
                    $result = $shake;
                }
                else
                {
                    $result['min'] = [
                        'type' => 'ma',
                        'value' => $ma,
                    ];
                    $result['max'] = $shake['max'];
                }
            }
            print_r($result);
        }
        //return $result;
    }

    public static function getTrend($arr)
    {
        if(empty($arr))
        {
            return false;
        }
        reset($arr);
        $first = current($arr);
        $end = end($arr);

        $startClose = $first['close'];
        $endClose = $end['close'];

        $trendValue = round(($endClose - $startClose)/$startClose,3);

        //上涨 1 下跌 2 震荡 3
        if($trendValue > 0.15)
        {
            return self::$rise;
        }

        if($trendValue < -0.15)
        {
            return self::$fall;
        }

        return self::$shake;
    }

    public static function shakePH($klineArr)
    {
        $maArr = Calculate::countMa($klineArr,3);
        if(!empty($maArr))
        {
            $result = [
                'max' => [
                    'type' => 'price',
                    'value' => max($maArr),
                    ],
                'min' => [
                    'type' => 'price',
                    'value' => min($maArr),
                ],
            ];
            return $result;
        }
        return false;
    }

    public static function frPH($klineArr, $totalLine, $startKey, $type)
    {
        $pArr = self::getKeyPoint($klineArr, $type);
        $result = [];
        $final = 0;
        if(!empty($pArr))
        {
            foreach($pArr as $k=>$v)
            {
                //获得在关键点位置获取在0.5%偏差以内的ma均线的均值数
                $realKey = $startKey+$k;
                $total = 0;
                for($j=0;$j<4;$j++)
                {
                    $total += $totalLine[$realKey-$j]['close'];
                }
                for($i=5;$i<=($realKey+1);$i++)
                {
                    $total += $totalLine[$realKey-$i+1]['close'];
                    $avg = round($total/$i,3);
                    $minus = round((abs($v-$avg))/$v,3);
                    if($minus<=0.005)
                    {
                        if(array_key_exists($i, $result))
                        {
                            $result[$i] = count($result[$i])+1;
                        }
                        else
                        {
                            $result[$i] = 1;
                        }
                    }
                }
            }
            //比较不同均值数穿越的关键点最多，同时要确保均线都在特定值的上方（下降趋势）或下方（上升趋势）
            arsort($result);
            $maCount = 0;
            $badCount = -1;
            $record = 0;
            foreach($result as $ma=>$p)
            {
                if($maCount == 0)
                {
                    $maCount = $p;
                }
                $tmpArr = array_slice($totalLine, $startKey-$ma+1, null, false);
                $bad = self::compareMa($tmpArr,$ma,$type);
                if($badCount == -1 || $bad < $badCount)
                {
                    if($bad <= ($maCount+2))
                    {
                        $final = $ma;
                        return $final;
                    }
                    else
                    {
                        $record = $ma;
                    }
                    $badCount = $bad;
                }
            }
            $final = $record;
        }
        return $final;
    }

    public static function compareMa($arr, $num, $type)
    {
        if(empty($arr))
        {
            return false;
        }

        $count = 0;
        $i = 0;

        foreach($arr as $k=>$v)
        {
            if($i >= ($num-1))
            {
                $total = 0;
                for($j=0; $j<$num; $j++)
                {
                    $total += $arr[$i-$j]['close'];
                }
                $ma = round($total/$num,2);
                if($type == self::$fall && $ma < $arr[$i]['high'])
                {
                    $count++;
                }
                if($type == self::$rise && $ma > $arr[$i]['low'])
                {
                    $count++;
                }
            }
            $i++;
        }
        return $count;
    }

    public static function getKeyPoint($klineArr, $type)
    {
        if(empty($klineArr))
        {
            return false;
        }
        $result = [];
        $num = count($klineArr);
        if($num>=5)
        {
            $i = 0;
            $l2 = $l1 = $r2 = $r1 = 0;
            while($i<$num)
            {
                if($type == self::$fall)
                {
                    $now = $klineArr[$i]['high'];
                    if($i >= 2)
                    {
                        $l2 = $klineArr[$i-2]['high'];
                        $l1 = $klineArr[$i-1]['high'];
                    }
                    if($i <= ($num-3))
                    {
                        $r2 = $klineArr[$i+2]['high'];
                        $r1 = $klineArr[$i+1]['high'];
                    }
                    $flag1 = $flag2 = $flag3 = $flag4 = false;
                    if($l2 == 0 || ($l2!=0 && $l1>$l2))
                    {
                        $flag1 = true;
                    }
                    if($l1 == 0 || ($l1!=0 && $now>$l1))
                    {
                        $flag2 = true;
                    }
                    if($r1 == 0 || ($r1!=0 && $now>$r1))
                    {
                        $flag3 = true;
                    }
                    if($r2 == 0 || ($r2!=0 && $r1>$r2))
                    {
                        $flag4 = true;
                    }
                    if($flag1 && $flag2 && $flag3 && $flag4)
                    {
                        //$date = date('Ymd',strtotime($klineArr[$i]['time']));
                        $result[$i] = $now;
                        $i = $i + 5;
                        continue;
                    }
                }
                else if($type == self::$rise)
                {
                    $now = $klineArr[$i]['low'];
                    if($i >= 2)
                    {
                        $l2 = $klineArr[$i-2]['low'];
                        $l1 = $klineArr[$i-1]['low'];
                    }
                    if($i <= ($num-3))
                    {
                        $r2 = $klineArr[$i+2]['low'];
                        $r1 = $klineArr[$i+1]['low'];
                    }
                    $flag1 = $flag2 = $flag3 = $flag4 = false;
                    if($l2 == 0 || ($l2!=0 && $l1<$l2))
                    {
                        $flag1 = true;
                    }
                    if($l1 == 0 || ($l1!=0 && $now<$l1))
                    {
                        $flag2 = true;
                    }
                    if($r1 == 0 || ($r1!=0 && $now<$r1))
                    {
                        $flag3 = true;
                    }
                    if($r2 == 0 || ($r2!=0 && $r1<$r2))
                    {
                        $flag4 = true;
                    }
                    if($flag1 && $flag2 && $flag3 && $flag4)
                    {
                        //$date = date('Ymd',strtotime($klineArr[$i]['time']));
                        $result[$i] = $now;
                        $i = $i + 5;
                        continue;
                    }
                }
                $i++;
            }
        }
        else
        {
            if($type == self::$fall)
            {
                $value = 0;
                $key = 0;
                foreach($klineArr as $k=>$v)
                {
                    if($v['high'] > $value)
                    {
                        $value = $v['high'];
                        //$date = date('Ymd',strtotime($v['time']));
                        $key = $k;
                    }
                }
                $result[$key] = $value;
            }
            else if($type == self::$rise)
            {
                $value = 0;
                $key = 0;
                foreach($klineArr as $k=>$v)
                {
                    if($v['low'] < $value || $value == 0)
                    {
                        $value = $v['low'];
                        //$date = date('Ymd',strtotime($v['time']));
                        $key = $k;
                    }
                }
                $result[$key] = $value;
            }
        }
        return $result;
    }
}