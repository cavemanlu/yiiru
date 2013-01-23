<?php
/**
 * Файл класса CJuiSortable.
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.jui.CJuiWidget');

/**
 * Виджет CJuiSortable отображает сортируемый (перетаскиванием элементов мышкой) список
 *
 * Виджет CJuiSortable инкапсулирует {@link http://jqueryui.com/sortable/ плагин сортировки JUI}.
 *
 * Для использования виджета можно вставить в представление следующий код:
 * <pre>
 * $this->widget('zii.widgets.jui.CJuiSortable', array(
 *     'items'=>array(
 *         'id1'=>'Item 1',
 *         'id2'=>'Item 2',
 *         'id3'=>'Item 3',
 *     ),
 *     // дополнительные javascript-опции для плагина сортировки
 *     'options'=>array(
 *         'delay'=>'300',
 *     ),
 * ));
 * </pre>
 *
 * Настройкой свойства {@link options} можно определить опции, передаваемые в
 * плагин сортировки. Обратитесь к
 * {@link http://api.jqueryui.com/sortable/ API плагина JUI sortable}
 * за списком возможных опций (пар имя-значение) и к
 * {@link http://jqueryui.com/sortable/ основной странице плагина} за
 * описанием и примерами.
 *
 * Если вы используете javascript-выражение в каком-либо любом месте кода,
 * заключите это выражение в {@link CJavaScriptExpression} и Yii будет
 * использовать это выражение как код JS.
 * 
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @package zii.widgets.jui
 * @since 1.1
 */
class CJuiSortable extends CJuiWidget
{
	/**
	 * @var array список сортирумых элементов (идентификатор => содержимое элемента).
	 * Примечание: содержимое элемента не проходит HTML-кодирование
	 */
	public $items=array();
	/**
	 * @var string имя контейнера, содержащего все элементы. По умолчанию - 'ul'
	 */
	public $tagName='ul';
	/**
	 * @var string шаблон, используемый для генерации каждого сортируемого элемента.
	 * Маркер "{content}" в шаблоне будет заменен на содержимое элемента, а
	 * маркер "{id}" - на идентификатор элемента
	 */
	public $itemTemplate='<li id="{id}">{content}</li>';

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

		$options=CJavaScript::encode($this->options);
		Yii::app()->getClientScript()->registerScript(__CLASS__.'#'.$id,"jQuery('#{$id}').sortable({$options});");

		echo CHtml::openTag($this->tagName,$this->htmlOptions)."\n";
		foreach($this->items as $id=>$content)
			echo strtr($this->itemTemplate,array('{id}'=>$id,'{content}'=>$content))."\n";
		echo CHtml::closeTag($this->tagName);
	}
}