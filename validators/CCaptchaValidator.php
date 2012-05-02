<?php
/**
 * Файл класса CCaptchaValidator.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Валидатор CCaptchaValidator проверяет атрибут на соответствие коду верификации, отображенному на изображении капчи.
 *
 * CCaptchaValidator должен использоваться с компонентом {@link CCaptchaAction}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CCaptchaValidator.php 3124 2011-03-25 15:48:05Z qiang.xue $
 * @package system.validators
 * @since 1.0
 */
class CCaptchaValidator extends CValidator
{
	/**
	 * @var boolean должно ли сравнение быть регистрозависимым. По умолчанию - false.
	 */
	public $caseSensitive=false;
	/**
	 * @var string идентификатор действия, которое генерирует изображение капчи. По умолчанию - 'captcha',
	 * т.е. действие 'captcha', определенное в текущем контроллере.
	 * Также это может быть маршрут, содержащий идентификаторы контроллера и действия.
	 */
	public $captchaAction='captcha';
	/**
	 * @var boolean может ли быть значение атрибута пустым или равным null. По умолчанию - true,
	 * т.е. пустой атрибут считается валидным
	 */
	public $allowEmpty=false;

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
		$captcha=$this->getCaptchaAction();
		if(!$captcha->validate($value,$this->caseSensitive))
		{
			$message=$this->message!==null?$this->message:Yii::t('yii','The verification code is incorrect.');
			$this->addError($object,$attribute,$message);
		}
	}

	/**
	 * Returns the CAPTCHA action object.
	 * @return CCaptchaAction the action object
	 * @since 1.1.7
	 */
	protected function getCaptchaAction()
	{
		if(($captcha=Yii::app()->getController()->createAction($this->captchaAction))===null)
		{
			if(strpos($this->captchaAction,'/')!==false) // contains controller or module
			{
				if(($ca=Yii::app()->createController($this->captchaAction))!==null)
				{
					list($controller,$actionID)=$ca;
					$captcha=$controller->createAction($actionID);
				}
			}
			if($captcha===null)
				throw new CException(Yii::t('yii','CCaptchaValidator.action "{id}" is invalid. Unable to find such an action in the current controller.',
						array('{id}'=>$this->captchaAction)));
		}
		return $captcha;
	}

	/**
	 * Returns the JavaScript needed for performing client-side validation.
	 * @param CModel $object the data object being validated
	 * @param string $attribute the name of the attribute to be validated.
	 * @return string the client-side validation script.
	 * @see CActiveForm::enableClientValidation
	 * @since 1.1.7
	 */
	public function clientValidateAttribute($object,$attribute)
	{
		$captcha=$this->getCaptchaAction();
		$message=$this->message!==null ? $this->message : Yii::t('yii','The verification code is incorrect.');
		$message=strtr($message, array(
			'{attribute}'=>$object->getAttributeLabel($attribute),
		));
		$code=$captcha->getVerifyCode(false);
		$hash=$captcha->generateValidationHash($this->caseSensitive ? $code : strtolower($code));
		$js="
var hash = $('body').data('{$this->captchaAction}.hash');
if (hash == null)
	hash = $hash;
else
	hash = hash[".($this->caseSensitive ? 0 : 1)."];
for(var i=value.length-1, h=0; i >= 0; --i) h+=value.".($this->caseSensitive ? '' : 'toLowerCase().')."charCodeAt(i);
if(h != hash) {
	messages.push(".CJSON::encode($message).");
}
";

		if($this->allowEmpty)
		{
			$js="
if($.trim(value)!='') {
	$js
}
";
		}

		return $js;
	}
}

