<?php
/**
 * Файл класса CJuiDraggable.
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.jui.CJuiWidget');

/**
 * Виджет CJuiDraggable отображает перетаскиваемый элемент.
 *
 * Виджет CJuiDraggable инкапсулирует {@link http://jqueryui.com/demos/draggable/ плагин JUI draggable}.
 *
 * Для использования данного виджета нужно вставить в представление следующий код:
 * <pre>
 * $this->beginWidget('zii.widgets.jui.CJuiDraggable', array(
 *     // дополнительные javascript-опции для плагина перетаскиваемого элемента
 *     'options'=>array(
 *         'scope'=>'myScope',
 *     ),
 * ));
 *     echo 'Содержимое перетаскиваемого элемента';
 *     
 * $this->endWidget();
 * 
 * </pre>
 *
 * Настройкой свойства {@link options} можно определить опции, передаваемые в плагин перетаскиваемого элемента.
 * Обратитесь к {@link http://jqueryui.com/demos/draggable/ документации о плагине JUI draggable}
 * за списком возможных опций (пар имя-значение).
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @package zii.widgets.jui
 * @since 1.1
 */
class CJuiDraggable extends CJuiWidget
{
	/**
	 * @var string имя тега контейнера перетаскиваемого элемента. По умолчанию - 'div'
	 */
	public $tagName='div';

	/**
	 * Генерирует открывающий тег перетаскиваемого элемента.
	 * Метод также регистрирует требуемый javascript-код
	 */
	public function init(){
		parent::init();
		
		$id=$this->getId();
		if (isset($this->htmlOptions['id']))
			$id = $this->htmlOptions['id'];
		else
			$this->htmlOptions['id']=$id;
		
		$options=empty($this->options) ? '' : CJavaScript::encode($this->options);
		Yii::app()->getClientScript()->registerScript(__CLASS__.'#'.$id,"jQuery('#{$id}').draggable($options);");

		echo CHtml::openTag($this->tagName,$this->htmlOptions)."\n";
	}

	/**
	 * Генерирует закрывающий тег перетаскиваемого элемента
	 */
	public function run(){
		echo CHtml::closeTag($this->tagName);
	}
	
}


