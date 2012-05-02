<?php
/**
 * Файл класса CTimestamp.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CTimestamp предоставляет функции для работы с временными отметками (timestamp).
 *
 * Часть класса адаптирована из библиотеки ADOdb Date
 * ({@link http://phplens.com/phpeverywhere/ библиотека абстракций ADOdb}).
 * Оригинальный код опубликован по лицензиям BSD и GNU Lesser GPL,
 * со следующими копирайтами:
 *     Copyright (c) 2000, 2001, 2002, 2003, 2004 John Lim
 *     All rights reserved.
 *
 * Данный класс предоставлен для поддержки UNIX-времени, находящемуся в интервале
 * 1901-2038 годов в Unix и 1970-2038 - в Windows. Кроме метода {@link getTimestamp},
 * все остальные методы данного класса могут работать с расширенным временным диапазоном.
 * Из-за того, что метод {@link getTimestamp} является просто оберткой для PHP-функции
 * {@link http://php.net/manual/en/function.mktime.php mktime}, он может быфь причиной
 * ограничения временного интервала на некоторых платформах. Обратитесь к документации
 * PHP за дополнительной информацией.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id: CTimestamp.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.utils
 * @since 1.0
 */
class CTimestamp
{
	/**
	 * Возвращает день недели, 0 = воскресенье,... 6=суббота.
	 * Алгоритм заимствован из PEAR::Date_Calc
	 * @param integer $year год
	 * @param integer $month месяц
	 * @param integer $day день
	 * @return integer день недели
	 */
	public static function getDayofWeek($year, $month, $day)
	{
		/*
		Pope Gregory removed 10 days - October 5 to October 14 - from the year 1582 and
		proclaimed that from that time onwards 3 days would be dropped from the calendar
		every 400 years.

		Thursday, October 4, 1582 (Julian) was followed immediately by Friday, October 15, 1582 (Gregorian).
		*/
		if ($year <= 1582)
		{
			if ($year < 1582 ||
				($year == 1582 && ($month < 10 || ($month == 10 && $day < 15))))
			{
				$greg_correction = 3;
			}
			else
			{
				$greg_correction = 0;
			}
		}
		else
		{
			$greg_correction = 0;
		}

		if($month > 2)
		    $month -= 2;
		else
		{
		    $month += 10;
		    $year--;
		}

		$day =  floor((13 * $month - 1) / 5) +
		        $day + ($year % 100) +
		        floor(($year % 100) / 4) +
		        floor(($year / 100) / 4) - 2 *
		        floor($year / 100) + 77 + $greg_correction;

		return $day - 7 * floor($day / 7);
	}

	/**
	 * Проверяет, високосный ли год. Проверяется год в формате YYYY либо в формате YY.
	 * Также правильно учитывает юлианский календарь.
	 * @param integer $year год для проверки
	 * @return boolean true, если год високосный
	 */
	public static function isLeapYear($year)
	{
		$year = self::digitCheck($year);
		if ($year % 4 != 0)
			return false;

		if ($year % 400 == 0)
			return true;
		// if gregorian calendar (>1582), century not-divisible by 400 is not leap
		else if ($year > 1582 && $year % 100 == 0 )
			return false;
		return true;
	}

	/**
	 * Преобразует год в формате YY в формат YYYY. Работает для любого века.
	 * Предполагается, что если формат YY и вычисляемый год больше, чем текущий + 30, то год
	 * относится к предыдущему веку.
	 * @param integer $y год
	 * @return integer измененный формат года YY -> YYYY
	 */
	protected static function digitCheck($y)
	{
		if ($y < 100){
			$yr = (integer) date("Y");
			$century = (integer) ($yr /100);

			if ($yr%100 > 50) {
				$c1 = $century + 1;
				$c0 = $century;
			} else {
				$c1 = $century;
				$c0 = $century - 1;
			}
			$c1 *= 100;
			// if 2-digit year is less than 30 years in future, set it to this century
			// otherwise if more than 30 years in future, then we set 2-digit year to the prev century.
			if (($y + $c1) < $yr+30) $y = $y + $c1;
			else $y = $y + $c0*100;
		}
		return $y;
	}

	/**
	 * Возвращает год в формате YYYY.
	 * @param integer $y год
	 * @return integer год в формате YYYY
	 */
	public static function get4DigitYear($y)
	{
		return self::digitCheck($y);
	}

	/**
	 * @return integer местный часовой пояс относительно GMT (Гринвич)
	 */
	public static function getGMTDiff()
	{
		static $TZ;
		if (isset($TZ)) return $TZ;

		$TZ = mktime(0,0,0,1,2,1970) - gmmktime(0,0,0,1,2,1970);
		return $TZ;
	}

	/**
	 * Возвращает массив getdate().
	 * @param integer|boolean $d временная метка даты. False для использования текущих времени/даты.
	 * @param boolean $fast false для вычисления дня недели; по умолчанию - true (прим.перев. - в данный момент не используется)
	 * @param boolean $gmt true для вычисления GMT-дат
	 * @return array массив с данными даты.
	 */
	public static function getDate($d=false,$fast=false,$gmt=false)
	{
		if($d===false)
			$d=time();
		if($gmt)
		{
			$tz = date_default_timezone_get();
			date_default_timezone_set('GMT');
			$result = getdate($d);
			date_default_timezone_set($tz);
		}
		else
		{
			$result = getdate($d);
		}
		return $result;
	}

	/**
	 * Проверяет, является ли набор год - месяц - день допустимым.
	 * @param integer $y год
	 * @param integer $m месяц
	 * @param integer $d день
	 * @return boolean true, если дата допустима; только семантическая проверка.
	 */
	public static function isValidDate($y,$m,$d)
	{
		return checkdate($m, $d, $y);
	}

	/**
	 * Проверяет, является ли набор часы - минуты - секунды допустимым.
	 * @param integer $h часы
	 * @param integer $m минуты
	 * @param integer $s секунды
	 * @param boolean $hs24 является ли формат часов форматов 24 часов (от 0 до 23; по умолчанию) или форматом 12 часов (от 1 до 12).
	 * @return boolean true, если набор часы - минуты - секунды допустим; только семантическая проверка
	 */
	public static function isValidTime($h,$m,$s,$hs24=true)
	{
		if($hs24 && ($h < 0 || $h > 23) || !$hs24 && ($h < 1 || $h > 12)) return false;
		if($m > 59 || $m < 0) return false;
		if($s > 59 || $s < 0) return false;
		return true;
	}

	/**
	 * Форматирует временную отметку в строку даты.
	 * @param string $fmt шаблон для форматирования
	 * @param integer|boolean $d временная отметка
	 * @param boolean $is_gmt является ли временная отметка отметкой пояса GMT
	 * @return string форматированная дата, основанная на временной отметке $d
	 */
	public static function formatDate($fmt,$d=false,$is_gmt=false)
	{
		if ($d === false)
			return ($is_gmt)? @gmdate($fmt): @date($fmt);

		// check if number in 32-bit signed range
		if ((abs($d) <= 0x7FFFFFFF))
		{
			// if windows, must be +ve integer
			if ($d >= 0)
				return ($is_gmt)? @gmdate($fmt,$d): @date($fmt,$d);
		}

		$_day_power = 86400;

		$arr = self::getDate($d,true,$is_gmt);

		$year = $arr['year'];
		$month = $arr['mon'];
		$day = $arr['mday'];
		$hour = $arr['hours'];
		$min = $arr['minutes'];
		$secs = $arr['seconds'];

		$max = strlen($fmt);
		$dates = '';

		/*
			at this point, we have the following integer vars to manipulate:
			$year, $month, $day, $hour, $min, $secs
		*/
		for ($i=0; $i < $max; $i++)
		{
			switch($fmt[$i])
			{
			case 'T': $dates .= date('T');break;
			// YEAR
			case 'L': $dates .= $arr['leap'] ? '1' : '0'; break;
			case 'r': // Thu, 21 Dec 2000 16:01:07 +0200

				// 4.3.11 uses '04 Jun 2004'
				// 4.3.8 uses  ' 4 Jun 2004'
				$dates .= gmdate('D',$_day_power*(3+self::getDayOfWeek($year,$month,$day))).', '
					. ($day<10?'0'.$day:$day) . ' '.date('M',mktime(0,0,0,$month,2,1971)).' '.$year.' ';

				if ($hour < 10) $dates .= '0'.$hour; else $dates .= $hour;

				if ($min < 10) $dates .= ':0'.$min; else $dates .= ':'.$min;

				if ($secs < 10) $dates .= ':0'.$secs; else $dates .= ':'.$secs;

				$gmt = self::getGMTDiff();
				$dates .= sprintf(' %s%04d',($gmt<=0)?'+':'-',abs($gmt)/36);
				break;

			case 'Y': $dates .= $year; break;
			case 'y': $dates .= substr($year,strlen($year)-2,2); break;
			// MONTH
			case 'm': if ($month<10) $dates .= '0'.$month; else $dates .= $month; break;
			case 'Q': $dates .= ($month+3)>>2; break;
			case 'n': $dates .= $month; break;
			case 'M': $dates .= date('M',mktime(0,0,0,$month,2,1971)); break;
			case 'F': $dates .= date('F',mktime(0,0,0,$month,2,1971)); break;
			// DAY
			case 't': $dates .= $arr['ndays']; break;
			case 'z': $dates .= $arr['yday']; break;
			case 'w': $dates .= self::getDayOfWeek($year,$month,$day); break;
			case 'l': $dates .= gmdate('l',$_day_power*(3+self::getDayOfWeek($year,$month,$day))); break;
			case 'D': $dates .= gmdate('D',$_day_power*(3+self::getDayOfWeek($year,$month,$day))); break;
			case 'j': $dates .= $day; break;
			case 'd': if ($day<10) $dates .= '0'.$day; else $dates .= $day; break;
			case 'S':
				$d10 = $day % 10;
				if ($d10 == 1) $dates .= 'st';
				else if ($d10 == 2 && $day != 12) $dates .= 'nd';
				else if ($d10 == 3) $dates .= 'rd';
				else $dates .= 'th';
				break;

			// HOUR
			case 'Z':
				$dates .= ($is_gmt) ? 0 : -self::getGMTDiff(); break;
			case 'O':
				$gmt = ($is_gmt) ? 0 : self::getGMTDiff();

				$dates .= sprintf('%s%04d',($gmt<=0)?'+':'-',abs($gmt)/36);
				break;

			case 'H':
				if ($hour < 10) $dates .= '0'.$hour;
				else $dates .= $hour;
				break;
			case 'h':
				if ($hour > 12) $hh = $hour - 12;
				else {
					if ($hour == 0) $hh = '12';
					else $hh = $hour;
				}

				if ($hh < 10) $dates .= '0'.$hh;
				else $dates .= $hh;
				break;

			case 'G':
				$dates .= $hour;
				break;

			case 'g':
				if ($hour > 12) $hh = $hour - 12;
				else {
					if ($hour == 0) $hh = '12';
					else $hh = $hour;
				}
				$dates .= $hh;
				break;
			// MINUTES
			case 'i': if ($min < 10) $dates .= '0'.$min; else $dates .= $min; break;
			// SECONDS
			case 'U': $dates .= $d; break;
			case 's': if ($secs < 10) $dates .= '0'.$secs; else $dates .= $secs; break;
			// AM/PM
			// Note 00:00 to 11:59 is AM, while 12:00 to 23:59 is PM
			case 'a':
				if ($hour>=12) $dates .= 'pm';
				else $dates .= 'am';
				break;
			case 'A':
				if ($hour>=12) $dates .= 'PM';
				else $dates .= 'AM';
				break;
			default:
				$dates .= $fmt[$i]; break;
			// ESCAPE
			case "\\":
				$i++;
				if ($i < $max) $dates .= $fmt[$i];
				break;
			}
		}
		return $dates;
	}

	/**
	 * Генерирует временную отметку.
	 * Метод такой же, как и PHP-функция {@link http://php.net/manual/en/function.mktime.php mktime}
	 * @param integer $hr часы
	 * @param integer $min минуты
	 * @param integer $sec секунды
	 * @param integer|boolean $mon месяц
	 * @param integer|boolean $day день
	 * @param integer|boolean $year год
	 * @param boolean $is_gmt является ли время временем GMT. If true, gmmktime() will be used.
	 * @return integer|float временная метка по местному времени.
     */
	public static function getTimestamp($hr,$min,$sec,$mon=false,$day=false,$year=false,$is_gmt=false)
	{
		if ($mon === false)
			return $is_gmt? @gmmktime($hr,$min,$sec): @mktime($hr,$min,$sec);
		return $is_gmt ? @gmmktime($hr,$min,$sec,$mon,$day,$year) : @mktime($hr,$min,$sec,$mon,$day,$year);
	}
}
