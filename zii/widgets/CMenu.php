<?php
/**
 * Файл класса CMenu.
 *
 * @author Jonah Turnquist <poppitypop@gmail.com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Виджет CMenu отображает многоуровневое меню, ипользуя вложенные HTML-списки.
 *
 * Основной параметр виджета CMenu - это {@link items}, определеяющий возможные элементы меню.
 * Элемент меню имеет 3 главных свойства: visible, active и items. Свойство "visible" определяет,
 * должен ли элемент меню отображаться. Свойство "active" определяет, должен ли элемент меню
 * быть выделен (текущий ли он). Свойство "items" определяет элементы подменю.
 *
 * Следующий пример показывает, как использовать виджет CMenu:
 * <pre>
 * $this->widget('zii.widgets.CMenu', array(
 *     'items'=>array(
 *         // Важно: необходимо определить url-адрес как 'controller/action',
 *         // а не просто как 'controller' даже если используется действие по умолчанию.
 *         array('label'=>'Домашняя', 'url'=>array('site/index')),
 *         // элемент меню 'Товары' будет выбран независимо от того, каково значение параметра тега
 *         array('label'=>'Товары', 'url'=>array('product/index'), 'items'=>array(
 *             array('label'=>'Новые поступления', 'url'=>array('product/new', 'tag'=>'new')),
 *             array('label'=>'Наиболее популярные', 'url'=>array('product/index', 'tag'=>'popular')),
 *         )),
 *         array('label'=>'Вход', 'url'=>array('site/login'), 'visible'=>Yii::app()->user->isGuest),
 *     ),
 * ));
 * </pre>
 *
 *
 * @author Jonah Turnquist <poppitypop@gmail.com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CMenu.php 3520 2011-12-29 09:54:22Z mdomba $
 * @package zii.widgets
 * @since 1.1
 */
class CMenu extends CWidget
{
	/**
	 * @var array список элементов меню. Каждый элемент меню определяется массивом пар имя-значение.
	 * Возможные имена опций:
	 * <ul>
	 * <li>label: опциональная строка, определяющая имя элемента меню. Если свойство {@link encodeLabel} установлено
	 * в значение true, то имя подвергнется HTML-кодированию. Если имя элемента не определено, то по умолчанию оно
	 * устанавливается равным пустой строке;</li>
	 * <li>url: опциональная строка или массив. Определяет URL-адрес элемента меню. Передается в метод {@link CHtml::normalizeUrl}
	 * для генерации валидного URL-адреса. Если опция не установлена, элемент меню будет сгенерирован как текст;</li>
	 * <li>visible: опциональное булево значение, показывающее, видим ли данный элемент меню. По умолчанию - true.
	 * Может быть использовано для управления отображением элементов меню в зависимости от прав доступа пользователя;</li>
	 * <li>items: опциональный массив, определяющий элементы подменю. Формат такой же, как родительского элемента;</li>
	 * <li>active: опциональное булево значение, показывающее, активен ли элемент меню (выбран в данный момент).
	 * Если элемент меню активен и свойство {@link activeClass} не пусто, то в CSS-классы элемента добавится класс {@link activeClass}.
	 * Если опция не установлена, элемент меню будет установлен активным автоматически в случае, если текущий запрос
	 * вызван по ссылке {@link url}. Примечание: GET-параметры, не определенные в опции 'url', будут проигнорированы;</li>
	 * <li>template: опциональная строка, определяющая шаблон, используемый для генерации элемента меню.
	 * Если данная опция установлена, то она будет перезаписывать глобальные настройки свойства {@link itemTemplate}.
	 * За деталями обратитесь к описанию свойства {@link itemTemplate}. Данная опция доступна с версии 1.1.1;</li>
	 * <li>linkOptions: опциональный массив, определяющий дополнительные HTML-атрибуты, генерируемые в теге ссылки или теге 'span' элемента меню;</li>
	 * <li>itemOptions: опциональный массив, определяющий дополнительные HTML-атрибуты, генерируемые для тега контейнера элемента меню;</li>
	 * <li>submenuOptions: опциональный массив, определяющий дополнительные HTML-атрибуты, генерируемые контейнера подменю, если оно есть для элемента меню.
	 * Если данная опция установлена, свойство {@link submenuHtmlOptions} будет проигнорировано для данного частного подменю.
	 * Данная опция доступна с версии 1.1.6;</li>
	 * </ul>
	 */
	public $items=array();
	/**
	 * @var string шаблон, используемый для генерации отдельного элемента меню. В данном шаблоне
	 * метка "{menu}" будет заменена соответствующей ссылкой или текстом меню.
	 * Если данное свойство не установлео, каждое меню будет сгенерировано без какого-либо обрамления.
	 * Данное свойство будет перезаписано опцией 'template', установленной для отдельного элемента меню свойством {@items}
	 * @since 1.1.1
	 */
	public $itemTemplate;
	/**
	 * @var boolean проводить ли HTML-кодирование имен элементов меню. По умолчанию - true
	 */
	public $encodeLabel=true;
	/**
	 * @var string CSS-класс, добавляемый к активному элементу меню. По умолчанию - 'active'.
	 * Если значние пусто, то CSS-класс элементов меню не будет изменен
	 */
	public $activeCssClass='active';
	/**
	 * @var boolean активировать ли автоматически элементы соответственно настройкам их маршрутов,
	 * найденным в маршруте текущего запроса. По умолчанию - true
	 * @since 1.1.3
	 */
	public $activateItems=true;
	/**
	 * @var boolean активировать ли элемент родительского меню в случае, когда активирован
	 * один из соответствующих элементов подменю. Активированные элементы родительского меню
	 * также будут иметь их CSS-классы наряду с классом, записанным в свойстве {@link activeCssClass}.
	 * По умолчанию - false
	 */
	public $activateParents=false;
	/**
	 * @var boolean скрывать ли пустые элементы меню. Пустой элемент меню - это элемент с неустановленной опцией 'url'
	 * и который не содержит видимых элементов подменю. По умолчанию - true
	 */
	public $hideEmptyItems=true;
	/**
	 * @var array HTML-атрибуты для тега контейнера меню
	 */
	public $htmlOptions=array();
	/**
	 * @var array HTML-атрибуты для тега контейнера подменю
	 */
	public $submenuHtmlOptions=array();
	/**
	 * @var string имя HTML-элемента, используемое для обрамления имен всех ссылок меню.
	 * Например, если свойство установлено в значение 'span', то элемент меню будет сгенерирован как
	 * &lt;li&gt;&lt;a href="url"&gt;&lt;span&gt;label&lt;/span&gt;&lt;/a&gt;&lt;/li&gt;
	 * Это полезно при реализации меню с использованием техники слайдера (sliding).
	 * По умолчанию - null, т.е. обрамляющий тег не будет сгенерирован
	 * @since 1.1.4
	 */
	public $linkLabelWrapper;
	/**
	 * @var string CSS-класс первого элемента главного меню и каждого подменю.
	 * По умолчанию - null, т.е., данный класс не будет присвоен элементам
	 * @since 1.1.4
	 */
	public $firstItemCssClass;
	/**
	 * @var string CSS-класс последнего элемента главного меню и каждого подменю.
	 * По умолчанию - null, т.е., данный класс не будет присвоен элементам
	 * @since 1.1.4
	 */
	public $lastItemCssClass;
	/**
	 * @var string CSS-класс, присваиваемый каждому элементу. По умолчанию -
	 * null, т.е., данный класс не будет присвоен элементам
	 * @since 1.1.9
	 */
	public $itemCssClass;

	/**
	 * Инициализирует виджет меню.
	 * Главным образом метод нормализует свойство {@link items}.
	 * Если метод переопределяется, убедитесь, что родительская реализация вызывается
	 */
	public function init()
	{
		$this->htmlOptions['id']=$this->getId();
		$route=$this->getController()->getRoute();
		$this->items=$this->normalizeItems($this->items,$route,$hasActiveChild);
	}

	/**
	 * Вызывает метод {@link renderMenu} для генерации меню
	 */
	public function run()
	{
		$this->renderMenu($this->items);
	}

	/**
	 * Генерирует элементы меню
	 * @param array $items элементы меню. Каждый элемент меню - это массив, содержащий как минимум 2 элемента -
	 * 'label' и 'active'. Также могут быть дополнительные элементы 'items', 'linkOptions' и 'itemOptions'
	 */
	protected function renderMenu($items)
	{
		if(count($items))
		{
			echo CHtml::openTag('ul',$this->htmlOptions)."\n";
			$this->renderMenuRecursive($items);
			echo CHtml::closeTag('ul');
		}
	}

	/**
	 * Рекурсивно генерирует элементы меню
	 * @param array $items рекурсивно генерируемые элементы меню
	 */
	protected function renderMenuRecursive($items)
	{
		$count=0;
		$n=count($items);
		foreach($items as $item)
		{
			$count++;
			$options=isset($item['itemOptions']) ? $item['itemOptions'] : array();
			$class=array();
			if($item['active'] && $this->activeCssClass!='')
				$class[]=$this->activeCssClass;
			if($count===1 && $this->firstItemCssClass!==null)
				$class[]=$this->firstItemCssClass;
			if($count===$n && $this->lastItemCssClass!==null)
				$class[]=$this->lastItemCssClass;
			if($this->itemCssClass!==null)
				$class[]=$this->itemCssClass;
			if($class!==array())
			{
				if(empty($options['class']))
					$options['class']=implode(' ',$class);
				else
					$options['class'].=' '.implode(' ',$class);
			}

			echo CHtml::openTag('li', $options);

			$menu=$this->renderMenuItem($item);
			if(isset($this->itemTemplate) || isset($item['template']))
			{
				$template=isset($item['template']) ? $item['template'] : $this->itemTemplate;
				echo strtr($template,array('{menu}'=>$menu));
			}
			else
				echo $menu;

			if(isset($item['items']) && count($item['items']))
			{
				echo "\n".CHtml::openTag('ul',isset($item['submenuOptions']) ? $item['submenuOptions'] : $this->submenuHtmlOptions)."\n";
				$this->renderMenuRecursive($item['items']);
				echo CHtml::closeTag('ul')."\n";
			}

			echo CHtml::closeTag('li')."\n";
		}
	}

	/**
	 * Генерирует содержимое элемента меню.
	 * Примечание: контейнер меню и подменю не генерируются в данном методе
	 * @param array $item генерируемый элемент меню. Смотрите описание свойства {@link items}, чтобы узнать,
	 * какие данные могут находиться в элементе
	 * @return string
	 * @since 1.1.6
	 */
	protected function renderMenuItem($item)
	{
		if(isset($item['url']))
		{
			$label=$this->linkLabelWrapper===null ? $item['label'] : '<'.$this->linkLabelWrapper.'>'.$item['label'].'</'.$this->linkLabelWrapper.'>';
			return CHtml::link($label,$item['url'],isset($item['linkOptions']) ? $item['linkOptions'] : array());
		}
		else
			return CHtml::tag('span',isset($item['linkOptions']) ? $item['linkOptions'] : array(), $item['label']);
	}

	/**
	 * Нормализует свойство {@link items} так, чтобы состояние 'active' правильно идентифицировалось для каждого элемента меню
	 * @param array $items нормализуемый элемент
	 * @param string $route маршрут текущего запроса
	 * @param boolean $active есть ли активные элементы подменю текущего элемента
	 * @return array нормализованные элементы меню
	 */
	protected function normalizeItems($items,$route,&$active)
	{
		foreach($items as $i=>$item)
		{
			if(isset($item['visible']) && !$item['visible'])
			{
				unset($items[$i]);
				continue;
			}
			if(!isset($item['label']))
				$item['label']='';
			if($this->encodeLabel)
				$items[$i]['label']=CHtml::encode($item['label']);
			$hasActiveChild=false;
			if(isset($item['items']))
			{
				$items[$i]['items']=$this->normalizeItems($item['items'],$route,$hasActiveChild);
				if(empty($items[$i]['items']) && $this->hideEmptyItems)
				{
					unset($items[$i]['items']);
					if(!isset($item['url']))
					{
						unset($items[$i]);
						continue;
					}
				}
			}
			if(!isset($item['active']))
			{
				if($this->activateParents && $hasActiveChild || $this->activateItems && $this->isItemActive($item,$route))
					$active=$items[$i]['active']=true;
				else
					$items[$i]['active']=false;
			}
			else if($item['active'])
				$active=true;
		}
		return array_values($items);
	}

	/**
	 * Проверяет, активен ли элемент меню.
	 * Выполняется проверкой, что запрошенный URL-адрес сгенерирован опцией 'url' элемента меню.
	 * Примечание: GET-параметры, не определенные в опции 'url', будут проигнорированы
	 * @param array $item проверяемый элемент меню
	 * @param string $route маршрут текущего запроса
	 * @return boolean активен ли элемент меню
	 */
	protected function isItemActive($item,$route)
	{
		if(isset($item['url']) && is_array($item['url']) && !strcasecmp(trim($item['url'][0],'/'),$route))
		{
			if(count($item['url'])>1)
			{
				foreach(array_splice($item['url'],1) as $name=>$value)
				{
					if(!isset($_GET[$name]) || $_GET[$name]!=$value)
						return false;
				}
			}
			return true;
		}
		return false;
	}
}