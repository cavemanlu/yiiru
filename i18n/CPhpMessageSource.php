<?php
/**
 * Файл класса CPhpMessageSource.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CPhpMessageSource представляет источник сообщений, хранящий переведенные сообщения в PHP-скриптах.
 *
 * CPhpMessageSource использует PHP файлы и массивы, содержащие переведенные сообщения.
 * <ul>
 * <li>Все перевод хранятся в директории, заданной свойством {@link basePath}.</li>
 * <li>Переводы для одного языка хранятся в отдельной директории, имя которой равно идентификатору языка.
 *   Файлы с одинаковыми именами в разных директориях содержат переводы одной и той же категории,
 *   а имена данных файлов равны имени категории.</li>
 * <li>PHP файл возвращает массив пар исходное сообщение => переведенное сообщение.
 * Например:
 * <pre>
 * return array(
 *     'оригинальное сообщение 1' => 'переведенное сообщение 1',
 *     'оригинальное сообщение 2' => 'переведенное сообщение 2',
 * );
 * </pre>
 * </li>
 * </ul>
 * Если значение свойства {@link cachingDuration} - положительное число, то переводы сообщений будут кэшироваться.
 *
 * Можно специально управлять и использовать сообщения классов расширений (например, виджетов, модулей).
 * В частности, если сообщения относятся к расширению, класс которого имеет имя Xyz, тогда категория сообщения может быть определена
 * в формате 'Xyz.categoryName'. При этом предполагается, что соответствующий файл сообщений 
 * имеет путь 'BasePath/messages/LanguageID/categoryName.php', где 'BasePath' - директория,
 * содержащая файл класса расширения. При использовании метода Yii::t() для перевода сообщений расширения
 * имя категории должно быть установлено в значение 'Xyz.categoryName'.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CPhpMessageSource.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.i18n
 * @since 1.0
 */
class CPhpMessageSource extends CMessageSource
{
	const CACHE_KEY_PREFIX='Yii.CPhpMessageSource.';

	/**
	 * @var integer срок годности кэша сообщений в секундах.
	 * По умолчанию - 0, т.е., кэш отключен.
	 */
	public $cachingDuration=0;
	/**
	 * @var string идентификатор компонента кэша приложения, используемого для кэширования сообщений.
	 * По умолчанию - 'cache', соответствующий основному компоненту кэша приложения.
	 * Установите данное свойство в значение false для отключения кэширования сообщений
	 */
	public $cacheID='cache';
	/**
	 * @var string базовый путь ко всем переведенным сообщениям. По умолчанию - null, т.е.,
	 * используется поддиректория "messages" директории приложения (например, "protected/messages").
	 */
	public $basePath;

	private $_files=array();

	/**
	 * Инициализирует компонент приложения.
	 * Метод переопределяет родительский метод, подготавливая данные пользовательского запроса.
	 */
	public function init()
	{
		parent::init();
		if($this->basePath===null)
			$this->basePath=Yii::getPathOfAlias('application.messages');
	}

	/**
	 * Определяет файл сообщений на основании переданных значений категори и языка.
	 * Если имя категории содержит точку, то оно будет разделено на имя модуля и имя категории.
	 * В этом случае предполагается, что файл сообщений находится в поддиректории 'messages'
	 * директории, содержащей класс модуля.
	 * Иначе считается, что файл находится в директории, заданной свойством {@link basePath}.
	 * @param string $category имя категории
	 * @param string $language идентификатор языка
	 * @return string путь к файлу сообщений
	 */
	protected function getMessageFile($category,$language)
	{
		if(!isset($this->_files[$category][$language]))
		{
			if(($pos=strpos($category,'.'))!==false)
			{
				$moduleClass=substr($category,0,$pos);
				$moduleCategory=substr($category,$pos+1);
				$class=new ReflectionClass($moduleClass);
				$this->_files[$category][$language]=dirname($class->getFileName()).DIRECTORY_SEPARATOR.'messages'.DIRECTORY_SEPARATOR.$language.DIRECTORY_SEPARATOR.$moduleCategory.'.php';
			}
			else
				$this->_files[$category][$language]=$this->basePath.DIRECTORY_SEPARATOR.$language.DIRECTORY_SEPARATOR.$category.'.php';
		}
		return $this->_files[$category][$language];
	}

	/**
	 * Загружает переведенные на определенный язык сообщения определенной категории.
	 * @param string $category категория сообщения
	 * @param string $language целевой язык
	 * @return array загруженные сообщения
	 */
	protected function loadMessages($category,$language)
	{
		$messageFile=$this->getMessageFile($category,$language);

		if($this->cachingDuration>0 && $this->cacheID!==false && ($cache=Yii::app()->getComponent($this->cacheID))!==null)
		{
			$key=self::CACHE_KEY_PREFIX . $messageFile;
			if(($data=$cache->get($key))!==false)
				return unserialize($data);
		}

		if(is_file($messageFile))
		{
			$messages=include($messageFile);
			if(!is_array($messages))
				$messages=array();
			if(isset($cache))
			{
				$dependency=new CFileCacheDependency($messageFile);
				$cache->set($key,serialize($messages),$this->cachingDuration,$dependency);
			}
			return $messages;
		}
		else
			return array();
	}
}