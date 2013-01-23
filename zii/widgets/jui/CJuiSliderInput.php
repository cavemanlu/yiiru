<?php
/**
 * Файл класса CJuiSliderInput.
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.jui.CJuiInputWidget');

/**
 * Виджет CJuiSliderInput отображает слайдер. Может использоваться в формах It can be used in forms and post its value.
 *
 * Виджет CJuiSlider инкапсулирует плагин {@link http://jqueryui.com/slider/ JUI slider}.
 *
 * Для использования данного виджета нужно вставить в представление следующий код:
 * <pre>
 * $this->widget('zii.widgets.jui.CJuiSliderInput', array(
 *     'name'=>'rate',
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
 * Виджет также может использоваться в режиме диапазона, при котором используются 2 слайдера (ползунка) для задания диапазона.
 * В данном режиме свойства {@link attribute} и {@link maxAttribute} будут определять имена атрибутов
 * минимального и максимального значений диапазона соответственно. Например:
 *
 * <pre>
 * $this->widget('zii.widgets.jui.CJuiSliderInput', array(
 *     'model'=>$model,
 *     'attribute'=>'timeMin',
 *     'maxAttribute'=>'timeMax',
 *     // дополнительные javascript-опции для плагина слайдера
 *     'options'=>array(
 *         'range'=>true,
 *         'min'=>0,
 *         'max'=>24,
 *     ),
 * ));
 * </pre>
 *
 * Если требуется использовать событие слайдера, измените значение свойства события на 'stop' или 'change'.
 *
 * Настройкой свойства {@link options} можно определить опции, передаваемые в плагин слайдера.
 * Обратитесь к {@link http://api.jqueryui.com/slider/ API плагина JUI slider}
 * за списком возможных опций (пар имя-значение) и к
 * {@link http://jqueryui.com/slider/ основной странице плагина} за
 * описанием и примерами.
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @package zii.widgets.jui
 * @since 1.1
 */
class CJuiSliderInput extends CJuiInputWidget
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
	 * @var string имя события, в котором входные данные будут присоединены к слайдеру. Может принимать
	 * сначения 'slide', 'stop' или 'change'. Если вы хотите использовать событие 'slide', измените значение
	 * данного свойства на 'change'
	 */
	public $event = 'slide';

	/**
	 * @var string имя атрибута максимального значения, если слайдер используется в режиме диапазона
	 */
	public $maxAttribute;

	/**
	 * Выполняет виджет.
	 * Метод регистрирует требуемый javascript-код и генерирует HTML-код
	 */
	public function run()
	{
		list($name,$id)=$this->resolveNameID();

		if(isset($this->htmlOptions['id']))
			$id=$this->htmlOptions['id'];
		else
			$this->htmlOptions['id']=$id;
		if(isset($this->htmlOptions['name']))
			$name=$this->htmlOptions['name'];

		$isRange=isset($this->options['range']) && $this->options['range'];

		if($this->hasModel())
		{
			$attribute=$this->attribute;
			if($isRange)
			{
				$options=$this->htmlOptions;
				echo CHtml::activeHiddenField($this->model,$this->attribute,$options);
				$options['id']=$options['id'].'_end';
				echo CHtml::activeHiddenField($this->model,$this->maxAttribute,$options);
				$attrMax=$this->maxAttribute;
				$this->options['values']=array($this->model->$attribute,$this->model->$attrMax);
			}
			else
			{
				echo CHtml::activeHiddenField($this->model,$this->attribute,$this->htmlOptions);
				$this->options['value']=$this->model->$attribute;
			}
		}
		else
		{
			echo CHtml::hiddenField($name,$this->value,$this->htmlOptions);
			if($this->value!==null)
				$this->options['value']=$this->value;
		}

		$idHidden=$this->htmlOptions['id'];
		$this->htmlOptions['id']=$idHidden.'_slider';
		echo CHtml::tag($this->tagName,$this->htmlOptions,'');

		$this->options[$this->event]= $isRange
			? new CJavaScriptExpression("function(e,ui){ v=ui.values; jQuery('#{$idHidden}').val(v[0]); jQuery('#{$idHidden}_end').val(v[1]); }")
			: new CJavaScriptExpression("function(event, ui) { jQuery(#'{$idHidden}').val(ui.value); }");

		$options=CJavaScript::encode($this->options);
		Yii::app()->getClientScript()->registerScript(__CLASS__.'#'.$id,"jQuery('#{$id}_slider').slider($options);");
	}
}
