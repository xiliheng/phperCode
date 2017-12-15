<?php
/**
 * Created by PhpStorm.
 * User: lee
 * Date: 17/12/10
 * Time: 下午12:02
 */

Class Calculate
{
    public static function countMa($arr, $num)
    {
        if(empty($arr))
        {
            return false;
        }

        $maArr = [];
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
                $maArr[] = $ma;
            }
            $i++;
        }
        return $maArr;
    }
}