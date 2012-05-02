<?php
/**
 * Файл класса CLocale.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CLocale представляет данные, относящиеся к локали.
 *
 * Данные включают информацию форматирования чисел и дат.
 * 
 * @property string $id идентификатор локали (в канонической форме)
 * @property CNumberFormatter $numberFormatter форматер чисел для данной локали
 * @property CDateFormatter $dateFormatter форматер дат для данной локали
 * @property string $decimalFormat десятичный формат
 * @property string $currencyFormat денежный формат
 * @property string $percentFormat формат процентов
 * @property string $scientificFormat научный формат
 * @property array $monthNames названия месяцев, индексированные по номеру
 * месяца (1-12)
 * @property array $weekDayNames названия дней недели, индексированные по
 * номеру дня недели (0-6, 0 - воскресенье, 1 - понедельник и т.д.)
 * @property string $aMName наименование AM
 * @property string $pMName наименование PM
 * @property string $dateFormat формат даты
 * @property string $timeFormat формат времени
 * @property string $dateTimeFormat формат даты и времени - порядок, в котором
 * идут дата и время
 * @property string $orientation направление текста, может быть либо 'ltr'
 * (слева направо) либо 'rtl' (справа налево)
 * @property array $pluralRules выражения плюральных форм
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CLocale.php 3518 2011-12-28 23:31:29Z alexander.makarow $
 * @package system.i18n
 * @since 1.0
 */
class CLocale extends CComponent
{
	/**
	 * @var string директория, содержащая данные локали. Если свойство не установлено,
	 * данные локали будут загружены из директории 'framework/i18n/data'.
	 * @since 1.1.0
	 */
	public static $dataPath;

	private $_id;
	private $_data;
	private $_dateFormatter;
	private $_numberFormatter;

	/**
	 * Возвращает экземпляр определенной локали.
	 * Т.к. конструктор объектов класса CLocale защищен, для получения экземпляра
	 * определенной локали вы можете использовать только данный метод
	 * @param string $id идентификатор локали (например, en_US)
	 * @return CLocale экземпляр локали
	 */
	public static function getInstance($id)
	{
		static $locales=array();
		if(isset($locales[$id]))
			return $locales[$id];
		else
			return $locales[$id]=new CLocale($id);
	}

	/**
	 * @return array идентификаторы локалей, которые фреймворк может распознать
	 */
	public static function getLocaleIDs()
	{
		static $locales;
		if($locales===null)
		{
			$locales=array();
			$dataPath=self::$dataPath===null ? dirname(__FILE__).DIRECTORY_SEPARATOR.'data' : self::$dataPath;
			$folder=@opendir($dataPath);
			while(($file=@readdir($folder))!==false)
			{
				$fullPath=$dataPath.DIRECTORY_SEPARATOR.$file;
				if(substr($file,-4)==='.php' && is_file($fullPath))
					$locales[]=substr($file,0,-4);
			}
			closedir($folder);
			sort($locales);
		}
		return $locales;
	}

	/**
	 * Конструктор.
	 * Т.к. конструктор защищен, для получения экземпляра определенной локали
	 * используйте метод {@link getInstance}
	 * @param string $id идентификатор локали (например, en_US)
	 */
	protected function __construct($id)
	{
		$this->_id=self::getCanonicalID($id);
		$dataPath=self::$dataPath===null ? dirname(__FILE__).DIRECTORY_SEPARATOR.'data' : self::$dataPath;
		$dataFile=$dataPath.DIRECTORY_SEPARATOR.$this->_id.'.php';
		if(is_file($dataFile))
			$this->_data=require($dataFile);
		else
			throw new CException(Yii::t('yii','Unrecognized locale "{locale}".',array('{locale}'=>$id)));
	}

	/**
	 * Конвертирует идентификатор локали в его каноническую форму.
	 * В канонической форме идентификатор содержит только знак подчеркивания и строчные буквы
	 * @param string $id конвертируемый идентификатор локали
	 * @return string идентификатор локали в канонической форме
	 */
	public static function getCanonicalID($id)
	{
		return strtolower(str_replace('-','_',$id));
	}

	/**
	 * @return string идентификатор локали (в канонической форме)
	 */
	public function getId()
	{
		return $this->_id;
	}

	/**
	 * @return CNumberFormatter форматер чисел для данной локали
	 */
	public function getNumberFormatter()
	{
		if($this->_numberFormatter===null)
			$this->_numberFormatter=new CNumberFormatter($this);
		return $this->_numberFormatter;
	}

	/**
	 * @return CDateFormatter форматер дат для данной локали
	 */
	public function getDateFormatter()
	{
		if($this->_dateFormatter===null)
			$this->_dateFormatter=new CDateFormatter($this);
		return $this->_dateFormatter;
	}

	/**
	 * @param string $currency трёхсимвольный код валюты в ISO 4217. Например, код "USD" представляет доллар США, а "EUR" - евро
	 * @return string локализованный символ валюты. Если символа нет, возвращается значение null
	 */
	public function getCurrencySymbol($currency)
	{
		return isset($this->_data['currencySymbols'][$currency]) ? $this->_data['currencySymbols'][$currency] : null;
	}

	/**
	 * Возвращает числовой символ по имени
	 * @param string $name имя символа
	 * @return string символ
	 */
	public function getNumberSymbol($name)
	{
		return isset($this->_data['numberSymbols'][$name]) ? $this->_data['numberSymbols'][$name] : null;
	}

	/**
	 * @return string десятичный формат
	 */
	public function getDecimalFormat()
	{
		return $this->_data['decimalFormat'];
	}

	/**
	 * @return string денежный формат
	 */
	public function getCurrencyFormat()
	{
		return $this->_data['currencyFormat'];
	}

	/**
	 * @return string формат процентов
	 */
	public function getPercentFormat()
	{
		return $this->_data['percentFormat'];
	}

	/**
	 * @return string научный формат
	 */
	public function getScientificFormat()
	{
		return $this->_data['scientificFormat'];
	}

	/**
	 * @param integer $month номер месяца (1-12)
	 * @param string $width вид названия месяца. Может принимать значения 'wide', 'abbreviated' или 'narrow'
	 * @param boolean $standAlone возвращать ли название месяца в формате stand-alone
	 * @return string название месяца
	 */
	public function getMonthName($month,$width='wide',$standAlone=false)
	{
		if($standAlone)
			return isset($this->_data['monthNamesSA'][$width][$month]) ? $this->_data['monthNamesSA'][$width][$month] : $this->_data['monthNames'][$width][$month];
		else
			return isset($this->_data['monthNames'][$width][$month]) ? $this->_data['monthNames'][$width][$month] : $this->_data['monthNamesSA'][$width][$month];
	}

	/**
	 * Возвращает названия месяцев в определенном формате
	 * @param string $width вид названий месяцев. Может принимать значения 'wide', 'abbreviated' или 'narrow'
	 * @param boolean $standAlone возвращать ли названия месяцев в формате stand-alone
	 * @return array названия месяцев, индексированные по номеру месяца (1-12)
	 */
	public function getMonthNames($width='wide',$standAlone=false)
	{
		if($standAlone)
			return isset($this->_data['monthNamesSA'][$width]) ? $this->_data['monthNamesSA'][$width] : $this->_data['monthNames'][$width];
		else
			return isset($this->_data['monthNames'][$width]) ? $this->_data['monthNames'][$width] : $this->_data['monthNamesSA'][$width];
	}

	/**
	 * @param integer $day номер дня недели (0-6, 0 - воскресенье)
	 * @param string $width вид названия дня недели. Модет принимать значения 'wide', 'abbreviated' или 'narrow'
	 * @param boolean $standAlone возвращать ли название дня недели в формате stand-alone
	 * @return string название дня недели
	 */
	public function getWeekDayName($day,$width='wide',$standAlone=false)
	{
		if($standAlone)
			return isset($this->_data['weekDayNamesSA'][$width][$day]) ? $this->_data['weekDayNamesSA'][$width][$day] : $this->_data['weekDayNames'][$width][$day];
		else
			return isset($this->_data['weekDayNames'][$width][$day]) ? $this->_data['weekDayNames'][$width][$day] : $this->_data['weekDayNamesSA'][$width][$day];
	}

	/**
	 * Возвращает названия дней недели в определенном формате
	 * @param string $width вид названий дней недели. Может принимать значения 'wide', 'abbreviated' или 'narrow'
	 * @param boolean $standAlone возвращать ли названия дней недели в формате stand-alone
	 * @return array названия дней недели, индексированные по номеру дня недели (0-6, 0 - воскресенье, 1 - понедельник и т.д.)
	 */
	public function getWeekDayNames($width='wide',$standAlone=false)
	{
		if($standAlone)
			return isset($this->_data['weekDayNamesSA'][$width]) ? $this->_data['weekDayNamesSA'][$width] : $this->_data['weekDayNames'][$width];
		else
			return isset($this->_data['weekDayNames'][$width]) ? $this->_data['weekDayNames'][$width] : $this->_data['weekDayNamesSA'][$width];
	}

	/**
	 * @param integer $era номер эры (0,1)
	 * @param string $width вид названия эры. Может принимать значения 'wide', 'abbreviated' или 'narrow'
	 * @return string название эры
	 */
	public function getEraName($era,$width='wide')
	{
		return $this->_data['eraNames'][$width][$era];
	}

	/**
	 * @return string наименование AM
	 */
	public function getAMName()
	{
		return $this->_data['amName'];
	}

	/**
	 * @return string наименование PM
	 */
	public function getPMName()
	{
		return $this->_data['pmName'];
	}

	/**
	 * @param string $width вид формата даты. Может принимать значения 'full', 'long', 'medium' или 'short'
	 * @return string формат даты
	 */
	public function getDateFormat($width='medium')
	{
		return $this->_data['dateFormats'][$width];
	}

	/**
	 * @param string $width вид формата времени. Может принимать значения 'full', 'long', 'medium' или 'short'
	 * @return string формат времени
	 */
	public function getTimeFormat($width='medium')
	{
		return $this->_data['timeFormats'][$width];
	}

	/**
	 * @return string формат даты и времени - порядок, в котором идут дата и время
	 */
	public function getDateTimeFormat()
	{
		return $this->_data['dateTimeFormat'];
	}

	/**
	 * @return string направление текста, может быть либо 'ltr' (слева направо)
	 * либо 'rtl' (справа налево)
	 * @since 1.1.2
	 */
	public function getOrientation()
	{
		return isset($this->_data['orientation']) ? $this->_data['orientation'] : 'ltr';
	}

	/**
	 * @return array выражения плюральных форм
	 */
	public function getPluralRules()
	{
		return isset($this->_data['pluralRules']) ? $this->_data['pluralRules'] : array();
	}

	/**
	 * Конвертирует идентификатор локали в идентификатор языка. Идентификатор
	 * языка состоит только из первой группы символов идентификатора локали,
	 * находящейся перед знаком подчеркивания или тире
	 * @param string $id конвертируемый идентификатор локали
	 * @return string идентификатор языка
	 * @since 1.1.9
	 */
	public function getLanguageID($id)
	{
		// normalize id
		$id = $this->getCanonicalID($id);
		// remove sub tags
		if(($underscorePosition=strpos($id, '_'))!== false)
		{
			$id = substr($id, 0, $underscorePosition);
		}
		return $id;
	}

	/**
	 * Конвертирует идентификатор локали в идентификатор системы письма.
	 * Идентификатор системы письма содержит только последние 4 символа
	 * идентификатора локали, находящиеся после знака подчеркивания или тире
	 * @param string $id конвертируемый идентификатор локали
	 * @return string идентификатор системы письма
	 * @since 1.1.9
	 */
	public function getScriptID($id)
	{
		// normalize id
		$id = $this->getCanonicalID($id);
		// find sub tags
		if(($underscorePosition=strpos($id, '_'))!==false)
		{
			$subTag = explode('_', $id);
			// script sub tags can be distigused from territory sub tags by length
			if (strlen($subTag[1])===4)
			{
				$id = $subTag[1];
			}
			else
			{
				$id = null;
			}
		}
		else
		{
			$id = null;
		}
		return $id;
	}

	/**
	 * Конвертирует идентификатор локали в идентификатор территории.
	 * Идентификатор территории содержит только последние 2 или 3 буквы или
	 * цифры идентификатора локали, находящиеся после знака подчеркивания или
	 * тире
	 * @param string $id конвертируемый идентификатор локали
	 * @return string идентификатор территории
	 * @since 1.1.9
	 */
	public function getTerritoryID($id)
	{
		// normalize id
		$id = $this->getCanonicalID($id);
		// find sub tags
		if (($underscorePosition=strpos($id, '_'))!== false)
		{
			$subTag = explode('_', $id);
			// territory sub tags can be distigused from script sub tags by length
			if (strlen($subTag[1])<4)
			{
				$id = $subTag[1];
			}
			else
			{
				$id = null;
			}
		}
		else
		{
			$id = null;
		}
		return $id;
	}

	/**
	 * Возвращает локализованное имя из файла данных интернационализации
	 * (один из файлов пакета framework/i18n/data/)
	 *
	 * @param string $id ключ массива для массива категорий
	 * @param string $category категория данных. Может быть 'languages',
	 * 'scripts' или 'territories'
	 * @return string локализованное имя для определенного идентификатора.
	 * Null, если данные не существуют
	 * @since 1.1.9
	 */
	public function getLocaleDisplayName($id=null, $category='languages')
	{
		$id = $this->getCanonicalID($id);
		if (isset($this->_data[$category][$id]))
		{
			return $this->_data[$category][$id];
		}
		else if (($category == 'languages') && ($id=$this->getLanguageID($id)) && (isset($this->_data[$category][$id])))
		{
			return $this->_data[$category][$id];
		}
		else if (($category == 'scripts') && ($id=$this->getScriptID($id)) && (isset($this->_data[$category][$id])))
		{
			return $this->_data[$category][$id];
		}
		else if (($category == 'territories') && ($id=$this->getTerritoryID($id)) && (isset($this->_data[$category][$id])))
		{
			return $this->_data[$category][$id];
		}
		else {
			return null;
		}
	}

	/**
	 * @param string $id идентификатор языка в юникоде по стандарту
	 * IETF BCP 47. Например, код "en_US" представляет американский английский,
	 * а код "en_GB" - британский английский
	 * @param string $category
	 * @return string локально отображаемое имя языка. Null, если код языка не
	 * существует
	 * @since 1.1.9
	 */
	public function getLanguage($id)
	{
		return $this->getLocaleDisplayName($id, 'languages');
	}

	/**
	 * @param string $id идентификатор языка в юникоде по стандарту
	 * IETF BCP 47. Например, код "en_US" представляет американский английский,
	 * а код "en_GB" - британский английский
	 * @param string $category
	 * @return string локально отображаемое имя системы письма. Null, если код
	 * системы письма не существует
	 * @since 1.1.9
	 */
	public function getScript($id)
	{
		return $this->getLocaleDisplayName($id, 'scripts');
	}

	/**
	 * @param string $id идентификатор языка в юникоде по стандарту
	 * IETF BCP 47. Например, код "en_US" представляет американский английский,
	 * а код "en_GB" - британский английский
	 * @param string $category
	 * @return string локально отображаемое имя территории. Null, если код
	 * территории не существует
	 * @since 1.1.9
	 */
	public function getTerritory($id)
	{
		return $this->getLocaleDisplayName($id, 'territories');
	}
}