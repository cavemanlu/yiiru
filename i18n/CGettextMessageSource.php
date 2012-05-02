<?php
/**
 * Файл класса CGettextMessageSource.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CGettextMessageSource представляет источник сообщений, основанный на технологии GNU Gettext.
 *
 * Каждый экземпляр класса CGettextMessageSource представляет переводы сообщений для отдельного домена.
 * А каждая  категория сообщений предсталяет контекст сообщений в Gettext.
 * Переведенные сообщения хранятся в виде файлов MO или PO,
 * в зависимости отзначения свойства {@link useMoFile}.
 *
 * Все переводы хранятся в директории, задаваемой свойством {@link basePath}.
 * Переводы на один язык хранятся в виде файлов MO или PO в отдельной поддиректории с именем,
 * равным идентификатору языка. Имя файла определяется свойством
 * {@link catalog}, принимающим по умолчанию значение 'messages'.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CGettextMessageSource.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.i18n
 * @since 1.0
 */
class CGettextMessageSource extends CMessageSource
{
	const CACHE_KEY_PREFIX='Yii.CGettextMessageSource.';
	const MO_FILE_EXT='.mo';
	const PO_FILE_EXT='.po';

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
	/**
	 * @var boolean загружать ли сообщения из файлов MO. По умолчанию - true.
	 * Если значение равно false, то сообщения загружаются из файлов PO.
	 */
	public $useMoFile=true;
	/**
	 * @var boolean использовать ли порядок байтов "Big Endian" для чтения и записи файлов MO.
	 * По умолчанию - false. Свойство используется только если свойство {@link useMoFile} имеет значение true.
	 */
	public $useBigEndian=false;
	/**
	 * @var string имя каталога сообщений. Это имя файла сообщений (без расширения),
	 * содержащего переведенные сообщения. По умолчанию - 'messages'.
	 */
	public $catalog='messages';

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
	 * Загружает переведенные на определенный язык сообщения определенной категории.
	 * @param string $category категория сообщения
	 * @param string $language целевой язык
	 * @return array загруженные сообщения
	 */
	protected function loadMessages($category, $language)
	{
        $messageFile=$this->basePath . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . $this->catalog;
        if($this->useMoFile)
        	$messageFile.=self::MO_FILE_EXT;
        else
        	$messageFile.=self::PO_FILE_EXT;

		if ($this->cachingDuration > 0 && $this->cacheID!==false && ($cache=Yii::app()->getComponent($this->cacheID))!==null)
		{
			$key = self::CACHE_KEY_PREFIX . $messageFile;
			if (($data=$cache->get($key)) !== false)
				return unserialize($data);
		}

		if (is_file($messageFile))
		{
			if($this->useMoFile)
				$file=new CGettextMoFile($this->useBigEndian);
			else
				$file=new CGettextPoFile();
			$messages=$file->load($messageFile,$category);
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
