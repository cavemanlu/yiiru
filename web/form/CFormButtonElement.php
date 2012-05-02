<?php
/**
 * Файл класса CFormButtonElement.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CFormButtonElement представляет элемент кнопки формы.
 *
 * CFormButtonElement может представлять следующие типы кнопок на основе
 * свойства {@link type}:
 * <ul>
 * <li>htmlButton: обычная кнопка, генерируемая методом {@link CHtml::htmlButton};</li>
 * <li>htmlReset: кнопка сброса значений, генерируемая методом {@link CHtml::htmlButton};</li>
 * <li>htmlSubmit: кнопка отправки формы, генерируемая методом {@link CHtml::htmlButton};</li>
 * <li>submit: кнопка отправки формы, генерируемая методом {@link CHtml::submitButton};</li>
 * <li>button: обычная кнопка, генерируемая методом {@link CHtml::button};</li>
 * <li>image: кнопка-изображение, генерируемая методом {@link CHtml::imageButton};</li>
 * <li>reset: кнопка сброса значений, генерируемая методом {@link CHtml::resetButton};</li>
 * <li>link: кнопка-ссылка, генерируемая методом {@link CHtml::linkButton}.</li>
 * </ul>
 * Свойство {@link type} также может быть именем класса или псевдонимом пути к
 * классу. К этом случае кнопка генерируется виджетом установленного класса.
 * Примечание: виджет должен содержать свойство "name".
 *
 * Т.к. класс CFormElement является предком класса CFormButtonElement значение,
 * присвоенное несуществующему свойству, будет сохранено в свойстве
 * {@link attributes}, передаваемым в качестве значений HTML-атрибутов в метод
 * {@link CHtml}, генерирующий кнопку, или инициализирующим начальные значения
 * свойств виджета.
 *
 * @property string $on имена сценариев, разделенных запятыми
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CFormButtonElement.php 3426 2011-10-25 00:01:09Z alexander.makarow $
 * @package system.web.form
 * @since 1.1
 */
class CFormButtonElement extends CFormElement
{
	/**
	 * @var array встроенные типы кнопок (псевдоним => метод класса CHtml)
	 */
	public static $coreTypes=array(
		'htmlButton'=>'htmlButton',
		'htmlSubmit'=>'htmlButton',
		'htmlReset'=>'htmlButton',
		'button'=>'button',
		'submit'=>'submitButton',
		'reset'=>'resetButton',
		'image'=>'imageButton',
		'link'=>'linkButton',
	);

	/**
	 * @var string тип данной кнопки. Может быть именем класса, псевдонимом
	 * имени класса или псевдонимом типа кнопки (submit, button, image, reset,
	 * link, htmlButton, htmlSubmit, htmlReset)
	 */
	public $type;
	/**
	 * @var string имя данной кнопки
	 */
	public $name;
	/**
	 * @var string метка (заголовок) данной кнопки. Данное свойство
	 * игнорируется при использовании виджета для генерации кнопки
	 */
	public $label;

	private $_on;

	/**
	 * Возвращает значение, показывающее, в каких сценариях видима данная
	 * строка. Если значение пусто, то строка видима во всех сценариях. Иначе
	 * данная строка будет видима только в том случае, когда модель находится
	 * в сценарии, имя которого задано в данном свойстве. За подробной
	 * информацией о сценариях модели обратитесь к описанию свойства
	 * {@link CModel::scenario}
	 */
	public function getOn()
	{
		return $this->_on;
	}

	/**
	 * @param string $value имена сценариев, разделенных запятыми
	 */
	public function setOn($value)
	{
		$this->_on=preg_split('/[\s,]+/',$value,-1,PREG_SPLIT_NO_EMPTY);
	}

	/**
	 * Возвращает код кнопки
	 * @return string результат генерации
	 */
	public function render()
	{
		$attributes=$this->attributes;
		if(isset(self::$coreTypes[$this->type]))
		{
			$method=self::$coreTypes[$this->type];
			if($method==='linkButton')
			{
				if(!isset($attributes['params'][$this->name]))
					$attributes['params'][$this->name]=1;
			}
			else if($method==='htmlButton')
			{
				$attributes['type']=$this->type==='htmlSubmit' ? 'submit' : ($this->type==='htmlReset' ? 'reset' : 'button');
				$attributes['name']=$this->name;
			}
			else
				$attributes['name']=$this->name;
			if($method==='imageButton')
				return CHtml::imageButton(isset($attributes['src']) ? $attributes['src'] : '',$attributes);
			else
				return CHtml::$method($this->label,$attributes);
		}
		else
		{
			$attributes['name']=$this->name;
			ob_start();
			$this->getParent()->getOwner()->widget($this->type, $attributes);
			return ob_get_clean();
		}
	}

	/**
	 * Определяет видимость данного элемента. Данный элемент проверяет, что
	 * свойство {@link on} содержит имя сценария, в котором находится модель
	 * @return boolean видим ли данный элемент
	 */
	protected function evaluateVisible()
	{
		return empty($this->_on) || in_array($this->getParent()->getModel()->getScenario(),$this->_on);
	}
}
