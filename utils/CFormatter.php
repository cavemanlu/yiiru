<?php
/**
 * Файл класса CFormatter.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * CFormatter предоставляет набор методов, обычно использумых для форматирования данных.
 *
 * Методы форматирования, предоставляемые классом CFormatter имеют названия вида <code>formatXyz</code>.
 * Поведение некоторых из них может быть настроено свойствами CFormatter. Например, настройкой свойства
 * {@link dateFormat} можно контролировать, как метод {@link formatDate} будет форматировать переданное ему значение в дату.
 *
 * Для удобства CFormatter реализует механизм вызова методов форматирования по их коротким ссылкам (типу форматирования).
 * В частновсти, если метод форматирования называется <code>formatXyz</code>, то его короткая ссылка - <code>xyz</code>
 * (регистронезависимо). Например, вызов <code>$formatter->date($value)</code> эквивалентен вызову
 * <code>$formatter->formatDate($value)</code>.
 *
 * В настоящий момент распознаются типы:
 * <ul>
 * <li>raw: переданное значение не изменяется никак;</li>
 * <li>text: переданное значение проходит кодирование HTML при выводе на экран;</li>
 * <li>ntext: переданное значение проходит кодирование HTML при выводе на экран, а символы новой строки конвертируются в теги &lt;br /&gt;;</li>
 * <li>html: переданное значение проходит HTML-очистку;</li>
 * <li>date: переданное значение форматируется в виде даты;</li>
 * <li>time: переданное значение форматируется в виде времени;</li>
 * <li>datetime: переданное значение форматируется в виде даты со временем;</li>
 * <li>boolean: переданное значение форматируется в виде булева значения;</li>
 * <li>number: переданное значение форматируется в виде числа;</li>
 * <li>email: переданное значение форматируется в виде ссылки электронного адреса;</li>
 * <li>image: переданное значение форматируется в виде тега изображения, при этом, значение является ссылкой на изображение;</li>
 * <li>url: переданное значение форматируется в виде ссылки, при этом, значение является URL-ссылкой;</li>
 * </ul>
 *
 * По умолчанию {@link CApplication} регистрирует {@link CFormatter} как компонент приложения с идентификатором 'format'.
 * Таким образом, к компоненту форматирования можно обращаться так: <code>Yii::app()->format->boolean(1)</code>.
 *
 * @property CHtmlPurifier $htmlPurifier экземпляр HTML-фильтра
 * {@link CHtmlPurifier}
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.utils
 * @since 1.1.0
 */
class CFormatter extends CApplicationComponent
{
	/**
	 * @var CHtmlPurifier
	 */
	private $_htmlPurifier;

	/**
	 * @var string строка форматирования, используемая для форматирования даты при помощи PHP функции date(). По умолчанию - 'Y/m/d'.
	 */
	public $dateFormat='Y/m/d';
	/**
	 * @var string строка форматирования, используемая для форматирования времени при помощи PHP функции date(). По умолчанию - 'h:i:s A'.
	 */
	public $timeFormat='h:i:s A';
	/**
	 * @var string строка форматирования, используемая для форматирования  даты и времени при помощи PHP функции date(). По умолчанию - 'Y/m/d h:i:s A'.
	 */
	public $datetimeFormat='Y/m/d h:i:s A';
	/**
	 * @var array массив настроек форматирования, используемый для форматирования чисел при помощи PHP функции number_format().
	 * Могут быть заданы три элемента - "decimals", "decimalSeparator" и "thousandSeparator". Они определяют,
	 * соответственно, число цифр после точки, разделитель целой и дробной части и разделитель тысяч
	 */
	public $numberFormat=array('decimals'=>null, 'decimalSeparator'=>null, 'thousandSeparator'=>null);
	/**
	 * @var array текст, отображаемый при форматировании булева значения. Первый элемент соответствует значению
	 * false, второй - значению true. По умолчанию - <code>array('No', 'Yes')</code>.
	 */
	public $booleanFormat=array('No','Yes');
	/**
	 * @var array the options to be passed to CHtmlPurifier instance used in this class. CHtmlPurifier is used
	 * in {@link formatHtml} method, so this property could be useful to customize HTML filtering behavior.
	 * @since 1.1.13
	 */
	public $htmlPurifierOptions=array();

	/**
	 * @var array the format used to format size (bytes). Three elements may be specified: "base", "decimals" and "decimalSeparator".
	 * They correspond to the base at which a kilobyte is calculated (1000 or 1024 bytes per kilobyte, defaults to 1024),
	 * the number of digits after the decimal point (defaults to 2) and the character displayed as the decimal point.
	 * "decimalSeparator" is available since version 1.1.13
	 * @since 1.1.11
	 */
	public $sizeFormat=array(
		'base'=>1024,
		'decimals'=>2,
		'decimalSeparator'=>null,
	);

	/**
	 * Вызывает метод форматирования по его короткой ссылке.
	 * Магический метод PHP, переопределенный для реализации доступа к методам форматирования по их коротким ссылкам.
	 * @param string $name имя метода
	 * @param array $parameters параметры метода
	 * @return mixed значение, возвращаемое методом
	 */
	public function __call($name,$parameters)
	{
		if(method_exists($this,'format'.$name))
			return call_user_func_array(array($this,'format'.$name),$parameters);
		else
			return parent::__call($name,$parameters);
	}

	/**
	 * Форматирует значение на основе переданного типа.
	 * @param mixed $value форматируемое значение
	 * @param string $type тип данных. Должен соответствовать методу, доступному в классе CFormatter.
	 * Например, можно использовать тип 'text', т.к. существует метод с именем {@link formatText}.
	 * @return string результат форматирования
	 */
	public function format($value,$type)
	{
		$method='format'.$type;
		if(method_exists($this,$method))
			return $this->$method($value);
		else
			throw new CException(Yii::t('yii','Unknown type "{type}".',array('{type}'=>$type)));
	}

	/**
	 * Форматирует значение в виде исходного значения.
	 * Метод просто возвращает переданное значение без какого-либо форматирования.
	 * @param mixed $value форматируемое значение
	 * @return string результат форматирования
	 */
	public function formatRaw($value)
	{
		return $value;
	}

	/**
	 * Форматирует значение в виде HTML текста с кодированием спец-символов ({@link CHtml::encode()}).
	 * @param mixed $value форматируемое значение
	 * @return string результат форматирования
	 */
	public function formatText($value)
	{
		return CHtml::encode($value);
	}

	/**
	 * Форматирует значение в виде HTML текста с кодированием спец-символов ({@link CHtml::encode()}) и конвертацией перевода строк в HTML тег "br".
	 * @param mixed $value форматируемое значение
	 * @return string результат форматирования
	 */
	public function formatNtext($value)
	{
		return nl2br(CHtml::encode($value));
	}

	/**
	 * Форматирует значение в виде HTML текста без кодирования спец-символов (encoding).
	 * @param mixed $value форматируемое значение
	 * @return string результат форматирования
	 */
	public function formatHtml($value)
	{
		return $this->getHtmlPurifier()->purify($value);
	}

	/**
	 * Форматирует значение в виде даты.
	 * @param mixed $value форматируемое значение
	 * @return string результат форматирования
	 * @see dateFormat
	 */
	public function formatDate($value)
	{
		return date($this->dateFormat,$this->normalizeDateValue($value));
	}

	/**
	 * Форматирует значение в виде времени.
	 * @param mixed $value форматируемое значение
	 * @return string результат форматирования
	 * @see timeFormat
	 */
	public function formatTime($value)
	{
		return date($this->timeFormat,$this->normalizeDateValue($value));
	}

	/**
	 * Форматирует значение в виде даты и времени.
	 * @param mixed $value форматируемое значение
	 * @return string результат форматирования
	 * @see datetimeFormat
	 */
	public function formatDatetime($value)
	{
		return date($this->datetimeFormat,$this->normalizeDateValue($value));
	}

	private function normalizeDateValue($time)
	{
		if(is_string($time))
		{
			if(ctype_digit($time) || ($time{0}=='-' && ctype_digit(substr($time, 1))))
				return (int)$time;
			else
				return strtotime($time);
		}
		return (int)$time;
	}

	/**
	 * Форматирует значение в виде булева значения.
	 * @param mixed $value форматируемое значение
	 * @return string результат форматирования
	 * @see booleanFormat
	 */
	public function formatBoolean($value)
	{
		return $value ? $this->booleanFormat[1] : $this->booleanFormat[0];
	}

	/**
	 * Форматирует значение в виде ссылки на электронный адрес.
	 * @param mixed $value форматируемое значение
	 * @return string результат форматирования
	 */
	public function formatEmail($value)
	{
		return CHtml::mailto($value);
	}

	/**
	 * Форматирует значение в виде тега изображения.
	 * @param mixed $value форматируемое значение
	 * @return string результат форматирования
	 */
	public function formatImage($value)
	{
		return CHtml::image($value);
	}

	/**
	 * Форматирует значение в виде гиперссылки.
	 * @param mixed $value форматируемое значение
	 * @return string результат форматирования
	 */
	public function formatUrl($value)
	{
		$url=$value;
		if(strpos($url,'http://')!==0 && strpos($url,'https://')!==0)
			$url='http://'.$url;
		return CHtml::link(CHtml::encode($value),$url);
	}

	/**
	 * Форматирует значение в виде числа, используя PHP функцию number_format().
	 * @param mixed $value форматируемое значение
	 * @return string результат форматирования
	 * @see numberFormat
	 */
	public function formatNumber($value)
	{
		return number_format($value,$this->numberFormat['decimals'],$this->numberFormat['decimalSeparator'],$this->numberFormat['thousandSeparator']);
	}

	/**
	 * @return CHtmlPurifier экземпляр HTML-фильтра {@link CHtmlPurifier}
	 */
	public function getHtmlPurifier()
	{
		if($this->_htmlPurifier===null)
			$this->_htmlPurifier=new CHtmlPurifier;
		$this->_htmlPurifier->options=$this->htmlPurifierOptions;
		return $this->_htmlPurifier;
	}

	/**
	 * Formats the value in bytes as a size in human readable form.
	 * @param integer $value value in bytes to be formatted
	 * @param boolean $verbose if full names should be used (e.g. bytes, kilobytes, ...).
	 * Defaults to false meaning that short names will be used (e.g. B, KB, ...).
	 * @return string the formatted result
	 * @see sizeFormat
	 * @since 1.1.11
	 */
	public function formatSize($value,$verbose=false)
	{
		$base=$this->sizeFormat['base'];
		for($i=0; $base<=$value && $i<5; $i++)
			$value=$value/$base;

		$value=round($value, $this->sizeFormat['decimals']);
		$formattedValue=isset($this->sizeFormat['decimalSeparator']) ? str_replace('.',$this->sizeFormat['decimalSeparator'],$value) : $value;
		$params=array($value,'{n}'=>$formattedValue);

		switch($i)
		{
			case 0:
				return $verbose ? Yii::t('yii','{n} byte|{n} bytes',$params) : Yii::t('yii', '{n} B',$params);
			case 1:
				return $verbose ? Yii::t('yii','{n} kilobyte|{n} kilobytes',$params) : Yii::t('yii','{n} KB',$params);
			case 2:
				return $verbose ? Yii::t('yii','{n} megabyte|{n} megabytes',$params) : Yii::t('yii','{n} MB',$params);
			case 3:
				return $verbose ? Yii::t('yii','{n} gigabyte|{n} gigabytes',$params) : Yii::t('yii','{n} GB',$params);
			default:
				return $verbose ? Yii::t('yii','{n} terabyte|{n} terabytes',$params) : Yii::t('yii','{n} TB',$params);
		}
	}
}
