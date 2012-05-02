<?php
/**
 * Файл класса CFormElement.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CFormElement - это базовый класс для представления всех видов
 * элементов форм.
 *
 * Класс CFormElement реализует способ установки и получения произвольных
 * атрибутов.
 *
 * @property boolean $visible является ли данный элемент видимым и должен ли он
 * быть сгенерирован
 * @property mixed $parent прямой родитель данного элемента. Может быть
 * объектом либо класса {@link CForm} либо класса {@link CBaseController}
 * (контроллер или виджет)
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CFormElement.php 3426 2011-10-25 00:01:09Z alexander.makarow $
 * @package system.web.form
 * @since 1.1
 */
abstract class CFormElement extends CComponent
{
	/**
	 * @var array список атрибутов (имя => значение) для HTML-элемента,
	 * представляемого данным объектом
	 */
	public $attributes=array();

	private $_parent;
	private $_visible;

	/**
	 * Генерирует данный элемент
	 * @return string результат генерации
	 */
	abstract function render();

	/**
	 * Конструктор
	 * @param mixed $config конфигурация данного элемента
	 * @param mixed $parent прямой родитель данного элемента
	 * @see configure
	 */
	public function __construct($config,$parent)
	{
		$this->configure($config);
		$this->_parent=$parent;
	}

	/**
	 * Конвертирует объект в строку. Данный метод является "магическим"
	 * PHP-методом. Реализация по умолчанию просто вызывает метод
	 * {@link render} и возвращает результат генерации
	 * @return string строка, представляющая данный объект
	 */
	public function __toString()
	{
		return $this->render();
	}

	/**
	 * Возвращает значение свойства или атрибута. Данный метод является
	 * "магическим" PHP-методом, переопределенным для возможности использования
	 * следующего синтаксиса для чтения свойства или атрибута:
	 * <pre>
	 * $value=$element->propertyName;
	 * $value=$element->attributeName;
	 * </pre>
	 * @param string $name имя свойства или атрибута
	 * @return mixed значение свойства или атрибута
	 * @throws CException вызывается, если свойство или атрибут не определены
	 * @see __set
	 */
	public function __get($name)
	{
		$getter='get'.$name;
		if(method_exists($this,$getter))
			return $this->$getter();
		else if(isset($this->attributes[$name]))
			return $this->attributes[$name];
		else
			throw new CException(Yii::t('yii','Property "{class}.{property}" is not defined.',
				array('{class}'=>get_class($this), '{property}'=>$name)));
	}

	/**
	 * Устанавливает значение свойства или атрибута. Данный метод является
	 * "магическим" PHP-методом, переопределенным для возможности использования
	 * следующего синтаксиса для установки свойства или атрибута:
	 * <pre>
	 * $this->propertyName=$value;
	 * $this->attributeName=$value;
	 * </pre>
	 * @param string $name имя свойства или атрибута
	 * @param mixed $value значение свойства или атрибута
	 * @see __get
	 */
	public function __set($name,$value)
	{
		$setter='set'.$name;
		if(method_exists($this,$setter))
			$this->$setter($value);
		else
			$this->attributes[$name]=$value;
	}

	/**
	 * Конфигурирует данный объект начальными значениями свойств
	 * @param mixed $config конфигурация для данного объекта. Может быть
	 * массивом, представляющим имена свойств и их начальные значения, либо
	 * строкой, представляющей имя файла PHP-скрипта, возвращающего массив
	 * конфигурации
	 */
	public function configure($config)
	{
		if(is_string($config))
			$config=require(Yii::getPathOfAlias($config).'.php');
		if(is_array($config))
		{
			foreach($config as $name=>$value)
				$this->$name=$value;
		}
	}

	/**
	 * Возвращает значение, показывающее, является ли данный элемент видимым и
	 * должен ли он быть сгенерирован. Данный метод вызывает метод
	 * {@link evaluateVisible} для определения видимости элемента
	 * @return boolean является ли данный элемент видимым и должен ли он быть
	 * сгенерирован
	 */
	public function getVisible()
	{
		if($this->_visible===null)
			$this->_visible=$this->evaluateVisible();
		return $this->_visible;
	}

	/**
	 * Устанавливает значение, показывающее, является ли данный элемент видимым
	 * и должен ли он быть сгенерирован
	 * @param boolean $value является ли данный элемент видимым и должен ли он
	 * быть сгенерирован
	 */
	public function setVisible($value)
	{
		$this->_visible=$value;
	}

	/**
	 * Возвращает прямого родителя данного элемента
	 * @return mixed прямой родитель данного элемента. Может быть объектом либо
	 * класса {@link CForm} либо класса {@link CBaseController} (контроллер или
	 * виджет)
	 */
	public function getParent()
	{
		return $this->_parent;
	}

	/**
	 * Определяет видимость данного элемента. Классы-потомки должны
	 * переопределять данный метод для реализации актуального алгоритма
	 * определения видимости элемента
	 * @return boolean видим ли данный элемент. По умолчанию - true
	 */
	protected function evaluateVisible()
	{
		return true;
	}
}
