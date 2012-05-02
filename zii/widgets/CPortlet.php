<?php
/**
 * CPortlet class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CPortlet - это базовый класс для виджетов-портлетов.
 *
 * Портлет отображает фрагмент содержимого, обычно в виде блока в сайдбаре веб-страницы.
 *
 * Для определения содержимого портлета требуется переопределить метод {@link renderContent}
 * либо вставить содержимое между вызовами методов {@link CController::beginWidget}
 * и {@link CController::endWidget}. Например,
 *
 * <pre>
 * <?php $this->beginWidget('zii.widgets.CPortlet'); ?>
 *     ...вставить содержимое здесь...
 * <?php $this->endWidget(); ?>
 * </pre>
 *
 * Портлет также имеет необязательное свойство {@link title} (заголовок). Оно тоже может
 * быть переопределено {@link renderDecoration} для дальнейшей настройки
 * отображения портлета (например, для добавления кнопок min/max).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CPortlet.php 2799 2011-01-01 19:31:13Z qiang.xue $
 * @package zii.widgets
 * @since 1.1
 */
class CPortlet extends CWidget
{
	/**
	 * @var string имя тега контейнера портлета. По умолчанию - 'div'
	 */
	public $tagName='div';
	/**
	 * @var array HTML-атрибуты для тега контейнера портлета
	 */
	public $htmlOptions=array('class'=>'portlet');
	/**
	 * @var string заголовок портлета. По умолчанию - null.
	 * Если данное свойство не установлено, то тег, обрамляющий портлет, не будет отображен.
	 * Примечание: заголовок не подвергается HTML-кодированию при генерации
	 */
	public $title;
	/**
	 * @var string CSS-класс для тега контейнера, обрамляющего портлет. По умолчанию - 'portlet-decoration'
	 */
	public $decorationCssClass='portlet-decoration';
	/**
	 * @var string CSS-класс для тега заголовка портлета. По умолчанию - 'portlet-title'
	 */
	public $titleCssClass='portlet-title';
	/**
	 * @var string CSS-класс для тега контейнера содержимого. По умолчанию - 'portlet-content'
	 */
	public $contentCssClass='portlet-content';
	/**
	 * @var boolean скрывать ли портлет, если основное модержимое пусто. По умолчанию - true
	 * @since 1.1.4
	 */
	public $hideOnEmpty=true;

	private $_openTag;

	/**
	 * Инициализирует портлет.
	 * Данный метод генерирует открывающие теги, требуемые портлетом.
	 * Также он генерирует обрамление портлета, если таковое имеется
	 */
	public function init()
	{
		ob_start();
		ob_implicit_flush(false);

		$this->htmlOptions['id']=$this->getId();
		echo CHtml::openTag($this->tagName,$this->htmlOptions)."\n";
		$this->renderDecoration();
		echo "<div class=\"{$this->contentCssClass}\">\n";

		$this->_openTag=ob_get_contents();
		ob_clean();
	}

	/**
	 * Генерирует содержимое портлета
	 */
	public function run()
	{
		$this->renderContent();
		$content=ob_get_clean();
		if($this->hideOnEmpty && trim($content)==='')
			return;
		echo $this->_openTag;
		echo $content;
		echo "</div>\n";
		echo CHtml::closeTag($this->tagName);
	}

	/**
	 * Генерирует обрамление портлета.
	 * Реализация по умолчанию генерирует заголовок портлета, если он установлен
	 */
	protected function renderDecoration()
	{
		if($this->title!==null)
		{
			echo "<div class=\"{$this->decorationCssClass}\">\n";
			echo "<div class=\"{$this->titleCssClass}\">{$this->title}</div>\n";
			echo "</div>\n";
		}
	}

	/**
	 * Генерирует содержимое портлета.
	 * Дочерние классы должны переопределять данный метод для генерации реального содержимого
	 */
	protected function renderContent()
	{
	}
}