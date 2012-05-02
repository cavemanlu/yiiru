<?php
/**
 * Файл класса CDateTimeParser
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Tomasz Suchanek <tomasz[dot]suchanek[at]gmail[dot]com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CDateTimeParser конвертирует строку даты/времени во временную отметку в UNIX-формате по определенному шаблону.
 *
 * В шаблоне допустимы следующие символы:
 * <pre>
 * Шаблон  |             Описание
 * ----------------------------------------------------
 * d       | День месяца от 1 до 31, без нулей
 * dd      | День месяца от 01 до 31, с нулем
 * M       | Номер месяца от 1 до 12, без нулей
 * MM      | Номер месяца от 01 до 12, с нулем
 * yy      | 2 цифры года, например, 96, 05
 * yyyy    | 4 цифры года, например, 2005
 * h       | Часы с 0 до 23, без нулей
 * hh      | Часы с 00 до 23, с нулем
 * H       | Часы с 0 до 23, без нулей
 * HH      | Часы с 00 до 23, с нулем
 * m       | Минуты с 0 до 59, без нулей
 * mm      | Минуты с 00 до 59, с нулем
 * s	   | Секунды с 0 до 59, без нулей
 * ss      | Секунды с 00 до 59, с нулем
 * a       | Формат времени AM или PM, регистронезависим (с версии 1.1.5)
 * ----------------------------------------------------
 * </pre>
 * Все остальные символы должны появиться в строке даты на соответствующих позициях.
 *
 * Например, для конвертации строки даты вида '21/10/2008' используется следующий код:
 * <pre>
 * $timestamp=CDateTimeParser::parse('21/10/2008','dd/MM/yyyy');
 * </pre>
 *
 * Для форматирования временной отметки в UNIX-формате в строку даты используйте класс {@link CDateFormatter}.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CDateTimeParser.php 2928 2011-02-01 17:41:51Z alexander.makarow $
 * @package system.utils
 * @since 1.0
 */
class CDateTimeParser
{
	/**
	 * Конвертирует строку даты/времени во временную отметку в UNIX-формате.
	 * @param string $value конвертируемая строка даты
	 * @param string $pattern шаблон, по которому профодится конвертация
	 * @param array $defaults значения по умолчанию для года, месяца, дня, часа, минут и секунд.
	 * Значения по умолчанию будут использоваться, если шаблон не определяет соответствующие поля.
	 * Например, есть шаблон 'MM/dd/yyyy' и данный параметр - это массив
	 * array('minute'=>0, 'second'=>0), тогда реальные значения минут и секунд для результата
	 * парсинга примут значения 0, а реальное значение часа будет равно текущему часу, полученному
	 * при вызове функции date('H'). Параметр доступен с версии 1.1.5.
	 * @return integer временная отметка в UNIX-формате для переданной строки даты.
	 * Если конвертация закончилась неудачей, возвращается false.
	 */
	public static function parse($value,$pattern='MM/dd/yyyy',$defaults=array())
	{
		$tokens=self::tokenize($pattern);
		$i=0;
		$n=strlen($value);
		foreach($tokens as $token)
		{
			switch($token)
			{
				case 'yyyy':
				{
					if(($year=self::parseInteger($value,$i,4,4))===false)
						return false;
					$i+=4;
					break;
				}
				case 'yy':
				{
					if(($year=self::parseInteger($value,$i,1,2))===false)
						return false;
					$i+=strlen($year);
					break;
				}
				case 'MM':
				{
					if(($month=self::parseInteger($value,$i,2,2))===false)
						return false;
					$i+=2;
					break;
				}
				case 'M':
				{
					if(($month=self::parseInteger($value,$i,1,2))===false)
						return false;
					$i+=strlen($month);
					break;
				}
				case 'dd':
				{
					if(($day=self::parseInteger($value,$i,2,2))===false)
						return false;
					$i+=2;
					break;
				}
				case 'd':
				{
					if(($day=self::parseInteger($value,$i,1,2))===false)
						return false;
					$i+=strlen($day);
					break;
				}
				case 'h':
				case 'H':
				{
					if(($hour=self::parseInteger($value,$i,1,2))===false)
						return false;
					$i+=strlen($hour);
					break;
				}
				case 'hh':
				case 'HH':
				{
					if(($hour=self::parseInteger($value,$i,2,2))===false)
						return false;
					$i+=2;
					break;
				}
				case 'm':
				{
					if(($minute=self::parseInteger($value,$i,1,2))===false)
						return false;
					$i+=strlen($minute);
					break;
				}
				case 'mm':
				{
					if(($minute=self::parseInteger($value,$i,2,2))===false)
						return false;
					$i+=2;
					break;
				}
				case 's':
				{
					if(($second=self::parseInteger($value,$i,1,2))===false)
						return false;
					$i+=strlen($second);
					break;
				}
				case 'ss':
				{
					if(($second=self::parseInteger($value,$i,2,2))===false)
						return false;
					$i+=2;
					break;
				}
				case 'a':
				{
				    if(($ampm=self::parseAmPm($value,$i))===false)
				        return false;
				    if(isset($hour))
				    {
				    	if($hour==12 && $ampm==='am')
				    		$hour=0;
				    	else if($hour<12 && $ampm==='pm')
				    		$hour+=12;
				    }
					$i+=2;
					break;
				}
				default:
				{
					$tn=strlen($token);
					if($i>=$n || substr($value,$i,$tn)!==$token)
						return false;
					$i+=$tn;
					break;
				}
			}
		}
		if($i<$n)
			return false;

		if(!isset($year))
			$year=isset($defaults['year']) ? $defaults['year'] : date('Y');
		if(!isset($month))
			$month=isset($defaults['month']) ? $defaults['month'] : date('n');
		if(!isset($day))
			$day=isset($defaults['day']) ? $defaults['day'] : date('j');

		if(strlen($year)===2)
		{
			if($year>=70)
				$year+=1900;
			else
				$year+=2000;
		}
		$year=(int)$year;
		$month=(int)$month;
		$day=(int)$day;

		if(
			!isset($hour) && !isset($minute) && !isset($second)
			&& !isset($defaults['hour']) && !isset($defaults['minute']) && !isset($defaults['second'])
		)
			$hour=$minute=$second=0;
		else
		{
			if(!isset($hour))
				$hour=isset($defaults['hour']) ? $defaults['hour'] : date('H');
			if(!isset($minute))
				$minute=isset($defaults['minute']) ? $defaults['minute'] : date('i');
			if(!isset($second))
				$second=isset($defaults['second']) ? $defaults['second'] : date('s');
			$hour=(int)$hour;
			$minute=(int)$minute;
			$second=(int)$second;
		}

		if(CTimestamp::isValidDate($year,$month,$day) && CTimestamp::isValidTime($hour,$minute,$second))
			return CTimestamp::getTimestamp($hour,$minute,$second,$month,$day,$year);
		else
			return false;
	}

	/*
	 * @param string $pattern the pattern that the date string is following
	 */
	private static function tokenize($pattern)
	{
		if(!($n=strlen($pattern)))
			return array();
		$tokens=array();
		for($c0=$pattern[0],$start=0,$i=1;$i<$n;++$i)
		{
			if(($c=$pattern[$i])!==$c0)
			{
				$tokens[]=substr($pattern,$start,$i-$start);
				$c0=$c;
				$start=$i;
			}
		}
		$tokens[]=substr($pattern,$start,$n-$start);
		return $tokens;
	}

	/*
	 * @param string $value строка даты для парсинга
	 * @param integer $offset начальное смещение
	 * @param integer $minLength минимальная длина
	 * @param integer $maxLength максимальная длина
	 */
	protected static function parseInteger($value,$offset,$minLength,$maxLength)
	{
		for($len=$maxLength;$len>=$minLength;--$len)
		{
			$v=substr($value,$offset,$len);
			if(ctype_digit($v) && strlen($v)>=$minLength)
				return $v;
		}
		return false;
	}

	/*
	 * @param string $value строка даты для парсинга
	 * @param integer $offset начальное смещение
	 */
	protected static function parseAmPm($value, $offset)
	{
		$v=strtolower(substr($value,$offset,2));
		return $v==='am' || $v==='pm' ? $v : false;
	}
}
