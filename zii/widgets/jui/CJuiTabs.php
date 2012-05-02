<?php
/**
 * Файл класса CJuiTabs.
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.jui.CJuiWidget');

/**
 * Виджет CJuiTabs отображает блок табов.
 *
 * Виджет CJuiTabs инкапсулирует {@link http://jqueryui.com/demos/tabs/ плагин JUI tabs}.
 *
 * Для использования данного виджета нужно вставить в представление следующий код:
 * <pre>
 * $this->widget('zii.widgets.jui.CJuiTabs', array(
 *     'tabs'=>array(
 *         'Статичный таб 1'=>'Содержимое первого таба',
 *         'Статичный таб 2'=>array('content'=>'Содержимое второго таба', 'id'=>'tab2'),
 *         // содержимое третьей панели генерируется частичным представлением
 *         'AjaxTab'=>array('ajax'=>$ajaxUrl),
 *     ),
 *     // дополнительные javascript-опции для плагина табов
 *     'options'=>array(
 *         'collapsible'=>true,
 *     ),
 * ));
 * </pre>
 *
 * Настройкой свойства {@link options} можно определить опции, передаваемые в плагин табов.
 * Обратитесь к {@link http://jqueryui.com/demos/tabs/ документации о плагине JUI tabs}
 * за списком возможных опций (пар имя-значение).
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @version $Id: CJuiTabs.php 3400 2011-09-22 00:47:39Z sebathi $
 * @package zii.widgets.jui
 * @since 1.1
 */
class CJuiTabs extends CJuiWidget
{
	/**
	 * @var array список табов (заголовок таба => содержимое таба).
	 * Примечание: заголовок таба не проходит HTML-кодирование.
	 * Содержимое таба может быть строкой ли массивом. Если это массив, то он может быть в
	 * одном из двух форматах:
	 * <pre>
	 * array('id'=>'myTabID', 'content'=>'содержимое таба')
	 * // или
	 * array('id'=>'myTabID', 'ajax'=>URL)
	 * </pre>,
	 * где элемент 'id' - опционален. Второй формат позволяет динамически получать содержимое таба
	 * по определенному URL-адресу посредством AJAX-запроса. URL-адрес может быть строкой или массивом.
	 * Если это массив, то он будет нормализован в URL-адрес с использованием {@link CHtml::normalizeUrl}
	 */
	public $tabs=array();
	/**
	 * @var string имя элемента контейнера, содержащего все панели. По умолчанию - 'div'
	 */
	public $tagName='div';
	/**
	 * @var string шаблон, используемый для генерации заголовков каждой панели.
	 * Метка "{title}" в шаблоне будет заменена заголовком панели, а метка
	 * "{url}" - строкой "#TabID" или URL-адресом ajax-запроса
	 */
	public $headerTemplate='<li><a href="{url}" title="{id}">{title}</a></li>';
	/**
	 * @var string шаблон, используемый для генерации содержимого каждого таба.
	 * Метка "{content}" в шаблоне будет заменена содержимым панели, а метка 
	 * "{id}" - идентификатором таба
	 */
	public $contentTemplate='<div id="{id}">{content}</div>';

	/**
	 * Выполняет виджет.
	 * Метод регистрирует требуемый javascript-код и генерирует HTML-код
	 */
	public function run()
	{
		$id=$this->getId();
		if (isset($this->htmlOptions['id']))
			$id = $this->htmlOptions['id'];
		else
			$this->htmlOptions['id']=$id;

		echo CHtml::openTag($this->tagName,$this->htmlOptions)."\n";

		$tabsOut = "";
		$contentOut = "";
		$tabCount = 0;

		foreach($this->tabs as $title=>$content)
		{
			$tabId = (is_array($content) && isset($content['id']))?$content['id']:$id.'_tab_'.$tabCount++;

			if (!is_array($content))
			{
				$tabsOut .= strtr($this->headerTemplate, array('{title}'=>$title, '{url}'=>'#'.$tabId, '{id}'=>'#' . $tabId))."\n";
				$contentOut .= strtr($this->contentTemplate, array('{content}'=>$content,'{id}'=>$tabId))."\n";
			}
			elseif (isset($content['ajax']))
			{
				$tabsOut .= strtr($this->headerTemplate, array('{title}'=>$title, '{url}'=>CHtml::normalizeUrl($content['ajax']), '{id}'=>'#' . $tabId))."\n";
			}
			else
			{
				$tabsOut .= strtr($this->headerTemplate, array('{title}'=>$title, '{url}'=>'#'.$tabId, '{id}'=>$tabId))."\n";
				if(isset($content['content']))
					$contentOut .= strtr($this->contentTemplate, array('{content}'=>$content['content'],'{id}'=>$tabId))."\n";
			}
		}
		echo "<ul>\n" . $tabsOut . "</ul>\n";
		echo $contentOut;

		echo CHtml::closeTag($this->tagName)."\n";

		$options=empty($this->options) ? '' : CJavaScript::encode($this->options);
		Yii::app()->getClientScript()->registerScript(__CLASS__.'#'.$id,"jQuery('#{$id}').tabs($options);");
	}

	/**
	 * Регистрирует файлы скриптов ядра.
	 * Метод переопределяет родительскую реализацию, регистрируя плагин cookie при использовании опции cookie
	 */
	protected function registerCoreScripts()
	{
		parent::registerCoreScripts();
		if(isset($this->options['cookie']))
			Yii::app()->getClientScript()->registerCoreScript('cookie');
	}
}
