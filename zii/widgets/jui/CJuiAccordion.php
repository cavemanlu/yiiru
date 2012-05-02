<?php
/**
 * Файл класса CJuiAccordion.
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @author Qiang XUe <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.jui.CJuiWidget');

/**
 * Виджет CJuiAccordion отображает блок-аккордеон (список раскрывающихся блоков).
 *
 * Виджет CJuiAccordion инкапсулирует {@link http://jqueryui.com/demos/accordion/ плагин JUI accordion}.
 *
 * Для использования данного виджета нужно вставить в представление следующий код:
 * <pre>
 * $this->widget('zii.widgets.jui.CJuiAccordion', array(
 *     'panels'=>array(
 *         'заголовок панели 1'=>'содержимое панели 1',
 *         'заголовок панели 2'=>'содержимое панели 2',
 *         // содержимое панели 3 генерируется частичным представлением
 *         'заголовок панели 3'=>$this->renderPartial('_partial',null,true),
 *     ),
 *     // дополнительные javascript-опции для плагина блока-аккордеона
 *     'options'=>array(
 *         'animated'=>'bounceslide',
 *     ),
 * ));
 * </pre>
 *
 * Настройкой свойства {@link options} можно определить опции, передаваемые в плагин блока-аккордеона.
 * Обратитесь к {@link http://jqueryui.com/demos/accordion/ документации о плагине JUI accordion}
 * за списком возможных опций (пар имя-значение).
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @author Qiang XUe <qiang.xue@gmail.com>
 * @version $Id: CJuiAccordion.php 2799 2011-01-01 19:31:13Z qiang.xue $
 * @package zii.widgets.jui
 * @since 1.1
 */
class CJuiAccordion extends CJuiWidget
{
	/**
	 * @var array список панелей (заголовок панели => содержимое панели).
	 * Примечание: ни заголовок панели ни ее содержимое не проходят HTML-кодирование
	 */
	public $panels=array();
	/**
	 * @var string имя элемента контейнера, содержащего все панели. По умолчанию - 'div'
	 */
	public $tagName='div';
	/**
	 * @var string шаблон, используемый для генерации заголовка каждой панели.
	 * Маркер "{title}" в шаблоне заменяется заголовком панели.
	 * Примечание: если в данном шаблоне сделаны изменения, может также потребоваться настроить
	 * опцию 'header' в свойстве {@link options}
	 */
	public $headerTemplate='<h3><a href="#">{title}</a></h3>';
	/**
	 * @var string шаблон, используемый для генерации содержимого каждой панели.
	 * Маркер "{content}" в шаблоне заменяется содержимым панели
	 */
	public $contentTemplate='<div>{content}</div>';

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

		echo CHtml::openTag($this->tagName,$this->htmlOptions)."\n";
		foreach($this->panels as $title=>$content)
		{
			echo strtr($this->headerTemplate,array('{title}'=>$title))."\n";
			echo strtr($this->contentTemplate,array('{content}'=>$content))."\n";
		}
		echo CHtml::closeTag($this->tagName);

		$options=empty($this->options) ? '' : CJavaScript::encode($this->options);
		Yii::app()->getClientScript()->registerScript(__CLASS__.'#'.$id,"jQuery('#{$id}').accordion($options);");
	}
}
