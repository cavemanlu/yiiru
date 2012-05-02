<?php
/**
 * Файл класса CListView.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.CBaseListView');

/**
 * Виджет CListView отображает набор элементов данных в виде списка.
 *
 * В отличие от от виджета {@link CGridView}, который отображает данные в виде таблицы, виджет CListView позволяет
 * использовать шаблон представления для генерации каждого элемента данных. В результате, CListView может генерировать
 * более гибкий результат.
 *
 * Виджет CListView поддерживает как сортировку так и пагинацию элементов данных. Сортировка и
 * пагинация может использоваться как в AJAX-режиме, так и в обычном запросе. Преимущество использования виджета CListView
 * в том, что если в браузере пользователя отключен JavaScript, сортировка и пагинация автоматически переключится в режим
 * обычных запросов страниц и данный функционал будет также работать как ожидается.
 *
 * Виджет CListView должен использоваться вместе с {@link IDataProvider поставщиком данных (data provider)},
 * предпочтительно, с {@link CActiveDataProvider}.
 *
 * Минимальный требуемый для использования виджета CListView код:
 *
 * <pre>
 * $dataProvider=new CActiveDataProvider('Post');
 *
 * $this->widget('zii.widgets.CListView', array(
 *     'dataProvider'=>$dataProvider,
 *     'itemView'=>'_post',   // ссылается на частичное представление '_post'
 *     'sortableAttributes'=>array(
 *         'title',
 *         'create_time'=>'Post Time',
 *     ),
 * ));
 * </pre>
 *
 * Код выше сначала создает поставщик данных для ActiveRecord-класса <code>Post</code>.
 * Затем он использует виджет CListView для отображения каждого элемента данных, возвращаемых поставщиком данных.
 * Отображение происходит с помощью частичного представления '_post'. Данное представление генерируется один раз для
 * каждого элемента данных. В представлении есть доступ к данным текущего элемента через переменную <code>$data</code>.
 * За деталями обратитесь к описанию свойства {@link itemView}.
 *
 * Для поддержки сортировки нужно определить свойство {@link sortableAttributes}.
 * После этого будет отображен список ссылок, с помощью которых можно отсортировать данные.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CListView.php 3286 2011-06-16 17:34:34Z qiang.xue $
 * @package zii.widgets
 * @since 1.1
 */
class CListView extends CBaseListView
{
	/**
	 * @var string представление, используемое для генерации каждого элемента данных.
	 * Значение данного свойства будет передано первым параметром либо в метод {@link CController::renderPartial}
	 * либо в метод {@link CWidget::render} для генерации каждого элемента данных.
	 * В соответствующем шаблоне представления, в дополнение к переменным, определенным в свойстве {@link viewData}, доступны следующие переменные:
	 * <ul>
	 * <li><code>$this</code>: владелец (owner) данного виджета списка данных. Например, если виджет находится в представлении контроллера,
	 * то <code>$this</code> - это контроллер;</li>
	 * <li><code>$data</code>: элемент генерируемых в данный момент данных;</li>
	 * <li><code>$index</code>: индекс элемента генерируемых в данный момент данных (начинается с 0);</li>
	 * <li><code>$widget</code>: экземпляр данного виджета списка данных.</li>
	 * </ul>
	 */
	public $itemView;
	/**
	 * @var string HTML-код, отображаемый между двумя последовательными элементами
	 * @since 1.1.7
	 */
	public $separator;
	/**
	 * @var array дополнительные данные, передаваемые в представление {@link itemView} при генерации каждого элемента данных.
	 * Данный массив будет распакован в виде локальных переменных, к которым имеет доступ представление {@link itemView}
	 */
	public $viewData=array();
	/**
	 * @var array список имен атрибутов, которые можно сортировать. Для того, чтобы атрибут был сортируемым, он должен
	 * появиться как сортируемый атрибут в свойстве {@link IDataProvider::sort} {@link dataProvider поставщика данных}
	 * @see enableSorting
	 */
	public $sortableAttributes;
	/**
	 * @var string шаблон, используемый для контроля макета различных компонентов виджета.
	 * Распознаваемые метки: {summary}, {sorter}, {items} и {pager}. Они будут заменены общим текстом,
	 * ссылками сортировки, списком элементов с данными и пагинатором
	 */
	public $template="{summary}\n{sorter}\n{items}\n{pager}";
	/**
	 * @var string имя CSS-класса, который будет присвоен контейнеру виджета во время обновления
	 * виджета посредством AJAX-запроса. По умолчанию - 'list-view-loading'
	 * @since 1.1.1
	 */
	public $loadingCssClass='list-view-loading';
	/**
	 * @var string имя CSS-класса для контейнера сортировщика. По умолчанию - 'sorter'
	 */
	public $sorterCssClass='sorter';
	/**
	 * @var string текст, показываемый перед ссылками сортировки. По умолчанию - 'Sort by: '
	 */
	public $sorterHeader;
	/**
	 * @var string текст, показываемый после ссылок сортировки. По умолчанию пусто
	 */
	public $sorterFooter='';
	/**
	 * @var mixed идентификатор контейнера, контент которого может быть обновлен AJAX-ответом.
	 * По умолчанию - null, т.е., используется контейнер данного экземпляра виджета.
	 * Если установлен в значение false, то сортировка и пагинация будет выполняться в обычном режиме запросов
	 * вместо AJAX-запросов. Если сортировка и пагинация должны вызывать обновление содержимого нескольких
	 * контейнеров при AJAX-запросе, то идентификаторы данных контейнеров могут быть заданы здесь (разделенные запятыми)
	 */
	public $ajaxUpdate;
	/**
	 * @var string селектор jQuery для HTML-элементов, которые могут запускать AJAX-обновление при клике на них.
	 * Если не установлено, AJAX-обновление будут вызывать только ссылки пагинации и сортировки
	 * @since 1.1.7
	 */
	public $updateSelector;
	/**
	 * @var string имя GET-переменной, показывающей, что данный запрос является AJAX-запросом, вызванным
	 * виджетом. По умолчанию - 'ajax'. Имеет значение только при значении свойства {@link ajaxUpdate} не равном false
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
	 * @var string javascript-функция, которая будет вызываться перед выполнением AJAX-запроса.
	 * Описание функции: <code>function(id)</code>, где 'id' - идентификатор виджета
	 */
	public $beforeAjaxUpdate;
	/**
	 * @var string javascript-функция, которая будет вызываться после успешного выполнения AJAX-запроса.
	 * Описание функции: <code>function(id, data)</code>, где 'id' - идентификатор виджета, а
	 * 'data' - полученные в процессе ajax-запроса данные
	 */
	public $afterAjaxUpdate;
	/**
	 * @var string базовый URL-адрес для всех ресурсов виджета (javascript, CSS-файлов, изображений).
	 * По умолчанию - null, т.е., используются интегрированные ресурсы виджета (которые публикуются как assets-ресурсы)
	 */
	public $baseScriptUrl;
	/**
	 * @var string URL-адрес CSS-файла, используемого данным виджетом. По умолчанию - null, т.е. используется встроенный
	 * CSS-файл. Если установлено в значение false, подключение требуемого CSS-файла лежит на вашей ответственности
	 */
	public $cssFile;
	/**
	 * @var string имя HTML-тега для контейнера всех отображаемых данных. По умолчанию - 'div'
	 * @since 1.1.4
	 */
	public $itemsTagName='div';

	/**
	 * Инициализирует представление списка.
	 * Данный метод инициализирует требуемые свойства значениями и инстанцирует объекты {@link columns}
	 */
	public function init()
	{
		if($this->itemView===null)
			throw new CException(Yii::t('zii','The property "itemView" cannot be empty.'));
		parent::init();

		if(!isset($this->htmlOptions['class']))
			$this->htmlOptions['class']='list-view';

		if($this->baseScriptUrl===null)
			$this->baseScriptUrl=Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias('zii.widgets.assets')).'/listview';

		if($this->cssFile!==false)
		{
			if($this->cssFile===null)
				$this->cssFile=$this->baseScriptUrl.'/styles.css';
			Yii::app()->getClientScript()->registerCssFile($this->cssFile);
		}
	}

	/**
	 * Регистрирует неообходимые клиентские скрипты
	 */
	public function registerClientScript()
	{
		$id=$this->getId();

		if($this->ajaxUpdate===false)
			$ajaxUpdate=array();
		else
			$ajaxUpdate=array_unique(preg_split('/\s*,\s*/',$this->ajaxUpdate.','.$id,-1,PREG_SPLIT_NO_EMPTY));
		$options=array(
			'ajaxUpdate'=>$ajaxUpdate,
			'ajaxVar'=>$this->ajaxVar,
			'pagerClass'=>$this->pagerCssClass,
			'loadingClass'=>$this->loadingCssClass,
			'sorterClass'=>$this->sorterCssClass,
		);
		if($this->ajaxUrl!==null)
			$options['url']=CHtml::normalizeUrl($this->ajaxUrl);
		if($this->updateSelector!==null)
			$options['updateSelector']=$this->updateSelector;
		if($this->beforeAjaxUpdate!==null)
			$options['beforeAjaxUpdate']=(strpos($this->beforeAjaxUpdate,'js:')!==0 ? 'js:' : '').$this->beforeAjaxUpdate;
		if($this->afterAjaxUpdate!==null)
			$options['afterAjaxUpdate']=(strpos($this->afterAjaxUpdate,'js:')!==0 ? 'js:' : '').$this->afterAjaxUpdate;

		$options=CJavaScript::encode($options);
		$cs=Yii::app()->getClientScript();
		$cs->registerCoreScript('jquery');
		$cs->registerCoreScript('bbq');
		$cs->registerScriptFile($this->baseScriptUrl.'/jquery.yiilistview.js',CClientScript::POS_END);
		$cs->registerScript(__CLASS__.'#'.$id,"jQuery('#$id').yiiListView($options);");
	}

	/**
	 * Генерирует список элементов данных
	 */
	public function renderItems()
	{
		echo CHtml::openTag($this->itemsTagName,array('class'=>$this->itemsCssClass))."\n";
		$data=$this->dataProvider->getData();
		if(($n=count($data))>0)
		{
			$owner=$this->getOwner();
			$render=$owner instanceof CController ? 'renderPartial' : 'render';
			$j=0;
			foreach($data as $i=>$item)
			{
				$data=$this->viewData;
				$data['index']=$i;
				$data['data']=$item;
				$data['widget']=$this;
				$owner->$render($this->itemView,$data);
				if($j++ < $n-1)
					echo $this->separator;
			}
		}
		else
			$this->renderEmptyText();
		echo CHtml::closeTag($this->itemsTagName);
	}

	/**
	 * Генерирует сортировщик
	 */
	public function renderSorter()
	{
		if($this->dataProvider->getItemCount()<=0 || !$this->enableSorting || empty($this->sortableAttributes))
			return;
		echo CHtml::openTag('div',array('class'=>$this->sorterCssClass))."\n";
		echo $this->sorterHeader===null ? Yii::t('zii','Sort by: ') : $this->sorterHeader;
		echo "<ul>\n";
		$sort=$this->dataProvider->getSort();
		foreach($this->sortableAttributes as $name=>$label)
		{
			echo "<li>";
			if(is_integer($name))
				echo $sort->link($label);
			else
				echo $sort->link($name,$label);
			echo "</li>\n";
		}
		echo "</ul>";
		echo $this->sorterFooter;
		echo CHtml::closeTag('div');
	}
}
