<?php
/**
 * Файл класса CJuiSlider.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.jui.CJuiWidget');

/**
 * Виджет CJuiSlider отображает слайдер.
 *
 * Виджет CJuiSlider инкапсулирует плагин {@link http://jqueryui.com/slider/ JUI slider}.
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
 * Обратитесь к {@link http://api.jqueryui.com/slider/ API плагина JUI slider}
 * за списком возможных опций (пар имя-значение) и к
 * {@link http://jqueryui.com/slider/ основной странице плагина} за
 * описанием и примерами.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
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
		if(isset($this->htmlOptions['id']))
			$id=$this->htmlOptions['id'];
		else
			$this->htmlOptions['id']=$id;

		echo CHtml::tag($this->tagName,$this->htmlOptions,'');

		if($this->value!==null)
			$this->options['value']=$this->value;

		$options=CJavaScript::encode($this->options);
		Yii::app()->getClientScript()->registerScript(__CLASS__.'#'.$id,"jQuery('#{$id}').slider($options);");
	}
}