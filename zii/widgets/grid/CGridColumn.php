<?php
/**
 * Файл класса CGridColumn.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * CGridColumn - это базовый класс для всех клаасов столбцов таблиц.
 *
 * Объект класса CGridColumn представляет спецификацию для генерации ячеек в
 * определенном столбце.
 *
 * В каждом столбце есть одна ячейка-заголовок, несколько ячеек данный и опциональная ячейка-футер.
 * Классы-потомки могут переопределять методы {@link renderHeaderCellContent}, {@link renderDataCellContent}
 * и {@link renderFooterCellContent} для настройки генерации данных ячеек.
 *
 * @property boolean $hasFooter имеет ли данный столбец ячейку-футер.
 * Определяется по установленному свойству {@link footer}
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CGridColumn.php 3426 2011-10-25 00:01:09Z alexander.makarow $
 * @package zii.widgets.grid
 * @since 1.1
 */
abstract class CGridColumn extends CComponent
{
	/**
	 * @var string идентификатор столбца. Данной значение должно быть уникально среди всех столбцов таблицы.
	 * Если не установлено, то значение будет присвоено автоматически
	 */
	public $id;
	/**
	 * @var CGridView объект таблицы, включающей в себя данный столбец
	 */
	public $grid;
	/**
	 * @var string текст ячейки-заголовка. Примечание: не проходит HTML-кодирование
	 */
	public $header;
	/**
	 * @var string текст ячейки-футера. Примечание: не проходит HTML-кодирование
	 */
	public $footer;
	/**
	 * @var boolean видим ли данный столбец. По умолчанию - true
	 */
	public $visible=true;
	/**
	 * @var string PHP-выражение, выполняемое для каждой ячейки данных и результат которого
	 * используется в качестве имени CSS-класса для ячейки данных. В данном выражении доступны переменные:
	 * <code>$row</code> - номер строки (начиная с нуля), <code>$data</code> - модель данных для строки и
	 * <code>$this</code> - объект столбца
	 */
	public $cssClassExpression;
	/**
	 * @var array HTML-опции для тега ячейки данных
	 */
	public $htmlOptions=array();
	/**
	 * @var array HTML-опции для тега ячейки-заголовка
	 */
	public $headerHtmlOptions=array();
	/**
	 * @var array HTML-опции для тега ячейки-футера
	 */
	public $footerHtmlOptions=array();

	/**
	 * Конструктор
	 * @param CGridView $grid объект таблицы, частью которой является данный столбец
	 */
	public function __construct($grid)
	{
		$this->grid=$grid;
	}

	/**
	 * Инициализирует столбец.
	 * Данный метод вызывается таблицей при ее инициализации перед генерацией.
	 * Вы можете переопределить данный метод для подготовки столбца к генерации
	 */
	public function init()
	{
	}

	/**
	 * @return boolean имеет ли данный столбец ячейку-футер.
	 * Определяется по установленному свойству {@link footer}
	 */
	public function getHasFooter()
	{
		return $this->footer!==null;
	}

	/**
	 * Генерирует ячейку-фильтр
	 * @since 1.1.1
	 */
	public function renderFilterCell()
	{
		echo "<td>";
		$this->renderFilterCellContent();
		echo "</td>";
	}

	/**
	 * Генерирует ячейку-заголовок
	 */
	public function renderHeaderCell()
	{
		$this->headerHtmlOptions['id']=$this->id;
		echo CHtml::openTag('th',$this->headerHtmlOptions);
		$this->renderHeaderCellContent();
		echo "</th>";
	}

	/**
	 * Генерирует ячейку данных.
	 * @param integer $row номер строки (начиная с нуля)
	 */
	public function renderDataCell($row)
	{
		$data=$this->grid->dataProvider->data[$row];
		$options=$this->htmlOptions;
		if($this->cssClassExpression!==null)
		{
			$class=$this->evaluateExpression($this->cssClassExpression,array('row'=>$row,'data'=>$data));
			if(isset($options['class']))
				$options['class'].=' '.$class;
			else
				$options['class']=$class;
		}
		echo CHtml::openTag('td',$options);
		$this->renderDataCellContent($row,$data);
		echo '</td>';
	}

	/**
	 * Генерирует ячейку-футер
	 */
	public function renderFooterCell()
	{
		echo CHtml::openTag('td',$this->footerHtmlOptions);
		$this->renderFooterCellContent();
		echo '</td>';
	}

	/**
	 * Генерирует содержимое ячейки-заголовка.
	 * Реализация по умолчанию просто выводит значение свойства {@link header}.
	 * Метод может быть переопределен для настройки генерации ячейки-заголовка
	 */
	protected function renderHeaderCellContent()
	{
		echo trim($this->header)!=='' ? $this->header : $this->grid->blankDisplay;
	}

	/**
	 * Генерирует содержимое ячейки-футера.
	 * Реализация по умолчанию просто выводит значение свойства {@link footer}.
	 * Метод может быть переопределен для настройки генерации ячейки-футера
	 */
	protected function renderFooterCellContent()
	{
		echo trim($this->footer)!=='' ? $this->footer : $this->grid->blankDisplay;
	}

	/**
	 * Генерирует содержимое ячейки данных.
	 * Метод ДОЛЖЕН быть переопределен для настройки генерации ячейки данных
	 * @param integer $row номер строки (начиная с нуля)
	 * @param mixed $data ассоциированные с ячейкой данные
	 */
	protected function renderDataCellContent($row,$data)
	{
		echo $this->grid->blankDisplay;
	}

	/**
	 * Генерирует содержимое ячейки-фильтра.
	 * Реализация по умолчанию просто выводит неразрывный пробел.
	 * Метод может быть переопределен для настройки генерации ячейки-фильтра (если требуется)
	 * @since 1.1.1
	 */
	protected function renderFilterCellContent()
	{
		echo $this->grid->blankDisplay;
	}
}
