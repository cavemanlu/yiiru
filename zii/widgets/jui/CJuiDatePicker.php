<?php
/**
 * Файл класса CJuiDatePicker.
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.jui.CJuiInputWidget');

/**
 * Виджет CJuiDatePicker отображает элемент выбора даты.
 *
 * Виджет CJuiDatePicker инкапсулирует {@link http://jqueryui.com/demos/datepicker/ плагин JUI datepicker}.
 *
 * Для использования данного виджета нужно вставить в представление следующий код:
 * <pre>
 * $this->widget('zii.widgets.jui.CJuiDatePicker', array(
 *     'name'=>'publishDate',
 *     // дополнительные javascript-опции для плагина элемента выбора даты
 *     'options'=>array(
 *         'showAnim'=>'fold',
 *     ),
 *     'htmlOptions'=>array(
 *         'style'=>'height:20px;'
 *     ),
 * ));
 * </pre>
 *
 * Настройкой свойства {@link options} можно определить опции, передаваемые в плагин элемента выбора даты.
 * Обратитесь к {@link http://jqueryui.com/demos/datepicker/ документации о плагине JUI datepicker}
 * за списком возможных опций (пар имя-значение).
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @version $Id: CJuiDatePicker.php 3539 2012-01-15 18:55:01Z mdomba $
 * @package zii.widgets.jui
 * @since 1.1
 */
class CJuiDatePicker extends CJuiInputWidget
{
	/**
	 * @var string идентификатор локали (например, 'fr', 'de') для определения языка, используемого элементом выбора даты.
	 * Если данное свойство не установлено, интернационализация не будет использоваться. Т.е., элемент выбора даты будет
	 * использовать английский язык. Можно выполнить принудительную загрузку английского языка, установив свойство
	 * {@link language} в значение '' (пустая строка)
	 */
	public $language;

	/**
	 * @var string файл скрипта интернационализации Jquery UI. Использует свойство scriptUrl в качестве базового url-адреса
	 */
	public $i18nScriptFile = 'jquery-ui-i18n.min.js';

	/**
	 * @var array опции по умолчанию, вызываемые один раз за запрос. Данные опции будут затрагивать каждый экземпляр
	 * виджета CJuiDatePicker на странице. Они должны быть установлены при первом вызове виджета за запрос
	 */
	public $defaultOptions;

	/**
	 * @var boolean если значение равно true, то виджет показывается в виде календаря на странице (в отличие от появлении
	 * календаря при клике на текстовом поле), а поле ввода даты - в виде скрытого поля. Использует событие
	 * onSelect для обновления скрытого поля
	 */
	public $flat = false;

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

		if ($this->flat===false)
		{
			if($this->hasModel())
				echo CHtml::activeTextField($this->model,$this->attribute,$this->htmlOptions);
			else
				echo CHtml::textField($name,$this->value,$this->htmlOptions);
		}
		else
		{
			if($this->hasModel())
			{
				echo CHtml::activeHiddenField($this->model,$this->attribute,$this->htmlOptions);
				$attribute = $this->attribute;
				$this->options['defaultDate'] = $this->model->$attribute;
			}
			else
			{
				echo CHtml::hiddenField($name,$this->value,$this->htmlOptions);
				$this->options['defaultDate'] = $this->value;
			}

			if (!isset($this->options['onSelect']))
				$this->options['onSelect']="js:function( selectedDate ) { jQuery('#{$id}').val(selectedDate);}";

			$id = $this->htmlOptions['id'] = $id.'_container';
			$this->htmlOptions['name'] = $name.'_container';

			echo CHtml::tag('div', $this->htmlOptions, '');
		}

		$options=CJavaScript::encode($this->options);
		$js = "jQuery('#{$id}').datepicker($options);";

		if ($this->language!='' && $this->language!='en')
		{
			$this->registerScriptFile($this->i18nScriptFile);
			$js = "jQuery('#{$id}').datepicker(jQuery.extend({showMonthAfterYear:false}, jQuery.datepicker.regional['{$this->language}'], {$options}));";
		}

		$cs = Yii::app()->getClientScript();

		if (isset($this->defaultOptions))
		{
			$this->registerScriptFile($this->i18nScriptFile);
			$cs->registerScript(__CLASS__, 	$this->defaultOptions!==null?'jQuery.datepicker.setDefaults('.CJavaScript::encode($this->defaultOptions).');':'');
		}
		$cs->registerScript(__CLASS__.'#'.$id, $js);

	}
}