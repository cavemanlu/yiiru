<?php
/**
 * Файл класса CFormElementCollection.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CFormElementCollection реализует коллекцию для хранения элементов
 * формы.
 *
 * Т.к. класс CFormElementCollection наследует {@link CMap}, он может
 * использоваться как ассоциативный массив. Например,
 * <pre>
 * $element=$collection['username'];
 * $collection['username']=array('type'=>'text', 'maxlength'=>128);
 * $collection['password']=new CFormInputElement(array('type'=>'password'),$form);
 * $collection[]='some string';
 * </pre>
 *
 * CFormElementCollection может хранить три типа значений: массив конфигурации,
 * объект класса {@link CFormElement} или строку, как показано в примере выше.
 * Внутренним механизмом эти значения преобразуются в объекты класса
 * {@link CFormElement}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CFormElementCollection.php 3054 2011-03-12 21:30:21Z qiang.xue $
 * @package system.web.form
 * @since 1.1
 */
class CFormElementCollection extends CMap
{
	private $_form;
	private $_forButtons;

	/**
	 * Конструктор
	 * @param CForm $form объект формы, содержащей данную коллекцию
	 * @param boolean $forButtons используется ли данная коллекция для хранения
	 * кнопок
	 */
	public function __construct($form,$forButtons=false)
	{
		parent::__construct();
		$this->_form=$form;
		$this->_forButtons=$forButtons;
	}

	/**
	 * Добавляет элемент в коллекцию. Данный метод переопределяет родительскую
	 * реализацию, чтобы в коллекцию сохранялась конфигурация только в виде
	 * массивов, строк или объектов класса {@link CFormElement}
	 * @param mixed $key ключ
	 * @param mixed $value значение
	 * @throws CException вызывается, если значение невалидно
	 */
	public function add($key,$value)
	{
		if(is_array($value))
		{
			if(is_string($key))
				$value['name']=$key;

			if($this->_forButtons)
			{
				$class=$this->_form->buttonElementClass;
				$element=new $class($value,$this->_form);
			}
			else
			{
				if(!isset($value['type']))
					$value['type']='text';
				if($value['type']==='string')
				{
					unset($value['type'],$value['name']);
					$element=new CFormStringElement($value,$this->_form);
				}
				else if(!strcasecmp(substr($value['type'],-4),'form'))	// a form
				{
					$class=$value['type']==='form' ? get_class($this->_form) : Yii::import($value['type']);
					$element=new $class($value,null,$this->_form);
				}
				else
				{
					$class=$this->_form->inputElementClass;
					$element=new $class($value,$this->_form);
				}
			}
		}
		else if($value instanceof CFormElement)
		{
			if(property_exists($value,'name') && is_string($key))
				$value->name=$key;
			$element=$value;
		}
		else
			$element=new CFormStringElement(array('content'=>$value),$this->_form);
		parent::add($key,$element);
		$this->_form->addedElement($key,$element,$this->_forButtons);
	}

	/**
	 * Удаляет определенный элемент по ключу
	 * @param string $key имя удаляемого из коллекции элемента
	 */
	public function remove($key)
	{
		if(($item=parent::remove($key))!==null)
			$this->_form->removedElement($key,$item,$this->_forButtons);
	}
}
