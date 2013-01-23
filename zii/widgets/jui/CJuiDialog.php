<?php
/**
 * Файл класса CJuiDialog.
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.jui.CJuiWidget');

/**
 * Виджет CJuiDialog отображает диалоговое окно.
 *
 * Виджет CJuiDialog инкапсулирует {@link http://jqueryui.com/dialog/ плагин JUI dialog}.
 *
 * Для использования данного виджета нужно вставить в представление следующий код:
 * <pre>
 * $this->beginWidget('zii.widgets.jui.CJuiDialog', array(
 *     'id'=>'mydialog',
 *     // дополнительные javascript-опции для плагина диалогового окна
 *     'options'=>array(
 *         'title'=>'Заголовок диалогового окна',
 *         'autoOpen'=>false,
 *     ),
 * ));
 *
 *     echo 'Содержимое диалогового окна';
 *
 * $this->endWidget('zii.widgets.jui.CJuiDialog');
 *
 * // ссылка, по которой можно открыть диалоговое окно
 * echo CHtml::link('open dialog', '#', array(
 *    'onclick'=>'$("#mydialog").dialog("open"); return false;',
 * ));
 * </pre>
 *
 * Настройкой свойства {@link options} можно определить опции, передаваемые в плагин диалогового окна.
 * Обратитесь к {@link http://api.jqueryui.com/dialog/ API плагина JUI dialog}
 * за списком возможных опций (пар имя-значение) и к
 * {@link http://jqueryui.com/dialog/ основной странице плагина} за
 * описанием и примерами.
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @package zii.widgets.jui
 * @since 1.1
 */
class CJuiDialog extends CJuiWidget
{
	/**
	 * @var string имя тега контейнера диалогового окна. По умолчанию - 'div'
	 */
	public $tagName='div';

	/**
	 * Генерирует открывающий тег виджета диалогового окна.
	 * Данный метод также регистрирует требуемый javascript-код
	 */
	public function init()
	{
		parent::init();

		$id=$this->getId();
		if(isset($this->htmlOptions['id']))
			$id=$this->htmlOptions['id'];
		else
			$this->htmlOptions['id']=$id;

		$options=CJavaScript::encode($this->options);
		Yii::app()->getClientScript()->registerScript(__CLASS__.'#'.$id,"jQuery('#{$id}').dialog($options);");

		echo CHtml::openTag($this->tagName,$this->htmlOptions)."\n";
	}
	
	/**
	 * Генерирует закрывающий тег виджета диалогового окна
	 */
	public function run()
	{
		echo CHtml::closeTag($this->tagName);
	}
}