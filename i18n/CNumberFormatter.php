<?php
/**
 * Файл класса CNumberFormatter.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CNumberFormatter предоставляет функционал локализации чисел.
 *
 * Объект класса CNumberFormatter форматирует число (целое или с плавающей точкой) и выводит строку,
 * основанную на некотором формате. Экземпляр CNumberFormatter ассоциирован с локалью, и поэтому
 * генерирует строку, представляющую число, в локалезависимом формате.
 *
 * На данный момент класс CNumberFormatter поддерживает форматы валют, процентов, десятичный формат и
 * настраиваемый формат. Первые три формата определены в данных локали, а настраиваемый формат позволяет вам
 * ввести свою строку форматирования format.
 *
 * Строка форматирования может содержать следующие специальные символы:
 * <ul>
 * <li>точка (.): десятичная точка. Будет заменена локализованной десятичной точкой;</li>
 * <li>запятая (,): разделитель групп. Будет заменен локализованным разделителем групп;</li>
 * <li>ноль (0): обязательная цифра. Определяет место, где обязана находиться цифра (если цифры нет, то на данном месте будет 0);</li>
 * <li>решетка (#): необязательная цифра. В основном используется для установки местоположения десятичной точки и разделителя групп;</li>
 * <li>знак валюты (¤): метка валюты. Будет заменена локализованным символом валюты;</li>
 * <li>процент (%): метка процентов. При появлении данной метки число будет умножено на 100 перед форматированием;</li>
 * <li>промилле (‰): метка промилле. При появлении данной метки число будет умножено на 1000 перед форматированием;.</li>
 * <li>точка с запятой (;): символ, разделяющий положительную и отрицательную часть подшаблонов.</li>
 * </ul>
 *
 * Все окружающее шаблоны (или подшаблоны) будет сохранено.
 *
 * Ниже приведены несколько примеров:
 * <pre>
 * Шаблон "#,##0.00" будет форматировать число 12345.678 как "12,345.68".
 * Шаблон "#,#,#0.00" будет форматировать число 12345.6 как "1,2,3,45.60".
 * </pre>
 * Примечание: в первом примере число округляется перед применением форматирования,
 * во втором шаблон определяет два размера группировки.
 *
 * Класс CNumberFormatter предполагает реализацию форматирования чисел согласно
 * {@link http://www.unicode.org/reports/tr35/ Unicode Technical Standard #35}.
 * Следующие особенности НЕ реализованы:
 * <ul>
 * <li>значащая цифра (significant digit);</li>
 * <li>научный формат (scientific format);</li>
 * <li>произвольная буква (arbitrary literal characters);</li>
 * <li>произвольные символы (arbitrary padding).</li>
 * </ul>
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CNumberFormatter.php 2798 2011-01-01 19:29:03Z qiang.xue $
 * @package system.i18n
 * @since 1.0
 */
class CNumberFormatter extends CComponent
{
	private $_locale;
	private $_formats=array();

	/**
	 * Конструктор.
	 * @param mixed $locale идентификатор локали (строка) или экземпляр локали (CLocale)
	 */
	public function __construct($locale)
	{
		if(is_string($locale))
			$this->_locale=CLocale::getInstance($locale);
		else
			$this->_locale=$locale;
	}

	/**
	 * Форматирует число согласно определенному шаблону.
	 * Примечание: если формат содержит '%', то число предварительно будет умножено на 100,
	 * если формат содержит '%', то число предварительно будет умножено на 1000,
	 * если формат содержит метку валюты, она будет заменена определенным локализованным
	 * символом валюты
	 * @param string $pattern шаблон формата
	 * @param mixed $value форматируемое число
	 * @param string $currency трёхбуквенный код валюты в ISO 4217. Например, код "USD" представляет доллар США, а "EUR" - евро.
	 * Метка валюты будет заменена в шаблоне символом валюты.
	 * Если передано значение null, замена производиться не будет
	 * @return string результат форматирования
	 */
	public function format($pattern,$value,$currency=null)
	{
		$format=$this->parseFormat($pattern);
		$result=$this->formatNumber($format,$value);
		if($currency===null)
			return $result;
		else if(($symbol=$this->_locale->getCurrencySymbol($currency))===null)
			$symbol=$currency;
		return str_replace('¤',$symbol,$result);
	}

	/**
	 * Форматирует число, используя формат валюты, определенный в локали
	 * @param mixed $value форматируемое число
	 * @param string $currency трёхбуквенный код валюты в ISO 4217. Например, код "USD" представляет доллар США, а "EUR" - евро.
	 * Метка валюты будет заменена в шаблоне символом валюты
	 * @return string результат форматирования
	 */
	public function formatCurrency($value,$currency)
	{
		return $this->format($this->_locale->getCurrencyFormat(),$value,$currency);
	}

	/**
	 * Форматирует число, используя формат процентов, определенный в локали.
	 * Примечание: если формат процентов содержит знак '%', то число будет предварительно умножено на 100, 
	 *  если формат процентов содержит знак '%', то число будет предварительно умножено на 1000
	 * @param mixed $value форматируемое число
	 * @return string результат форматирования
	 */
	public function formatPercentage($value)
	{
		return $this->format($this->_locale->getPercentFormat(),$value);
	}

	/**
	 * Форматирует число, используя десятичный формат, определенный в локали
	 * @param mixed $value форматируемое число
	 * @return string результат форматирования
	 */
	public function formatDecimal($value)
	{
		return $this->format($this->_locale->getDecimalFormat(),$value);
	}

	/**
	 * Форматирует число по некоторому формату.
	 * Данный метод выполняет всю реальную работу по форматированию числа
	 * @param array $format формат числа следующей структуры:
	 * <pre>
	 * array(
	 * 	'decimalDigits'=>2,     // число обязательных цифр после десятичной точки; если цифр недостаточно, будут добавлены нули ("0"); если передано значение -1, то десятичной части не будет
	 *  'maxDecimalDigits'=>3,  // максимальное число цифр после десятичной точки. Дополнительные цифры будут обрезаны
	 * 	'integerDigits'=>1,     // число обязательных цифр перед десятичной точкой; если цифр недостаточно, будут добавлены нули ("0")
	 * 	'groupSize1'=>3,        // первичный размер групп; если передан 0, то первичная группировка не используется
	 * 	'groupSize2'=>0,        // вторичный размер групп; если передан 0, то вторичная группировка не используется
	 * 	'positivePrefix'=>'+',  // префикс для положительного числа
	 * 	'positiveSuffix'=>'',   // суффикс для положительного числа
	 * 	'negativePrefix'=>'(',  // префикс для отрицательного числа
	 * 	'negativeSuffix'=>')',  // суффикс для отрицательного числа
	 * 	'multiplier'=>1,        // 100 - для процентов, 1000 - для промилле
	 * );
	 * </pre>
	 * @param mixed $value форматируемое число
	 * @return string результат форматирования
	 */
	protected function formatNumber($format,$value)
	{
		$negative=$value<0;
		$value=abs($value*$format['multiplier']);
		if($format['maxDecimalDigits']>=0)
			$value=round($value,$format['maxDecimalDigits']);
		$value="$value";
		if(($pos=strpos($value,'.'))!==false)
		{
			$integer=substr($value,0,$pos);
			$decimal=substr($value,$pos+1);
		}
		else
		{
			$integer=$value;
			$decimal='';
		}

		if($format['decimalDigits']>strlen($decimal))
			$decimal=str_pad($decimal,$format['decimalDigits'],'0');
		if(strlen($decimal)>0)
			$decimal=$this->_locale->getNumberSymbol('decimal').$decimal;

		$integer=str_pad($integer,$format['integerDigits'],'0',STR_PAD_LEFT);
		if($format['groupSize1']>0 && strlen($integer)>$format['groupSize1'])
		{
			$str1=substr($integer,0,-$format['groupSize1']);
			$str2=substr($integer,-$format['groupSize1']);
			$size=$format['groupSize2']>0?$format['groupSize2']:$format['groupSize1'];
			$str1=str_pad($str1,(int)((strlen($str1)+$size-1)/$size)*$size,' ',STR_PAD_LEFT);
			$integer=ltrim(implode($this->_locale->getNumberSymbol('group'),str_split($str1,$size))).$this->_locale->getNumberSymbol('group').$str2;
		}

		if($negative)
			$number=$format['negativePrefix'].$integer.$decimal.$format['negativeSuffix'];
		else
			$number=$format['positivePrefix'].$integer.$decimal.$format['positiveSuffix'];

		return strtr($number,array('%'=>$this->_locale->getNumberSymbol('percentSign'),'‰'=>$this->_locale->getNumberSymbol('perMille')));
	}

	/**
	 * Разбирает (парсит) переданную строку шаблона
	 * @param string $pattern разбираемый шаблон
	 * @return array разобранный (распарсенный) шаблон
	 * @see formatNumber
	 */
	protected function parseFormat($pattern)
	{
		if(isset($this->_formats[$pattern]))
			return $this->_formats[$pattern];

		$format=array();

		// find out prefix and suffix for positive and negative patterns
		$patterns=explode(';',$pattern);
		$format['positivePrefix']=$format['positiveSuffix']=$format['negativePrefix']=$format['negativeSuffix']='';
		if(preg_match('/^(.*?)[#,\.0]+(.*?)$/',$patterns[0],$matches))
		{
			$format['positivePrefix']=$matches[1];
			$format['positiveSuffix']=$matches[2];
		}

		if(isset($patterns[1]) && preg_match('/^(.*?)[#,\.0]+(.*?)$/',$patterns[1],$matches))  // with a negative pattern
		{
			$format['negativePrefix']=$matches[1];
			$format['negativeSuffix']=$matches[2];
		}
		else
		{
			$format['negativePrefix']=$this->_locale->getNumberSymbol('minusSign').$format['positivePrefix'];
			$format['negativeSuffix']=$format['positiveSuffix'];
		}
		$pat=$patterns[0];

		// find out multiplier
		if(strpos($pat,'%')!==false)
			$format['multiplier']=100;
		else if(strpos($pat,'‰')!==false)
			$format['multiplier']=1000;
		else
			$format['multiplier']=1;

		// find out things about decimal part
		if(($pos=strpos($pat,'.'))!==false)
		{
			if(($pos2=strrpos($pat,'0'))>$pos)
				$format['decimalDigits']=$pos2-$pos;
			else
				$format['decimalDigits']=0;
			if(($pos3=strrpos($pat,'#'))>=$pos2)
				$format['maxDecimalDigits']=$pos3-$pos;
			else
				$format['maxDecimalDigits']=$format['decimalDigits'];
			$pat=substr($pat,0,$pos);
		}
		else   // no decimal part
		{
			$format['decimalDigits']=0;
			$format['maxDecimalDigits']=0;
		}

		// find out things about integer part
		$p=str_replace(',','',$pat);
		if(($pos=strpos($p,'0'))!==false)
			$format['integerDigits']=strrpos($p,'0')-$pos+1;
		else
			$format['integerDigits']=0;
		// find out group sizes. some patterns may have two different group sizes
		$p=str_replace('#','0',$pat);
		if(($pos=strrpos($pat,','))!==false)
		{
			$format['groupSize1']=strrpos($p,'0')-$pos;
			if(($pos2=strrpos(substr($p,0,$pos),','))!==false)
				$format['groupSize2']=$pos-$pos2-1;
			else
				$format['groupSize2']=0;
		}
		else
			$format['groupSize1']=$format['groupSize2']=0;

		return $this->_formats[$pattern]=$format;
	}
}