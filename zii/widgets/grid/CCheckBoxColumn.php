<?php
/**
 * Файл класса CCheckBoxColumn.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.grid.CGridColumn');

/**
 * Класс CCheckBoxColumn представляет столбец флажков таблицы.
 *
 * CCheckBoxColumn поддерживает возможности флажка только для чтения, одиночного выбора и множественного выбора.
 * Режим определяется согласно свойству {@link selectableRows}. В режиме множественного выбора ячейка-заголовок будет
 * отображать дополнительный флажок, щелчок по которому будет включать или отключать все флажки в ячейках столбца.
 * The header cell can be customized by {@link headerTemplate}.
 *
 * Дополнительно, выбор флажка может выбирать строку таблицы (в зависимости от значения {@link CGridView::selectableRows}),
 * если свойство {@link selectableRows} является нулевым (по умолчанию).
 
 * По умолчанию генерируемые в ячейках таблицы флажки будут иметь значения, заданные моделью данных.
 * Можно изменить данное поведени установкой значения свойства {@link name} или {@link value}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package zii.widgets.grid
 * @since 1.1
 */
class CCheckBoxColumn extends CGridColumn
{
	/**
	 * @var string имя атрибута модели данных. Соответствующее значение атрибута будет генерироваться в каждой ячейке данных
	 * в виде значения флажка. Примечание: если свойство {@link value} определено, то данное свойство будет игнорироваться
	 * @see value
	 */
	public $name;
	/**
	 * @var string PHP-выражение, выполняемое для каждой ячейки данных, и результат которого будет генерироваться
	 * для каждой ячейки данных в виде значения флажка. В выражении доступны переменные:
	 * <code>$row</code> - номер строки (начиная с нуля), <code>$data</code> - модель данных для строки и
	 * <code>$this</code> - объект столбца
	 */
	public $value;
	/**
	 * @var string PHP-выражение, выполняемое для каждой ячейки данных, и результат которого будет определять,
	 * отмечен ли флажок в ячейке данных. В выражении доступны переменные:
	 * <code>$row</code> - номер строки (начиная с нуля), <code>$data</code> - модель данных для строки и
	 * <code>$this</code> - объект столбца
	 * @since 1.1.4
	 */
	public $checked;
	/**
	 * @var string a PHP expression that will be evaluated for every data cell and whose result will
	 * determine if checkbox for each data cell is disabled. In this expression, the variable
	 * <code>$row</code> the row number (zero-based); <code>$data</code> the data model for the row;
	 * and <code>$this</code> the column object. Note that expression result will
	 * overwrite value set with <code>checkBoxHtmlOptions['disabled']</code>.
	 * @since 1.1.13
	 */
	public $disabled;
	/**
	 * @var array HTML-опции для тега ячейки данных
	 */
	public $htmlOptions=array('class'=>'checkbox-column');
	/**
	 * @var array HTML-опции для тега ячейки заголовка таблицы
	 */
	public $headerHtmlOptions=array('class'=>'checkbox-column');
	/**
	 * @var array HTML-опции для тега ячейки подошвы (footer) таблицы
	 */
	public $footerHtmlOptions=array('class'=>'checkbox-column');
	/**
	 * @var array HTML-опции для флажков
	 */
	public $checkBoxHtmlOptions=array();
	/**
	 * @var integer количество флажков в строках, которые могут быть установлены
	 * Возможные значения:
	 * <ul>
	 * <li>0 - флажок не может быть изменен (режим "только чтение")</li>
	 * <li>1 - только 1 флажок может быть установлен. Установка флажка не выделяет строку;</li>
	 * <li>2 или больше - могут быть установлены несколько флажков. Установка флажка не выделяет строку;</li>
	 * <li>null - свойство {@link CGridView::selectableRows} используется для контроля количества возможных флажков для выбора.
	 * Установка флажка также выделяет строку.</li>
	 * </ul>
	 * Вы также можете вызвать JavaScript-функцию <code>$.fn.yiiGridView.getChecked(containerID,columnID)</code>
	 * для получения значений выбранных строк
	 * @since 1.1.6
	 */
	public $selectableRows=null;
	/**
	 * @var string the template to be used to control the layout of the header cell.
	 * The token "{item}" is recognized and it will be replaced with a "check all" checkbox.
	 * By default if in multiple checking mode, the header cell will display an additional checkbox, 
	 * clicking on which will check or uncheck all of the checkboxes in the data cells.
	 * See {@link selectableRows} for more details.
	 * @since 1.1.11
	 */
	public $headerTemplate='{item}';

	/**
	 * Инициализирует столбец.
	 * Данный метод регистрирует требуемый клиентский скрипт для столбца флажков
	 */
	public function init()
	{
		if(isset($this->checkBoxHtmlOptions['name']))
			$name=$this->checkBoxHtmlOptions['name'];
		else
		{
			$name=$this->id;
			if(substr($name,-2)!=='[]')
				$name.='[]';
			$this->checkBoxHtmlOptions['name']=$name;
		}
		$name=strtr($name,array('['=>"\\[",']'=>"\\]"));

		if($this->selectableRows===null)
		{
			if(isset($this->checkBoxHtmlOptions['class']))
				$this->checkBoxHtmlOptions['class'].=' select-on-check';
			else
				$this->checkBoxHtmlOptions['class']='select-on-check';
			return;
		}

		$cball=$cbcode='';
		if($this->selectableRows==0)
		{
			//.. read only
			$cbcode="return false;";
		}
		elseif($this->selectableRows==1)
		{
			//.. only one can be checked, uncheck all other
			$cbcode="jQuery(\"input:not(#\"+this.id+\")[name='$name']\").prop('checked',false);";
		}
		elseif(strpos($this->headerTemplate,'{item}')!==false)
		{
			//.. process check/uncheck all
			$cball=<<<CBALL
jQuery(document).on('click','#{$this->id}_all',function() {
	var checked=this.checked;
	jQuery("input[name='$name']:enabled").each(function() {this.checked=checked;});
});

CBALL;
			$cbcode="jQuery('#{$this->id}_all').prop('checked', jQuery(\"input[name='$name']\").length==jQuery(\"input[name='$name']:checked\").length);";
		}

		if($cbcode!=='')
		{
			$js=$cball;
			$js.=<<<EOD
jQuery(document).on('click', "input[name='$name']", function() {
	$cbcode
});
EOD;
			Yii::app()->getClientScript()->registerScript(__CLASS__.'#'.$this->id,$js);
		}
	}

	/**
	 * Генерирует содержимое ячейки заголовка (header) таблицы.
	 * Данный метод будет генерировать флажок в заголовке, если свойство {@link selectableRows} больше 1 или
	 * если свойство {@link selectableRows} имеет значение null при свойстве {@link CGridView::selectableRows} большем 1
	 */
	protected function renderHeaderCellContent()
	{
		if(trim($this->headerTemplate)==='')
		{
			echo $this->grid->blankDisplay;
			return;
		}

		$item = '';
		if($this->selectableRows===null && $this->grid->selectableRows>1)
			$item = CHtml::checkBox($this->id.'_all',false,array('class'=>'select-on-check-all'));
		elseif($this->selectableRows>1)
			$item = CHtml::checkBox($this->id.'_all',false);
		else
		{
			ob_start();
			parent::renderHeaderCellContent();
			$item = ob_get_clean();
		}

		echo strtr($this->headerTemplate,array(
			'{item}'=>$item,
		));
	}

	/**
	 * Генерирует содержимое ячейки данных.
	 * Данный метод генерирует флажок в ячейке данных
	 * @param integer $row номер строки (начиная с нуля)
	 * @param mixed $data ассоциированные с данной строкой данные
	 */
	protected function renderDataCellContent($row,$data)
	{
		if($this->value!==null)
			$value=$this->evaluateExpression($this->value,array('data'=>$data,'row'=>$row));
		elseif($this->name!==null)
			$value=CHtml::value($data,$this->name);
		else
			$value=$this->grid->dataProvider->keys[$row];

		$checked = false;
		if($this->checked!==null)
			$checked=$this->evaluateExpression($this->checked,array('data'=>$data,'row'=>$row));

		$options=$this->checkBoxHtmlOptions;
		if($this->disabled!==null)
			$options['disabled']=$this->evaluateExpression($this->disabled,array('data'=>$data,'row'=>$row));

		$name=$options['name'];
		unset($options['name']);
		$options['value']=$value;
		$options['id']=$this->id.'_'.$row;
		echo CHtml::checkBox($name,$checked,$options);
	}
}
