<?php
/**
 * Файл класса CApplication.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * CApplication - это базовый класс для всех классов приложения.
 *
 * Приложение служит в качестве глобального контекста, в котором выполняется запрос пользователя.
 * Оно управляет набором компонентов приложения, предоставляющих
 * специальные функции для всего приложения.
 *
 * Список компонентов ядра приложения, предоставляемых классом CApplication:
 * <ul>
 * <li>{@link getErrorHandler errorHandler}: обрабатывает ошибки PHP и
 *   неперехваченные исключения. Данный компонент приложения загружается динамически при необходимости;</li>
 * <li>{@link getSecurityManager securityManager}: предоставляет функции безопасности
 *   такие, как хэширование, шифрование. Данный компонент приложения загружается динамически при необходимости;</li>
 * <li>{@link getStatePersister statePersister}: предоставляет функцию постоянного глобального состояния.
 *   Данный компонент приложения загружается динамически при необходимости;</li>
 * <li>{@link getCache cache}: предоставляет функции кэширования. Данный компонент по умолчанию отключен;</li>
 * <li>{@link getMessages messages}: предоставляет источник сообщений для перевода сообщений
 *   приложения. Данный компонент приложения загружается динамически при необходимости;</li>
 * <li>{@link getCoreMessages coreMessages}: предоставляет источник сообщений для перевода сообщений
 *   фреймворка Yii. Данный компонент приложения загружается динамически при необходимости.</li>
 * </ul>
 *
 * CApplication работает по следующему жизненному циклу при обработке пользовательского запроса:
 * <ol>
 * <li>загружает конфигурацию приложения;</li>
 * <li>устанавливает класс автозагрузчика и обработчика ошибок;</li>
 * <li>загружает статические компоненты приложения;</li>
 * <li>{@link onBeginRequest}: выполняет действия перед выполнением пользовательского запроса;</li>
 * <li>{@link processRequest}: выполняет пользовательский запрос;</li>
 * <li>{@link onEndRequest}: выполняет действия после выполнения пользовательского запроса;</li>
 * </ol>
 *
 * Начиная с пункта 3, при возникновении ошибки PHP или неперехваченного
 * исключения, приложение переключается на его обработчик ошибок и после
 * переходит к шагу 6.
 *
 * @property string $id уникальный идентификатор приложения
 * @property string $basePath корневая директория приложения. По умолчанию -
 * 'protected'
 * @property string $runtimePath директория, хранящая рабочие файлы. По
 * умолчанию - 'protected/runtime'
 * @property string $extensionPath директория, содержащая все расширения. По
 * умолчанию - директория 'extensions' в директории 'protected'
 * @property string $language язык, используемый пользователем и приложением.
 * По умолчанию задан свойством {@link sourceLanguage}
 * @property string $timeZone временная зона, используемая приложением
 * @property CLocale $locale экземпляр локали
 * @property string $localeDataPath директория, содержащая данные локали. По
 * умолчанию - 'framework/i18n/data'
 * @property CNumberFormatter $numberFormatter локалезависимый менеджер
 * форматирования чисел. Используется текущая {@link getLocale локаль приложения}
 * @property CDateFormatter $dateFormatter локалезависимый менеджер
 * форматирования дат. Используется текущая {@link getLocale локаль приложения}
 * @property CDbConnection $db компонент соединения с базой
 * @property CErrorHandler $errorHandler комопонент приложения, отвечающий за
 * обработку ошибок
 * @property CSecurityManager $securityManager компонент приложения, отвечающий
 * за безопасность
 * @property CStatePersister $statePersister компонент приложения,
 * представляющий постоянное состояние (state persister)
 * @property CCache $cache компонент приложения кэша. Null, если компонент не
 * включен
 * @property CPhpMessageSource $coreMessages компонент приложения, отвечающий
 * за перевод сообщений ядра
 * @property CMessageSource $messages компонент приложения, отвечающий за
 * перевод сообщений приложения
 * @property CHttpRequest $request компонент запроса
 * @property CUrlManager $urlManager менеджер URL маршрутов
 * @property CController $controller текущий активный контроллер. В данном
 * базовом классе возвращается значение null
 * @property string $baseUrl относительный URL-адрес приложения
 * @property string $homeUrl URL-адрес домашней страницы
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CApplication.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.base
 * @since 1.0
 */
abstract class CApplication extends CModule
{
	/**
	 * @var string имя приложения. По умолчанию - 'My Application'.
	 */
	public $name='My Application';
	/**
	 * @var string кодировка, используемая приложением. По умолчанию - 'UTF-8'.
	 */
	public $charset='UTF-8';
	/**
	 * @var string язык приложения. В основном это язык сообщений и представлений.
	 * По умолчанию - 'en_us' (US English).
	 */
	public $sourceLanguage='en_us';

	private $_id;
	private $_basePath;
	private $_runtimePath;
	private $_extensionPath;
	private $_globalState;
	private $_stateChanged;
	private $_ended=false;
	private $_language;
	private $_homeUrl;

	/**
	 * Выполняет запрос.
	 * Это то место, где выполняется основная работа по запросу.
	 * Классы-наследники должны переопределить данный метод.
	 */
	abstract public function processRequest();

	/**
	 * Конструктор.
	 * @param mixed $config конфигурация приложения.
	 * Если передана строка, она считается путем к файлу, содержащему конфигурацию;
	 * если передан массив, он считается реальной информацией конфигурации.
	 * Убедитесь, что свойство {@link getBasePath basePath} определено в конфигурации и
	 * указывает на директорию, содержащую всю логику приложения, шаблоны и данные.
	 * Если это свойство не указано, по умолчанию будет использована директория 'protected'.
	 */
	public function __construct($config=null)
	{
		Yii::setApplication($this);

		// set basePath at early as possible to avoid trouble
		if(is_string($config))
			$config=require($config);
		if(isset($config['basePath']))
		{
			$this->setBasePath($config['basePath']);
			unset($config['basePath']);
		}
		else
			$this->setBasePath('protected');
		Yii::setPathOfAlias('application',$this->getBasePath());
		Yii::setPathOfAlias('webroot',dirname($_SERVER['SCRIPT_FILENAME']));
		Yii::setPathOfAlias('ext',$this->getBasePath().DIRECTORY_SEPARATOR.'extensions');

		$this->preinit();

		$this->initSystemHandlers();
		$this->registerCoreComponents();

		$this->configure($config);
		$this->attachBehaviors($this->behaviors);
		$this->preloadComponents();

		$this->init();
	}


	/**
	 * Запускает приложение.
	 * Метод загружает статические компоненты приложения. Классы-наследники обычно переопределяют
	 * данны метод для выполнения более специфичных задач приложения.
	 * Не забудьте вызвать метод родителя для загрузки статических компонентов приложения.
	 */
	public function run()
	{
		if($this->hasEventHandler('onBeginRequest'))
			$this->onBeginRequest(new CEvent($this));
		$this->processRequest();
		if($this->hasEventHandler('onEndRequest'))
			$this->onEndRequest(new CEvent($this));
	}

	/**
	 * Завершает приложение.
	 * Метод заменяет PHP функцию exit() вызовом метода {@link onEndRequest} перед выходом.
	 * @param integer $status статус выхода (значение 0 означает нормальный выход, другое значение означает выход с ошибкой)
	 * @param boolean $exit завершить ли текущий запрос. Данный параметр доступен с версии 1.1.5.
	 * По умолчанию - true, т.е., PHP функция exit() будет вызываться в конце данного метода
	 */
	public function end($status=0, $exit=true)
	{
		if($this->hasEventHandler('onEndRequest'))
			$this->onEndRequest(new CEvent($this));
		if($exit)
			exit($status);
	}

	/**
	 * Выполняется непосредственно ПЕРЕД обработкой запроса приложением.
	 * @param CEvent $event параметр события
	 */
	public function onBeginRequest($event)
	{
		$this->raiseEvent('onBeginRequest',$event);
	}

	/**
	 * Выполняется сразу ПОСЛЕ обработки запроса приложением.
	 * @param CEvent $event параметр события
	 */
	public function onEndRequest($event)
	{
		if(!$this->_ended)
		{
			$this->_ended=true;
			$this->raiseEvent('onEndRequest',$event);
		}
	}

	/**
	 * Возвращает уникальный идентификатор приложения
	 * @return string уникальный идентификатор приложения
	 */
	public function getId()
	{
		if($this->_id!==null)
			return $this->_id;
		else
			return $this->_id=sprintf('%x',crc32($this->getBasePath().$this->name));
	}

	/**
	 * Устанавливает уникальный идентификатор приложения
	 * @param string $id уникальный идентификатор приложения
	 */
	public function setId($id)
	{
		$this->_id=$id;
	}

	/**
	 * Возвращает корневую директорию приложения
	 * @return string корневая директория приложения. По умолчанию - 'protected'
	 */
	public function getBasePath()
	{
		return $this->_basePath;
	}

	/**
	 * Устанавливает корневую директорию приложения.
	 * Метод может быть вызван только в начале конструктора
	 * @param string $path корневая директория приложения
	 * @throws CException вызывается, если директория не существует
	 */
	public function setBasePath($path)
	{
		if(($this->_basePath=realpath($path))===false || !is_dir($this->_basePath))
			throw new CException(Yii::t('yii','Application base path "{path}" is not a valid directory.',
				array('{path}'=>$path)));
	}

	/**
	 * Возвращает директорию, хранящую рабочие файлы
	 * @return string директория, хранящая рабочие файлы. По умолчанию - 'protected/runtime'
	 */
	public function getRuntimePath()
	{
		if($this->_runtimePath!==null)
			return $this->_runtimePath;
		else
		{
			$this->setRuntimePath($this->getBasePath().DIRECTORY_SEPARATOR.'runtime');
			return $this->_runtimePath;
		}
	}

	/**
	 * Устанавливает директорию, хранящую рабочие файлы
	 * @param string $path директория, хранящая рабочие файлы
	 * @throws CException вызывается, если директория не существует или недоступна для записи
	 */
	public function setRuntimePath($path)
	{
		if(($runtimePath=realpath($path))===false || !is_dir($runtimePath) || !is_writable($runtimePath))
			throw new CException(Yii::t('yii','Application runtime path "{path}" is not valid. Please make sure it is a directory writable by the Web server process.',
				array('{path}'=>$path)));
		$this->_runtimePath=$runtimePath;
	}

	/**
	 * Возвращает корневую директорию, хранящую все сторонние расширения
	 * @return string директория, содержащая все расширения. По умолчанию - директория 'extensions' в директории 'protected'
	 */
	public function getExtensionPath()
	{
		return Yii::getPathOfAlias('ext');
	}

	/**
	 * Устанавливает корневую директорию, хранящую все сторонние расширения
	 * @param string $path директория, содержащая все сторонние расширения
	 */
	public function setExtensionPath($path)
	{
		if(($extensionPath=realpath($path))===false || !is_dir($extensionPath))
			throw new CException(Yii::t('yii','Extension path "{path}" does not exist.',
				array('{path}'=>$path)));
		Yii::setPathOfAlias('ext',$extensionPath);
	}

	/**
	 * Возвращает язык, используемый пользователем и приложением
	 * @return string язык, используемый пользователем и приложением.
	 * По умолчанию задан свойством {@link sourceLanguage}
	 */
	public function getLanguage()
	{
		return $this->_language===null ? $this->sourceLanguage : $this->_language;
	}

	/**
	 * Определяет язык, используемый приложением.
	 *
	 * Это язык, отображаемый приложением конечным пользователям.
	 * Если null, будет использован язык, заданный свойством {@link sourceLanguage}.
	 *
	 * Если ваше приложение должно поддерживать несколько языков, вы должны всегда
	 * устанавливать данный язык в null для улучшения производительности приложения
	 * @param string $language язык пользователя (например, 'en_US', 'zh_CN').
	 * Если null, будет использован язык, заданный свойством {@link sourceLanguage}
	 */
	public function setLanguage($language)
	{
		$this->_language=$language;
	}

	/**
	 * Возвращает временную зону, используемую приложением.
	 * Это простая обертка PHP-функции date_default_timezone_get()
	 * @return string временная зона, используемая приложением
	 * @see http://php.net/manual/en/function.date-default-timezone-get.php
	 */
	public function getTimeZone()
	{
		return date_default_timezone_get();
	}

	/**
	 * Устанавливает временную зону, используемую приложением.
	 * Это простая обертка PHP-функции date_default_timezone_set()
	 * @param string $value временная зона, используемая приложением
	 * @see http://php.net/manual/en/function.date-default-timezone-set.php
	 */
	public function setTimeZone($value)
	{
		date_default_timezone_set($value);
	}

	/**
	 * Возвращает локализованную версию определенного файла.
	 *
	 * Поиск идет по коду определенного языка. В частности,
	 * файл с таким же именем будет искаться в поддиректории с именем,
	 * равным иеднтификатору локали. Например, если переданы файл "path/to/view.php"
	 * и локаль "zh_cn", то путёт поиска локализованнного файла будет
	 * "path/to/zh_cn/view.php". Если файл не найден, будет возвращен оригинальный файл.
	 *
	 * Для согласованности рекомендуется передавать идентификатор локали
	 * в нижнем регистре и в формате идентификаторЯзыка_идентификаторРегиона (например, "en_us")
	 *
	 * @param string $srcFile оригинальный файл
	 * @param string $srcLanguage язык оригинального файла. Если null, используется язык, заданный свойством {@link sourceLanguage}
	 * @param string $language желаемый язык, локализованная версия файла которого требуется. Если null, используется {@link getLanguage язык приложения}
	 * @return string соответствующий локализованный файл. Если локализованныя версия не найдена или исходный язык равен желаемомоу, возвращается оригинальный файл
	 */
	public function findLocalizedFile($srcFile,$srcLanguage=null,$language=null)
	{
		if($srcLanguage===null)
			$srcLanguage=$this->sourceLanguage;
		if($language===null)
			$language=$this->getLanguage();
		if($language===$srcLanguage)
			return $srcFile;
		$desiredFile=dirname($srcFile).DIRECTORY_SEPARATOR.$language.DIRECTORY_SEPARATOR.basename($srcFile);
		return is_file($desiredFile) ? $desiredFile : $srcFile;
	}

	/**
	 * Возвращает экземпляр локали
	 * @param string $localeID идентификатор локали (например, en_US). Если null, используется идентификатор {@link getLanguage языка приложения}
	 * @return CLocale экземпляр локали
	 */
	public function getLocale($localeID=null)
	{
		return CLocale::getInstance($localeID===null?$this->getLanguage():$localeID);
	}

	/**
	 * Возвращает директорию, содержащую данные локали
	 * @return string директория, содержащая данные локали. По умолчанию - 'framework/i18n/data'
	 * @since 1.1.0
	 */
	public function getLocaleDataPath()
	{
		return CLocale::$dataPath===null ? Yii::getPathOfAlias('system.i18n.data') : CLocale::$dataPath;
	}

	/**
	 * Устанавливает директорию, содержащую данные локали
	 * @param string $value директория, содержащая данные локали
	 * @since 1.1.0
	 */
	public function setLocaleDataPath($value)
	{
		CLocale::$dataPath=$value;
	}

	/**
	 * Возвращает локалезависимый менеджер форматирования чисел
	 * @return CNumberFormatter локалезависимый менеджер форматирования чисел.
	 * Используется текущая {@link getLocale локаль приложения}
	 */
	public function getNumberFormatter()
	{
		return $this->getLocale()->getNumberFormatter();
	}

	/**
	 * Возвращает локалезависимый менеджер форматирования дат
	 * @return CDateFormatter локалезависимый менеджер форматирования дат.
	 * Используется текущая {@link getLocale локаль приложения}
	 */
	public function getDateFormatter()
	{
		return $this->getLocale()->getDateFormatter();
	}

	/**
	 * Возвращает компонент соединения с базой
	 * @return CDbConnection компонент соединения с базой
	 */
	public function getDb()
	{
		return $this->getComponent('db');
	}

	/**
	 * Возвращает комопонент приложения, отвечающий за обработку ошибок
	 * @return CErrorHandler комопонент приложения, отвечающий за обработку ошибок
	 */
	public function getErrorHandler()
	{
		return $this->getComponent('errorHandler');
	}

	/**
	 * Возвращает компонент приложения, отвечающий за безопасность
	 * @return CSecurityManager компонент приложения, отвечающий за безопасность
	 */
	public function getSecurityManager()
	{
		return $this->getComponent('securityManager');
	}

	/**
	 * Возвращает компонент приложения, представляющий постоянное состояние
	 * (state persister)
	 * @return CStatePersister компонент приложения, представляющий постоянное
	 * состояние (state persister)
	 */
	public function getStatePersister()
	{
		return $this->getComponent('statePersister');
	}

	/**
	 * Возвращает  компонент приложения кэша
	 * @return CCache компонент приложения кэша. Null, если компонент не включен
	 */
	public function getCache()
	{
		return $this->getComponent('cache');
	}

	/**
	 * Возвращает компонент приложения, отвечающий за перевод сообщений ядра
	 * @return CPhpMessageSource компонент приложения, отвечающий за перевод сообщений ядра
	 */
	public function getCoreMessages()
	{
		return $this->getComponent('coreMessages');
	}

	/**
	 * Возвращает компонент приложения, отвечающий за перевод сообщений приложения
	 * @return CMessageSource компонент приложения, отвечающий за перевод сообщений приложения
	 */
	public function getMessages()
	{
		return $this->getComponent('messages');
	}

	/**
	 * Возвращает компонент запроса
	 * @return CHttpRequest компонент запроса
	 */
	public function getRequest()
	{
		return $this->getComponent('request');
	}

	/**
	 * Возвращает менеджер URL маршрутов
	 * @return CUrlManager менеджер URL маршрутов
	 */
	public function getUrlManager()
	{
		return $this->getComponent('urlManager');
	}

	/**
	 * @return CController текущий активный контроллер. В данном базовом классе
	 * возвращается значение null
	 * @since 1.1.8
	 */
	public function getController()
	{
		return null;
	}

	/**
	 * Создает относительный URL-адрес приложения на основе информации о
	 * переданных контроллере и действии
	 * @param string $route URL-маршрут. Должен быть в формате
	 * 'ControllerID/ActionID'
	 * @param array $params дополнительные GET-параметры (имя => значение). И
	 * имя и значение пройдут URL-кодирование
	 * @param string $ampersand символ, разделяющий пары имя-значение в
	 * URL-адресе
	 * @return string созданный URL-адрес
	 */
	public function createUrl($route,$params=array(),$ampersand='&')
	{
		return $this->getUrlManager()->createUrl($route,$params,$ampersand);
	}

	/**
	 * Создает абсолютный URL-адрес приложения на основе информации о
	 * переданных контроллере и действии
	 * @param string $route URL-маршрут. Должен быть в формате
	 * 'ControllerID/ActionID'
	 * @param array $params дополнительные GET-параметры (имя => значение). И
	 * имя и значение пройдут URL-кодирование
	 * @param string $schema используемый протокол (например, http, https).
	 * Если пусто, то используется протокол текущего запроса
	 * @param string $ampersand символ, разделяющий пары имя-значение в
	 * URL-адресе
	 * @return string созданный URL-адрес
	 */
	public function createAbsoluteUrl($route,$params=array(),$schema='',$ampersand='&')
	{
		$url=$this->createUrl($route,$params,$ampersand);
		if(strpos($url,'http')===0)
			return $url;
		else
			return $this->getRequest()->getHostInfo($schema).$url;
	}

	/**
	 * Возвращает относительный URL-адрес приложения. Является оберткой для
	 * метода {@link CHttpRequest::getBaseUrl()}
	 * @param boolean $absolute возвращать ли абсолютный URL-адрес. По
	 * умолчанию - false, т.е., возвращается относительный URL-адрес
	 * @return string относительный URL-адрес приложения
	 * @see CHttpRequest::getBaseUrl()
	 */
	public function getBaseUrl($absolute=false)
	{
		return $this->getRequest()->getBaseUrl($absolute);
	}

	/**
	 * @return string URL-адрес домашней страницы
	 */
	public function getHomeUrl()
	{
		if($this->_homeUrl===null)
		{
			if($this->getUrlManager()->showScriptName)
				return $this->getRequest()->getScriptUrl();
			else
				return $this->getRequest()->getBaseUrl().'/';
		}
		else
			return $this->_homeUrl;
	}

	/**
	 * @param string $value URL-адрес домашней страницы
	 */
	public function setHomeUrl($value)
	{
		$this->_homeUrl=$value;
	}

	/**
	 * Возвращает глобальное значение.
	 *
	 * Глобальное значение - это постоянное для пользовательских сессий и запросов значение.
	 * @param string $key имя возвращаемого значения
	 * @param mixed $defaultValue значение по умолчанию. Возвращается, если именованное глобальное значение не было найдено.
	 * @return mixed именованное глобальное значение
	 * @see setGlobalState
	 */
	public function getGlobalState($key,$defaultValue=null)
	{
		if($this->_globalState===null)
			$this->loadGlobalState();
		if(isset($this->_globalState[$key]))
			return $this->_globalState[$key];
		else
			return $defaultValue;
	}

	/**
	 * Устанавливает глобальное значение.
	 *
	 * Глобальное значение - это постоянное для пользовательских сессий и запросов значение.
	 * Убедитесь, что значение сериализуемо и десереализуемо.
	 * @param string $key имя сохраняемого значения
	 * @param mixed $value сохраняемое значение. Должно быть сериализуемо
	 * @param mixed $defaultValue значение по умолчанию. Если именованое глобальное значение такое же как и данное, оно будет удалено из текущего хранилища
	 * @see getGlobalState
	 */
	public function setGlobalState($key,$value,$defaultValue=null)
	{
		if($this->_globalState===null)
			$this->loadGlobalState();

		$changed=$this->_stateChanged;
		if($value===$defaultValue)
		{
			if(isset($this->_globalState[$key]))
			{
			unset($this->_globalState[$key]);
				$this->_stateChanged=true;
			}
		}
		else if(!isset($this->_globalState[$key]) || $this->_globalState[$key]!==$value)
		{
			$this->_globalState[$key]=$value;
			$this->_stateChanged=true;
		}

		if($this->_stateChanged!==$changed)
			$this->attachEventHandler('onEndRequest',array($this,'saveGlobalState'));
	}

	/**
	 * Очищает глобальное значение.
	 *
	 * Очищенное значение больше не будет доступно ни в данном запросе ни в последующих.
	 * @param string $key имя очищаемого значения
	 */
	public function clearGlobalState($key)
	{
		$this->setGlobalState($key,true,true);
	}

	/**
	 * Загружает данные глобального значения из постоянного хранилища.
	 * @see getStatePersister
	 * @throws CException вызывается, если менеджер постоянного состояния недоступен
	 */
	public function loadGlobalState()
	{
		$persister=$this->getStatePersister();
		if(($this->_globalState=$persister->load())===null)
			$this->_globalState=array();
		$this->_stateChanged=false;
		$this->detachEventHandler('onEndRequest',array($this,'saveGlobalState'));
	}

	/**
	 * Сохраняет данные глобального состояния в постоянное хранилище.
	 * @see getStatePersister
	 * @throws CException вызывается, если менеджер постоянного состояния недоступен
	 */
	public function saveGlobalState()
	{
		if($this->_stateChanged)
		{
			$this->_stateChanged=false;
			$this->detachEventHandler('onEndRequest',array($this,'saveGlobalState'));
			$this->getStatePersister()->save($this->_globalState);
		}
	}

	/**
	 * Обрабатывает неперехваченные исключения PHP.
	 *
	 * Метод реализован как обработчик исключений PHP. Он требует, чтобы
	 * константа YII_ENABLE_EXCEPTION_HANDLER была установлена в значение true.
	 *
	 * Сначала метод вызывает событие {@link onException}.
	 * Если исключение не обработано каким-либо другим обработчиком, для его
	 * обработки будет вызван {@link getErrorHandler errorHandler}.
	 *
	 * При вызове данного метода приложение завершается.
	 *
	 * @param Exception $exception неперехваченное исключение
	 */
	public function handleException($exception)
	{
		// disable error capturing to avoid recursive errors
		restore_error_handler();
		restore_exception_handler();

		$category='exception.'.get_class($exception);
		if($exception instanceof CHttpException)
			$category.='.'.$exception->statusCode;
		// php <5.2 doesn't support string conversion auto-magically
		$message=$exception->__toString();
		if(isset($_SERVER['REQUEST_URI']))
			$message.="\nREQUEST_URI=".$_SERVER['REQUEST_URI'];
		if(isset($_SERVER['HTTP_REFERER']))
			$message.="\nHTTP_REFERER=".$_SERVER['HTTP_REFERER'];
		$message.="\n---";
		Yii::log($message,CLogger::LEVEL_ERROR,$category);

		try
		{
			$event=new CExceptionEvent($this,$exception);
			$this->onException($event);
			if(!$event->handled)
			{
				// try an error handler
				if(($handler=$this->getErrorHandler())!==null)
					$handler->handle($event);
				else
					$this->displayException($exception);
			}
		}
		catch(Exception $e)
		{
			$this->displayException($e);
		}

		try
		{
		$this->end(1);
	}
		catch(Exception $e)
		{
			// use the most primitive way to log error
			$msg = get_class($e).': '.$e->getMessage().' ('.$e->getFile().':'.$e->getLine().")\n";
			$msg .= $e->getTraceAsString()."\n";
			$msg .= "Previous exception:\n";
			$msg .= get_class($exception).': '.$exception->getMessage().' ('.$exception->getFile().':'.$exception->getLine().")\n";
			$msg .= $exception->getTraceAsString()."\n";
			$msg .= '$_SERVER='.var_export($_SERVER,true);
			error_log($msg);
			exit(1);
		}
	}

	/**
	 * Обрабатывает ошибки выполнения PHP такие, как предупреждения (warnings), замечания (notices).
	 *
	 * Метод реализован как обработчик ошибок PHP. Он требует, чтобы
	 * константа YII_ENABLE_ERROR_HANDLER была установлена в значение true.
	 *
	 * Сначала метод вызывает событие {@link onError}.
	 * Если ошибка не обработана каким-либо другим обработчиком, для ее
	 * обработки будет вызван {@link getErrorHandler errorHandler}.
	 *
	 * При вызове данного метода приложение завершается.
	 *
	 * @param integer $code уровень ошибки
	 * @param string $message сообщение ошибки
	 * @param string $file файл, в котором произошла ошибка
	 * @param string $line строка кода, в которой произошла ошибка
	 */
	public function handleError($code,$message,$file,$line)
	{
		if($code & error_reporting())
		{
			// disable error capturing to avoid recursive errors
			restore_error_handler();
			restore_exception_handler();

			$log="$message ($file:$line)\nStack trace:\n";
			$trace=debug_backtrace();
			// skip the first 3 stacks as they do not tell the error position
			if(count($trace)>3)
				$trace=array_slice($trace,3);
			foreach($trace as $i=>$t)
			{
				if(!isset($t['file']))
					$t['file']='unknown';
				if(!isset($t['line']))
					$t['line']=0;
				if(!isset($t['function']))
					$t['function']='unknown';
				$log.="#$i {$t['file']}({$t['line']}): ";
				if(isset($t['object']) && is_object($t['object']))
					$log.=get_class($t['object']).'->';
				$log.="{$t['function']}()\n";
			}
			if(isset($_SERVER['REQUEST_URI']))
				$log.='REQUEST_URI='.$_SERVER['REQUEST_URI'];
			Yii::log($log,CLogger::LEVEL_ERROR,'php');

			try
			{
				Yii::import('CErrorEvent',true);
				$event=new CErrorEvent($this,$code,$message,$file,$line);
				$this->onError($event);
				if(!$event->handled)
				{
					// try an error handler
					if(($handler=$this->getErrorHandler())!==null)
						$handler->handle($event);
					else
						$this->displayError($code,$message,$file,$line);
				}
			}
			catch(Exception $e)
			{
				$this->displayException($e);
			}

			try
			{
			$this->end(1);
		}
			catch(Exception $e)
			{
				// use the most primitive way to log error
				$msg = get_class($e).': '.$e->getMessage().' ('.$e->getFile().':'.$e->getLine().")\n";
				$msg .= $e->getTraceAsString()."\n";
				$msg .= "Previous error:\n";
				$msg .= $log."\n";
				$msg .= '$_SERVER='.var_export($_SERVER,true);
				error_log($msg);
				exit(1);
			}
		}
	}

	/**
	 * Выполняется при возникновении неперехваченного исключения PHP.
	 *
	 * Обработчик события может установить свойство {@link CErrorEvent::handled handled}
	 * параметра события в значение true для индикации того, что дальнейшая обработка ошибок не
	 * требуется. В ином случае, компонент приложения {@link getErrorHandler errorHandler}
	 * будет продолжать обрабатывать ошибки.
	 *
	 * @param CExceptionEvent $event параметр события
	 */
	public function onException($event)
	{
		$this->raiseEvent('onException',$event);
	}

	/**
	 * Выполняется при возникновении ошибки исполнения скрипта PHP.
	 *
	 * Обработчик события может установить свойство {@link CErrorEvent::handled handled}
	 * параметра события в значение true для индикации того, что дальнейшая обработка ошибок не
	 * требуется. В ином случае, компонент приложения {@link getErrorHandler errorHandler}
	 * будет продолжать обрабатывать ошибки.
	 *
	 * @param CErrorEvent $event параметр события
	 */
	public function onError($event)
	{
		$this->raiseEvent('onError',$event);
	}

	/**
	 * Отображает перехваченную ошибку PHP.
	 * Метод отображает ошибку в коде HTML, если
	 * для нее нет обработчика.
	 * @param integer $code код ошибки
	 * @param string $message сообщение об ошибке
	 * @param string $file файл, в котором произошла ошибка
	 * @param string $line строка кода, в которой произошла ошибка
	 */
	public function displayError($code,$message,$file,$line)
	{
		if(YII_DEBUG)
		{
			echo "<h1>PHP Error [$code]</h1>\n";
			echo "<p>$message ($file:$line)</p>\n";
			echo '<pre>';

			$trace=debug_backtrace();
			// skip the first 3 stacks as they do not tell the error position
			if(count($trace)>3)
				$trace=array_slice($trace,3);
			foreach($trace as $i=>$t)
			{
				if(!isset($t['file']))
					$t['file']='unknown';
				if(!isset($t['line']))
					$t['line']=0;
				if(!isset($t['function']))
					$t['function']='unknown';
				echo "#$i {$t['file']}({$t['line']}): ";
				if(isset($t['object']) && is_object($t['object']))
					echo get_class($t['object']).'->';
				echo "{$t['function']}()\n";
			}

			echo '</pre>';
		}
		else
		{
			echo "<h1>PHP Error [$code]</h1>\n";
			echo "<p>$message</p>\n";
		}
	}

	/**
	 * Отображает неперехваченные исключения PHP.
	 * Метод отображает исключения в HTML, когда нет активного обработчика ошибок.
	 * @param Exception $exception неперехваченное исключение
	 */
	public function displayException($exception)
	{
		if(YII_DEBUG)
		{
			echo '<h1>'.get_class($exception)."</h1>\n";
			echo '<p>'.$exception->getMessage().' ('.$exception->getFile().':'.$exception->getLine().')</p>';
			echo '<pre>'.$exception->getTraceAsString().'</pre>';
		}
		else
		{
			echo '<h1>'.get_class($exception)."</h1>\n";
			echo '<p>'.$exception->getMessage().'</p>';
		}
	}

	/**
	 * Инициализирует обработчики исключений и ошибок.
	 */
	protected function initSystemHandlers()
	{
		if(YII_ENABLE_EXCEPTION_HANDLER)
			set_exception_handler(array($this,'handleException'));
		if(YII_ENABLE_ERROR_HANDLER)
			set_error_handler(array($this,'handleError'),error_reporting());
	}

	/**
	 * Регистрирует компоненты ядра приложения.
	 * @see setComponents
	 */
	protected function registerCoreComponents()
	{
		$components=array(
			'coreMessages'=>array(
				'class'=>'CPhpMessageSource',
				'language'=>'en_us',
				'basePath'=>YII_PATH.DIRECTORY_SEPARATOR.'messages',
			),
			'db'=>array(
				'class'=>'CDbConnection',
			),
			'messages'=>array(
				'class'=>'CPhpMessageSource',
			),
			'errorHandler'=>array(
				'class'=>'CErrorHandler',
			),
			'securityManager'=>array(
				'class'=>'CSecurityManager',
			),
			'statePersister'=>array(
				'class'=>'CStatePersister',
			),
			'urlManager'=>array(
				'class'=>'CUrlManager',
			),
			'request'=>array(
				'class'=>'CHttpRequest',
			),
			'format'=>array(
				'class'=>'CFormatter',
			),
		);

		$this->setComponents($components);
	}
}
