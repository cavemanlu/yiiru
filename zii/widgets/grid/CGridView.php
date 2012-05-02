<?php
/**
 * Файл класса CGridView.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.CBaseListView');
Yii::import('zii.widgets.grid.CDataColumn');
Yii::import('zii.widgets.grid.CLinkColumn');
Yii::import('zii.widgets.grid.CButtonColumn');
Yii::import('zii.widgets.grid.CCheckBoxColumn');

/**
 * Виджет CGridView отображает список элементов данных в виде таблицы.
 *
 * Каждая строка таблицы прдеставляет данные одного элемента, а столбец обычно представляет
 * атрибут элемента (некоторые столбцы могут отображать результат сложного выражения или статический текст).
 *
 * CGridView поддерживает сортировку и пагинацию элементов данных. Сортировка и пагинация
 * могут работать как в режиме AJAX-запросов так и в режиме обычных запросов страниц. Преимущество использования CGridView
 * в том, что если в браузере пользователя отключен JavaScript, сортировка и пагинация автоматически используют
 * режим обычных запросов страниц и функционируют так, как ожидается.
 *
 * CGridView должен использоваться совместно с {@link IDataProvider поставщиком данных}, желательно, с
 * {@link CActiveDataProvider}.
 *
 * Минимальный код, требуемый для использования CGridView:
 *
 * <pre>
 * $dataProvider=new CActiveDataProvider('Post');
 *
 * $this->widget('zii.widgets.grid.CGridView', array(
 *     'dataProvider'=>$dataProvider,
 * ));
 * </pre>
 *
 * В коде выше сначала создается поставщик данных для ActiveRecord-класса <code>Post</code>.
 * Затем используется виджет CGridView для отображения каждого атрибута в каждом экземпляре <code>Post</code>.
 * Отображаемая таблица снабжается сортировкой и пагинацией.
 *
 * Для выборочного отображения атрибутов в различных форматах можно настроить свойство
 * {@link CGridView::columns}. Например, можно для отображения определить только атрибуты <code>title</code> и
 * <code>create_time</code>, а атрибут <code>create_time</code> должен быть отформатирован
 * в виде строки времени. Также можно отображать атрибуты связанных объектов с использованием точечного синтаксиса
 * как показано ниже:
 *
 * <pre>
 * $this->widget('zii.widgets.grid.CGridView', array(
 *     'dataProvider'=>$dataProvider,
 *     'columns'=>array(
 *         'title',          // отображает атрибут 'title'
 *         'category.name',  // отображает атрибут 'name' связанного объекта 'category'
 *         'content:html',   // отображает атрибут 'content' в виде очищенного HTML-кода
 *         array(            // отображает атрибут 'create_time' с использованием PHP-выражения
 *             'name'=>'create_time',
 *             'value'=>'date("M j, Y", $data->create_time)',
 *         ),
 *         array(            // отображает атрибут 'author.username' с использованием PHP-выражения
 *             'name'=>'authorName',
 *             'value'=>'$data->author->username',
 *         ),
 *         array(            // отображает столбец с кнопками просмотра (view), обновления (update) и удаления (delete)
 *             'class'=>'CButtonColumn',
 *         ),
 *     ),
 * ));
 * </pre>
 *
 * Обратитесь к описанию свойства {@link columns} за деталями о конфигурации данного свойства.
 *
 * @property boolean должна ли таблица генерировать подошву (футер). Возвращает
 * true, если в любом элементе свойства {@link columns} есть значение
 * {@link CGridColumn::hasFooter}, равное true
 * @property CFormatter $formatter экземпляр форматтера. По умолчанию - компонент приложения 'format'
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CGridView.php 3551 2012-02-02 12:45:25Z mdomba $
 * @package zii.widgets.grid
 * @since 1.1
 */
class CGridView extends CBaseListView
{
	const FILTER_POS_HEADER='header';
	const FILTER_POS_FOOTER='footer';
	const FILTER_POS_BODY='body';

	private $_formatter;
	/**
	 * @var array конфигурация столбцов таблицы. Каждый элемент массива представляет конфигурацию одного отдельного
	 * столбца таблицы и может быть строкой или массивом.
	 *
	 * Если столбец определяется строкой, то эта строка должна иметь формат "имя:тип:заголовок", где
	 * "тип" и "заголовок" - опциональны. В этом случае будет создан экземпляр класса {@link CDataColumn},
	 * свойства {@link CDataColumn::name}, {@link CDataColumn::type} и {@link CDataColumn::header}
	 * которого будут определены согласно заданной строке.
	 *
	 * Если столбец определяется массивом, он будет использоваться для создания экземпляра столбца таблицы.
	 * При этом элемент 'class' массива определяет класс столбца (по умолчанию - {@link CDataColumn}).
	 * В настоящий момент фреймворк предоставляет следующие классы столбцов: {@link CDataColumn},
	 * {@link CLinkColumn}, {@link CButtonColumn} и {@link CCheckBoxColumn}
	 */
	public $columns=array();
	/**
	 * @var array имена CSS-классов строк основной части таблицы. Если передано несколько имен CSS-классов,
	 * они будут присвоены строкам последовательно и циклически повторяясь. Свойство игнорируется, если
	 * установлено свойство {@link rowCssClassExpression}. По умолчанию - <code>array('odd', 'even')</code>
	 * @see rowCssClassExpression
	 */
	public $rowCssClass=array('odd','even');
	/**
	 * @var string PHP-выражение, вычисляемое для каждой строки основной части таблицы и результат которого используется
	 * в качестве имени CSS-класса для данной строки. В данном выражении доступны переменные: <code>$row</code> -
	 * номер строки (начиная с нуля), <code>$data</code> - модель данных, ассоциированных со строкой и
	 * <code>$this</code> - объект виджета
	 * @see rowCssClass
	 */
	public $rowCssClassExpression;
	/**
	 * @var boolean отображать ли таблицу в случае, если данных нет. По умолчанию - true.
	 * Значение свойства {@link emptyText} отображается при отсутствии реальных данных
	 */
	public $showTableOnEmpty=true;
	/**
	 * @var mixed идентификатор контейнера, содержимое которого может быть изменено при помощи AJAX-запроса.
	 * По умолчанию - null - обновляется контейнер данного экземпляра виджета.
	 * Если установлено в значение false, то сортировка и пагинация будут проходить в виде обычных запросов страниц
	 * вместо AJAX-запросов. Если сортировка и пагинация должны запускать обновление содержимого нескольких
	 * контейнеров в AJAX-виде, идентификаторы данных контейнеров должны быть перечислены в данном свойстве (разделенные запятыми)
	 */
	public $ajaxUpdate;
	/**
	 * @var string селектор jQuery для HTML-элементов, которые могут запускать AJAX-обновление при клике на них.
	 * Если не установлено, AJAX-обновление будут вызывать только ссылки пагинации и сортировки
	 * @since 1.1.7
	 */
	public $updateSelector;
	/**
	 * @var string javascript-функция, вызываемая в случае возникновения ошибки при AJAX-запросе обновления.
	 *
	 * Функция имеет вид <code>function(xhr, textStatus, errorThrown, errorMessage)</code>, где
	 * <ul>
	 * <li><code>xhr</code> - объект XMLHttpRequest;</li>
	 * <li><code>textStatus</code> - строка, описывающая тип возникшей ошибки. Возможные
	 * значения (кроме null): "timeout", "error", "notmodified" и "parsererror";</li>
	 * <li><code>errorThrown</code> - опциональный объект исключения, в случае его возникновения;</li>
	 * <li><code>errorMessage</code> - сообщение виджета CGridView об ошибке по умолчанию, производная от объектов xhr и errorThrown.
	 * Полезно, если хочется просто отобразить ошибку по-другому. Виджет CGridView по умолчанию ошибку при помощи javascript.alert().</li>
	 * </ul>
	 * Примечание: данный обработчик не вызывается для JSONP-запросов, т.к. они не используют XMLHttpRequest.
	 *
	 * Например (добавление вызова в виджет CGridView):
	 * <pre>
	 *  ...
	 *  'ajaxUpdateError'=>'function(xhr,ts,et,err){ $("#myerrordiv").text(err); }',
	 *  ...
	 * </pre>
	 */
	public $ajaxUpdateError;
	/**
	 * @var string имя GET-переменной, показывающей, что запрос является AJAX-запросом, вызванным данным
	 * виджетом. По умолчанию - 'ajax'. Имеет значение только если свойство {@link ajaxUpdate} не равно false
	 */
	public $ajaxVar='ajax';
	/**
	 * @var mixed URL-адрес, на который должны посылаться AJAX-запросы. Для
	 * данного свойства будет вызван метод {@link CHtml::normalizeUrl()}. Если
	 * не установлено, то для AJAX-запросов будет использоваться URL-адрес
	 * текущей страницы
	 * @since 1.1.8
	 */
	public $ajaxUrl;
	/**
	 * @var string javascript function-функция, вызываемая перед выполнением AJAX-запроса на обновление.
	 * Вид функции - <code>function(id,options)</code>, где 'id' - идентификатор виджета, а
	 * 'options' - опции AJAX-запроса (см. документацию по API для jQuery.ajax)
	 */
	public $beforeAjaxUpdate;
	/**
	 * @var string javascript-функция, вызываемая после успешного получения результата AJAX-запроса.
	 * Вид функции - <code>function(id, data)</code>, где 'id' - идентификатор виджета таблицы, а
	 * 'data' - полученные в результате ajax-запроса данные
	 */
	public $afterAjaxUpdate;
	/**
	 * @var string javascript-функция, вызываемая после изменения списка выбранных строк.
	 * Вид функции - <code>function(id)</code>, где 'id' - идентификатор виджета таблицы.
	 * В данной функции можно использовать функцию <code>$.fn.yiiGridView.getSelection(id)</code> для получения значений
	 * ключей выбранных строк
	 * @see selectableRows
	 */
	public $selectionChanged;
	/**
	 * @var integer количество доступных для выделения строк таблицы. Если равно 0, то ни одна строка не может быть выбрана;
	 * если 1 - только 1 строка может быть выбрана; если 2 и больше - могут быть выбраны несколько строк.
	 * Выбранная строка будет иметь CSS-класс с именем 'selected'. Также можно вызвать JavaScript-функцию
	 * <code>$.fn.yiiGridView.getSelection(containerID)</code> для получения значений ключей выбранных строк
	 */
	public $selectableRows=1;
	/**
	 * @var string базовый URL-адрес для всех ресурсов виджета (например, javascript, CSS-файлы, изображения).
	 * По умолчанию - null, т.е., используются встроенные ресурсы (которые публикуются как веб-ресурсы)
	 */
	public $baseScriptUrl;
	/**
	 * @var string URL-адрес CSS-файла, используемого данным виджетом. По умолчанию - null, т.е., используется
	 * встроенный CSS-файл. Если установлено в значение false, необходимо самостоятельно явно включить требуемый
	 * CSS-файл в страницу
	 */
	public $cssFile;
	/**
	 * @var string текст, отображаемый в ячейке данных, если значение равно null. Данное свойство
	 * НЕ будет проходить HTML-кодирование при генерации. По умолчанию - HTML-пробел (nbsp)
	 */
	public $nullDisplay='&nbsp;';
	/**
	 * @var string текст, отображаемый в пустой ячейки таблицы. Свойство НЕ проходит HTML-кодирование при генерации.
	 * По умолчанию - неразрывный пробел. Отличается от свойства {@link nullDisplay}, тем, что {@link nullDisplay}
	 * используется для генерации нулевых значений классом {@link CDataColumn}
	 * @since 1.1.7
	 */
	public $blankDisplay='&nbsp;';
	/**
	 * @var string имя CSS-класса, присваемого элементу контейнера виджета при
	 * обновлении содержимого виджета AJAX-запросом. По умолчанию - 'grid-view-loading'
	 * @since 1.1.1
	 */
	public $loadingCssClass='grid-view-loading';
	/**
	 * @var string имя CSS-класса для элемента строки таблицы, содержащим все поля ввода фильтров. По умолчанию - 'filters'
	 * @see filter
	 * @since 1.1.1
	 */
	public $filterCssClass='filters';
	/**
	 * @var string отображать ли фильтры таблицы. Допустимые значения:
	 * <ul>
	 *    <li>header: фильтры отображаются наверху каждого столбца в ячейке-заголовке;</li>
	 *    <li>body: фильтры отображаются сразу за ячейкой-заголовком;</li>
	 *    <li>footer: фильтры отображаются ниже ячейки-футера.</li>
	 * </ul>
	 * @see filter
	 * @since 1.1.1
	 */
	public $filterPosition='body';
	/**
	 * @var CModel экземпляр модели, содержащей введенные пользователем фильтрующие данные. Если данное свойство установлено,
	 * таблица активирует фильтрацию для данного столбца. Каждый столбец данных по умолчанию отображает наверху таблицы текстовое поле,
	 * которое пользователь может заполнить фильтрующими данными. Примечание: для того, чтобы показать поле ввода
	 * для фильтрации, столбец должен иметь установленное свойство {@link CDataColumn::name} или
	 * свойство {@link CDataColumn::filter} в виде HTML-кода поля ввода.
	 * Фильтрация отключена, когда данное свойство не установлено (null)
	 * @since 1.1.1
	 */
	public $filter;
	/**
	 * @var boolean скрывать ли ячейки-заголовки таблицы. Если установлено в значение true, ячейки-заголовки
	 * не будут генерироваться, т.е., таблицу нельзя будет отсортировать, т.к. ссылки для сортировки находятся
	 * в заголовке. По умолчанию - false
	 * @since 1.1.1
	 */
	public $hideHeader=false;

	/**
	 * Инициализирует таблицу.
	 * Данный метод инициализирует значениями требуемые свойства и инстанцирует объекты {@link columns}
	 */
	public function init()
	{
		parent::init();

		if(!isset($this->htmlOptions['class']))
			$this->htmlOptions['class']='grid-view';

		if($this->baseScriptUrl===null)
			$this->baseScriptUrl=Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias('zii.widgets.assets')).'/gridview';

		if($this->cssFile!==false)
		{
			if($this->cssFile===null)
				$this->cssFile=$this->baseScriptUrl.'/styles.css';
			Yii::app()->getClientScript()->registerCssFile($this->cssFile);
		}

		$this->initColumns();
	}

	/**
	 * Создает объекты столбцов и инициализирует их
	 */
	protected function initColumns()
	{
		if($this->columns===array())
		{
			if($this->dataProvider instanceof CActiveDataProvider)
				$this->columns=$this->dataProvider->model->attributeNames();
			else if($this->dataProvider instanceof IDataProvider)
			{
				// use the keys of the first row of data as the default columns
				$data=$this->dataProvider->getData();
				if(isset($data[0]) && is_array($data[0]))
					$this->columns=array_keys($data[0]);
			}
		}
		$id=$this->getId();
		foreach($this->columns as $i=>$column)
		{
			if(is_string($column))
				$column=$this->createDataColumn($column);
			else
			{
				if(!isset($column['class']))
					$column['class']='CDataColumn';
				$column=Yii::createComponent($column, $this);
			}
			if(!$column->visible)
			{
				unset($this->columns[$i]);
				continue;
			}
			if($column->id===null)
				$column->id=$id.'_c'.$i;
			$this->columns[$i]=$column;
		}

		foreach($this->columns as $column)
			$column->init();
	}

	/**
	 * Создает {@link CDataColumn} основываясь на строке спецификации столбца
	 * @param string $text строка спецификации столбца
	 * @return CDataColumn экземпляр столбца
	 */
	protected function createDataColumn($text)
	{
		if(!preg_match('/^([\w\.]+)(:(\w*))?(:(.*))?$/',$text,$matches))
			throw new CException(Yii::t('zii','The column must be specified in the format of "Name:Type:Label", where "Type" and "Label" are optional.'));
		$column=new CDataColumn($this);
		$column->name=$matches[1];
		if(isset($matches[3]) && $matches[3]!=='')
			$column->type=$matches[3];
		if(isset($matches[5]))
			$column->header=$matches[5];
		return $column;
	}

	/**
	 * Регистрирует требуемые клиентские скрипты
	 */
	public function registerClientScript()
	{
		$id=$this->getId();

		if($this->ajaxUpdate===false)
			$ajaxUpdate=false;
		else
			$ajaxUpdate=array_unique(preg_split('/\s*,\s*/',$this->ajaxUpdate.','.$id,-1,PREG_SPLIT_NO_EMPTY));
		$options=array(
			'ajaxUpdate'=>$ajaxUpdate,
			'ajaxVar'=>$this->ajaxVar,
			'pagerClass'=>$this->pagerCssClass,
			'loadingClass'=>$this->loadingCssClass,
			'filterClass'=>$this->filterCssClass,
			'tableClass'=>$this->itemsCssClass,
			'selectableRows'=>$this->selectableRows,
		);
		if($this->ajaxUrl!==null)
			$options['url']=CHtml::normalizeUrl($this->ajaxUrl);
		if($this->updateSelector!==null)
			$options['updateSelector']=$this->updateSelector;
		if($this->enablePagination)
			$options['pageVar']=$this->dataProvider->getPagination()->pageVar;
		if($this->beforeAjaxUpdate!==null)
			$options['beforeAjaxUpdate']=(strpos($this->beforeAjaxUpdate,'js:')!==0 ? 'js:' : '').$this->beforeAjaxUpdate;
		if($this->afterAjaxUpdate!==null)
			$options['afterAjaxUpdate']=(strpos($this->afterAjaxUpdate,'js:')!==0 ? 'js:' : '').$this->afterAjaxUpdate;
		if($this->ajaxUpdateError!==null)
			$options['ajaxUpdateError']=(strpos($this->ajaxUpdateError,'js:')!==0 ? 'js:' : '').$this->ajaxUpdateError;
		if($this->selectionChanged!==null)
			$options['selectionChanged']=(strpos($this->selectionChanged,'js:')!==0 ? 'js:' : '').$this->selectionChanged;

		$options=CJavaScript::encode($options);
		$cs=Yii::app()->getClientScript();
		$cs->registerCoreScript('jquery');
		$cs->registerCoreScript('bbq');
		$cs->registerScriptFile($this->baseScriptUrl.'/jquery.yiigridview.js',CClientScript::POS_END);
		$cs->registerScript(__CLASS__.'#'.$id,"jQuery('#$id').yiiGridView($options);");
	}

	/**
	 * Генерирует элементы данных для таблицы
	 */
	public function renderItems()
	{
		if($this->dataProvider->getItemCount()>0 || $this->showTableOnEmpty)
		{
			echo "<table class=\"{$this->itemsCssClass}\">\n";
			$this->renderTableHeader();
			ob_start();
			$this->renderTableBody();
			$body=ob_get_clean();
			$this->renderTableFooter();
			echo $body; // TFOOT must appear before TBODY according to the standard.
			echo "</table>";
		}
		else
			$this->renderEmptyText();
	}

	/**
	 * Генерирует заголовок таблицы
	 */
	public function renderTableHeader()
	{
		if(!$this->hideHeader)
		{
			echo "<thead>\n";

			if($this->filterPosition===self::FILTER_POS_HEADER)
				$this->renderFilter();

			echo "<tr>\n";
			foreach($this->columns as $column)
				$column->renderHeaderCell();
			echo "</tr>\n";

			if($this->filterPosition===self::FILTER_POS_BODY)
				$this->renderFilter();

			echo "</thead>\n";
		}
		else if($this->filter!==null && ($this->filterPosition===self::FILTER_POS_HEADER || $this->filterPosition===self::FILTER_POS_BODY))
		{
			echo "<thead>\n";
			$this->renderFilter();
			echo "</thead>\n";
		}
	}

	/**
	 * Генерирует фильтр
	 * @since 1.1.1
	 */
	public function renderFilter()
	{
		if($this->filter!==null)
		{
			echo "<tr class=\"{$this->filterCssClass}\">\n";
			foreach($this->columns as $column)
				$column->renderFilterCell();
			echo "</tr>\n";
		}
	}

	/**
	 * Генерирует подошву (футер) таблицы
	 */
	public function renderTableFooter()
	{
		$hasFilter=$this->filter!==null && $this->filterPosition===self::FILTER_POS_FOOTER;
		$hasFooter=$this->getHasFooter();
		if($hasFilter || $hasFooter)
		{
			echo "<tfoot>\n";
			if($hasFooter)
			{
				echo "<tr>\n";
				foreach($this->columns as $column)
					$column->renderFooterCell();
				echo "</tr>\n";
			}
			if($hasFilter)
				$this->renderFilter();
			echo "</tfoot>\n";
		}
	}

	/**
	 * Генерирует основную часть таблицы (тело)
	 */
	public function renderTableBody()
	{
		$data=$this->dataProvider->getData();
		$n=count($data);
		echo "<tbody>\n";

		if($n>0)
		{
			for($row=0;$row<$n;++$row)
				$this->renderTableRow($row);
		}
		else
		{
			echo '<tr><td colspan="'.count($this->columns).'">';
			$this->renderEmptyText();
			echo "</td></tr>\n";
		}
		echo "</tbody>\n";
	}

	/**
	 * Генерирует строку основной части таблицы
	 * @param integer $row номер строки (начиная с нуля)
	 */
	public function renderTableRow($row)
	{
		if($this->rowCssClassExpression!==null)
		{
			$data=$this->dataProvider->data[$row];
			echo '<tr class="'.$this->evaluateExpression($this->rowCssClassExpression,array('row'=>$row,'data'=>$data)).'">';
		}
		else if(is_array($this->rowCssClass) && ($n=count($this->rowCssClass))>0)
			echo '<tr class="'.$this->rowCssClass[$row%$n].'">';
		else
			echo '<tr>';
		foreach($this->columns as $column)
			$column->renderDataCell($row);
		echo "</tr>\n";
	}

	/**
	 * @return boolean должна ли таблица генерировать подошву (футер).
	 * Возвращает true, если в любом элементе свойства {@link columns} есть
	 * значение {@link CGridColumn::hasFooter}, равное true
	 */
	public function getHasFooter()
	{
		foreach($this->columns as $column)
			if($column->getHasFooter())
				return true;
		return false;
	}

	/**
	 * @return CFormatter экземпляр форматтера. По умолчанию - компонент приложения 'format'
	 */
	public function getFormatter()
	{
		if($this->_formatter===null)
			$this->_formatter=Yii::app()->format;
		return $this->_formatter;
	}

	/**
	 * @param CFormatter $value экземпляр форматтера
	 */
	public function setFormatter($value)
	{
		$this->_formatter=$value;
	}
}
