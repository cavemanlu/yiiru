<?php
/**
 * Файл класса CMessageSource.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CMessageSource - это базовый класс для классов репозиториев переведенных сообщений.
 *
 * Источник сообщений - это компонент приложения, обеспечивающий интернационализацию сообщений (i18n).
 * Он хранит переведенные сообщения на различных языках и предоставляет эти переведенные версии по запросу.
 *
 * Конкретный класс должен реализовать метод {@link loadMessages} или переопределить метод {@link translateMessage}.
 *
 * @property string $language язык исходных сообщений. По умолчанию -
 * {@link CApplication::language язык приложения}
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CMessageSource.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.i18n
 * @since 1.0
 */
abstract class CMessageSource extends CApplicationComponent
{
	/**
	 * @var boolean выполнять ли поиск перевода в случае, если исходный и целевой языки совпадают
	 * По умолчанию - false, т.е., перевод выполняется только когда исходный и целевой языки различны
	 * @since 1.1.4
	 */
	public $forceTranslation=false;

	private $_language;
	private $_messages=array();

	/**
	 * Загружает переведенные на определенный язык сообщения определенной категории
	 * @param string $category категория сообщения
	 * @param string $language целевой язык
	 * @return array загруженные сообщения
	 */
	abstract protected function loadMessages($category,$language);

	/**
	 * Возвращает язык исходных сообщений
	 * @return string язык исходных сообщений.
	 * По умолчанию - {@link CApplication::language язык приложения}
	 */
	public function getLanguage()
	{
		return $this->_language===null ? Yii::app()->sourceLanguage : $this->_language;
	}

	/**
	 * Устанавливает язык исходных сообщений
	 * @param string $language язык исходных сообщений
	 */
	public function setLanguage($language)
	{
		$this->_language=CLocale::getCanonicalID($language);
	}

	/**
	 * Переводит сообщение на определенный язык.
	 *
	 * Помните, что если требуемый язык идентичен
	 * {@link getLanguage исходному языку сообщения}, то сообщение НЕ будет переведено.
	 *
	 * Если сообщение не найдено в переводах, то вызывается событие {@link onMissingTranslation}.
	 * Обработчики могут отметить данное сообщение или осуществить некую другую обработку.
	 * Возвращается свойство {@link CMissingTranslationEvent::message}
	 * параметра события.
	 *
	 * @param string $category категория, к которой относится сообщение
	 * @param string $message переводимое сообщение
	 * @param string $language целевой язык. Если передано значение null (по умлочанию), то
	 * используется {@link CApplication::getLanguage язык приложения}
	 * @return string переведенное сообщение (или оригинальное сообщение, если перевод не требуется)
	 */
	public function translate($category,$message,$language=null)
	{
		if($language===null)
			$language=Yii::app()->getLanguage();
		if($this->forceTranslation || $language!==$this->getLanguage())
			return $this->translateMessage($category,$message,$language);
		else
			return $message;
	}

	/**
	 * Переводит определенное сообщение.
	 * Если сообщение не найдено, будет вызвано событие {@link onMissingTranslation}
	 * @param string $category категория, к которой относится сообщение
	 * @param string $message переводимое сообщение
	 * @param string $language целевой язык
	 * @return string переведенное сообщение
	 */
	protected function translateMessage($category,$message,$language)
	{
		$key=$language.'.'.$category;
		if(!isset($this->_messages[$key]))
			$this->_messages[$key]=$this->loadMessages($category,$language);
		if(isset($this->_messages[$key][$message]) && $this->_messages[$key][$message]!=='')
			return $this->_messages[$key][$message];
		else if($this->hasEventHandler('onMissingTranslation'))
		{
			$event=new CMissingTranslationEvent($this,$category,$message,$language);
			$this->onMissingTranslation($event);
			return $event->message;
		}
		else
			return $message;
	}

	/**
	 * Вызывается, когда сообщение не может быть переведено.
	 * Обработчики могут журналировать данное сообщение или делать другую требуемую обработку.
	 * Методом {@link translateMessage} будет возвращено свойство {@link CMissingTranslationEvent::message}
	 * @param CMissingTranslationEvent $event параметр события
	 */
	public function onMissingTranslation($event)
	{
		$this->raiseEvent('onMissingTranslation',$event);
	}
}


/**
 * Класс CMissingTranslationEvent представляет параметр для события {@link CMessageSource::onMissingTranslation onMissingTranslation}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CMessageSource.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.i18n
 * @since 1.0
 */
class CMissingTranslationEvent extends CEvent
{
	/**
	 * @var string переводимое сообщение
	 */
	public $message;
	/**
	 * @var string категория, к которой относится сообщение
	 */
	public $category;
	/**
	 * @var string идентификатор языка, на который должно быть переведено сообщение
	 */
	public $language;

	/**
	 * Конструктор
	 * @param mixed $sender объект, пославший событие
	 * @param string $category категория, к которой относится сообщение
	 * @param string $message переводимое сообщение
	 * @param string $language идентификатор языка, на который должно быть переведено сообщение
	 */
	public function __construct($sender,$category,$message,$language)
	{
		parent::__construct($sender);
		$this->message=$message;
		$this->category=$category;
		$this->language=$language;
	}
}
