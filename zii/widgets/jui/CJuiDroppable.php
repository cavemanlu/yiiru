<?php
/**
 * Файл класса CJuiDroppable.
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.jui.CJuiWidget');

/**
 * Виджет CJuiDroppable отображает droppable-элемент (допускающий сброс другого элемента в пределах самого себя).
 *
 * Виджет CJuiDroppable инкапсулирует {@link http://jqueryui.com/demos/droppable/ плагин JUI droppable}.
 *
 * Для использования данного виджета нужно вставить в представление следующий код:
 * <pre>
 * $this->beginWidget('zii.widgets.jui.CJuiDroppable', array(
 *     // дополнительные javascript-опции для плагина droppable-элемента
 *     'options'=>array(
 *         'scope'=>'myScope',
 *     ),
 * ));
 *     echo 'Сожержимое droppable-элемента';
 *
 * $this->endWidget();
 *
 * </pre>
 *
 * Настройкой свойства {@link options} можно определить опции, передаваемые в плагин droppable-элемента.
 * Обратитесь к {@link http://jqueryui.com/demos/droppable/ документации о плагине JUI droppable}
 * за списком возможных опций (пар имя-значение).
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @package zii.widgets.jui
 * @since 1.1
 */
class CJuiDroppable extends CJuiWidget
{
	/**
	 * @var string имя тега контейнера droppable-элемента. По умолчанию - 'div'
	 */
	public $tagName='div';

	/**
	 * Генерирует открывающий тег droppable-элемента.
	 * Метод также регистрирует требуемый javascript-код
	 */
	public function init()
	{
		parent::init();
		$id=$this->getId();
		if (isset($this->htmlOptions['id']))
			$id = $this->htmlOptions['id'];
		else
			$this->htmlOptions['id']=$id;

		echo CHtml::openTag($this->tagName,$this->htmlOptions)."\n";
		
		$options=empty($this->options) ? '' : CJavaScript::encode($this->options);
		Yii::app()->getClientScript()->registerScript(__CLASS__.'#'.$id,"jQuery('#{$id}').droppable($options);");
	}

	/**
	 * Генерирует закрывающий тег droppable-элемента
	 */
	public function run(){
		echo CHtml::closeTag($this->tagName);
	}

}


