<?php
/**
 * Файл класса CDataColumn.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.grid.CGridColumn');

/**
 * Класс CDataColumn представляет столбец таблицы, ассоциированный с данными атрибута или выражением.
 *
 * Должны быть определены свойства {@link name} или {@link value}. Первый определяет имя атрибута
 * данных, а второй - PHP-выражение, значение которого будет генерироваться вместо значения атрибута.
 *
 * Свойство {@link sortable} определяет, может ли таблица сортироваться по данному столбцу.
 * Примечание: свойство {@link name} всегда должно устанавливаться, если необходимо, чтобы столбец был
 * сортируемым. Значение свойства {@link name} будет использоваться классом {@link CSort} для генерации
 * ссылки в ячейке-заголовке таблицы для включения и переключения направления сортировки.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CDataColumn.php 3448 2011-11-18 10:21:42Z mdomba $
 * @package zii.widgets.grid
 * @since 1.1
 */
class CDataColumn extends CGridColumn
{
	/**
	 * @var string имя атрибута модели данных. Значение соответствующего атрибута будет генерироваться
	 * в каждой ячейке данных. Если определено свойство {@link value}, то данное свойство будет проигнорировано,
	 * если только столбец не должен быть сортируемым или фильтруемым
	 * @see value
	 * @see sortable
	 */
	/**
	 * @var string имя атрибута модели данных. Используется для сортировки
	 * столбца, фильтрации и генерации соответствующего значения каждой ячейки
	 * данных. Если определено свойство {@link value}, то оно будет
	 * использоваться для генерации данных ячейки вместо соответствующего
	 * значения атрибута
	 * @see value
	 * @see sortable
	 */
	public $name;
	/**
	 * @var string PHP-выражение, выполняемое для каждой ячейки данных, и результат которого будет генерироваться
	 * в качестве содержимого ячеек данных. В данном выражении переменная <code>$row</code> - это номер строки (отсчет с нуля),
	 * переменная <code>$data</code> - модель данных для строки, переменная <code>$this</code> - объект столбца
	 */
	public $value;
	/**
	 * @var string тип значения атрибута. Данное свойство определяет, как форматировать значение атрибута при выводе на экран.
	 * Валидные значения, распознаваемые {@link CGridView::formatter}: raw, text, ntext, html, date, time,
	 * datetime, boolean, number, email, image, url. За деталями обратитесь к классу {@link CFormatter}.
	 * По умолчанию - 'text', т.е., значение атрибута будет подвержено HTML-кодированию
	 */
	public $type='text';
	/**
	 * @var boolean сортируем ли столбец. Если да, то ячейка заголовка столбца будет содержать ссылку, по которой можно включить
	 * сортировку и переключить направление сортировки. По умолчанию - true. Примечание: если свойство {@link name} не установлено
	 * или недоступно из {@link CSort}, то данное свойство будет считаться свойством со значением false
	 * @see name
	 */
	public $sortable=true;
	/**
	 * @var mixed HTML-код, представляющий фильтр фходных данных (например, текстовое поле, выпадающий список),
	 * используемый для данного столбца. Данное свойство имеет значение только если свойство
	 * {@link CGridView::filter} установлено.
	 * Если данное свойство не установлено, будет сгенерировано текстовое поле.
	 * Если данное свойство является массивом, будет сгенерирован выпадающий список, использующий данное свойство в
	 * качестве списка опций. Если вы не хотите фильтровать содержимое данного столбца, установите свойства в
	 * значение false
	 * @since 1.1.1
	 */
	public $filter;

	/**
	 * Инициализирует столбец
	 */
	public function init()
	{
		parent::init();
		if($this->name===null)
			$this->sortable=false;
		if($this->name===null && $this->value===null)
			throw new CException(Yii::t('zii','Either "name" or "value" must be specified for CDataColumn.'));
	}

	/**
	 * Генерирует содержимое ячейки фильтра.
	 * Данный метод генерирует {@link filter} как если бы он был строкой.
	 * Если свойство {@link filter} - это массив, он считается списком опций и будет сгенерирова выпадающий список.
	 * В ином случае, если свойство {@link filter} не равно false, будет сгенерировано текстовое поле
	 * @since 1.1.1
	 */
	protected function renderFilterCellContent()
	{
		if(is_string($this->filter))
			echo $this->filter;
		else if($this->filter!==false && $this->grid->filter!==null && $this->name!==null && strpos($this->name,'.')===false)
		{
			if(is_array($this->filter))
				echo CHtml::activeDropDownList($this->grid->filter, $this->name, $this->filter, array('id'=>false,'prompt'=>''));
			else if($this->filter===null)
				echo CHtml::activeTextField($this->grid->filter, $this->name, array('id'=>false));
		}
		else
			parent::renderFilterCellContent();
	}

	/**
	 * Генерирует содержимое ячейки заголовка таблицы.
	 * Данный метод генерирует ссылку, по которой можно сортировать и переключать направление сортировки,
	 * если столбец является сортируемым
	 */
	protected function renderHeaderCellContent()
	{
		if($this->grid->enableSorting && $this->sortable && $this->name!==null)
			echo $this->grid->dataProvider->getSort()->link($this->name,$this->header);
		else if($this->name!==null && $this->header===null)
		{
			if($this->grid->dataProvider instanceof CActiveDataProvider)
				echo CHtml::encode($this->grid->dataProvider->model->getAttributeLabel($this->name));
			else
				echo CHtml::encode($this->name);
		}
		else
			parent::renderHeaderCellContent();
	}

	/**
	 * Генерирует содержимое ячейки данных.
	 * Данный метод оценивает содержимое свойства {@link value} или {@link name} и генерирует результат
	 * @param integer $row номер строки (начиная с нуля)
	 * @param mixed $data ассоциированные со строкой данные
	 */
	protected function renderDataCellContent($row,$data)
	{
		if($this->value!==null)
			$value=$this->evaluateExpression($this->value,array('data'=>$data,'row'=>$row));
		else if($this->name!==null)
			$value=CHtml::value($data,$this->name);
		echo $value===null ? $this->grid->nullDisplay : $this->grid->getFormatter()->format($value,$this->type);
	}
}
