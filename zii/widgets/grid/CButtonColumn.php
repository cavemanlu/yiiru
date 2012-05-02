<?php
/**
 * Файл класса CButtonColumn.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.grid.CGridColumn');

/**
 * Класс CButtonColumn представляет столбец таблицы, генерирующий одну или несколько кнопок.
 *
 * По умолчанию будт отображено 3 кнопки - "view" (показать), "update" (обновить) и "delete"(удалить),
 * которые выполняют соответствующие действия над строкой модели.
 *
 * Настройка свойств {@link buttons} и {@link template} позволяет столбцу отображать другие кнопки и
 * настраивать порядок отображения кнопок.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CButtonColumn.php 3424 2011-10-24 20:13:19Z mdomba $
 * @package zii.widgets.grid
 * @since 1.1
 */
class CButtonColumn extends CGridColumn
{
	/**
	 * @var array HTML-опции для тега ячейки данных
	 */
	public $htmlOptions=array('class'=>'button-column');
	/**
	 * @var array HTML-опции для тега ячейки-заголовка
	 */
	public $headerHtmlOptions=array('class'=>'button-column');
	/**
	 * @var array HTML-опции для тега ячейки-футера
	 */
	public $footerHtmlOptions=array('class'=>'button-column');
	/**
	 * @var string шаблон, используемый для генерации содержимого каждой ячейки.
	 * По умолчанию распознаются метки: {view}, {update} и {delete}. Если свойство {@link buttons}
	 * определяет дополнительные кнопки, их идентификаторы также могут быть распознаны. Например, если в свойстве
	 * {@link buttons} определена кнопка с именем 'preview', мы можем использовать метку
	 * '{preview}' для определения места вывода кнопки на экран
	 */
	public $template='{view} {update} {delete}';
	/**
	 * @var string надпись для кнопки просмотра. По умолчанию - "View".
	 * Примечание: надпись не проходит HTML-кодирование при генерации
	 */
	public $viewButtonLabel;
	/**
	 * @var string URL-адрес изображения для кнопки просмотра. Если не установлено, используется встроенная кнопка.
	 * Можно установлить данное свойство в значение false для генерации текстовой ссылки вместо ссылки-пиктограммы
	 */
	public $viewButtonImageUrl;
	/**
	 * @var string PHP-выражение, выполняющееся для каждой кнопки просмотра и результат которого используется
	 * в качестве URL-адреса кнопки. В данном выражении доступны переменные:
	 * <code>$row</code> - номер строки (начиная с нуля), <code>$data</code> - модель данных для строки и
	 * <code>$this</code> - объект столбца
	 */
	public $viewButtonUrl='Yii::app()->controller->createUrl("view",array("id"=>$data->primaryKey))';
	/**
	 * @var array HTML-опции для тега кнопки просмотра
	 */
	public $viewButtonOptions=array('class'=>'view');

	/**
	 * @var string надпись кнопки обновления. По умолчанию - "Update".
	 * Примечание: надпись не проходит HTML-кодирование при генерации
	 */
	public $updateButtonLabel;
	/**
	 * @var string URL-адрес изображения для кнопки обновления. Если не установлено, используется встроенная кнопка.
	 * Можно установлить данное свойство в значение false для генерации текстовой ссылки вместо ссылки-пиктограммы
	 */
	public $updateButtonImageUrl;
	/**
	 * @var string PHP-выражение, выполняющееся для каждой кнопки обновления и результат которого используется
	 * в качестве URL-адреса кнопки. В данном выражении доступны переменные:
	 * <code>$row</code> - номер строки (начиная с нуля), <code>$data</code> - модель данных для строки и
	 * <code>$this</code> - объект столбца
	 */
	public $updateButtonUrl='Yii::app()->controller->createUrl("update",array("id"=>$data->primaryKey))';
	/**
	 * @var array HTML-опции для тега кнопки обновления
	 */
	public $updateButtonOptions=array('class'=>'update');

	/**
	 * @var string надпись кнопки удаления. По умолчанию - "Delete".
	 * Примечание: надпись не проходит HTML-кодирование при генерации
	 */
	public $deleteButtonLabel;
	/**
	 * @var string URL-адрес изображения для кнопки удаления. Если не установлено, используется встроенная кнопка.
	 * Можно установлить данное свойство в значение false для генерации текстовой ссылки вместо ссылки-пиктограммы
	 */
	public $deleteButtonImageUrl;
	/**
	 * @var string PHP-выражение, выполняющееся для каждой кнопки удаления и результат которого используется
	 * в качестве URL-адреса кнопки. В данном выражении доступны переменные:
	 * <code>$row</code> - номер строки (начиная с нуля), <code>$data</code> - модель данных для строки и
	 * <code>$this</code> - объект столбца
	 */
	public $deleteButtonUrl='Yii::app()->controller->createUrl("delete",array("id"=>$data->primaryKey))';
	/**
	 * @var array HTML-опции для тега кнопки удаления
	 */
	public $deleteButtonOptions=array('class'=>'delete');
	/**
	 * @var string сообщение подтверждения, отображаемое при щелчке на кнопке удаления.
	 * Установка данного свойства в значение false позволяет не отображать сообщение подтверждения.
	 * Свойство используется только если <code>$this->buttons['delete']['click']</code> не установлено
	 */
	public $deleteConfirmation;
	/**
	 * @var string javascript-функция, выполняемая после ajax-вызова удаления.
	 * Свойство используется только если <code>$this->buttons['delete']['click']</code> не установлено.
	 *
	 * Функция имеет вид <code>function(link, success, data)</code>, где
	 * <ul>
	 * <li><code>link</code> - ссылка для удаления;</li>
	 * <li><code>success</code> - статус ajax-вызова, true; если ajax-вызов прошел успешно, false - неуспешно;</li>
	 * <li><code>data</code> - возвращаемые сервером данные в случае успешного выполнения запроса или объект XHR в случае ошибки.</li>
	 * </ul>
	 * Примечание: если флаг успешности имеет значение true, это не значит, что само удаление прошло успешно,
	 * это все лишь значит, что ajax-запросов выполнен успешно.
	 *
	 * Пример:
	 * <pre>
	 *  array(
	 *     class'=>'CButtonColumn',
	 *     'afterDelete'=>'function(link,success,data){ if(success) alert("Удаление успешно проведено"); }',
	 *  ),
	 * </pre>
	 */
	public $afterDelete;
	/**
	 * @var array настройка дополнительных кнопкок. Каждый элемент массива определяет отдельную кнопку в
	 * следующем формате:
	 * <pre>
	 * 'buttonID' => array(
	 *     'label'=>'...',     // текстовая надпись кнопки
	 *     'url'=>'...',       // PHP-выражение для генерации URL-адреса для кнопки
	 *     'imageUrl'=>'...',  // URL-адрес изображения кнопки. Если не установлено или установлено в значение false, используется текстовая ссылка
	 *     'options'=>array(...), // HTML-опции для тега кнопки
	 *     'click'=>'...',     // JavaScript-функция, вызываемая при щелчке по кнопке
	 *     'visible'=>'...',   // PHP-выражение для определения видимости кнопки
	 * )
	 * </pre>
	 * В PHP-выражении для опций 'url' и/или 'visible' доступны переменные: <code>$row</code> -
	 * текущий номер строки (начиная с нуля) и <code>$data</code> - модель данных для строки.
	 *
	 * Примечание: для отображения данных дополнительных кнопок необходимо настроить свойство {@link template}
	 * так, чтобы соответствующий идентификатор кнопки присутствовал в виде метки в шаблоне
	 */
	public $buttons=array();

	/**
	 * Инициализирует столбец.
	 * Данный метод регистрирует требуемый клиентский скрипт для столбца кнопок
	 */
	public function init()
	{
		$this->initDefaultButtons();

		foreach($this->buttons as $id=>$button)
		{
			if(strpos($this->template,'{'.$id.'}')===false)
				unset($this->buttons[$id]);
			else if(isset($button['click']))
			{
				if(!isset($button['options']['class']))
					$this->buttons[$id]['options']['class']=$id;
				if(strpos($button['click'],'js:')!==0)
					$this->buttons[$id]['click']='js:'.$button['click'];
			}
		}

		$this->registerClientScript();
	}

	/**
	 * Инициализирует кнопки по умолчанию - view (просмотра), update (обновления) и delete (удаления)
	 */
	protected function initDefaultButtons()
	{
		if($this->viewButtonLabel===null)
			$this->viewButtonLabel=Yii::t('zii','View');
		if($this->updateButtonLabel===null)
			$this->updateButtonLabel=Yii::t('zii','Update');
		if($this->deleteButtonLabel===null)
			$this->deleteButtonLabel=Yii::t('zii','Delete');
		if($this->viewButtonImageUrl===null)
			$this->viewButtonImageUrl=$this->grid->baseScriptUrl.'/view.png';
		if($this->updateButtonImageUrl===null)
			$this->updateButtonImageUrl=$this->grid->baseScriptUrl.'/update.png';
		if($this->deleteButtonImageUrl===null)
			$this->deleteButtonImageUrl=$this->grid->baseScriptUrl.'/delete.png';
		if($this->deleteConfirmation===null)
			$this->deleteConfirmation=Yii::t('zii','Are you sure you want to delete this item?');

		foreach(array('view','update','delete') as $id)
		{
			$button=array(
				'label'=>$this->{$id.'ButtonLabel'},
				'url'=>$this->{$id.'ButtonUrl'},
				'imageUrl'=>$this->{$id.'ButtonImageUrl'},
				'options'=>$this->{$id.'ButtonOptions'},
			);
			if(isset($this->buttons[$id]))
				$this->buttons[$id]=array_merge($button,$this->buttons[$id]);
			else
				$this->buttons[$id]=$button;
		}

		if(!isset($this->buttons['delete']['click']))
		{
			if(is_string($this->deleteConfirmation))
				$confirmation="if(!confirm(".CJavaScript::encode($this->deleteConfirmation).")) return false;";
			else
				$confirmation='';

			if(Yii::app()->request->enableCsrfValidation)
			{
				$csrfTokenName = Yii::app()->request->csrfTokenName;
				$csrfToken = Yii::app()->request->csrfToken;
				$csrf = "\n\t\tdata:{ '$csrfTokenName':'$csrfToken' },";
			}
			else
				$csrf = '';

			if($this->afterDelete===null)
				$this->afterDelete='function(){}';

			$this->buttons['delete']['click']=<<<EOD
function() {
	$confirmation
	var th=this;
	var afterDelete=$this->afterDelete;
	$.fn.yiiGridView.update('{$this->grid->id}', {
		type:'POST',
		url:$(this).attr('href'),$csrf
		success:function(data) {
			$.fn.yiiGridView.update('{$this->grid->id}');
			afterDelete(th,true,data);
		},
		error:function(XHR) {
			return afterDelete(th,false,XHR);
		}
	});
	return false;
}
EOD;
		}
	}

	/**
	 * Регистрирует клиентские скрипты для столбца кнопок
	 */
	protected function registerClientScript()
	{
		$js=array();
		foreach($this->buttons as $id=>$button)
		{
			if(isset($button['click']))
			{
				$function=CJavaScript::encode($button['click']);
				$class=preg_replace('/\s+/','.',$button['options']['class']);
				$js[]="jQuery('#{$this->grid->id} a.{$class}').live('click',$function);";
			}
		}

		if($js!==array())
			Yii::app()->getClientScript()->registerScript(__CLASS__.'#'.$this->id, implode("\n",$js));
	}

	/**
	 * Генерирует содержимое ячейки данных.
	 * Данный метод генерирует кнопки просмотра, обновления и удаления в ячейке данных
	 * @param integer $row номер строки (начиная с нуля)
	 * @param mixed $data ассоциированные со строкой данные
	 */
	protected function renderDataCellContent($row,$data)
	{
		$tr=array();
		ob_start();
		foreach($this->buttons as $id=>$button)
		{
			$this->renderButton($id,$button,$row,$data);
			$tr['{'.$id.'}']=ob_get_contents();
			ob_clean();
		}
		ob_end_clean();
		echo strtr($this->template,$tr);
	}

	/**
	 * Генерирует кнопку-ссылку
	 * @param string $id идентификатор кнопки
	 * @param array $button конфигурация кнопки, может содержать следующие элементы - 'label', 'url', 'imageUrl' и 'options'.
	 * За деталями обратитесь к описанию свойства {@link buttons}
	 * @param integer $row номер строки (начиная с нуля)
	 * @param mixed $data ассоциированные со строкой данные
	 */
	protected function renderButton($id,$button,$row,$data)
	{
		if (isset($button['visible']) && !$this->evaluateExpression($button['visible'],array('row'=>$row,'data'=>$data)))
  			return;
		$label=isset($button['label']) ? $button['label'] : $id;
		$url=isset($button['url']) ? $this->evaluateExpression($button['url'],array('data'=>$data,'row'=>$row)) : '#';
		$options=isset($button['options']) ? $button['options'] : array();
		if(!isset($options['title']))
			$options['title']=$label;
		if(isset($button['imageUrl']) && is_string($button['imageUrl']))
			echo CHtml::link(CHtml::image($button['imageUrl'],$label),$url,$options);
		else
			echo CHtml::link($label,$url,$options);
	}
}
