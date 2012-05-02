<?php
/**
 * Файл класса CBaseListView.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * CBaseListView - это базовый класс для классов {@link CListView} и {@link CGridView}.
 *
 * CBaseListView реализует общие функции, требуемые отображаемым виджетом для генерации отображаемого контента нескольких моделей.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CBaseListView.php 3101 2011-03-22 17:35:19Z qiang.xue $
 * @package zii.widgets
 * @since 1.1
 */
abstract class CBaseListView extends CWidget
{
	/**
	 * @var IDataProvider поставщик данных для представления
	 */
	public $dataProvider;
	/**
	 * @var string имя тега для контейнера представления. По умолчанию - 'div'
	 */
	public $tagName='div';
	/**
	 * @var array HTML-опции для тега контейнера представления
	 */
	public $htmlOptions=array();
	/**
	 * @var boolean включена ли сортировка. Примечание: если свойство {@link IDataProvider::sort}
	 * {@link dataProvider поставщика данных} имеет значение false, то данное свойство также примет значение false.
	 * При включенной сортировке сортируемые столбцы имеют кликабельные заголовки для включения и переключения
	 * направления сортировки данного столбца. По умолчанию - true
	 * @see sortableAttributes
	 */
	public $enableSorting=true;
	/**
	 * @var boolean включен ли пагинатор. Примечание: если свойство {@link IDataProvider::pagination}
	 * {@link dataProvider поставщика данных} имеет значение false, то данное свойство также примет значение false.
	 * При включенной пагинации в представлении будет отображен пагинатор, позволяющий перелистывать отображаемые данные.
	 * По умолчанию - true
	 */
	public $enablePagination=true;
	/**
	 * @var array конфигурация пагинатора. По умолчанию - <code>array('class'=>'CLinkPager')</code>
	 * @see enablePagination
	 */
	public $pager=array('class'=>'CLinkPager');
	/**
	 * @var string шаблон, используемый для контроля разметки различных разделов представления.
	 * Распознаваемые метки: {summary}, {items} и {pager}. Они будут заменены общим текстом,
	 * элементами и пагинатором соответственно
	 */
	public $template="{summary}\n{items}\n{pager}";
	/**
	 * @var string шаблон общего текста для представления. Данные метки распознаются и заменяются
	 * соответствующими значениями:
	 * <ul>
	 *   <li>{start}: начальный номер отображаемых строк (начиная с 1);</li>
	 *   <li>{end}: конечный номер отображаемых строк (начиная с 1);</li>
	 *   <li>{count}: общее количество строк;</li>
	 *   <li>{page}: номер текущей страницы (начиная с 1), доступно с версии 1.1.3;</li>
	 *   <li>{pages}: общее количество страниц, доступно с версии 1.1.3.</li>
	 * </ul>
	 */
	public $summaryText;
	/**
	 * @var string сообщение, отображаемое в случае, когда {@link dataProvider поставщик данных} не содержит каких-либо данных
	 */
	public $emptyText;
	/**
	 * @var string имя CSS-класса для контейнера для отображения всех данных элементов. По умолчанию - 'items'
	 */
	public $itemsCssClass='items';
	/**
	 * @var string имя CSS-класса для контейнера общего текста. По умолчанию - 'summary'
	 */
	public $summaryCssClass='summary';
	/**
	 * @var string имя CSS-класса для контейнера пагинатора. По умолчанию - 'pager'
	 */
	public $pagerCssClass='pager';
	/**
	 * @var string имя CSS-класса, который будет присвоен элементу контейнера виджета
	 * при обновлении содержимого виджета AJAX-запросом. По умолчанию - 'loading'
	 * @since 1.1.1
	 */
	public $loadingCssClass='loading';

	/**
	 * Инициализирует представление.
	 * Данный метод инициализирует свойства требуемыми значениями и инстанцирует объекты свойства {@link columns}
	 */
	public function init()
	{
		if($this->dataProvider===null)
			throw new CException(Yii::t('zii','The "dataProvider" property cannot be empty.'));

		$this->dataProvider->getData();

		$this->htmlOptions['id']=$this->getId();

		if($this->enableSorting && $this->dataProvider->getSort()===false)
			$this->enableSorting=false;
		if($this->enablePagination && $this->dataProvider->getPagination()===false)
			$this->enablePagination=false;
	}

	/**
	 * Генерирует представление.
	 * Это главная точка начала генерации всего представления.
	 * Классы-потомки главным образом должны переопределять метод {@link renderContent}
	 */
	public function run()
	{
		$this->registerClientScript();

		echo CHtml::openTag($this->tagName,$this->htmlOptions)."\n";

		$this->renderContent();
		$this->renderKeys();

		echo CHtml::closeTag($this->tagName);
	}

	/**
	 * Генерирует основное содержимое представления.
	 * Содержимое делится на разделы такие, как summary, items, pager.
	 * Каждый раздел генерируется методом с именем "renderXyz", где "Xyz" - имя раздела.
	 * Результат генерации будет заменять соответствующую метку в шаблоне {@link template}
	 */
	public function renderContent()
	{
		ob_start();
		echo preg_replace_callback("/{(\w+)}/",array($this,'renderSection'),$this->template);
		ob_end_flush();
	}

	/**
	 * Генерирует раздел.
	 * Данный метод вызывается методом {@link renderContent} для каждой метки, найденной в шаблоне {@link template}.
	 * Метод должен возвращать результат генерации, который может заменить метку
	 * @param array $matches массив совпадений, где $matches[0] представляет всю метку,
	 * а $matches[1] содержит имя метки
	 * @return string результат генерации раздела
	 */
	protected function renderSection($matches)
	{
		$method='render'.$matches[1];
		if(method_exists($this,$method))
		{
			$this->$method();
			$html=ob_get_contents();
			ob_clean();
			return $html;
		}
		else
			return $matches[0];
	}

	/**
	 * Генерирует сообщение о пустом результате при отсутствии данных
	 */
	public function renderEmptyText()
	{
		$emptyText=$this->emptyText===null ? Yii::t('zii','No results found.') : $this->emptyText;
		echo CHtml::tag('span', array('class'=>'empty'), $emptyText);
	}

	/**
	 * Генерирует значения ключей данных в скрытые теги
	 */
	public function renderKeys()
	{
		echo CHtml::openTag('div',array(
			'class'=>'keys',
			'style'=>'display:none',
			'title'=>Yii::app()->getRequest()->getUrl(),
		));
		foreach($this->dataProvider->getKeys() as $key)
			echo "<span>".CHtml::encode($key)."</span>";
		echo "</div>\n";
	}

	/**
	 * Генерирует общий текст
	 */
	public function renderSummary()
	{
		if(($count=$this->dataProvider->getItemCount())<=0)
			return;

		echo '<div class="'.$this->summaryCssClass.'">';
		if($this->enablePagination)
		{
			if(($summaryText=$this->summaryText)===null)
				$summaryText=Yii::t('zii','Displaying {start}-{end} of {count} result(s).');
			$pagination=$this->dataProvider->getPagination();
			$total=$this->dataProvider->getTotalItemCount();
			$start=$pagination->currentPage*$pagination->pageSize+1;
			$end=$start+$count-1;
			if($end>$total)
			{
				$end=$total;
				$start=$end-$count+1;
			}
			echo strtr($summaryText,array(
				'{start}'=>$start,
				'{end}'=>$end,
				'{count}'=>$total,
				'{page}'=>$pagination->currentPage+1,
				'{pages}'=>$pagination->pageCount,
			));
		}
		else
		{
			if(($summaryText=$this->summaryText)===null)
				$summaryText=Yii::t('zii','Total {count} result(s).');
			echo strtr($summaryText,array(
				'{count}'=>$count,
				'{start}'=>1,
				'{end}'=>$count,
				'{page}'=>1,
				'{pages}'=>1,
			));
		}
		echo '</div>';
	}

	/**
	 * Генерирует пагинатор
	 */
	public function renderPager()
	{
		if(!$this->enablePagination)
			return;

		$pager=array();
		$class='CLinkPager';
		if(is_string($this->pager))
			$class=$this->pager;
		else if(is_array($this->pager))
		{
			$pager=$this->pager;
			if(isset($pager['class']))
			{
				$class=$pager['class'];
				unset($pager['class']);
			}
		}
		$pager['pages']=$this->dataProvider->getPagination();

		if($pager['pages']->getPageCount()>1)
		{
			echo '<div class="'.$this->pagerCssClass.'">';
			$this->widget($class,$pager);
			echo '</div>';
		}
		else
			$this->widget($class,$pager);
	}

	/**
	 * Регистрирует требуемые клиентские скрипты.
	 * Метод вызывается методом {@link run}.
	 * Классы-потомки должны переопределить данный метод для регистрации настроенных клиентских скриптов
	 */
	public function registerClientScript()
	{
	}

	/**
	 * Генерирует элементы данных для представления.
	 * Каждый элемент соответствует одному экземпляру модели данных.
	 * Классы-потомки должны переопределить данный метод для обеспечения правильной логики генерации
	 */
	abstract public function renderItems();
}
