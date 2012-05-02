<?php
/**
 * Файл класса CCodeModel.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CCodeModel - это базовый класс для классов моделей, используемых для генерации кода.
 *
 * Каждый генератор кода должен иметь как минимум один класс модели кода, наследующий данный класс.
 * Цель модели кода - представить пользовательские параметры и использовать их для
 * генерации настроенного кода.
 *
 * Классы-потомки должны реализовать метод {@link prepare}, главной задачей которого является заполнение
 * свойства {@link files} на основе пользовательских параметров.
 *
 * Свойство {@link files} должно быть заполнено набором экземпляров класса {@link CCodeFile},
 * каждый из которых представляет один генерируемый файл кода.
 *
 * Класс CCodeModel реализует функцию "липких атрибутов". "Липкий" атрибут - это атрибут, который может
 * запоминать свое последнее валидное значение, даже если пользователь закрыл окно браузера и снова его открыл.
 * Для объявления атрибута "липким" просто впишите в правилах валидации строку с именем валидатора "sticky".
 *
 * @property array $templates список доступных шаблонов кода (имя => директория)
 * @property string $templatePath директория, содержащая файлы шаблона
 * @property string $stickyFile путь к файлу, хранящему значения "липких" атрибутов
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CCodeModel.php 3547 2012-01-24 10:07:28Z mdomba $
 * @package system.gii
 * @since 1.1.2
 */
abstract class CCodeModel extends CFormModel
{
	const STATUS_NEW=1;
	const STATUS_PREVIEW=2;
	const STATUS_SUCCESS=3;
	const STATUS_ERROR=4;
	
	static $keywords=array(
		'__class__',
		'__dir__',
		'__file__',
		'__function__',
		'__line__',
		'__method__',
		'__namespace__',
		'abstract',
		'and',
		'array',
		'as',
		'break',
		'case',
		'catch',
		'cfunction',
		'class',
		'clone',
		'const',
		'continue',
		'declare',
		'default',
		'die',
		'do',
		'echo',
		'else',
		'elseif',
		'empty',
		'enddeclare',
		'endfor',
		'endforeach',
		'endif',
		'endswitch',
		'endwhile',
		'eval',
		'exception',
		'exit',
		'extends',
		'final',
		'final',
		'for',
		'foreach',
		'function',
		'global',
		'goto',
		'if',
		'implements',
		'include',
		'include_once',
		'instanceof',
		'interface',
		'isset',
		'list',
		'namespace',
		'new',
		'old_function',
		'or',
		'parent',
		'php_user_filter',
		'print',
		'private',
		'protected',
		'public',
		'require',
		'require_once',
		'return',
		'static',
		'switch',
		'this',
		'throw',
		'try',
		'unset',
		'use',
		'var',
		'while',
		'xor',
	);

	/**
	 * @var array подтверждения пользователя, нужно ли перезаписывать существующие файлы кода только что сгенерированными.
	 * Значение данного свойства управляется внутри данного класса и класса {@link CCodeGenerator}
	 */
	public $answers;
	/**
	 * @var string название шаблона кода, выбранного пользователем.
	 * Значение данного свойства управляется внутри данного класса и класса {@link CCodeGenerator}
	 */
	public $template;
	/**
	 * @var array список объектов класса  {@link CCodeFile}, представляющих генерируемые файлы кода.
	 * Метод {@link prepare} выполняет задачу по заполнению данного свойства
	 */
	public $files=array();
	/**
	 * @var integer статус данной модели.  
	 * Значение данного свойства управляется внутри класса {@link CCodeGenerator}
	 */
	public $status=self::STATUS_NEW;

	private $_stickyAttributes=array();

	/**
	 * Подготавливает генерируемые файлы кода.
	 * Это главный метод, который должны реализовывать классы-потомки. Он должен содержать логику
	 * заполнения свойства {@link files} списком генерируемых файлов кода
	 */
	abstract public function prepare();

	/**
	 * Объявляет правила валидации модели.
	 * Классы-потомки должны переопределять данный метод в следующем формате:
	 * <pre>
	 * return array_merge(parent::rules(), array(
	 *     ...правила для класса-потомка...
	 * ));
	 * </pre>
	 * @return array правила валидации
	 */
	public function rules()
	{
		return array(
			array('template', 'required'),
			array('template', 'validateTemplate', 'skipOnError'=>true),
			array('template', 'sticky'),
		);
	}

	/**
	 * Валидирует выбор шаблон.
	 * Данный метод впроверяет, выбрал ли пользователь существующий шаблон и
	 * содержит ли этот шаблон все требуемые файлы шаблона, определенные в
	 * свойстве {@link requiredTemplates}
	 * @param string $attribute валидируемый атрибут
	 * @param array $params параметры валидации
	 */
	public function validateTemplate($attribute,$params)
	{
		$templates=$this->templates;
		if(!isset($templates[$this->template]))
			$this->addError('template', 'Invalid template selection.');
		else
		{
			$templatePath=$this->templatePath;
			foreach($this->requiredTemplates() as $template)
			{
				if(!is_file($templatePath.'/'.$template))
					$this->addError('template', "Unable to find the required code template file '$template'.");
			}
		}
	}

	/**
	 * Проверяет существование класса по имени (регистрозависимо)
	 * @param string $name имя проверяемого класса
	 * @return boolean существует ли класс
	 */
	public function classExists($name)
	{
		return class_exists($name,false) && in_array($name, get_declared_classes());
	}

	/**
	 * Объявляет имена атрибутов модели.
	 * Классы-потомки должны переопределять данный метод в следующем формате:
	 * <pre>
	 * return array_merge(parent::attributeLabels(), array(
	 *     ...названия атрибутов класса-потомка...
	 * ));
	 * </pre>
	 * @return array названия атрибутов
	 */
	public function attributeLabels()
	{
		return array(
			'template'=>'Code Template',
		);
	}

	/**
	 * Возвращает список требуемых шаблонов кода.
	 * Классы-потомки обычно должны переопределять данный метод
	 * @return array список требуемых шаблонов кода. Должны быть путями к файлам
	 * относительно пути {@link templatePath}
	 */
	public function requiredTemplates()
	{
		return array();
	}

	/**
	 * Сохраняет сгенерированный код в файлы
	 */
	public function save()
	{
		$result=true;
		foreach($this->files as $file)
		{
			if($this->confirmed($file))
				$result=$file->save() && $result;
		}
		return $result;
	}

	/**
	 * Возвращает сообщение, отображаемое на экране при успешном сохранении только что сгенерированного кода.
	 * Классы-потомки должны переопределить данный метод, если требуется настроить сообщение
	 * @return string сообщение, отображаемое на экране при успешном сохранении только что сгенерированного кода
	 */
	public function successMessage()
	{
		return 'The code has been generated successfully.';
	}

	/**
	 * Возвращает сообщение, отображаемое на экране при возникновении ошибки при сохранении файла кода.
	 * Классы-потомки должны переопределить данный метод, если требуется настроить сообщение
	 * @return string сообщение, отображаемое на экране при возникновении ошибки при сохранении файла кода
	 */
	public function errorMessage()
	{
		return 'There was some error when generating the code. Please check the following messages.';
	}

	/**
	 * Возвращает список доступных шаблонов кода (имя => директория).
	 * Данный метод просто возвращает значение свойства {@link CCodeGenerator::templates}
	 * @return array список доступных шаблонов кода (имя => директория)
	 */
	public function getTemplates()
	{
		return Yii::app()->controller->templates;
	}

	/**
	 * @return string директория, содержащая файлы шаблона
	 * @throw CException вызывается, если свойство {@link templates} пусто или выбранный шаблон неверен
	 */
	public function getTemplatePath()
	{
		$templates=$this->getTemplates();
		if(isset($templates[$this->template]))
			return $templates[$this->template];
		else if(empty($templates))
			throw new CHttpException(500,'No templates are available.');
		else
			throw new CHttpException(500,'Invalid template selection.');

	}

	/**
	 * @param CCodeFile $file должен ли файл кода быть сохранен
	 */
	public function confirmed($file)
	{
		return $this->answers===null && $file->operation===CCodeFile::OP_NEW
			|| is_array($this->answers) && isset($this->answers[md5($file->path)]);
	}

	/**
	 * Генерирует код, используя определенный файл шаблона кода.
	 * Данный метод главный образом используется в методе {@link generate} для генерации кода
	 * @param string $templateFile путь к файлу шаблона кода
	 * @param array $_params_ набор распаковываемых и доступных в шаблоне кода параметров
	 * @return string генерируемый код
	 */
	public function render($templateFile,$_params_=null)
	{
		if(!is_file($templateFile))
			throw new CException("The template file '$templateFile' does not exist.");

		if(is_array($_params_))
			extract($_params_,EXTR_PREFIX_SAME,'params');
		else
			$params=$_params_;
		ob_start();
		ob_implicit_flush(false);
		require($templateFile);
		return ob_get_clean();
	}

	/**
	 * @return string журнал результата генерации кода
	 */
	public function renderResults()
	{
		$output='Generating code using template "'.$this->templatePath."\"...\n";
		foreach($this->files as $file)
		{
			if($file->error!==null)
				$output.="<span class=\"error\">generating {$file->relativePath}<br/>           {$file->error}</span>\n";
			else if($file->operation===CCodeFile::OP_NEW && $this->confirmed($file))
				$output.=' generated '.$file->relativePath."\n";
			else if($file->operation===CCodeFile::OP_OVERWRITE && $this->confirmed($file))
				$output.=' overwrote '.$file->relativePath."\n";
			else
				$output.='   skipped '.$file->relativePath."\n";
		}
		$output.="done!\n";
		return $output;
	}

	/**
	 * Валидатор "липкости".
	 * Данный валидатор не просто валидирует атрибуты.
	 * В действительности, он сохраняет значение атрибута в файле, чтобы сделать его "липким"
	 * @param string $attribute валидируемый атрибут
	 * @param array $params параметры валидации
	 */
	public function sticky($attribute,$params)
	{
		if(!$this->hasErrors())
			$this->_stickyAttributes[$attribute]=$this->$attribute;
	}

	/**
	 * Загружает "липкие" атрибуты из файла и распространяет их на модель
	 */
	public function loadStickyAttributes()
	{
		$this->_stickyAttributes=array();
		$path=$this->getStickyFile();
		if(is_file($path))
		{
			$result=@include($path);
			if(is_array($result))
			{
				$this->_stickyAttributes=$result;
				foreach($this->_stickyAttributes as $name=>$value)
				{
					if(property_exists($this,$name) || $this->canSetProperty($name))
						$this->$name=$value;
				}
			}
		}
	}

	/**
	 * Сохраняет "липкие" атрибуты в файл
	 */
	public function saveStickyAttributes()
	{
		$path=$this->getStickyFile();
		@mkdir(dirname($path),0755,true);
		file_put_contents($path,"<?php\nreturn ".var_export($this->_stickyAttributes,true).";\n");
	}

	/**
	 * @return string путь к файлу, хранящему значения "липких" атрибутов
	 */
	public function getStickyFile()
	{
		return Yii::app()->runtimePath.'/gii-'.Yii::getVersion().'/'.get_class($this).'.php';
	}

	/**
	 * Конвертирует слово во множественную форму (плюрализация).
	 * Примечание: только для английских слов!
	 * Например, 'apple' станет 'apples', а 'child' - 'children'
	 * @param string $name плюрализуемое слово
	 * @return string плюрализованное слово
	 */
	public function pluralize($name)
	{
		$rules=array(
			'/move$/i' => 'moves',
			'/foot$/i' => 'feet',
			'/child$/i' => 'children',
			'/human$/i' => 'humans',
			'/man$/i' => 'men',
			'/tooth$/i' => 'teeth',
			'/person$/i' => 'people',
			'/([m|l])ouse$/i' => '\1ice',
			'/(x|ch|ss|sh|us|as|is|os)$/i' => '\1es',
			'/([^aeiouy]|qu)y$/i' => '\1ies',
			'/(?:([^f])fe|([lr])f)$/i' => '\1\2ves',
			'/(shea|lea|loa|thie)f$/i' => '\1ves',
			'/([ti])um$/i' => '\1a',
			'/(tomat|potat|ech|her|vet)o$/i' => '\1oes',
			'/(bu)s$/i' => '\1ses',
			'/(ax|test)is$/i' => '\1es',
			'/s$/' => 's',
		);
		foreach($rules as $rule=>$replacement)
		{
			if(preg_match($rule,$name))
				return preg_replace($rule,$replacement,$name);
		}
		return $name.'s';
	}

	/**
	 * Конвертирует имя класса в HTML-идентификатор.
	 * Например, 'PostTag' будет сконвертировано в 'post-tag'
	 * @param string $name конвертируемая строка
	 * @return string результирующий идентификатор
	 */
	public function class2id($name)
	{
		return trim(strtolower(str_replace('_','-',preg_replace('/(?<![A-Z])[A-Z]/', '-\0', $name))),'-');
	}

	/**
	 * Конвертирует имя класса в несколько разделенных пробелом слов.
	 * Например, 'PostTag' будет сконвертировано в 'Post Tag'
	 * @param string $name конвертируемая строка
	 * @param boolean $ucwords делать ли первые буквы каждого слова заглавными
	 * @return string результирующие слова
	 */
	public function class2name($name,$ucwords=true)
	{
		$result=trim(strtolower(str_replace('_',' ',preg_replace('/(?<![A-Z])[A-Z]/', ' \0', $name))));
		return $ucwords ? ucwords($result) : $result;
	}

	/**
	 * Конвертирует имя класса в имя переменной со строчной первой буквой.
	 * Метод предоставляется в связи с тем, что PHP-функция lcfirst() доступна с версии PHP 5.3+
	 * @param string $name имя класса
	 * @return string сконвертированное из имени класса имя переменной
	 * @since 1.1.4
	 */
	public function class2var($name)
	{
		$name[0]=strtolower($name[0]);
		return $name;
	}

	/**
	 * Валидирует атрибут на предмет того, что атрибут не является зарезервированным PHP-словом
	 * @param string $attribute валидируемый атрибут
	 * @param array $params параметры валидации
	 */
	public function validateReservedWord($attribute,$params)
	{
		$value=$this->$attribute;
		if(in_array(strtolower($value),self::$keywords))
			$this->addError($attribute, $this->getAttributeLabel($attribute).' cannot take a reserved PHP keyword.');
	}
}