<?php
/**
 * Файл класса CCodeGenerator.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CCodeGenerator - это базовый класс для классов-генераторов кода.
 *
 * CCodeGenerator - это контроллер, предопределяющий несколько действий для целей генерации кода.
 * Классы-потом главным образом должны сконфигурировать свойство {@link codeModel}
 * и переопределить метод {@link getSuccessMessage}. Свойство определяет, какую модель
 * кода (наследующую класс {@link CCodeModel}) должен использовать генератор, а
 * метод должен возвращать сообщение об успехе, отображаемое при успешной генерации файлов кода.
 *
 * @property string $pageTitle заголовок страницы
 * @property string $viewPath путь представления генератора
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CCodeGenerator.php 3426 2011-10-25 00:01:09Z alexander.makarow $
 * @package system.gii
 * @since 1.1.2
 */
class CCodeGenerator extends CController
{
	/**
	 * @var string макет, используемый генератором. По умолчанию - 'generator'.
	 */
	public $layout='generator';
	/**
	 * @var array список доступных шаблонов кода (имя => путь)
	 */
	public $templates=array();
	/**
	 * @var string класс модели кода. Может быть либо именем класса (если он может быть автозагружен)
	 * либо псевдонимом пути до нужного файла класса.
	 * Классы-потомки должны сконфигурировать данное свойство конкретным значением
	 */
	public $codeModel;

	private $_viewPath;

	/**
	 * @return string заголовок страницы
	 */
	public function getPageTitle()
	{
		return 'Gii - '.ucfirst($this->id).' Generator';
	}

	/**
	 * Действие генерации кода.
	 * Данное действие показывает интерфейс генерации кода.
	 * Классы-потомки главным образом должны предоставить представление 'index' для сбора
	 * пользовательских параметров генерации
	 */
	public function actionIndex()
	{
		$model=$this->prepare();
		if($model->files!=array() && isset($_POST['generate'], $_POST['answers']))
		{
			$model->answers=$_POST['answers'];
			$model->status=$model->save() ? CCodeModel::STATUS_SUCCESS : CCodeModel::STATUS_ERROR;
		}

		$this->render('index',array(
			'model'=>$model,
		));
	}

	/**
	 * Действие предпросмотра кода.
	 * Данное действие показывает сгенерированный код
	 */
	public function actionCode()
	{
		$model=$this->prepare();
		if(isset($_GET['id']) && isset($model->files[$_GET['id']]))
		{
			$this->renderPartial('/common/code', array(
				'file'=>$model->files[$_GET['id']],
			));
		}
		else
			throw new CHttpException(404,'Unable to find the code you requested.');
	}

	/**
	 * Действие сравнения кода. Данное действие показывает различия между
	 * новым сгенерированным кодом и соответствующим существующим кодом
	 */
	public function actionDiff()
	{
		Yii::import('gii.components.TextDiff');

		$model=$this->prepare();
		if(isset($_GET['id']) && isset($model->files[$_GET['id']]))
		{
			$file=$model->files[$_GET['id']];
			if(!in_array($file->type,array('php', 'txt','js','css')))
				$diff=false;
			else if($file->operation===CCodeFile::OP_OVERWRITE)
				$diff=TextDiff::compare(file_get_contents($file->path), $file->content);
			else
				$diff='';

			$this->renderPartial('/common/diff',array(
				'file'=>$file,
				'diff'=>$diff,
			));
		}
		else
			throw new CHttpException(404,'Unable to find the code you requested.');
	}

	/**
	 * Возвращает путь представления генератора,
	 * директория "views" в директории, содержащей файл класса генератора
	 * @return string путь представления генератора
	 */
	public function getViewPath()
	{
		if($this->_viewPath===null)
		{
			$class=new ReflectionClass(get_class($this));
			$this->_viewPath=dirname($class->getFileName()).DIRECTORY_SEPARATOR.'views';
		}
		return $this->_viewPath;
	}

	/**
	 * @param string $value путь представления генератора
	 */
	public function setViewPath($value)
	{
		$this->_viewPath=$value;
	}

	/**
	 * Подготавливает модель кода
	 */
	protected function prepare()
	{
		if($this->codeModel===null)
			throw new CException(get_class($this).'.codeModel property must be specified.');
		$modelClass=Yii::import($this->codeModel,true);
		$model=new $modelClass;
		$model->loadStickyAttributes();
		if(isset($_POST[$modelClass]))
		{
			$model->attributes=$_POST[$modelClass];
			$model->status=CCodeModel::STATUS_PREVIEW;
			if($model->validate())
			{
				$model->saveStickyAttributes();
				$model->prepare();
			}
		}
		return $model;
	}
}