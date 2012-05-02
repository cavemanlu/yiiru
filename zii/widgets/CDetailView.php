<?php
/**
 * Файл класса CDetailView.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Виджет CDetailView отображает детали некоторой модели.
 *
 * Виджет CDetailView лучше всего используется для отображения модели в обычном формате (т.е., каждый
 * атрибут модели отображается как строка таблицы). Модель может быть либо экземпляром класса {@link CModel}
 * либо ассоциативным массивом.
 *
 * CDetailView использует свойство {@link attributes} для определения, какие атрибуты модели должны
 * отображаться и как они должны быть отформатированы.
 *
 * Типичное использование виджета CDetailView:
 * <pre>
 * $this->widget('zii.widgets.CDetailView', array(
 *     'data'=>$model,
 *     'attributes'=>array(
 *         'title',             // заголовок атрибута (обычный текст)
 *         'owner.name',        // атрибут связанного объекта "owner"
 *         'description:html',  // описание атрибута в HTML-формате
 *         array(               // связанный объект города, отображаемый как ссылка
 *             'label'=>'City',
 *             'type'=>'raw',
 *             'value'=>CHtml::link(CHtml::encode($model->city->name),
 *                                  array('city/view','id'=>$model->city->id)),
 *         ),
 *     ),
 * ));
 * </pre>
 *
 * @property CFormatter $formatter экземпляр форматтера. По умолчанию -
 * комопонент 'format' приложения
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CDetailView.php 3427 2011-10-25 00:03:52Z alexander.makarow $
 * @package zii.widgets
 * @since 1.1
 */
class CDetailView extends CWidget
{
	private $_formatter;

	/**
	 * @var mixed модель данных, детали которой будут отображаться. Может бть либо экземпляром класса {@link CModel}
	 * (например, объект класса {@link CActiveRecord} или {@link CFormModel}) либо ассоциативным массивом
	 */
	public $data;
	/**
	 * @var array список атрибутов, отображаемых виджетом. Каждый элемент массива
	 * представляет спецификацию для отображения одного отдельного атрибута.
	 *
	 * Атрибут может быть определен строкой формата "Name:Type:Label".
	 * "Type" и "Label" - опциональны.
	 *
	 * "Name" - название атрибута. Может быть свойством (например, "title") либо подсвойством (свойством связанного объекта, например, "owner.username").
	 *
	 * "Label" представляет текст для отображения атрибута. Если не установлен, для генерации подходящего текста будет использована опция "Name".
	 *
	 * "Type" представляет тип атрибута. Определяет, как значение атрибута должно быть отформатировано и отображено.
	 * По умолчанию - 'text'.
	 * "Type" должен определяться {@link formatter форматтером}. В частности, если "Type" равно "xyz", то будет вызван метод "formatXyz"
	 * {@link formatter форматтера} для форматирования отображаемого значения атрибута. По умолчанию, когда в качестве форматтера используется
	 * класс {@link CFormatter}, верными значениями для "Type" являются raw, text, ntext, html, date, time, datetime, boolean, number, email, image, url.
	 * За деталями об этих типах обратитесь к описанию класса {@link CFormatter}.
	 *
	 * Атрибут может быть также определен в виде массива со следующими элементами:
	 * <ul>
	 * <li>label: текст, ассоциированный с атрибутом. Если не установлен, для генерации подходящего текста атрибута будет
	 * использоваться элемент "name";</li>
	 * <li>name: имя атрибута. Может быть свойством либо подсвойством (свойство связанной модели) модели.
	 * Если определен элемент "value", данный элемент будет проигнорирован;</li>
	 * <li>value: отображаемое значение. Если не определено, то для получения соответствующего отображаемого значения
	 * атрибута будет использоваться элемент "name". Примечание: данное значение будет отформатировано согласно
	 * опции, заданной в элементе "type";</li>
	 * <li>type: тип атрибута, определяющий, как будет форматироваться значение атрибута.
	 * См. выше список возможных значений;</li>
	 * <li>cssClass: CSS-класс, используемый для данного элемента. Опция доступна с версии 1.1.3;</li>
	 * <li>template: шаблон. используемый для генерации атрибута. Если не установлен, то будет использован шаблон {@link itemTemplate}.
	 * За деталями того, как устанавливать данное свойство, обратитесь к его описанию - {@link itemTemplate}.
	 * Опция доступна с версии 1.1.1;</li>
	 * <li>visible: видим ли атрибут. Если установлено в значение <code>false</code>, строка таблицы данного атрибута не будет сгенерирована.
	 * Опция доступна с версии 1.1.5.</li>
	 * </ul>
	 */
	public $attributes;
	/**
	 * @var string текст, отображаемый в случае, если значение атрибута равно null. По умолчанию - "Not set"
	 */
	public $nullDisplay;
	/**
	 * @var string имя тега для генерации представления детализации. По умолчанию - 'table'
	 * @see itemTemplate
	 */
	public $tagName='table';
	/**
	 * @var string шаблон, используемый для генерации отдельного атрибута. По умолчанию - строка таблицы.
	 * Распознаваемые метки: "{class}", "{label}" и "{value}". Они будут заменены именем
	 * CSS-класса строки, заголовком атрибута и его значением сответственно
	 * @see itemCssClass
	 */
	public $itemTemplate="<tr class=\"{class}\"><th>{label}</th><td>{value}</td></tr>\n";
	/**
	 * @var array названия CSS-классов для значений атрибутов отображаемых элементов. Если передано несколько названий CSS-классов,
	 * то они будут присваиваться элементам циклически. По умолчанию - <code>array('odd', 'even')</code>
	 */
	public $itemCssClass=array('odd','even');
	/**
	 * @var array HTML-атрибуты, используемые для тега {@link tagName}
	 */
	public $htmlOptions=array('class'=>'detail-view');
	/**
	 * @var string базовый URL-адрес для всех ресурсов виджета (javascript, CSS-файлов, изображений).
	 * По умолчанию - null, т.е., используются интегрированные ресурсы виджета (которые публикуются как assets-ресурсы)
	 */
	public $baseScriptUrl;
	/**
	 * @var string URL-адрес CSS-файла, используемого виджетом. По умолчанию - null, т.е., используется интегрированный
	 * CSS-файл. Если установлено в значение false, то вы несете ответственность за явное включение необходимого CSS-файла на вашу страницу
	 */
	public $cssFile;

	/**
	 * Инициализация представления детализации.
	 * Данный метод инициализирует значения требуемых свойств
	 */
	public function init()
	{
		if($this->data===null)
			throw new CException(Yii::t('zii','Please specify the "data" property.'));
		if($this->attributes===null)
		{
			if($this->data instanceof CModel)
				$this->attributes=$this->data->attributeNames();
			else if(is_array($this->data))
				$this->attributes=array_keys($this->data);
			else
				throw new CException(Yii::t('zii','Please specify the "attributes" property.'));
		}
		if($this->nullDisplay===null)
			$this->nullDisplay='<span class="null">'.Yii::t('zii','Not set').'</span>';
		$this->htmlOptions['id']=$this->getId();

		if($this->baseScriptUrl===null)
			$this->baseScriptUrl=Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias('zii.widgets.assets')).'/detailview';

		if($this->cssFile!==false)
		{
			if($this->cssFile===null)
				$this->cssFile=$this->baseScriptUrl.'/styles.css';
			Yii::app()->getClientScript()->registerCssFile($this->cssFile);
		}
	}

	/**
	 * Генерирует представление детализации.
	 * Главный метод всей генерации представления детализации
	 */
	public function run()
	{
		$formatter=$this->getFormatter();
		echo CHtml::openTag($this->tagName,$this->htmlOptions);

		$i=0;
		$n=is_array($this->itemCssClass) ? count($this->itemCssClass) : 0;
						
		foreach($this->attributes as $attribute)
		{
			if(is_string($attribute))
			{
				if(!preg_match('/^([\w\.]+)(:(\w*))?(:(.*))?$/',$attribute,$matches))
					throw new CException(Yii::t('zii','The attribute must be specified in the format of "Name:Type:Label", where "Type" and "Label" are optional.'));
				$attribute=array(
					'name'=>$matches[1],
					'type'=>isset($matches[3]) ? $matches[3] : 'text',
				);
				if(isset($matches[5]))
					$attribute['label']=$matches[5];
			}
			
			if(isset($attribute['visible']) && !$attribute['visible'])
				continue;

			$tr=array('{label}'=>'', '{class}'=>$n ? $this->itemCssClass[$i%$n] : '');
			if(isset($attribute['cssClass']))
				$tr['{class}']=$attribute['cssClass'].' '.($n ? $tr['{class}'] : '');

			if(isset($attribute['label']))
				$tr['{label}']=$attribute['label'];
			else if(isset($attribute['name']))
			{
				if($this->data instanceof CModel)
					$tr['{label}']=$this->data->getAttributeLabel($attribute['name']);
				else
					$tr['{label}']=ucwords(trim(strtolower(str_replace(array('-','_','.'),' ',preg_replace('/(?<![A-Z])[A-Z]/', ' \0', $attribute['name'])))));
			}

			if(!isset($attribute['type']))
				$attribute['type']='text';
			if(isset($attribute['value']))
				$value=$attribute['value'];
			else if(isset($attribute['name']))
				$value=CHtml::value($this->data,$attribute['name']);
			else
				$value=null;

			$tr['{value}']=$value===null ? $this->nullDisplay : $formatter->format($value,$attribute['type']);

			echo strtr(isset($attribute['template']) ? $attribute['template'] : $this->itemTemplate,$tr);
			
			$i++;
															
		}

		echo CHtml::closeTag($this->tagName);
	}

	/**
	 * @return CFormatter экземпляр форматтера. По умолчанию - комопонент 'format' приложения
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
