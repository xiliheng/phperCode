<?php
/**
 * Created by PhpStorm.
 * User: lee
 * Date: 17/12/11
 * Time: 下午3:48
 */

$class = $argv[1];
$func = $argv[2];
$classFile = $class . ".php";

require $classFile;

$class::$func();