<?php
/**
 * Файл класса CJuiResizable.
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.jui.CJuiWidget');

/**
 * Виджет CJuiResizable отображает блок с изменяемыми размерами.
 *
 * Виджет CJuiResizable инкапсулирует {@link http://jqueryui.com/demos/resizable/ плагин JUI resizable}.
 *
 * Для использования данного виджета нужно вставить в представление следующий код:
 * <pre>
 * $this->beginWidget('zii.widgets.jui.CJuiResizable', array(
 *     // дополнительные javascript-опции для плагина блока с изменяемыми размерами
 *     'options'=>array(
 *         'minHeight'=>'150',
 *     ),
 * ));
 *     echo 'Ваше содержимое с изменяемыми размерами';
 *
 * $this->endWidget();
 *
 * </pre>
 *
 * Настройкой свойства {@link options} можно определить опции, передаваемые в плагин табов.
 * Обратитесь к {@link http://jqueryui.com/demos/resizable/ документации о плагине JUI resizable}
 * за списком возможных опций (пар имя-значение).
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @version $Id: CJuiResizable.php 2799 2011-01-01 19:31:13Z qiang.xue $
 * @package zii.widgets.jui
 * @since 1.1
 */
class CJuiResizable extends CJuiWidget
{
	/**
	 * @var string имя элемента блока с изменяемыми размерами. По умолчанию - 'div'
	 */
	public $tagName='div';

	/**
	 * Генерирует открывающий тег блок с изменяемыми размерами.
	 * Данный метод также регистрирует требуемый javascript-код
	 */
	public function init()
	{
		parent::init();
		$id=$this->getId();
		if (isset($this->htmlOptions['id']))
			$id = $this->htmlOptions['id'];
		else
			$this->htmlOptions['id']=$id;
		
		$options=empty($this->options) ? '' : CJavaScript::encode($this->options);
		
		Yii::app()->getClientScript()->registerScript(__CLASS__.'#'.$id,"jQuery('#{$id}').resizable($options);");

		echo CHtml::openTag($this->tagName,$this->htmlOptions)."\n";
	}

	/**
	 * Генерирует закрывающий тег блока с изменяемыми размерами
	 */
	public function run(){
		echo CHtml::closeTag($this->tagName);
	}

}


