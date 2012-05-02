<?php
/**
 * Файл класса CLinkColumn.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.grid.CGridColumn');

/**
 * Класс CLinkColumn представляет столбец таблицы, генерирующий ссылку в каждой ячейке данных таблицы.
 *
 * Свойства {@link label} и {@link url} определяют, как будет генерироваться каждая ссылка.
 * Свойства {@link labelExpression} и {@link urlExpression}, если они заданы, могут использоваться вместо предудыщих.
 * В дополнение, если установлено свойство {@link imageUrl}, то будет сгенерирована ссылка-изображение.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CLinkColumn.php 3424 2011-10-24 20:13:19Z mdomba $
 * @package zii.widgets.grid
 * @since 1.1
 */
class CLinkColumn extends CGridColumn
{
	/**
	 * @var string имя ссылки ячеек данных. Примечание: имя ссылки не будет подвергаться
	 * HTML-кодированию при генерации. Данное свойство игнорируется, если свойство {@link labelExpression} установлено
	 * @see labelExpression
	 */
	public $label='Link';
	/**
	 * @var string PHP-выражение, которое будет выполняться для каждой ячейки данных и результат которого будет генерироваться в
	 * качестве имени ссылки ячеек данных. В данном выражении доступны переменные:
	 * <code>$row</code> - номер строк (начиная с нуля), <code>$data</code> - модель данных для строки и
	 * <code>$this</code> - объект столбца
	 */
	public $labelExpression;
	/**
	 * @var string URL-адрес изображения. Если данное свойство установлено, то будет генерироваться ссылка-изображение
	 */
	public $imageUrl;
	/**
	 * @var string URL-адрес ссылки ячеек данных. Данное свойство
	 * игнорируется, если свойство {@link urlExpression} установлено
	 * @see urlExpression
	 */
	public $url='javascript:void(0)';
	/**
	 * @var string PHP-выражение, которое будет выполняться для каждой ячейки данных и результат которого будет генерироваться в
	 * качестве URL-адреса ссылки в ячейках данных. В данном выражении доступны переменные:
	 * <code>$row</code> - номер строк (начиная с нуля), <code>$data</code> - модель данных для строки и
	 * <code>$this</code> - объект столбца
	 */
	public $urlExpression;
	/**
	 * @var array HTML-опции для тега ячеек данных
	 */
	public $htmlOptions=array('class'=>'link-column');
	/**
	 * @var array HTML-опции для тега ячеек заголовка таблицы (header)
	 */
	public $headerHtmlOptions=array('class'=>'link-column');
	/**
	 * @var array HTML-опции для тега ячеек подошвы таблицы (footer)
	 */
	public $footerHtmlOptions=array('class'=>'link-column');
	/**
	 * @var array HTML-опции ссылок
	 */
	public $linkHtmlOptions=array();

	/**
	 * Генерирует содержимое ячейки данных.
	 * Данный метод генерирует ссылку в ячейке данных.
	 * @param integer $row номер строки (начиная с нуля)
	 * @param mixed $data ассоциированные со строкой данные
	 */
	protected function renderDataCellContent($row,$data)
	{
		if($this->urlExpression!==null)
			$url=$this->evaluateExpression($this->urlExpression,array('data'=>$data,'row'=>$row));
		else
			$url=$this->url;
		if($this->labelExpression!==null)
			$label=$this->evaluateExpression($this->labelExpression,array('data'=>$data,'row'=>$row));
		else
			$label=$this->label;
		$options=$this->linkHtmlOptions;
		if(is_string($this->imageUrl))
			echo CHtml::link(CHtml::image($this->imageUrl,$label),$url,$options);
		else
			echo CHtml::link($label,$url,$options);
	}
}
