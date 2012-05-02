<?php
/**
 * Файл класса CModule.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * CModule - это базовый класс для классов модулей и приложения.
 *
 * В основном CModule управляет компонентами приложения и подмодулями.
 *
 * @property string $id идентификатор модуля
 * @property string $basePath корневая директория модуля. По умолчанию -
 * директория, содержащая класс модуля
 * @property CAttributeCollection $params пользовательские параметры
 * @property string $modulePath директория, содержащая модули приложения. По
 * умолчанию - поддиректория 'modules' директории {@link basePath}
 * @property CModule $parentModule модуль-родитель. Null, если модуль не имеет
 * родителя
 * @property array $modules конфигурация текущих установленных модулей
 * (идентификатор модуля => конфигурация)
 * @property array $components список компонентов приложения (индексированные
 * по их идентификаторам)
 * @property array $import список импортируемых псевдонимов
 * @property array $aliases список определяемых псевдонимов. Ключи массива -
 * это корневые псевдонимы, а значения массива - пути или псевдонимы,
 * соответствующие корневым псевдонимам. Например,
 * <pre>
 * array(
 *    'models'=>'application.models',              // существующий псевдоним
 *    'extensions'=>'application.extensions',      // существующий псевдоним
 *    'backend'=>dirname(__FILE__).'/../backend',  // директория
 * )
 * </pre>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CModule.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.base
 */
abstract class CModule extends CComponent
{
	/**
	 * @var array идентификаторы компонентов приложения, которые должны быть предзагружены.
	 */
	public $preload=array();
	/**
	 * @var array поведения, которые должны быть присоединены к модулю.
	 * Поведения будут присоединены к модулю при вызове метода {@link init}.
	 * Обратитесь к {@link CModel::behaviors}, чтобы узнать, как определить значение данного свойства.
	 */
	public $behaviors=array();

	private $_id;
	private $_parentModule;
	private $_basePath;
	private $_modulePath;
	private $_params;
	private $_modules=array();
	private $_moduleConfig=array();
	private $_components=array();
	private $_componentConfig=array();


	/**
	 * Конструктор.
	 * @param string $id идентификатор данного модуля
	 * @param CModule $parent модуль-родитель (если есть)
	 * @param mixed $config конфигурация модуля. Либо массив либо путь к файлу
	 * PHP, возвращающему массив конфигурации.
	 */
	public function __construct($id,$parent,$config=null)
	{
		$this->_id=$id;
		$this->_parentModule=$parent;

		// set basePath at early as possible to avoid trouble
		if(is_string($config))
			$config=require($config);
		if(isset($config['basePath']))
		{
			$this->setBasePath($config['basePath']);
			unset($config['basePath']);
		}
		Yii::setPathOfAlias($id,$this->getBasePath());

		$this->preinit();

		$this->configure($config);
		$this->attachBehaviors($this->behaviors);
		$this->preloadComponents();

		$this->init();
	}

	/**
	 * "Магический" геттер.
	 * Метод переопределяет родительский метод для поддержки доступа к компонентам приложения
	 * как к свойствам модуля.
	 * @param string $name компонент приложения или имя свойства
	 * @return mixed именованное значение свойства
	 */
	public function __get($name)
	{
		if($this->hasComponent($name))
			return $this->getComponent($name);
		else
			return parent::__get($name);
	}

	/**
	 * Проверяет, существует ли значение свойства.
	 * Метод переопределяет родительскую реализацию проверкой,
	 * загружен ли именованный компонент приложения.
	 * @param string $name имя свойства или имя события
	 * @return boolean существует ли значение свойства (null ли оно)
	 */
	public function __isset($name)
	{
		if($this->hasComponent($name))
			return $this->getComponent($name)!==null;
		else
			return parent::__isset($name);
	}

	/**
	 * Возвращает идентификатор модуля
	 * @return string идентификатор модуля
	 */
	public function getId()
	{
		return $this->_id;
	}

	/**
	 * Устанавливает идентификатор модуля
	 * @param string $id идентификатор модуля
	 */
	public function setId($id)
	{
		$this->_id=$id;
	}

	/**
	 * Возвращает корневую директорию модуля
	 * @return string корневая директория модуля. По умолчанию - директория, содержащая класс модуля
	 */
	public function getBasePath()
	{
		if($this->_basePath===null)
		{
			$class=new ReflectionClass(get_class($this));
			$this->_basePath=dirname($class->getFileName());
		}
		return $this->_basePath;
	}

	/**
	 * Устанавливает корневую директорию модуля.
	 * Метод может быть вызван только в начале конструктора.
	 * @param string $path корневая директория модуля
	 * @throws CException вызывается, если директория не существует
	 */
	public function setBasePath($path)
	{
		if(($this->_basePath=realpath($path))===false || !is_dir($this->_basePath))
			throw new CException(Yii::t('yii','Base path "{path}" is not a valid directory.',
				array('{path}'=>$path)));
	}

	/**
	 * Возвращает пользовательские параметры
	 * @return CAttributeCollection список пользовательских параметров
	 */
	public function getParams()
	{
		if($this->_params!==null)
			return $this->_params;
		else
		{
			$this->_params=new CAttributeCollection;
			$this->_params->caseSensitive=true;
			return $this->_params;
		}
	}

	/**
	 * Устанавливает пользовательские параметры
	 * @param array $value пользовательские параметры. Должны быть в виде пар имя-значение
	 */
	public function setParams($value)
	{
		$params=$this->getParams();
		foreach($value as $k=>$v)
			$params->add($k,$v);
	}

	/**
	 * Возвращает директорию, хранящую модули приложения
	 * @return string директория, хранящая модули приложения. По умолчанию -
	 * поддиректория 'modules' директории {@link basePath}
	 */
	public function getModulePath()
	{
		if($this->_modulePath!==null)
			return $this->_modulePath;
		else
			return $this->_modulePath=$this->getBasePath().DIRECTORY_SEPARATOR.'modules';
	}

	/**
	 * Устанавливает директорию, хранящую модули приложения
	 * @param string $value директория, хранящая модули приложения
	 * @throws CException вызывается, если директория не существует
	 */
	public function setModulePath($value)
	{
		if(($this->_modulePath=realpath($value))===false || !is_dir($this->_modulePath))
			throw new CException(Yii::t('yii','The module path "{path}" is not a valid directory.',
				array('{path}'=>$value)));
	}

	/**
	 * Устанавливает псевдонимы, используемые в модуле.
	 * @param array $aliases список импортируемых псевдонимов
	 */
	public function setImport($aliases)
	{
		foreach($aliases as $alias)
			Yii::import($alias);
	}

	/**
	 * Определяет корневые псевдонимы.
	 * @param array $mappings список определяемых псевдонимов. Ключи массива - это корневые псевдонимы,
	 * а значения массива - пути или псевдонимы, соответствующие корневым псевдонимам.
	 * Например,
	 * <pre>
	 * array(
	 *    'models'=>'application.models',              // существующий псевдоним
	 *    'extensions'=>'application.extensions',      // существующий псевдоним
	 *    'backend'=>dirname(__FILE__).'/../backend',  // директория
	 * )
	 * </pre>
	 */
	public function setAliases($mappings)
	{
		foreach($mappings as $name=>$alias)
		{
			if(($path=Yii::getPathOfAlias($alias))!==false)
				Yii::setPathOfAlias($name,$path);
			else
				Yii::setPathOfAlias($name,$alias);
		}
	}

	/**
	 * Возвращает родительский модуль
	 * @return CModule родительский модуль. Null, если модуль не имеет родителя
	 */
	public function getParentModule()
	{
		return $this->_parentModule;
	}

	/**
	 * Получает именованный модуль приложения.
	 * Модуль должен быть объявлен в свойстве {@link modules}. Будет создан новый экземпляр
	 * при первом вызове метода с переданным идентификатором.
	 * @param string $id идентификатор модуля приложения (регистрозависимe)
	 * @return CModule экземпляр модуля; null, если модуль отклчен или не существует.
	 */
	public function getModule($id)
	{
		if(isset($this->_modules[$id]) || array_key_exists($id,$this->_modules))
			return $this->_modules[$id];
		else if(isset($this->_moduleConfig[$id]))
		{
			$config=$this->_moduleConfig[$id];
			if(!isset($config['enabled']) || $config['enabled'])
			{
				Yii::trace("Loading \"$id\" module",'system.base.CModule');
				$class=$config['class'];
				unset($config['class'], $config['enabled']);
				if($this===Yii::app())
					$module=Yii::createComponent($class,$id,null,$config);
				else
					$module=Yii::createComponent($class,$this->getId().'/'.$id,$this,$config);
				return $this->_modules[$id]=$module;
			}
		}
	}

	/**
	 * Возвращает значение, показывающее, установлен ли определенный модуль.
	 * @param string $id идентификатор модуля
	 * @return boolean установлен ли определенный модуль
	 * @since 1.1.2
	 */
	public function hasModule($id)
	{
		return isset($this->_moduleConfig[$id]) || isset($this->_modules[$id]);
	}

	/**
	 * Возвращает конфигурацию установленных модулей
	 * @return array конфигурация установленных модулей (идентификатор модуля => конфигурация)
	 */
	public function getModules()
	{
		return $this->_moduleConfig;
	}

	/**
	 * Конфигурирует подмодули модуля.
	 *
	 * Вызовите данный метод для объявления подмодулей и их конфигурирования с начальными значениями свойств.
	 * Параметр должен быть массивом конфигураций модулей. Каждый элемент массива представляет один модуль,
	 * который (элемент) может быть либо строкой - идентификатором модуля либо парой идентификатор-конфигурация, представляющей
	 * модуль с определенным идентификатором и начальными значениями свойств.
	 *
	 * Например, следующий массив объявляет два модуля:
	 * <pre>
	 * array(
	 *     'admin',                // идентификатор отдельного модуля
	 *     'payment'=>array(       // пара идентификатор-конфигурация
	 *         'server'=>'paymentserver.com',
	 *     ),
	 * )
	 * </pre>
	 *
	 * По умолчанию класс модуля определяется использованием выражения <code>ucfirst($moduleID).'Module'</code>.
	 * А файл класса находится в директории <code>modules/$moduleID</code>.
	 * Вы можете переопределить это поведение явным указанием опции 'class' в конфигурации.
	 *
	 * Вы можете также включать или выключать модуль, определяя опцию 'enabled' в конфигурации.
	 *
	 * @param array $modules конфигурации модулей.
	 */
	public function setModules($modules)
	{
		foreach($modules as $id=>$module)
		{
			if(is_int($id))
			{
				$id=$module;
				$module=array();
			}
			if(!isset($module['class']))
			{
				Yii::setPathOfAlias($id,$this->getModulePath().DIRECTORY_SEPARATOR.$id);
				$module['class']=$id.'.'.ucfirst($id).'Module';
			}

			if(isset($this->_moduleConfig[$id]))
				$this->_moduleConfig[$id]=CMap::mergeArray($this->_moduleConfig[$id],$module);
			else
				$this->_moduleConfig[$id]=$module;
		}
	}

	/**
	 * Проверяет, существует ли именованный компонент приложения
	 * @param string $id идентификатор компонента приложения
	 * @return boolean существует ли именованный компонент приложения (включая и загруженные и отключенные модули)
	 */
	public function hasComponent($id)
	{
		return isset($this->_components[$id]) || isset($this->_componentConfig[$id]);
	}

	/**
	 * Получает именованный компонент приложения.
	 * @param string $id идентификатор компонента приложения (регистрозависим)
	 * @param boolean $createIfNull создавать ли компонент, если он еще не существует
	 * @return IApplicationComponent экземпляр компонента приложения; null, если компонент приложения отключен или не существует
	 * @see hasComponent
	 */
	public function getComponent($id,$createIfNull=true)
	{
		if(isset($this->_components[$id]))
			return $this->_components[$id];
		else if(isset($this->_componentConfig[$id]) && $createIfNull)
		{
			$config=$this->_componentConfig[$id];
			if(!isset($config['enabled']) || $config['enabled'])
			{
				Yii::trace("Loading \"$id\" application component",'system.CModule');
				unset($config['enabled']);
				$component=Yii::createComponent($config);
				$component->init();
				return $this->_components[$id]=$component;
			}
		}
	}

	/**
	 * Передает компонент под управление модуля.
	 * Если компонент не инициализирован, это будет сделано (вызовом его метода {@link CApplicationComponent::init() init()}).
	 * @param string $id идентификатор компонента
	 * @param IApplicationComponent $component добавляемый в модуль компонент.
	 * Если равен null, то компонент с переданным идентификатором будет удален из модуля
	 */
	public function setComponent($id,$component)
	{
		if($component===null)
			unset($this->_components[$id]);
		else
		{
			$this->_components[$id]=$component;
			if(!$component->getIsInitialized())
				$component->init();
		}
	}

	/**
	 * Возвращает компоненты приложения.
	 * @param boolean $loadedOnly возвращать только загруженные компоненты. Если установлено в значение false,
	 * то будут возвращены все компоненты, определенные в конфигурации, загружены они или нет.
	 * Загруженные компоненты будут возвращены в виде объектов, а незагруженные в виде массивов конфигураций.
	 * Параметр доступен с версии 1.1.3.
	 * @return array компоненты приложения (индексированные по их идентификаторам)
	 */
	public function getComponents($loadedOnly=true)
	{
		if($loadedOnly)
			return $this->_components;
		else
			return array_merge($this->_componentConfig, $this->_components);
	}

	/**
	 * Устанавливает компоненты приложения.
	 *
	 * Когда конфигурация используется для определения компонента, она должна содержать начальные
	 * значения свойств компонента (пары имя-значение). В дополнение, компонент 
	 * может быть включен (по умолчанию) или выключен установкой значения 'enabled'
	 * в конфигурации.
	 *
	 * Если конфигурация определена с идентификатором, совпадающим с существующим компонентом или конфигурацией,
	 * существующие компонент или конфигурация будут заменены без уведомления.
	 *
	 * Следующая конфигурация предназначена для двух компонентов:
	 * <pre>
	 * array(
	 *     'db'=>array(
	 *         'class'=>'CDbConnection',
	 *         'connectionString'=>'sqlite:path/to/file.db',
	 *     ),
	 *     'cache'=>array(
	 *         'class'=>'CDbCache',
	 *         'connectionID'=>'db',
	 *         'enabled'=>!YII_DEBUG,  // включение кэшрование в безотладочном режиме
	 *     ),
	 * )
	 * </pre>
	 *
	 * @param array $components компоненты приложения (идентификатор=>конфигурация компонента или экземпляры компонентов)
	 * @param boolean $merge сливать ли новую конфигурацию компонента с уже существующей.
	 * По умолчанию - true, т.е., ранее зарегистрированная конфигурация компонента с таким же идентификатором
	 * будет слита с новой конфигурацией. Если значение равно false, существующая конфигурация будет полностью заменена
	 */
	public function setComponents($components,$merge=true)
	{
		foreach($components as $id=>$component)
		{
			if($component instanceof IApplicationComponent)
				$this->setComponent($id,$component);
			else if(isset($this->_componentConfig[$id]) && $merge)
				$this->_componentConfig[$id]=CMap::mergeArray($this->_componentConfig[$id],$component);
			else
				$this->_componentConfig[$id]=$component;
		}
	}

	/**
	 * Настраивает модуль определенной конфигурацией.
	 * @param array $config массив конфигурации
	 */
	public function configure($config)
	{
		if(is_array($config))
		{
			foreach($config as $key=>$value)
				$this->$key=$value;
		}
	}

	/**
	 * Загружает статические компоненты приложения.
	 */
	protected function preloadComponents()
	{
		foreach($this->preload as $id)
			$this->getComponent($id);
	}

	/**
	 * Преинициализирует модуль.
	 * Метод вызывается в начале конструктора модуля.
	 * Вы можете переопределить данный метод для выполнения некоторой предынициализационной работы.
	 * Помните, что к данному моменту модуль еще не сконфигурирован.
	 * @see init
	 */
	protected function preinit()
	{
	}

	/**
	 * Инициализирует модуль.
	 * Метод вызывается в конце конструктора модуля.
	 * Помните, что к данному моменту модуль сконфигурирован, поведения присоединены и компоненты приложения зарегистрированы.
	 * @see preinit
	 */
	protected function init()
	{
	}
}
