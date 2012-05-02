<?php
/**
 * Файл класса CJuiSlider.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.jui.CJuiWidget');

/**
 * Виджет CJuiSlider отображает слайдер.
 *
 * Виджет CJuiSlider инкапсулирует плагин {@link http://jqueryui.com/demos/slider/ JUI slider}.
 *
 * Для использования данного виджета нужно вставить в представление следующий код:
 * <pre>
 * $this->widget('zii.widgets.jui.CJuiSlider', array(
 *     'value'=>37,
 *     // дополнительные javascript-опции для плагина слайдера
 *     'options'=>array(
 *         'min'=>10,
 *         'max'=>50,
 *     ),
 *     'htmlOptions'=>array(
 *         'style'=>'height:20px;'
 *     ),
 * ));
 * </pre>
 *
 * Настройкой свойства {@link options} можно определить опции, передаваемые в плагин слайдера.
 * Обратитесь к {@link http://jqueryui.com/demos/slider/ документации о плагине JUI slider}
 * за списком возможных опций (пар имя-значение).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CJuiSlider.php 2799 2011-01-01 19:31:13Z qiang.xue $
 * @package zii.widgets.jui
 * @since 1.1
 */
class CJuiSlider extends CJuiWidget
{
	/**
	 * @var string имя элемента контейнера, содержащего слайдер. По умолчанию - 'div'
	 */
	public $tagName = 'div';
	/**
	 * @var integer значение слайдера, если имеется только 1 ползунок. Если имеется несколько ползунков, то определяет значение первого из них
	 */
	public $value;

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

		if($this->value!==null)
			$this->options['value']=$this->value;

		$options=empty($this->options) ? '' : CJavaScript::encode($this->options);
		Yii::app()->getClientScript()->registerScript(__CLASS__.'#'.$id,"jQuery('#{$id}').slider($options);");
	}
}