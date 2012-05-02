<?php
/**
 * Файл класса CFormInputElement.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CFormInputElement представляет элемент ввода данных формы.
 *
 * Класс CFormInputElement может представлять на основе свойства {@link type}
 * следующие типы элементов ввода данных форм:
 * <ul>
 * <li>text: поле ввода текста, генерируемое методом {@link CHtml::activeTextField};</li>
 * <li>hidden: поле скрытого значения, генерируемое методом {@link CHtml::activeHiddenField};</li>
 * <li>password: поле ввода пароля, генерируемое методом {@link CHtml::activePasswordField};</li>
 * <li>textarea: область ввода текста, генерируемое методом {@link CHtml::activeTextArea};</li>
 * <li>file: поле выбора файла, генерируемое методом {@link CHtml::activeFileField};</li>
 * <li>radio: радиокнопка, генерируемое методом {@link CHtml::activeRadioButton};</li>
 * <li>checkbox: чекбокс, генерируемый методом {@link CHtml::activeCheckBox};</li>
 * <li>listbox: блок списка, генерируемый методом {@link CHtml::activeListBox};</li>
 * <li>dropdownlist: выпадающий список, генерируемый методом {@link CHtml::activeDropDownList};</li>
 * <li>checkboxlist: список чекбоксов, генерируемый методом {@link CHtml::activeCheckBoxList};</li>
 * <li>radiolist: список радиокнопок, генерируемый методом {@link CHtml::activeRadioButtonList};</li>
 * <li>url: поле ввода url-адреса (HTML5), генерируемое методом {@link CHtml::activeUrlField};</li>
 * <li>email: поле ввода email-адреса (HTML5), генерируемое методом {@link CHtml::activeEmailField};</li>
 * <li>number: поле ввода числа (HTML5), генерируемое методом {@link CHtml::activeNumberField};</li>
 * <li>range: поле слайдера (HTML5), генерируемое методом {@link CHtml::activeRangeField};</li>
 * <li>date: поле выбора даты (HTML5), генерируемое методом {@link CHtml::activeDateField}.</li>
 * </ul>
 * Свойство {@link type} также может быть именем класса или псевдонимом пути к
 * классу. В этом случае элемент ввода генерируется виджетом определенного
 * класса. Примечание: виджет должен иметь свойство "model", представляющее
 * объект модели, и свойство "attribute", представляющее имя атрибута модели.
 *
 * Т.к. класс CFormElement является предком класса CFormInputElement, то
 * значение, присваиваемое несуществующему свойству будет сохранено в свойстве
 * {@link attributes}, которое будет передано в качестве значений
 * HTML-атрибутов в метод класса {@link CHtml}, генерирующий элемент ввода, или
 * будет начальными значениями свойств виджета.
 *
 * @property boolean $required обязателен ли данный элемент
 * @property string $label метка данного элемента. Если метка не установлена
 * вручную, то данный метод вызовет метод {@link CModel::getAttributeLabel}
 * соответствующей модели для определения метки
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CFormInputElement.php 3579 2012-02-17 21:43:44Z qiang.xue@gmail.com $
 * @package system.web.form
 * @since 1.1
 */
class CFormInputElement extends CFormElement
{
	/**
	 * @var array встроенные типы элементов ввода данных (псевдоним => метод класса CHtml)
	 */
	public static $coreTypes=array(
		'text'=>'activeTextField',
		'hidden'=>'activeHiddenField',
		'password'=>'activePasswordField',
		'textarea'=>'activeTextArea',
		'file'=>'activeFileField',
		'radio'=>'activeRadioButton',
		'checkbox'=>'activeCheckBox',
		'listbox'=>'activeListBox',
		'dropdownlist'=>'activeDropDownList',
		'checkboxlist'=>'activeCheckBoxList',
		'radiolist'=>'activeRadioButtonList',
		'url'=>'activeUrlField',
		'email'=>'activeEmailField',
		'number'=>'activeNumberField',
		'range'=>'activeRangeField',
		'date'=>'activeDateField'
	);

	/**
	 * @var string тип данного элемента ввода. Может быть именем класса
	 * виджета, псевдонимом пути виджета или псевдонимом типа элемента ввода
	 * (text, hidden, password, textarea, file, radio, checkbox, listbox,
	 * dropdownlist, checkboxlist, or radiolist). Если используется имя класса
	 * виджета, то этот виджет должен наследовать класс {@link CInputWidget}
	 * или (@link CJuiInputWidget)
	 */
	public $type;
	/**
	 * @var string имя данного элемента ввода
	 */
	public $name;
	/**
	 * @var string текст подсказки данного элемента ввода
	 */
	public $hint;
	/**
	 * @var array настройки данного элемента ввода в случае, если это блок
	 * списка, выпадающий список, список чекбоксов или список радиокнопок.
	 * За деталями генерации значения данного свойства обратитесь к описанию
	 * свойства {@link CHtml::listData}
	 */
	public $items=array();
	/**
	 * @var array настройки, используемые при генерации ошибок. Данное свойство
	 * будет передано в метод {@link CActiveForm::error} в качестве параметра
	 * $htmlOptions
	 * @see CActiveForm::error
	 * @since 1.1.1
	 */
	public $errorOptions=array();
	/**
	 * @var boolean доступна ли AJAX-валидация для данного элемента ввода.
	 * Примечание: для использования AJAX-валидации в настроках класса
	 * {@link CForm::activeForm} должно быть свойство 'enableAjaxValidation',
	 * имеющее значение true. Данное свойство позволяет включать и отключать
	 * AJAX-валидацию для отдельных полей ввода. По умолчанию - true
	 * @since 1.1.7
	 */
	public $enableAjaxValidation=true;
	/**
	 * @var boolean доступна ли валидация на стороне клиента для данного
	 * элемента ввода. Примечание: для использования валидации на стороне
	 * клиента в настройках класса {@link CForm::activeForm} должно быть
	 * свойство 'enableClientValidation', имеющее значение true. Данное
	 * свойство позволяет включать и отключать валидацию на стороне клиента для
	 * отдельных полей. По умолчанию - true
	 * @since 1.1.7
	 */
	public $enableClientValidation=true;
	/**
	 * @var string шаблон, используемый для генерации наименования (label),
	 * поля ввода (input), подсказки (hint) и ошибки (error). В данном шаблоне
	 * распознаются метки "{label}", "{input}", "{hint}" и "{error}"
	 */
	public $layout="{label}\n{input}\n{hint}\n{error}";

	private $_label;
	private $_required;

	/**
	 * Возвращает значение, показывающее, что данный элемент обязателен. Если
	 * данное свойство не установлено явно, оно будет определено вызовом метода
	 * {@link CModel::isAttributeRequired} ассоциированной модели с передачей
	 * ему атрибута данного элемента
	 * @return boolean обязателен ли данный элемент
	 */
	public function getRequired()
	{
		if($this->_required!==null)
			return $this->_required;
		else
			return $this->getParent()->getModel()->isAttributeRequired($this->name);
	}

	/**
	 * @param boolean $value обязателен ли данный элемент
	 */
	public function setRequired($value)
	{
		$this->_required=$value;
	}

	/**
	 * Возвращает наименование данного элемента
	 * @return string метка данного элемента. Если метка не установлена
	 * вручную, то данный метод вызовет метод {@link CModel::getAttributeLabel}
	 * соответствующей модели для определения метки
	 */
	public function getLabel()
	{
		if($this->_label!==null)
			return $this->_label;
		else
			return $this->getParent()->getModel()->getAttributeLabel($this->name);
	}

	/**
	 * Устанавливает наименование данного элемента
	 * @param string $value метка данного элемента
	 */
	public function setLabel($value)
	{
		$this->_label=$value;
	}

	/**
	 * Генерирует все части данного элемента ввода. Реализация по умолчанию
	 * просто возвращает результаты вызовов методов {@link renderLabel},
	 * {@link renderInput}, {@link renderHint}. Если свойство
	 * {@link CForm::showErrorSummary} имеет значение false, то также
	 * вызывается метод {@link renderError} для того, чтобы показать сообщения
	 * об ошбиках после отдельного элемента ввода
	 * @return string полный результат генерации данного элемента ввода,
	 * включая наименование, поле ввода, подсказку и ошибку
	 */
	public function render()
	{
		if($this->type==='hidden')
			return $this->renderInput();
		$output=array(
			'{label}'=>$this->renderLabel(),
			'{input}'=>$this->renderInput(),
			'{hint}'=>$this->renderHint(),
			'{error}'=>$this->getParent()->showErrorSummary ? '' : $this->renderError(),
		);
		return strtr($this->layout,$output);
	}

	/**
	 * Генерирует наименование данного элемента ввода. Реализация по умолчанию
	 * возвращает результат вызова метода {@link CHtml activeLabelEx}
	 * @return string результат генерации
	 */
	public function renderLabel()
	{
		$options = array(
			'label'=>$this->getLabel(),
			'required'=>$this->getRequired()
		);

		if(!empty($this->attributes['id']))
        {
            $options['for'] = $this->attributes['id'];
        }

		return CHtml::activeLabel($this->getParent()->getModel(), $this->name, $options);
	}

	/**
	 * Гененрирует поле ввода. Реализация по умолчанию возвращает результат
	 * вызова соответствующего метода класса CHtml или виджета
	 * @return string результат генерации
	 */
	public function renderInput()
	{
		if(isset(self::$coreTypes[$this->type]))
		{
			$method=self::$coreTypes[$this->type];
			if(strpos($method,'List')!==false)
				return CHtml::$method($this->getParent()->getModel(), $this->name, $this->items, $this->attributes);
			else
				return CHtml::$method($this->getParent()->getModel(), $this->name, $this->attributes);
		}
		else
		{
			$attributes=$this->attributes;
			$attributes['model']=$this->getParent()->getModel();
			$attributes['attribute']=$this->name;
			ob_start();
			$this->getParent()->getOwner()->widget($this->type, $attributes);
			return ob_get_clean();
		}
	}

	/**
	 * Генерирует отображаемую ошибку для данного элемента ввода. Реализация по
	 * умолчанию возвращает результат вызова метода {@link CHtml::error}
	 * @return string результат генерации
	 */
	public function renderError()
	{
		$parent=$this->getParent();
		return $parent->getActiveFormWidget()->error($parent->getModel(), $this->name, $this->errorOptions, $this->enableAjaxValidation, $this->enableClientValidation);
	}

	/**
	 * Генерирует текст подсказки для данного элемента ввода. Реализация по
	 * умолчанию возвращает свойство {@link hint}, заключенное в HTML-тег
	 * параграфа
	 * @return string результат генерации
	 */
	public function renderHint()
	{
		return $this->hint===null ? '' : '<div class="hint">'.$this->hint.'</div>';
	}

	/**
	 * Определяет видимость данного элемента. Данный метод проверяет, что
	 * атрибут, ассоциированный с данным полем ввода, безопасен для текущего
	 * сценария модели
	 * @return boolean видим ли данный элемент
	 */
	protected function evaluateVisible()
	{
		return $this->getParent()->getModel()->isAttributeSafe($this->name);
	}
}
