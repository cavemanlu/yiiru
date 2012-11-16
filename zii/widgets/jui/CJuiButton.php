<?php
/**
 * Файл класса CJuiButton.
 *
 * @author Sebastian Thierer <sebas@artfos.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.jui.CJuiInputWidget');

/**
 * Виджет CJuiButton отображает управляющие элементы формы в виде кнопки.
 *
 * Виджет CJuiButton инкапсулирует {@link http://jqueryui.com/demos/button/ плагин JUI button}.
 *
 * Для использования данного виджета в качестве submit-кнопки нужно вставить в представление следующий код:
 * <pre>
 * $this->widget('zii.widgets.jui.CJuiButton', array(
 * 		'name'=>'submit',
 * 		'caption'=>'Сохранить',
 * 		'options'=>array(
 *         'onclick'=>new CJavaScriptExpression('function(){alert("Да");}'),
 *     ),
 * ));
 * </pre>
 *
 * Для использования данного виджета в качестве кнопки нужно вставить в представление следующий код:
 * <pre>
 * $this->widget('zii.widgets.jui.CJuiButton',
 *		array(
 *			'name'=>'button',
 * 			'caption'=>'Сохранить',
 *			'value'=>'asd',
 *			'onclick'=>new CJavaScriptExpression('function(){alert("Нажата кнопка сохранения"); this.blur(); return false;}'),
 * 		)
 * );
 * </pre>
 *
 * Настройкой свойства {@link options} можно определить опции, передаваемые в плагин кнопки.
 * Обратитесь к {@link http://jqueryui.com/demos/button/ документации о плагине JUI button}
 * за списком возможных опций (пар имя-значение).
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @package zii.widgets.jui
 * @since 1.1.3
 */
class CJuiButton extends CJuiInputWidget
{
	/**
	 * @var string тип кнопки (возможные типы: submit, button, link, radio, checkbox, buttonset).
	 * По умолчанию - "submit"
	 */
	public $buttonType = 'submit';

	/**
	 * @var string имя тега контейнера для набора кнопок (buttonset)
	 */
	public $htmlTag = 'div';
	/**
	 * @var mixed URL-адрес или маршрут действия, который может быть
	 * использован для создания URL-адреса. Используется при выбранном
	 * buttonType "link". За подробностями об определении данного параметра
	 * обратитесь к {@link normalizeUrl}
	 */
	public $url = null;

	/**
	 * @var mixed значение текущего элемента. Используется только для типов
	 * "radio" и "checkbox"
	 */
	public $value;

	/**
	 * @var string текст кнопки
	 */
	public $caption="";
	/**
	 * @var string javascript-функция, вызываемая при нажатии на кнопку (клиентское событие)
	 */
	public $onclick;

	/**
	 * (non-PHPdoc)
	 * @see framework/zii/widgets/jui/CJuiWidget::init()
	 */
	public function init(){
		parent::init();
		if ($this->buttonType=='buttonset')
		{
			list($name,$id)=$this->resolveNameID();

			if(isset($this->htmlOptions['id']))
				$id=$this->htmlOptions['id'];
			else
				$this->htmlOptions['id']=$id;
			if(isset($this->htmlOptions['name']))
				$name=$this->htmlOptions['name'];
			else
				$this->htmlOptions['name']=$name;

			echo CHtml::openTag($this->htmlTag, $this->htmlOptions);
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see framework/CWidget::run()
	 */
	public function run()
	{
		$cs = Yii::app()->getClientScript();
		list($name,$id)=$this->resolveNameID();

		if(isset($this->htmlOptions['id']))
			$id=$this->htmlOptions['id'];
		else
			$this->htmlOptions['id']=$id;
		if(isset($this->htmlOptions['name']))
			$name=$this->htmlOptions['name'];
		else
			$this->htmlOptions['name']=$name;

		if ($this->buttonType=='buttonset')
		{
			echo CHtml::closeTag($this->htmlTag);
			$cs->registerScript(__CLASS__.'#'.$id,"jQuery('#{$id}').buttonset();");
		}
		else
		{
			switch($this->buttonType)
			{
				case 'submit':
					echo CHtml::submitButton($this->caption, $this->htmlOptions) . "\n";
					break;
				case 'button':
					echo CHtml::htmlButton($this->caption, $this->htmlOptions) . "\n";
					break;
				case 'link':
					echo CHtml::link($this->caption, $this->url, $this->htmlOptions) . "\n";
					break;
				case 'radio':
					if ($this->hasModel())
					{
						echo CHtml::activeRadioButton($this->model, $this->attribute, $this->htmlOptions);
						echo CHtml::label($this->caption, CHtml::activeId($this->model, $this->attribute)) . "\n";
					}
					else
					{
						echo CHtml::radioButton($name, $this->value, $this->htmlOptions);
						echo CHtml::label($this->caption, $id) . "\n";
					}
					break;
				case 'checkbox':
					if ($this->hasModel())
					{
						echo CHtml::activeCheckbox($this->model, $this->attribute, $this->htmlOptions);
						echo CHtml::label($this->caption, CHtml::activeId($this->model, $this->attribute)) . "\n";
					}
					else
					{
						echo CHtml::checkbox($name, $this->value, $this->htmlOptions);
						echo CHtml::label($this->caption, $id) . "\n";
					}
					break;
				default:
					throw new CException(Yii::t('zii','The button type "{type}" is not supported.',array('{type}'=>$this->buttonType)));
			}

			$options=empty($this->options) ? '' : CJavaScript::encode($this->options);
			if($this->onclick!==null)
			{
				if(!($this->onclick instanceof CJavaScriptExpression))
					$this->onclick=new CJavaScriptExpression($this->onclick);
				$click = CJavaScript::encode($this->onclick);
				$cs->registerScript(__CLASS__.'#'.$id,"jQuery('#{$id}').button($options).click($click);");
			}
			else
			{
				$cs->registerScript(__CLASS__.'#'.$id,"jQuery('#{$id}').button($options);");
			}
		}
	}
}
