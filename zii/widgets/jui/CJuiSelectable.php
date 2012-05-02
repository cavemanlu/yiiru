<?php
/**
 * Файл класса CJuiSelectable.
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.jui.CJuiWidget');

/**
 * Виджет CJuiSelectable отображает список выделяемых элементов.
 *
 * Виджет CJuiSelectable инкапсулирует {@link http://jqueryui.com/demos/selectable/ плагин JUI selectable}.
 *
 * Для использования данного виджета нужно вставить в представление следующий код:
 * <pre>
 * $this->widget('zii.widgets.jui.CJuiSelectable', array(
 *     'items'=>array(
 *         'id1'=>'Первый элемент',
 *         'id2'=>'Второй элемент',
 *         'id3'=>'Третий элемент',
 *     ),
 *     // дополнительные javascript-опции для плагина выделяемых элементов
 *     'options'=>array(
 *         'delay'=>'300',
 *     ),
 * ));
 * </pre>
 *
 * Настройкой свойства {@link options} можно определить опции, передаваемые в плагин выделяемых элементов.
 * Обратитесь к {@link http://jqueryui.com/demos/selectable/ документации о плагине JUI selectable}
 * за списком возможных опций (пар имя-значение).
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @version $Id: CJuiSelectable.php 3207 2011-05-12 08:05:26Z mdomba $
 * @package zii.widgets.jui
 * @since 1.1
 */
class CJuiSelectable extends CJuiWidget {
	/**
	 * @var array список выделяемых элементов (идентификатор => содержимое элемента).
	 * Примечание: содержимое элементов не проходит HTML-кодирование
	 */
	public $items=array();
	/**
	 * @var string имя тега контейнера, содержащего  все элементы. По умолчанию - 'ol'
	 */
	public $tagName='ol';
	/**
	 * @var string шаблон, используемый для генерации каждого выделяемого элемента.
	 * Маркер "{content}" в шаблоне заменяется содержимым элемента, а
	 * маркер "{id}" - идентификатором элемента
	 */
	public $itemTemplate='<li id="{id}">{content}</li>';

	/**
	 * Выполняет виджет.
	 * Метод регистрирует требуемый javascript-код и генерирует HTML-код
	 */
	public function run(){
		$id=$this->getId();
		if (isset($this->htmlOptions['id']))
			$id = $this->htmlOptions['id'];
		else
			$this->htmlOptions['id']=$id;

		$options=empty($this->options) ? '' : CJavaScript::encode($this->options);
		Yii::app()->getClientScript()->registerScript(__CLASS__.'#'.$id,"jQuery('#{$id}').selectable({$options});");

		echo CHtml::openTag($this->tagName,$this->htmlOptions)."\n";
		foreach($this->items as $id=>$content)
		{
			echo strtr($this->itemTemplate,array('{id}'=>$id,'{content}'=>$content))."\n";
		}
		echo CHtml::closeTag($this->tagName);
	}
}


