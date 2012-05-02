<?php
/**
 * Файл класса CBreadcrumbs.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Виджет CBreadcrumbs отображает список ссылок, показывающих местоположение текущей страницы на всем сайте.
 *
 * Например, "хлебные крошки" вида "Домашняя > Заметка для примера > Редактирование" означают,
 * что пользователь видит страницу редактирования для "Заметки для примера".
 * Он может кликнуть на ссылку "Заметка для примера", чтобы посмотреть соответствующую страницу страницу, или
 * на ссылку "Домашняя" для возврата на начальную страницу.
 *
 * Для использования виджета CBreadcrumbs обычно необходимо настроить свойство {@link links}, определяющее
 * отображаемые ссылки. Например,
 *
 * <pre>
 * $this->widget('zii.widgets.CBreadcrumbs', array(
 *     'links'=>array(
 *         'Заметка для примера'=>array('post/view', 'id'=>12),
 *         'Редактирование',
 *     ),
 * ));
 * </pre>
 *
 * Т.к. "хлебные крошки" появляются практически на всех страницах сайта, лучше всего помещать виджет
 * в представлении макета. Можно определить свойство "breadcrumbs" в базовом классе контроллеров и привязать его к виджету
 * в макете, как показано ниже:
 *
 * <pre>
 * $this->widget('zii.widgets.CBreadcrumbs', array(
 *     'links'=>$this->breadcrumbs,
 * ));
 * </pre>
 *
 * Затем, в каждом скрипте представления нужно только привязать свойство "breadcrumbs" как это требуется.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CBreadcrumbs.php 3602 2012-02-19 22:08:48Z qiang.xue $
 * @package zii.widgets
 * @since 1.1
 */
class CBreadcrumbs extends CWidget
{
	/**
	 * @var string имя тега для контейнера "хлебных крошек". По умолчанию- 'div'
	 */
	public $tagName='div';
	/**
	 * @var array HTML-атрибуты для контейнера "хлебных крошек"
	 */
	public $htmlOptions=array('class'=>'breadcrumbs');
	/**
	 * @var boolean проводить ли HTML-кодирование имен ссылок. По умолчанию - true
	 */
	public $encodeLabel=true;
	/**
	 * @var string первая ссылка "хлебных крошек" (домашняя ссылка).
	 * Если данное свойство не установлено, то по умолчанию используется ссылка с именем 'Home' и ведущая
	 * по адресу {@link CWebApplication::homeUrl}. Если данное свойство имеет значение
	 * false, домашняя ссылка не будет отображаться
	 */
	public $homeLink;
	/**
	 * @var array список ссылок "хлебных крошек". Если данное свойство пусто, то
	 * виджет ничего не сгенерирует. Каждая пара массива (ключ-значение) используется для
	 * генерации ссылки вызовом CHtml::link(key, value). По этой причине, ключ является
	 * названием ссылки, а значение может быть строкой или массивом и используется для создания
	 * (URL-адреса). За подробностями обратитесь к {@link CHtml::link}.
	 * Если ключ элемента - целое число, то элемент будет сгенерирован только как имя (т.е., это текущая страница).
	 *
	 * Следующий пример будет генерировать "хлебные крошки" в виде "Домашняя > Заметка для примера > Редактирование",
	 * где "Домашняя" - ссылка на начальную страницу, "Заметка для примера" - ссылка на страницу с адресом
	 * "index.php?r=post/view&id=12", а "Редактирование" - это просто метка. Примечание: ссылка "Домашняя"
	 * определяется отдельно свойсвом {@link homeLink}.
	 *
	 * <pre>
	 * array(
	 *     'Заметка для примера'=>array('post/view', 'id'=>12),
	 *     'Редактирование',
	 * )
	 * </pre>
	 */
	public $links=array();
	/**
	 * @var string строка, определяющая генерацию каждого активного элемента.
	 * По умолчанию - "<a href="{url}">{label}</a>", где "{label}" заменяется
	 * соответствующей меткой элемента, а "{url}" - URL-адресом элемента
	 * @since 1.1.11
	 */
	public $activeLinkTemplate='<a href="{url}">{label}</a>';
	/**
	 * @var string строка, определяющая генерацию каждого неактивного элемента.
	 * По умолчанию - "<span>{label}</span>", где "{label}" заменяется
	 * соответствующей меткой элемента. Примечание: шаблон неактивного элемента
	 * не имеет параметра "{url}"
	 * @since 1.1.11
	 */
	public $inactiveLinkTemplate='<span>{label}</span>';
	/**
	 * @var string разделитель между ссылок "хлебных крошек". По умолчанию - ' &raquo; '.
	 */
	public $separator=' &raquo; ';

	/**
	 * Генерирует контент виджета "хлебные крошки"
	 */
	public function run()
	{
		if(empty($this->links))
			return;

		echo CHtml::openTag($this->tagName,$this->htmlOptions)."\n";
		$links=array();
		if($this->homeLink===null)
			$links[]=CHtml::link(Yii::t('zii','Home'),Yii::app()->homeUrl);
		else if($this->homeLink!==false)
			$links[]=$this->homeLink;
		foreach($this->links as $label=>$url)
		{
			if(is_string($label) || is_array($url))
				$links[]=strtr($this->activeLinkTemplate,array(
					'{url}'=>CHtml::normalizeUrl($url),
					'{label}'=>$this->encodeLabel ? CHtml::encode($label) : $label,
				));
			else
				$links[]=str_replace('{label}',$this->encodeLabel ? CHtml::encode($url) : $url,$this->inactiveLinkTemplate);
		}
		echo implode($this->separator,$links);
		echo CHtml::closeTag($this->tagName);
	}
}