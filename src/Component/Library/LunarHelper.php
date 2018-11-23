<?php

/** @noinspection PhpLanguageLevelInspection */

/**
 * Created by PhpStorm.
 * User: liuzeyu
 * Date: 2018/11/21
 * Time: 17:15
 */

namespace Component\Library;


use DateTime;
use Manager\TechlogBundle\Entity\CalendarAlert;

class LunarHelper
{
    /**
     * @var Lunar
     */
    static $lunar;
    static $defaultString = '0000-00-00 00:00:00';

    /**
     * 通过 Entity 获取时间戳
     * @param CalendarAlert $entity
     * @return string 时间字符串
     */
    public static function getNextAlert($entity) {
        if ($entity->getStatus() != 1 && $entity->getStatus() != 0) {
            return self::$defaultString;
        }
        $startTime = $entity->getStartTime();
        $endTime = $entity->getEndTime();
        if ($entity->getLunar() == 1) {
            self::$lunar = new Lunar();
            $startTime = self::getSorlarDate($entity->getStartTime());
            $endTime = self::getSorlarDate($entity->getEndTime());
        }
        if ($entity->getStatus() == 0) {
            return (new DateTime($startTime))->format("U") < time() ? self::$defaultString : $startTime;
        }
        if ($entity->getCycleType() == 0 || (new DateTime($endTime))->format("U") < time()) {
            return self::$defaultString;
        }
        $cycleTime = 0;
        switch ($entity->getCycleType()) {
            case 1:
                $cycleTime = $entity->getPeriod() * 24 * 3600;
                break;
            case 2:
                $cycleTime = $entity->getPeriod() * 7 * 24 * 3600;
                break;
            default:
                break;
        }
        $startTimestamp = max((new DateTime($startTime))->format("U"), time());
        if ($cycleTime != 0) {
            while ($startTimestamp <= (new DateTime($endTime))->format("U")) {
                if ($startTimestamp >= time()) {
                    return date('Y-m-d H:i:s', $startTimestamp);
                } else {
                    $startTimestamp += $cycleTime;
                }
            }
            return self::$defaultString;
        }
        if ($entity->getCycleType() == 3) {
            while ($startTimestamp <= (new DateTime($endTime))->format("U")) {
                if ($startTimestamp >= time()) {
                    return date('Y-m-d H:i:s', $startTimestamp);
                } else {
                    $startTimestamp = (new DateTime("+1 month", $startTimestamp))->format("U");
                }
            }
            return self::$defaultString;
        }
        if ($entity->getCycleType() == 4) {
            while ($startTimestamp <= (new DateTime($endTime))->format("U")) {
                if ($startTimestamp >= time()) {
                    return date('Y-m-d H:i:s', $startTimestamp);
                } else {
                    $startTimestamp = (new DateTime("+1 year", $startTimestamp))->format("U");
                }
            }
            return self::$defaultString;
        }
        if ($entity->getCycleType() == 5) {
            while($startTimestamp <= (new DateTime($endTime))->format("U")) {
                if ($startTimestamp <= (new DateTime(date("Y-m-d"),time()) + 3600 * 24)->format('U')) {
                    $startTimestamp += 3600 * 24;
                }
                $jsoninfo = file_get_contents("http://api.goseek.cn/Tools/holiday?date="
                    .date('Ymd', $startTimestamp));
                $info = json_decode($jsoninfo, true);
                if (empty($info)) {
                    return self::$defaultString;
                }
                if ($info['data'] == 0) {
                    return date('Y-m-d H:i:s', $startTimestamp);
                }
                $startTimestamp += 24 * 3600;
            }
            return self::$defaultString;
        }

        return self::$defaultString;
    }

    private static function getSorlarDate($date) {
        $timestamp = (new DateTime($date))->format("U");
        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);
        $date = date('d', $timestamp);
        $leapMonth = self::$lunar->getLeapMonth($year);
        if ($leapMonth > 0 && $leapMonth < $month) {
            $month++;
        }
        $solar = self::$lunar->convertLunarToSolar($year, $month, $date);
        return $solar[0].'-'.$solar[1].'-'.$solar[2].' '.date('H:i:s', $date);
    }
}