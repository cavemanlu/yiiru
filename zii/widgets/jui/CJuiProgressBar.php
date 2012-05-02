<?php
/**
 * Файл класса CJuiProgressBar.
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.jui.CJuiWidget');

/**
 * Виждет CJuiProgressBar отображает полосу прогресса (прогресс-бар).
 *
 * Виджет CJuiProgressBar инкапсулирует {@link http://jqueryui.com/demos/progressbar/ плагин JUI
 * Progressbar}.
 *
 * Для использования данного виджета нужно вставить в представление следующий код:
 * <pre>
 * $this->widget('zii.widgets.jui.CJuiProgressBar', array(
 *     'value'=>75,
 *     // дополнительные javascript-опции для плагина прогресс-бара
 *     'options'=>array(
 *         'change'=>'js:function(event, ui) {...}',
 *     ),
 *     'htmlOptions'=>array(
 *         'style'=>'height:20px;'
 *     ),
 * ));
 * </pre>
 *
 * Настройкой свойства {@link options} можно определить опции, передаваемые в плагин прогресс-бара.
 * Обратитесь к {@link http://jqueryui.com/demos/progressbar/ документации о плагине JUI progressbar}
 * за списком возможных опций (пар имя-значение).
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @version $Id: CJuiProgressBar.php 2799 2011-01-01 19:31:13Z qiang.xue $
 * @package zii.widgets.jui
 * @since 1.1
 */
class CJuiProgressBar extends CJuiWidget
{
	/**
	 * @var string имя элемента контейнера прогресс-бара. По умолчанию - 'div'
	 */
	public $tagName = 'div';
	/**
	 * @var integer значение прогресса в процентах. Должно лежать в пределах от 0 до 100. По умолчанию - 0
	 */
	public $value = 0;

	/**
	 * Выполняет виджет.
	 * Метод регистрирует требуемый javascript-код и генерирует HTML-код
	 */
	public function run()
	{
		$id=$this->getId();
		if (isset($this->htmlOptions['id']))
			$id = $this->htmlOptions['id'];
		else
			$this->htmlOptions['id']=$id;

		echo CHtml::openTag($this->tagName,$this->htmlOptions);
		echo CHtml::closeTag($this->tagName);

		$this->options['value']=$this->value;
		$options=CJavaScript::encode($this->options);
		Yii::app()->getClientScript()->registerScript(__CLASS__.'#'.$id,"jQuery('#{$id}').progressbar($options);");
	}

}