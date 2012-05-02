<?php
/**
 * Файл класса CDateFormatter.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CDateFormatter предоставляет функционал локализации даты и времени.
 *
 * Класс CDateFormatter позволяет форматировать даты и время в локалезависимой форме.
 * Шаблоны интерпретируются в локали, ассоциированной с экземпляром класса CDateFormatter.
 * Например, названия месяцев и дней недели могут отличаться для разных локалей,
 * что дает различные результаты форматирования.
 * Шаблоны, понимаемые классом CDateFormatter, определены в
 * {@link http://www.unicode.org/reports/tr35/#Date_Format_Patterns CLDR}.
 *
 * Класс CDateFormatter поддерживает предустановленные шаблоны, а также пользовательские настройки:
 * <ul>
 * <li>Метод {@link formatDateTime()} форматирует дату или время или и то и другое используя предопределенные шаблоны,
 *   в которые входят шаблоны 'full', 'long', 'medium' (по умолчанию) и 'short';</li>
 * <li>метод {@link format()} форматирует дату и время, используя определенный шабло.
 *   За деталями о распознаваемых символах шаблона обратитесь к
 *   {@link http://www.unicode.org/reports/tr35/#Date_Format_Patterns}.</li>
 * </ul>
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CDateFormatter.php 2798 2011-01-01 19:29:03Z qiang.xue $
 * @package system.i18n
 * @since 1.0
 */
class CDateFormatter extends CComponent
{
	/**
	 * @var array карта (маппинг) символов шаблона в соответствующие методы
	 */
	private static $_formatters=array(
		'G'=>'formatEra',
		'y'=>'formatYear',
		'M'=>'formatMonth',
		'L'=>'formatMonth',
		'd'=>'formatDay',
		'h'=>'formatHour12',
		'H'=>'formatHour24',
		'm'=>'formatMinutes',
		's'=>'formatSeconds',
		'E'=>'formatDayInWeek',
		'c'=>'formatDayInWeek',
		'e'=>'formatDayInWeek',
		'D'=>'formatDayInYear',
		'F'=>'formatDayInMonth',
		'w'=>'formatWeekInYear',
		'W'=>'formatWeekInMonth',
		'a'=>'formatPeriod',
		'k'=>'formatHourInDay',
		'K'=>'formatHourInPeriod',
		'z'=>'formatTimeZone',
		'Z'=>'formatTimeZone',
		'v'=>'formatTimeZone',
	);

	private $_locale;

	/**
	 * Конструктор.
	 * @param mixed $locale идентификатор локали (строка) или экзепляр локали (CLocale)
	 */
	public function __construct($locale)
	{
		if(is_string($locale))
			$this->_locale=CLocale::getInstance($locale);
		else
			$this->_locale=$locale;
	}

	/**
	 * Форматирует дату/время согласно настроенному шаблону
	 * @param string $pattern шаблон (см. {@link http://www.unicode.org/reports/tr35/#Date_Format_Patterns})
	 * @param mixed $time временная отметка в формате UNIX или строка в формате, понятном функции strtotime
	 * @return string отформатированные дата/время
	 */
	public function format($pattern,$time)
	{
		if(is_string($time))
		{
			if(ctype_digit($time))
				$time=(int)$time;
			else
				$time=strtotime($time);
		}
		$date=CTimestamp::getDate($time,false,false);
		$tokens=$this->parseFormat($pattern);
		foreach($tokens as &$token)
		{
			if(is_array($token)) // a callback: method name, sub-pattern
				$token=$this->{$token[0]}($token[1],$date);
		}
		return implode('',$tokens);
	}

	/**
	 * Форматирует дату/время согласно предустановленному шаблону.
	 * Предустановленный шаблон определяется на основе названия видов даты и времени
	 * @param mixed $timestamp временная отметка в формате UNIX строка в формате, понятном функции strtotime
	 * @param string $dateWidth вид шаблона даты. Может принимать значения 'full', 'long', 'medium' или 'short'.
	 * Если передано значение null, то часть с датой НЕ появится в результате форматирования
	 * @param string $timeWidth вид шаблона времени. Может принимать значения 'full', 'long', 'medium' или 'short'.
	 * Если передано значение null, то часть со временем НЕ появится в результате форматирования
	 * @return string отформатированные дата/время
	 */
	public function formatDateTime($timestamp,$dateWidth='medium',$timeWidth='medium')
	{
		if(!empty($dateWidth))
			$date=$this->format($this->_locale->getDateFormat($dateWidth),$timestamp);

		if(!empty($timeWidth))
			$time=$this->format($this->_locale->getTimeFormat($timeWidth),$timestamp);

		if(isset($date) && isset($time))
		{
			$dateTimePattern=$this->_locale->getDateTimeFormat();
			return strtr($dateTimePattern,array('{0}'=>$time,'{1}'=>$date));
		}
		else if(isset($date))
			return $date;
		else if(isset($time))
			return $time;
	}

	/**
	 * Разбирает шаблон форматирования даты/времени
	 * @param string $pattern разбираемый шаблон
	 * @return array результат разбора с метками
	 */
	protected function parseFormat($pattern)
	{
		static $formats=array();  // cache
		if(isset($formats[$pattern]))
			return $formats[$pattern];
		$tokens=array();
		$n=strlen($pattern);
		$isLiteral=false;
		$literal='';
		for($i=0;$i<$n;++$i)
		{
			$c=$pattern[$i];
			if($c==="'")
			{
				if($i<$n-1 && $pattern[$i+1]==="'")
				{
					$tokens[]="'";
					$i++;
				}
				else if($isLiteral)
				{
					$tokens[]=$literal;
					$literal='';
					$isLiteral=false;
				}
				else
				{
					$isLiteral=true;
					$literal='';
				}
			}
			else if($isLiteral)
				$literal.=$c;
			else
			{
				for($j=$i+1;$j<$n;++$j)
				{
					if($pattern[$j]!==$c)
						break;
				}
				$p=str_repeat($c,$j-$i);
				if(isset(self::$_formatters[$c]))
					$tokens[]=array(self::$_formatters[$c],$p);
				else
					$tokens[]=$p;
				$i=$j-1;
			}
		}
		if($literal!=='')
			$tokens[]=$literal;

		return $formats[$pattern]=$tokens;
	}

	/**
	 * Возвращает год по шаблону.
 	 * Если шаблон равен "yy", то будет возвращены 2 последние цифры года,
 	 * если "y...y" - будут добавлены нули в начале строки, например, по шаблону "yyyyy" для 2008 года возвратится "02008"
	 * @param string $pattern шаблон
	 * @param array $date результат метода {@link CTimestamp::getdate}
	 * @return string отформатированный год
	 */
	protected function formatYear($pattern,$date)
	{
		$year=$date['year'];
		if($pattern==='yy')
			return str_pad($year%100,2,'0',STR_PAD_LEFT);
		else
			return str_pad($year,strlen($pattern),'0',STR_PAD_LEFT);
	}

	/**
	 * Возвращает месяц по шаблону. По шаблону
 	 * "M" будет возвращено целое число от 1 до 12;
 	 * "MM" - 2 цифры номера месяца с ведущим нулем, например, 05;
 	 * "MMM" -  сокращенное название месяца, например, "Янв";
 	 * "MMMM" - полное название месяца, например, "Январь";
 	 * "MMMMM" - первая буква названия месяца, например, "Я"
	 * @param string $pattern шаблон
	 * @param array $date результат метода {@link CTimestamp::getdate}
	 * @return string отформатированный месяц
	 */
	protected function formatMonth($pattern,$date)
	{
		$month=$date['mon'];
		switch($pattern)
		{
			case 'M':
				return $month;
			case 'MM':
				return str_pad($month,2,'0',STR_PAD_LEFT);
			case 'MMM':
				return $this->_locale->getMonthName($month,'abbreviated');
			case 'MMMM':
				return $this->_locale->getMonthName($month,'wide');
			case 'MMMMM':
				return $this->_locale->getMonthName($month,'narrow');
			case 'L':
				return $month;
			case 'LL':
				return str_pad($month,2,'0',STR_PAD_LEFT);
			case 'LLL':
				return $this->_locale->getMonthName($month,'abbreviated', true);
			case 'LLLL':
				return $this->_locale->getMonthName($month,'wide', true);
			case 'LLLLL':
				return $this->_locale->getMonthName($month,'narrow', true);
			default:
				throw new CException(Yii::t('yii','The pattern for month must be "M", "MM", "MMM", "MMMM", "L", "LL", "LLL" or "LLLL".'));
		}
	}

	/**
	 * Возвращает день месяца.
 	 * По шаблону "d" будет возвращено целое число без лидирующего нуля, по "dd" - с лидирующим нулем (например, 05)
	 * @param string $pattern шаблон
	 * @param array $date результат метода {@link CTimestamp::getdate}
	 * @return string день месяца
	 */
	protected function formatDay($pattern,$date)
	{
		$day=$date['mday'];
		if($pattern==='d')
			return $day;
		else if($pattern==='dd')
			return str_pad($day,2,'0',STR_PAD_LEFT);
		else
			throw new CException(Yii::t('yii','The pattern for day of the month must be "d" or "dd".'));
	}

	/**
	 * Возвращает день года (1-366)
	 * @param string $pattern шаблон
	 * @param array $date результат метода {@link CTimestamp::getdate}
	 * @return integer день года
	 */
	protected function formatDayInYear($pattern,$date)
	{
		$day=$date['yday'];
		if(($n=strlen($pattern))<=3)
			return str_pad($day,$n,'0',STR_PAD_LEFT);
		else
			throw new CException(Yii::t('yii','The pattern for day in year must be "D", "DD" or "DDD".'));
	}

	/**
	 * Возвращает день месяца по дню недели, например, "2 вторник января 2011" должен возвратить 11.
	 * Прим. переводчика: странный метод, реально возвращает количество полных недель, прошедших до этого дня.
	 * Думаю, что это неверная реализация, списанная из других языков
	 * @param string $pattern шаблон
	 * @param array $date результат метода {@link CTimestamp::getdate}
	 * @return integer день месяца
	 * @see http://www.unicode.org/reports/tr35/#Date_Format_Patterns
	 */
	protected function formatDayInMonth($pattern,$date)
	{
		if($pattern==='F')
			return (int)(($date['mday']+6)/7);
		else
			throw new CException(Yii::t('yii','The pattern for day in month must be "F".'));
	}

	/**
	 * Возвращает день недели по шаблону.
 	 * По шаблону "E", "EE" или "EEE" возвращается аббревиатура названия дня недели, например, "Вт";
 	 * "EEEE" - полное название дня недели;
 	 * "EEEEE" - первая буква названия дня недели, например, "В";
	 * @param string $pattern шаблон
	 * @param array $date результат метода {@link CTimestamp::getdate}
	 * @return string день недели
	 * @see http://www.unicode.org/reports/tr35/#Date_Format_Patterns
	 */
	protected function formatDayInWeek($pattern,$date)
	{
		$day=$date['wday'];
		switch($pattern)
		{
			case 'E':
			case 'EE':
			case 'EEE':
			case 'eee':
				return $this->_locale->getWeekDayName($day,'abbreviated');
			case 'EEEE':
			case 'eeee':
				return $this->_locale->getWeekDayName($day,'wide');
			case 'EEEEE':
			case 'eeeee':
				return $this->_locale->getWeekDayName($day,'narrow');
			case 'e':
			case 'ee':
			case 'c':
				return $day ? $day : 7;
			case 'ccc':
				return $this->_locale->getWeekDayName($day,'abbreviated',true);
			case 'cccc':
				return $this->_locale->getWeekDayName($day,'wide',true);
			case 'ccccc':
				return $this->_locale->getWeekDayName($day,'narrow',true);
			default:
				throw new CException(Yii::t('yii','The pattern for day of the week must be "E", "EE", "EEE", "EEEE", "EEEEE", "e", "ee", "eee", "eeee", "eeeee", "c", "cccc" or "ccccc".'));
		}
	}

	/**
	 * Возвращает обозначение AM/PM, 12 после полудня - это PM, 12 до полудня - AM
	 * @param string $pattern шаблон
	 * @param array $date результат метода {@link CTimestamp::getdate}
	 * @return string обозначение AM или PM
	 */
	protected function formatPeriod($pattern,$date)
	{
		if($pattern==='a')
		{
			if(intval($date['hours']/12))
				return $this->_locale->getPMName();
			else
				return $this->_locale->getAMName();
		}
		else
			throw new CException(Yii::t('yii','The pattern for AM/PM marker must be "a".'));
	}

	/**
	 * Возвращает часы в 24х-часовом формате (0-23).
	 * По шаблону "H" будет возвращаться без лидирующего нуля, по шаблону "HH" - с лидирующим нулем
	 * @param string $pattern шаблон
	 * @param array $date результат метода {@link CTimestamp::getdate}
	 * @return string часы в 24х-часовом формате
	 */
	protected function formatHour24($pattern,$date)
	{
		$hour=$date['hours'];
		if($pattern==='H')
			return $hour;
		else if($pattern==='HH')
			return str_pad($hour,2,'0',STR_PAD_LEFT);
		else
			throw new CException(Yii::t('yii','The pattern for 24 hour format must be "H" or "HH".'));
	}

	/**
	 * Возвращает часы в 12ти-часовом формате (1-12).
	 * По шаблону "h" будет возвращаться без лидирующего нуля, по шаблону "hh" - с лидирующим нулем
	 * @param string $pattern шаблон
	 * @param array $date результат метода {@link CTimestamp::getdate}
	 * @return string часы в 12ти-часовом формате
	 */
	protected function formatHour12($pattern,$date)
	{
		$hour=$date['hours'];
		$hour=($hour==12|$hour==0)?12:($hour)%12;
		if($pattern==='h')
			return $hour;
		else if($pattern==='hh')
			return str_pad($hour,2,'0',STR_PAD_LEFT);
		else
			throw new CException(Yii::t('yii','The pattern for 12 hour format must be "h" or "hh".'));
	}

	/**
	 * Возвращает час дня (1-24).
	 * По шаблону 'k' будет возвращаться без лидирующего нуля, по шаблону "kk" - с лидирующим нулем
	 * @param string $pattern шаблон
	 * @param array $date результат метода {@link CTimestamp::getdate}
	 * @return integer час дня (1-24)
	 */
	protected function formatHourInDay($pattern,$date)
	{
		$hour=$date['hours']==0?24:$date['hours'];
		if($pattern==='k')
			return $hour;
		else if($pattern==='kk')
			return str_pad($hour,2,'0',STR_PAD_LEFT);
		else
			throw new CException(Yii::t('yii','The pattern for hour in day must be "k" or "kk".'));
	}

	/**
	 * Возвращает часы в формате AM/PM (0-11)
	 * По шаблону 'K' будет возвращаться без лидирующего нуля, по шаблону "KK" - с лидирующим нулем
	 * @param string $pattern шаблон
	 * @param array $date результат метода {@link CTimestamp::getdate}
	 * @return integer hours in AM/PM format.
	 */
	protected function formatHourInPeriod($pattern,$date)
	{
		$hour=$date['hours']%12;
		if($pattern==='K')
			return $hour;
		else if($pattern==='KK')
			return str_pad($hour,2,'0',STR_PAD_LEFT);
		else
			throw new CException(Yii::t('yii','The pattern for hour in AM/PM must be "K" or "KK".'));
	}

	/**
	 * Возвращает минуты.
	 * По шаблону 'm' будет возвращаться без лидирующего нуля, по шаблону "mm" - с лидирующим нулем
	 * @param string $pattern шаблон
	 * @param array $date результат метода {@link CTimestamp::getdate}
	 * @return string минуты
	 */
	protected function formatMinutes($pattern,$date)
	{
		$minutes=$date['minutes'];
		if($pattern==='m')
			return $minutes;
		else if($pattern==='mm')
			return str_pad($minutes,2,'0',STR_PAD_LEFT);
		else
			throw new CException(Yii::t('yii','The pattern for minutes must be "m" or "mm".'));
	}

	/**
	 * Возвращает секунды
	 * По шаблону 's' будет возвращаться без лидирующего нуля, по шаблону "ss" - с лидирующим нулем
	 * @param string $pattern шаблон
	 * @param array $date результат метода {@link CTimestamp::getdate}
	 * @return string секунды
	 */
	protected function formatSeconds($pattern,$date)
	{
		$seconds=$date['seconds'];
		if($pattern==='s')
			return $seconds;
		else if($pattern==='ss')
			return str_pad($seconds,2,'0',STR_PAD_LEFT);
		else
			throw new CException(Yii::t('yii','The pattern for seconds must be "s" or "ss".'));
	}

	/**
	 * Возвращает неделю года
	 * @param string $pattern шаблон
	 * @param array $date результат метода {@link CTimestamp::getdate}
	 * @return integer неделя года
	 */
	protected function formatWeekInYear($pattern,$date)
	{
		if($pattern==='w')
			return @date('W',@mktime(0,0,0,$date['mon'],$date['mday'],$date['year']));
		else
			throw new CException(Yii::t('yii','The pattern for week in year must be "w".'));
	}

	/**
	 * Возвращает неделю месяца
	 * @param string $pattern шаблон
	 * @param array $date результат метода {@link CTimestamp::getdate}
	 * @return integer неделя месяца
	 */
	protected function formatWeekInMonth($pattern,$date)
	{
		if($pattern==='W')
			return @date('W',@mktime(0,0,0,$date['mon'], $date['mday'],$date['year']))-date('W', mktime(0,0,0,$date['mon'],1,$date['year']))+1;
		else
			throw new CException(Yii::t('yii','The pattern for week in month must be "W".'));
	}

	/**
	 * Возвращает временнную зону сервера
	 * @param string $pattern шаблон
	 * @param array $date результат метода {@link CTimestamp::getdate}
	 * @return string временная зона
	 * @todo Как получить временную зону различных регионов?
	 */
	protected function formatTimeZone($pattern,$date)
	{
		if($pattern[0]==='z' || $pattern[0]==='v')
			return @date('T', @mktime($date['hours'], $date['minutes'], $date['seconds'], $date['mon'], $date['mday'], $date['year']));
		elseif($pattern[0]==='Z')
			return @date('O', @mktime($date['hours'], $date['minutes'], $date['seconds'], $date['mon'], $date['mday'], $date['year']));
		else
			throw new CException(Yii::t('yii','The pattern for time zone must be "z" or "v".'));
	}

	/**
	 * Возвращает эру, т.е., для грегорианского времени - если год больше 0, то AD, иначе BC
	 * @param string $pattern шаблон
	 * @param array $date результат метода {@link CTimestamp::getdate}
	 * @return string эра
	 * @todo Как поддерживать несколько эр, например, Японскую?
	 */
	protected function formatEra($pattern,$date)
	{
		$era=$date['year']>0 ? 1 : 0;
		switch($pattern)
		{
			case 'G':
			case 'GG':
			case 'GGG':
				return $this->_locale->getEraName($era,'abbreviated');
			case 'GGGG':
				return $this->_locale->getEraName($era,'wide');
			case 'GGGGG':
				return $this->_locale->getEraName($era,'narrow');
			default:
				throw new CException(Yii::t('yii','The pattern for era must be "G", "GG", "GGG", "GGGG" or "GGGGG".'));
		}
	}
}
