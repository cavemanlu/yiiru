<?php
/**
 * Файл класса CDateTimeParser
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Tomasz Suchanek <tomasz[dot]suchanek[at]gmail[dot]com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
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
 * MMM     | Сокращенное название месяца из 3 букв (с версии 1.1.11)
 * MMM     | Abbreviation representation of month (available since 1.1.11; locale aware since 1.1.13)
 * MMMM    | Full name representation (available since 1.1.13; locale aware)
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
 * ?       | Соответствует любому символу (с версии 1.1.11)
 * ----------------------------------------------------
 * </pre>
 * Все остальные символы должны появиться в строке даты на соответствующих позициях.
 *
 * Например, для конвертации строки даты вида '21/10/2008' используется следующий код:
 * <pre>
 * $timestamp=CDateTimeParser::parse('21/10/2008','dd/MM/yyyy');
 * </pre>
 *
 * Locale specific patterns such as MMM and MMMM uses {@link CLocale} for retrieving needed information.
 *
 * Для форматирования временной отметки в UNIX-формате в строку даты используйте класс {@link CDateFormatter}.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.utils
 * @since 1.0
 */
class CDateTimeParser
{
	/**
	 * @var boolean whether 'mbstring' PHP extension available. This static property introduced for
	 * the better overall performance of the class functionality. Checking 'mbstring' availability
	 * through static property with predefined status value is much faster than direct calling
	 * of function_exists('...').
	 * Intended for internal use only.
	 * @since 1.1.13
	 */
	private static $_mbstringAvailable;
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
		if(self::$_mbstringAvailable===null)
			self::$_mbstringAvailable=extension_loaded('mbstring');

		$tokens=self::tokenize($pattern);
		$i=0;
		$n=self::$_mbstringAvailable ? mb_strlen($value,Yii::app()->charset) : strlen($value);
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
				case 'MMMM':
				{
					$monthName='';
					if(($month=self::parseMonth($value,$i,'wide',$monthName))===false)
						return false;
					$i+=self::$_mbstringAvailable ? mb_strlen($monthName,Yii::app()->charset) : strlen($monthName);
					break;
				}
				case 'MMM':
				{
					$monthName='';
					if(($month=self::parseMonth($value,$i,'abbreviated',$monthName))===false)
						return false;
					$i+=self::$_mbstringAvailable ? mb_strlen($monthName,Yii::app()->charset) : strlen($monthName);
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
				    	elseif($hour<12 && $ampm==='pm')
				    		$hour+=12;
				    }
					$i+=2;
					break;
				}
				default:
				{
					$tn=strlen($token);
					if($i>=$n || ($token{0}!='?' && (self::$_mbstringAvailable ? mb_substr($value,$i,$tn,Yii::app()->charset) : substr($value,$i,$tn))!==$token))
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

	/**
	 * @param string $value строка даты для парсинга
	 * @param integer $offset начальное смещение
	 * @param integer $minLength минимальная длина
	 * @param integer $maxLength максимальная длина
	 * @return string parsed integer value
	 */
	protected static function parseInteger($value,$offset,$minLength,$maxLength)
	{
		for($len=$maxLength;$len>=$minLength;--$len)
		{
			$v=self::$_mbstringAvailable ? mb_substr($value,$offset,$len,Yii::app()->charset) : substr($value,$offset,$len);
			if(ctype_digit($v) && (self::$_mbstringAvailable ? mb_strlen($v,Yii::app()->charset) : strlen($v))>=$minLength)
				return $v;
		}
		return false;
	}

	/*
	 * @param string $value строка даты для парсинга
	 * @param integer $offset начальное смещение
	 * @return string parsed day period value
	 */
	protected static function parseAmPm($value, $offset)
	{
		$v=strtolower(self::$_mbstringAvailable ? mb_substr($value,$offset,2,Yii::app()->charset) : substr($value,$offset,2));
		return $v==='am' || $v==='pm' ? $v : false;
	}

	/**
	 * @param string $value строка даты для парсинга
	 * @param integer $offset начальное смещение
	 * @param string $width month name width. It can be 'wide', 'abbreviated' or 'narrow'.
	 * @param string $monthName extracted month name. Passed by reference.
	 * @return string parsed month name.
	 * @since 1.1.13
	 */
	protected static function parseMonth($value,$offset,$width,&$monthName)
	{
		$valueLength=self::$_mbstringAvailable ? mb_strlen($value,Yii::app()->charset) : strlen($value);
		for($len=1; $offset+$len<=$valueLength; $len++)
		{
			$monthName=self::$_mbstringAvailable ? mb_substr($value,$offset,$len,Yii::app()->charset) : substr($value,$offset,$len);
			if(!preg_match('/^\p{L}+$/u',$monthName)) // unicode aware replacement for ctype_alpha($monthName)
			{
				$monthName=self::$_mbstringAvailable ? mb_substr($monthName,0,-1,Yii::app()->charset) : substr($monthName,0,-1);
				break;
			}
		}
		$monthName=self::$_mbstringAvailable ? mb_strtolower($monthName,Yii::app()->charset) : strtolower($monthName);

		$monthNames=Yii::app()->getLocale()->getMonthNames($width,false);
		foreach($monthNames as $k=>$v)
			$monthNames[$k]=rtrim(self::$_mbstringAvailable ? mb_strtolower($v,Yii::app()->charset) : strtolower($v),'.');

		$monthNamesStandAlone=Yii::app()->getLocale()->getMonthNames($width,true);
		foreach($monthNamesStandAlone as $k=>$v)
			$monthNamesStandAlone[$k]=rtrim(self::$_mbstringAvailable ? mb_strtolower($v,Yii::app()->charset) : strtolower($v),'.');

		if(($v=array_search($monthName,$monthNames))===false && ($v=array_search($monthName,$monthNamesStandAlone))===false)
			return false;
		return $v;
	}
}
