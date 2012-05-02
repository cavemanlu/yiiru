<?php
/**
 * Файл класса CJuiAutoComplete.
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.jui.CJuiInputWidget');

/**
 * Виджет CJuiAutoComplete отображает поле с автозаполнением.
 *
 * Виджет CJuiAutoComplete инкапсулирует {@link http://jqueryui.com/demos/autocomplete/ плагин JUI autocomplete}.
 *
 * Для использования данного виджета нужно вставить в представление следующий код:
 * <pre>
 * $this->widget('zii.widgets.jui.CJuiAutoComplete', array(
 *     'name'=>'city',
 *     'source'=>array('ac1', 'ac2', 'ac3'),
 *     // дополнительные javascript-опции для плагина поля с автозаполнением
 *     'options'=>array(
 *         'minLength'=>'2',
 *     ),
 *     'htmlOptions'=>array(
 *         'style'=>'height:20px;'
 *     ),
 * ));
 * </pre>
 *
 * Настройкой свойства {@link options} можно определить опции, передаваемые в плагин поля с автозаполнением.
 * Обратитесь к {@link http://jqueryui.com/demos/autocomplete/ документации о плагине JUI autocomplete}
 * за списком возможных опций (пар имя-значение).
 *
 * Настройкой свойства {@link source} можно определить, где искать опции автозаполнения для каждого
 * элемента. Если это массив, то он используется в качестве списка значений, используемых для
 * автозаполнения. Также можно настроить свойство {@link sourceUrl} для получения элементов автозаполнения
 * посредством ajax-запроса.
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @version $Id: CJuiAutoComplete.php 2799 2011-01-01 19:31:13Z qiang.xue $
 * @package zii.widgets.jui
 * @since 1.1.2
 */
class CJuiAutoComplete extends CJuiInputWidget
{
	/**
	 * @var mixed местоположение списка элементов джля автозаполенения. Может быть:
	 * <ul>
	 * <li>массивом с локальными данными;</li>
     * <li>строкой, определяющей URL-адрес, возвращающий элементы автозаполнения в виде JSON-данных;</li>
     * <li>обратный вызов javascript. Убедитесь, что в данном случае есть префикс "js:".</li>
     * </ul>
	 */
	public $source = array();
	/**
	 * @var mixed URL-адрес, возвращающий элементы автозаполнения в виде JSON-данных.
	 * Метод CHtml::normalizeUrl() будет применен к данному свойству, чтобы преобразовать свойство в
	 * правильный URL-адрес. Когда данное свойство установлено, свойство {@link source} игнорируется
	 */
	public $sourceUrl;

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

		if($this->hasModel())
			echo CHtml::activeTextField($this->model,$this->attribute,$this->htmlOptions);
		else
			echo CHtml::textField($name,$this->value,$this->htmlOptions);

		if($this->sourceUrl!==null)
			$this->options['source']=CHtml::normalizeUrl($this->sourceUrl);
		else
			$this->options['source']=$this->source;

		$options=CJavaScript::encode($this->options);

		$js = "jQuery('#{$id}').autocomplete($options);";

		$cs = Yii::app()->getClientScript();
		$cs->registerScript(__CLASS__.'#'.$id, $js);
	}
}
