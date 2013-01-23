<?php
/**
 * Файл класса CEmailValidator.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Валидатор CEmailValidator проверяет, что значение атрибута - правильный адрес email.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.validators
 * @since 1.0
 */
class CEmailValidator extends CValidator
{
	/**
	 * @var string регулярное выражение, используемое для проверки значения атрибута.
	 * @see http://www.regular-expressions.info/email.html
	 */
	public $pattern='/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/';
	/**
	 * @var string регулярное выражение, используемое для проверки адресов email с именем.
	 * Свойство используется только если свойство {@link allowName} установлено в true
	 * @see allowName
	 */
	public $fullPattern='/^[^@]*<[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?>$/';
	/**
	 * @var boolean допустимо ли имя в адресе email (например, "Qiang Xue <qiang.xue@gmail.com>"). По умолчанию - false
	 * @see fullPattern
	 */
	public $allowName=false;
	/**
	 * @var boolean проверять ли запись MX для адреса email.
	 * По умолчанию - false. Для включения необходимо убедиться, что функция 'checkdnsrr'
	 * существует в вашей инсталляции PHP.
	 */
	public $checkMX=false;
	/**
	 * @var boolean проверять ли порт 25 для адреса email.
	 * По умолчанию - false. To enable it, ensure that the PHP functions 'dns_get_record' and
	 * 'fsockopen' are available in your PHP installation.
	 */
	public $checkPort=false;
	/**
	 * @var boolean может ли быть значение атрибута пустым или равным null. По умолчанию - true,
	 * т.е. пустой атрибут считается валидным
	 */
	public $allowEmpty=true;
	/**
	 * @var boolean whether validation process should care about IDN (internationalized domain names). Default
	 * value is false which means that validation of emails containing IDN will always fail.
	 * @since 1.1.13
	 */
	public $validateIDN=false;

	/**
	 * Валидирует отдельный атрибут.
	 * При возникновении ошибки к объекту добавляется сообщение об ошибке.
	 * @param CModel $object валидируемый объект данных
	 * @param string $attribute имя валидируемого атрибута
	 */
	protected function validateAttribute($object,$attribute)
	{
		$value=$object->$attribute;
		if($this->allowEmpty && $this->isEmpty($value))
			return;
		if(!$this->validateValue($value))
		{
			$message=$this->message!==null?$this->message:Yii::t('yii','{attribute} is not a valid email address.');
			$this->addError($object,$attribute,$message);
		}
	}

	/**
	 * Проверяет статичное значение на соответствие email-адресу.
	 * Примечание: данный метод не использует свойство {@link allowEmpty}.
	 * Метод предоставлен для того, чтобы можно было вызывать его непосредственно без прохождения механизма правил валидации модели.
	 * @param mixed $value валидируемое значение
	 * @return boolean является ли значение верным email-адресом
	 * @since 1.1.1
	 */
	public function validateValue($value)
	{
		if($this->validateIDN)
			$value=$this->encodeIDN($value);
		// make sure string length is limited to avoid DOS attacks
		$valid=is_string($value) && strlen($value)<=254 && (preg_match($this->pattern,$value) || $this->allowName && preg_match($this->fullPattern,$value));
		if($valid)
			$domain=rtrim(substr($value,strpos($value,'@')+1),'>');
		if($valid && $this->checkMX && function_exists('checkdnsrr'))
			$valid=checkdnsrr($domain,'MX');
		if($valid && $this->checkPort && function_exists('fsockopen') && function_exists('dns_get_record'))
			$valid=$this->checkMxPorts($domain);
		return $valid;
	}

	/**
	 * Возвращает JavaScript-код, требуемый для выполнения валидации на стороне
	 * клиента
	 * @param CModel $object валидируемый объект
	 * @param string $attribute валидируемый атрибут
	 * @return string скрипт валидации на стороне клиента
	 * @see CActiveForm::enableClientValidation
	 * @since 1.1.7
	 */
	public function clientValidateAttribute($object,$attribute)
	{
		if($this->validateIDN)
		{
			Yii::app()->getClientScript()->registerCoreScript('punycode');
			// punycode.js works only with the domains - so we have to extract it before punycoding
			$validateIDN='
var info = value.match(/^(.[^@]+)@(.+)$/);
if (info)
	value = info[1] + "@" + punycode.toASCII(info[2]);
';
		}
		else
			$validateIDN='';

		$message=$this->message!==null ? $this->message : Yii::t('yii','{attribute} is not a valid email address.');
		$message=strtr($message, array(
			'{attribute}'=>$object->getAttributeLabel($attribute),
		));

		$condition="!value.match({$this->pattern})";
		if($this->allowName)
			$condition.=" && !value.match({$this->fullPattern})";

		return "
$validateIDN
if(".($this->allowEmpty ? "jQuery.trim(value)!='' && " : '').$condition.") {
	messages.push(".CJSON::encode($message).");
}
";
	}
	
	/**
	 * Retrieves the list of MX records for $domain and checks if port 25
	 * is opened on any of these.
	 * @since 1.1.11
	 * @param string $domain domain to be checked
	 * @return boolean true if a reachable MX server has been found
	 */
	protected function checkMxPorts($domain)
	{
		$records=dns_get_record($domain, DNS_MX);
		if($records===false || empty($records))
			return false;
		usort($records,array($this,'mxSort'));
		foreach($records as $record)
		{
			$handle=fsockopen($record['target'],25);
			if($handle!==false)
			{
				fclose($handle);
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Determines if one MX record has higher priority as another
	 * (i.e. 'pri' is lower). Used by {@link checkMxPorts}.
	 * @since 1.1.11
	 * @param mixed $a первый элемент для сравнения
	 * @param mixed $b второй элемент для сравнения
	 * @return boolean
	 */
	protected function mxSort($a, $b)
	{
		if($a['pri']==$b['pri'])
			return 0;
		return ($a['pri']<$b['pri'])?-1:1;
	}

	/**
	 * Converts given IDN to the punycode.
	 * @param $value IDN to be converted.
	 * @return string resulting punycode.
	 * @since 1.1.13
	 */
	private function encodeIDN($value)
	{
		require_once(Yii::getPathOfAlias('system.vendors.idna_convert').DIRECTORY_SEPARATOR.'idna_convert.class.php');
		$idnaConvert=new idna_convert();
		return $idnaConvert->encode($value);
	}
}
