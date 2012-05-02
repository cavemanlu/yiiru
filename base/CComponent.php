<?php
/**
 * Файл содержит базовые классы для комопнентно-ориентированного и событийно-управляемого программирования.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * CComponent - это базовый класс для всех компонентов.
 *
 * CComponent реализует протокол определения и использования свойств и событий.
 *
 * Свойство определяется методом-получателем (геттер, getter) и/или методом-установщиком (сеттер, setter).
 * Доступ к свойству можно получить как к обычному члену объекта.
 * Чтение или запись свойства приводит к вызову соответствующего геттера или сеттера, например,
 * <pre>
 * $a=$component->text;     // эквивалентно $a=$component->getText();
 * $component->text='abc';  // эквивалентно $component->setText('abc');
 * </pre>
 * Структура геттера и сеттера такова:
 * <pre>
 * // геттер, определяет читаемое свойство 'text'
 * public function getText() { ... }
 * // сеттер, определяет записываемое свойство 'text' со значением $value
 * public function setText($value) { ... }
 * </pre>
 *
 * Событие определяется методом с именем, начинающимся с 'on'.
 * Имя события - это имя метода. При появлении события, автоматически будут вызываться функции
 * (называемые обработчиками событий), присоединенные к событию.
 *
 * Событие может вызываться методом {@link raiseEvent}, в котором
 * присоединенные обработчики события будут вызваны в порядке присоединения к событию.
 * Обработчики события должны иметь следующую структуру:
 * <pre>
 * function eventHandler($event) { ... }
 * </pre>
 * где $event включает параметры, ассоциированные с событием.
 *
 * Для присоединения обработчика к событию предназначен метод {@link attachEventHandler}.
 * Вы также можете использовать следующий синтаксис:
 * <pre>
 * $component->onClick=$callback;    // или $component->onClick->add($callback);
 * </pre>
 * где $callback - валидный обратный вызов PHP. Ниже показаны примеры обратного вызова:
 * <pre>
 * 'handleOnClick'                   // handleOnClick() - глобальная функция
 * array($object,'handleOnClick')    // использование $object->handleOnClick()
 * array('Page','handleOnClick')     // использование Page::handleOnClick()
 * </pre>
 *
 * Для вызова события используется метод {@link raiseEvent}. 'on'-метод, определяющий событие
 * обычно пишется так:
 * <pre>
 * public function onClick($event)
 * {
 *     $this->raiseEvent('onClick',$event);
 * }
 * </pre>
 * где <code>$event</code> - экземпляр класса {@link CEvent} или его потомков.
 * Теперь можно вызывать событие вызовом 'on'-метода напрямую вместо {@link raiseEvent}.
 *
 * Имена свойств и событий регистронезависимы.
 *
 * CComponent поддерживает поведения. Поведение - это
 * экземпляр класса, реализующего интерфейс {@link IBehavior}, присоединенный к компоненту.
 * Методы поведения могут быть вызваны так, как если бы они принадлежали компоненту. К одному
 * компоненту может быть присоединено несколько поведений.
 *
 * Для присоединения поведения к компоненту вызовите метод {@link attachBehavior}, а для отсоединения
 * поведения от компонента - метод {@link detachBehavior}.
 *
 * Поведение может временно быть включено или выключено вызовом методов {@link enableBehavior}
 * или {@link disableBehavior} соответственно. Если поведение отключено, его методы не могут
 * быть вызваны из компонента.
 *
 * Начиная с версии 1.1.0, свойства поведения (и открытые члены и свойства,
 * определяемые геттерами и/или сеттерами) доступны из компонента, к которому присоединено поведение.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CComponent.php 3521 2011-12-29 22:10:57Z mdomba $
 * @package system.base
 * @since 1.0
 */
class CComponent
{
	private $_e;
	private $_m;

	/**
	 * Возвращает значение свойства, список обработчиков события или поведение по имени.
	 * Не вызывайте данный метод. Это "магический" метод PHP, который переопределяется,
	 * чтобы можно было использовать следующий синтаксис для чтения свойства или получения обработчика события:
	 * <pre>
	 * $value=$component->propertyName;
	 * $handlers=$component->eventName;
	 * </pre>
	 * @param string $name имя свойства или события
	 * @return mixed значение свойства, обработчики события, присоединенные к событию, или именованное поведение (с версии 1.0.2)
	 * @throws CException вызывается, если свойство или событие не определены
	 * @see __set
	 */
	public function __get($name)
	{
		$getter='get'.$name;
		if(method_exists($this,$getter))
			return $this->$getter();
		else if(strncasecmp($name,'on',2)===0 && method_exists($this,$name))
		{
			// duplicating getEventHandlers() here for performance
			$name=strtolower($name);
			if(!isset($this->_e[$name]))
				$this->_e[$name]=new CList;
			return $this->_e[$name];
		}
		else if(isset($this->_m[$name]))
			return $this->_m[$name];
		else if(is_array($this->_m))
		{
			foreach($this->_m as $object)
			{
				if($object->getEnabled() && (property_exists($object,$name) || $object->canGetProperty($name)))
					return $object->$name;
			}
		}
		throw new CException(Yii::t('yii','Property "{class}.{property}" is not defined.',
			array('{class}'=>get_class($this), '{property}'=>$name)));
	}

	/**
	 * Устанавливает значение свойства компонента.
	 * Не вызывайте данный метод. Это "магический" метод PHP, который переопределяется,
	 * чтобы можно было использовать следующий синтаксис для установки свойства или присоединения обработчика события:
	 * <pre>
	 * $this->propertyName=$value;
	 * $this->eventName=$callback;
	 * </pre>
	 * @param string $name имя свойства или события
	 * @param mixed $value значение свойства или обратный вызов
	 * @return mixed
	 * @throws CException вызывается, если свойство/событие не определены или свойство является свойством только для чтения.
	 * @see __get
	 */
	public function __set($name,$value)
	{
		$setter='set'.$name;
		if(method_exists($this,$setter))
			return $this->$setter($value);
		else if(strncasecmp($name,'on',2)===0 && method_exists($this,$name))
		{
			// duplicating getEventHandlers() here for performance
			$name=strtolower($name);
			if(!isset($this->_e[$name]))
				$this->_e[$name]=new CList;
			return $this->_e[$name]->add($value);
		}
		else if(is_array($this->_m))
		{
			foreach($this->_m as $object)
			{
				if($object->getEnabled() && (property_exists($object,$name) || $object->canSetProperty($name)))
					return $object->$name=$value;
			}
		}
		if(method_exists($this,'get'.$name))
			throw new CException(Yii::t('yii','Property "{class}.{property}" is read only.',
				array('{class}'=>get_class($this), '{property}'=>$name)));
		else
			throw new CException(Yii::t('yii','Property "{class}.{property}" is not defined.',
				array('{class}'=>get_class($this), '{property}'=>$name)));
	}

	/**
	 * Проверяет, нулевое (null) ли значение свойства.
	 * Не вызывайте данный метод. Это "магический" метод PHP, который переопределяется,
	 * чтобы можно было использовать фукцию isset() для определения, установлено свойство компонента или нет.
	 * @param string $name имя свойства или события
	 * @return boolean
	 */
	public function __isset($name)
	{
		$getter='get'.$name;
		if(method_exists($this,$getter))
			return $this->$getter()!==null;
		else if(strncasecmp($name,'on',2)===0 && method_exists($this,$name))
		{
			$name=strtolower($name);
			return isset($this->_e[$name]) && $this->_e[$name]->getCount();
		}
		else if(is_array($this->_m))
		{
 			if(isset($this->_m[$name]))
 				return true;
			foreach($this->_m as $object)
			{
				if($object->getEnabled() && (property_exists($object,$name) || $object->canGetProperty($name)))
					return $object->$name!==null;
			}
		}
		return false;
	}

	/**
	 * Устанавливает свойство компонента в null.
	 * Не вызывайте данный метод. Это "магический" метод PHP, который переопределяется,
	 * чтобы можно было использовать фукцию unset() для установки свойства компонента в null.
	 * @param string $name имя свойства или события
	 * @throws CException вызывается, если свойство является свойством только для чтения
	 * @return mixed
	 */
	public function __unset($name)
	{
		$setter='set'.$name;
		if(method_exists($this,$setter))
			$this->$setter(null);
		else if(strncasecmp($name,'on',2)===0 && method_exists($this,$name))
			unset($this->_e[strtolower($name)]);
		else if(is_array($this->_m))
		{
			if(isset($this->_m[$name]))
				$this->detachBehavior($name);
			else
			{
				foreach($this->_m as $object)
				{
					if($object->getEnabled())
					{
						if(property_exists($object,$name))
							return $object->$name=null;
						else if($object->canSetProperty($name))
							return $object->$setter(null);
					}
				}
			}
		}
		else if(method_exists($this,'get'.$name))
			throw new CException(Yii::t('yii','Property "{class}.{property}" is read only.',
				array('{class}'=>get_class($this), '{property}'=>$name)));
	}

	/**
	 * Вызывает именованный метод, не являющийся методом класса.
	 * Не вызывайте данный метод. Это "магический" метод PHP, который переопределяется
	 * для реализации функции поведения.
	 * @param string $name имя метода
	 * @param array $parameters параметры метода
	 * @return mixed значение, возвращаемое методом
	 */
	public function __call($name,$parameters)
	{
		if($this->_m!==null)
		{
			foreach($this->_m as $object)
			{
				if($object->getEnabled() && method_exists($object,$name))
					return call_user_func_array(array($object,$name),$parameters);
			}
		}
		if(class_exists('Closure', false) && $this->canGetProperty($name) && $this->$name instanceof Closure)
			return call_user_func_array($this->$name, $parameters);
		throw new CException(Yii::t('yii','{class} and its behaviors do not have a method or closure named "{name}".',
			array('{class}'=>get_class($this), '{name}'=>$name)));
	}

	/**
	 * Возвращает объект именованного поведения.
	 * Имя 'asa' означает 'as a'.
	 * @param string $behavior имя поведения
	 * @return IBehavior объект поведения; null, если поведение не существует
	 */
	public function asa($behavior)
	{
		return isset($this->_m[$behavior]) ? $this->_m[$behavior] : null;
	}

	/**
	 * Присоединяет список поведений к компоненту.
	 * Поведения индексированы по имени и должны быть экземплярами классов, реализующих интерфейс
	 * {@link IBehavior}, строкой, определяющих класс поведения или
	 * массивом со следующей структурой:
	 * <pre>
	 * array(
	 *     'class'=>'путь.к.BehaviorClass',
	 *     'property1'=>'value1',
	 *     'property2'=>'value2',
	 * )
	 * </pre>
	 * @param array $behaviors список присоединяемых к компоненту поведений
	 */
	public function attachBehaviors($behaviors)
	{
		foreach($behaviors as $name=>$behavior)
			$this->attachBehavior($name,$behavior);
	}

	/**
	 * Отсоединяет все поведения от компонента
	 */
	public function detachBehaviors()
	{
		if($this->_m!==null)
		{
			foreach($this->_m as $name=>$behavior)
				$this->detachBehavior($name);
			$this->_m=null;
		}
	}

	/**
	 * Присоединяет поведение к компоненту.
	 * Метод создает объект поведения на основе переданной конфигурации.
	 * После этого объект поведения инициализируется
	 * вызовом его метода {@link IBehavior::attach}.
	 * @param string $name имя поведения. Должен уникально идентифицировать данное поведение.
	 * @param mixed $behavior конфигурация поведения. Передается первым параметром
	 * в метод {@link YiiBase::createComponent} для создания объекта поведения.
	 * @return IBehavior объект поведения
	 */
	public function attachBehavior($name,$behavior)
	{
		if(!($behavior instanceof IBehavior))
			$behavior=Yii::createComponent($behavior);
		$behavior->setEnabled(true);
		$behavior->attach($this);
		return $this->_m[$name]=$behavior;
	}

	/**
	 * Отсоединяет поведение от компонента.
	 * Вызывается метод {@link IBehavior::detach} поведения.
	 * @param string $name имя поведения. Уникально идентифицирует поведение
	 * @return IBehavior отсоединенное поведение. Null, если поведение не существует
	 */
	public function detachBehavior($name)
	{
		if(isset($this->_m[$name]))
		{
			$this->_m[$name]->detach($this);
			$behavior=$this->_m[$name];
			unset($this->_m[$name]);
			return $behavior;
		}
	}

	/**
	 * Включает все поведения, присоединенные к компоненту
	 */
	public function enableBehaviors()
	{
		if($this->_m!==null)
		{
			foreach($this->_m as $behavior)
				$behavior->setEnabled(true);
		}
	}

	/**
	 * Отключает все поведения, присоединенные к компоненту
	 */
	public function disableBehaviors()
	{
		if($this->_m!==null)
		{
			foreach($this->_m as $behavior)
				$behavior->setEnabled(false);
		}
	}

	/**
	 * Включает присоединенное поведение.
	 * Поведение имеет действие только если включено.
	 * При первом присоединении поведение включено.
	 * @param string $name имя поведения. Уникально идентифицирует поведение
	 */
	public function enableBehavior($name)
	{
		if(isset($this->_m[$name]))
			$this->_m[$name]->setEnabled(true);
	}

	/**
	 * Откючает присоединенное поведение.
	 * Поведение имеет действие только если включено.
	 * @param string $name имя поведения. Уникально идентифицирует поведение
	 */
	public function disableBehavior($name)
	{
		if(isset($this->_m[$name]))
			$this->_m[$name]->setEnabled(false);
	}

	/**
	 * Показывает, определено ли свойство.
	 * Свойство определено, если для него в классе есть методы геттер и сеттер.
	 * Примечание: имена свойств регистронезависимы.
	 * @param string $name имя свойства
	 * @return boolean определено ли свойство
	 * @see canGetProperty
	 */
	public function hasProperty($name)
	{
		return method_exists($this,'get'.$name) || method_exists($this,'set'.$name);
	}

	/**
	 * Определяет, может ли свойство быть прочитано.
	 * Свойство читаемо, если класс имеет метод геттер для данного имени свойства.
	 * Примечание: имена свойств регистронезависимы.
	 * @param string $name имя свойства
	 * @return boolean может ли свойство быть прочитано
	 * @see canSetProperty
	 */
	public function canGetProperty($name)
	{
		return method_exists($this,'get'.$name);
	}

	/**
	 * Определяет, может ли свойство быть установлено (записываемое ли).
	 * Свойство записываемое, если класс имеет метод гсттер для данного имени свойства.
	 * Примечание: имена свойств регистронезависимы.
	 * @param string $name имя свойства
	 * @return boolean может ли свойство быть установлено (записываемое ли)
	 * @see canGetProperty
	 */
	public function canSetProperty($name)
	{
		return method_exists($this,'set'.$name);
	}

	/**
	 * Показывает, определено ли событие.
	 * Событие определено, если класс имеет метод с именем вида 'onXXX'.
	 * Примечание: имена событий регистронезависимы.
	 * @param string $name имя события
	 * @return boolean определено ли событие
	 */
	public function hasEvent($name)
	{
		return !strncasecmp($name,'on',2) && method_exists($this,$name);
	}

	/**
	 * Проверяет, есть ли у именованного события присоединенные обработчики.
	 * @param string $name имя события
	 * @return boolean есть ли у события присоединенные обработчики
	 */
	public function hasEventHandler($name)
	{
		$name=strtolower($name);
		return isset($this->_e[$name]) && $this->_e[$name]->getCount()>0;
	}

	/**
	 * Возвращает список присоединенных обработчиков для события.
	 * @param string $name имя события
	 * @return CList список присоединенных обработчиков для события
	 * @throws CException вызывается, если событие не определено
	 */
	public function getEventHandlers($name)
	{
		if($this->hasEvent($name))
		{
			$name=strtolower($name);
			if(!isset($this->_e[$name]))
				$this->_e[$name]=new CList;
			return $this->_e[$name];
		}
		else
			throw new CException(Yii::t('yii','Event "{class}.{event}" is not defined.',
				array('{class}'=>get_class($this), '{event}'=>$name)));
	}

	/**
	 * Присоединяет обработчик к событию.
	 *
	 * Обработчик события должен быть допустимым обратным вызовом PHP, т.е. строкой с именем глобальной функции
	 * или массивом, содержащим два элемента, где первый элемент - объект, а второй -
	 * имя метода объекта.
	 *
	 * Обработчик события должен иметь следующую структуру:
	 * <pre>
	 * function handlerName($event) {}
	 * </pre>
	 * где $event включает параметры, ассоциированные с событием.
	 *
	 * Это простой метод присоединения обработчика к событию.
	 * Он эквивалентен следующему коду:
	 * <pre>
	 * $component->getEventHandlers($eventName)->add($eventHandler);
	 * </pre>
	 *
	 * Используя метод {@link getEventHandlers} можно также определить последовательность
	 * выполнения нескольких обработчиков, присоединенных к одному событию. Например, код
	 * <pre>
	 * $component->getEventHandlers($eventName)->insertAt(0,$eventHandler);
	 * </pre>
	 * устанавливает, что обработчик будет выполняться первым.
	 *
	 * @param string $name имя события
	 * @param callback $handler обработчик события
	 * @throws CException вызывается, если событие не определено
	 * @see detachEventHandler
	 */
	public function attachEventHandler($name,$handler)
	{
		$this->getEventHandlers($name)->add($handler);
	}

	/**
	 * Отсоединяет существующий обработчик события.
	 * Метод противоположен методу {@link attachEventHandler}.
	 * @param string $name имя события
	 * @param callback $handler удаляемый обработчик события
	 * @return boolean успешен ли процесс отсоединения обработчика
	 * @see attachEventHandler
	 */
	public function detachEventHandler($name,$handler)
	{
		if($this->hasEventHandler($name))
			return $this->getEventHandlers($name)->remove($handler)!==false;
		else
			return false;
	}

	/**
	 * Выполняет (запускает) событие.
	 * Метод представляет собой наступление события. Он выполняет
	 * все присоединенные к событию обработчики.
	 * @param string $name имя события
	 * @param CEvent $event параметр события
	 * @throws CException вызывается, если событие неопределено или обработчик события является допустимым.
	 */
	public function raiseEvent($name,$event)
	{
		$name=strtolower($name);
		if(isset($this->_e[$name]))
		{
			foreach($this->_e[$name] as $handler)
			{
				if(is_string($handler))
					call_user_func($handler,$event);
				else if(is_callable($handler,true))
				{
					if(is_array($handler))
					{
						// an array: 0 - object, 1 - method name
						list($object,$method)=$handler;
						if(is_string($object))	// static method call
							call_user_func($handler,$event);
						else if(method_exists($object,$method))
							$object->$method($event);
						else
							throw new CException(Yii::t('yii','Event "{class}.{event}" is attached with an invalid handler "{handler}".',
								array('{class}'=>get_class($this), '{event}'=>$name, '{handler}'=>$handler[1])));
					}
					else // PHP 5.3: anonymous function
						call_user_func($handler,$event);
				}
				else
					throw new CException(Yii::t('yii','Event "{class}.{event}" is attached with an invalid handler "{handler}".',
						array('{class}'=>get_class($this), '{event}'=>$name, '{handler}'=>gettype($handler))));
				// stop further handling if param.handled is set true
				if(($event instanceof CEvent) && $event->handled)
					return;
			}
		}
		else if(YII_DEBUG && !$this->hasEvent($name))
			throw new CException(Yii::t('yii','Event "{class}.{event}" is not defined.',
				array('{class}'=>get_class($this), '{event}'=>$name)));
	}

	/**
	 * Выполняет выражение PHP или обратный вызов в контексте данного компонента.
	 *
	 * Допустимый обратный вызов PHP - это имя метода класса в формате
	 * array(ClassName/Object, MethodName) или анонимная функция (доступно только в PHP 5.3.0 или выше).
	 *
	 * Если используется обратный вызов PHP, структура соответствующих функции/метода должна быть такой:
	 * <pre>
	 * function foo($param1, $param2, ..., $component) { ... }
	 * </pre>
	 * где элементы массива второго параметра данного метода будут переданы в обратный вызов как параметры
	 * $param1, $param2, ..., а последний параметр является самим компонентом.
	 *
	 * Если используется выражение PHP, второй параметр будет "распакован" в переменные PHP,
	 * которые могут быть непосредственно использованы в выражении. За деталями обратитесь
	 * к функции {@link http://us.php.net/manual/en/function.extract.php PHP extract}.
	 * Объект компонента доступен в выражении посредством $this.
	 *
	 * @param mixed $_expression_ выражение PHP или обратный вызов PHP для выполнения
	 * @param array $_data_ дополнительные параметры, передаваемые в выражение/обратный вызов
	 * @return mixed результат выражения
	 * @since 1.1.0
	 */
	public function evaluateExpression($_expression_,$_data_=array())
	{
		if(is_string($_expression_))
		{
			extract($_data_);
			return eval('return '.$_expression_.';');
		}
		else
		{
			$_data_[]=$this;
			return call_user_func_array($_expression_, $_data_);
		}
	}
}


/**
 * CEvent - это базовый класс для всех классов событий.
 *
 * Инкапсулирует параметры, ассоциированные с событием.
 * Свойство {@link sender} объявляет, кто запускает событие.
 * Свойство {@link handled} показывает, обработано ли событие.
 * Если обработчик события устанавливает свойство {@link handled} в true, то последущие
 * еще не выполненные обработчики выполняться не будут.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CComponent.php 3521 2011-12-29 22:10:57Z mdomba $
 * @package system.base
 * @since 1.0
 */
class CEvent extends CComponent
{
	/**
	 * @var object отправитель события
	 */
	public $sender;
	/**
	 * @var boolean обработано ли событие. По умолчанию - false.
	 * Когда обработчик устанавливает даное свойство в true, последующие невыполненные обработчики не будут выполняться.
	 */
	public $handled=false;
	/**
	 * @var mixed дополнительные параметры события
	 * @since 1.1.7
	 */
	public $params;

	/**
	 * Конструктор.
	 * @param mixed $sender отправитель события
	 * @param mixed $params дополнительные параметры события
	 */
	public function __construct($sender=null,$params=null)
	{
		$this->sender=$sender;
		$this->params=$params;
	}
}


/**
 * CEnumerable - это базовый класс для всех перечисляемых типов.
 *
 * Для определения перечисляемого типа, отнаследуйте класс CEnumberable и определите строковые константы.
 * Каждая константа представляет собой перечисляемое значение.
 * Имя константы должно быть таким же, как и ее значение.
 * Например,
 * <pre>
 * class TextAlign extends CEnumerable
 * {
 *     const Left='Left';
 *     const Right='Right';
 * }
 * </pre>
 * Тогда можно использовать перечисляемые значения так - TextAlign::Left и TextAlign::Right.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CComponent.php 3521 2011-12-29 22:10:57Z mdomba $
 * @package system.base
 * @since 1.0
 */
class CEnumerable
{
}
