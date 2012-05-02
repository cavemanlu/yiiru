<?php
/**
 * Файл класса CViewAction.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CViewAction представляет действие, отображающее представление согласно
 * пользовательским параметрам.
 *
 * По умолчанию отображается представление, определенное в GET-параметре
 * <code>view</code>. Имя GET-параметра может быть настроено свойством
 * {@link viewParam}. Если пользователь не определил GET-параметр, то
 * отображается представление по умолчанию, определенное свойством
 * {@link defaultView}.
 *
 * Представление определеяется в формате <code>path.to.view</code>, который
 * транслируется в имя представления <code>BasePath/path/to/view</code>, где
 * <code>BasePath</code> определяется свойством {@link basePath}.
 *
 * Примечание: имя пользовательского представления может содержать только
 * буквы, точки и дефисы, а первый символ должен быть буквой.
 *
 * @property string $requestedView имя представления (в формате
 * 'path.to.view'), запрошенного пользователем
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CViewAction.php 3426 2011-10-25 00:01:09Z alexander.makarow $
 * @package system.web.actions
 * @since 1.0
 */
class CViewAction extends CAction
{
	/**
	 * @var string имя GET-параметра, содержащего имя запрошенного
	 * представления. По умолчанию - 'view'
	 */
	public $viewParam='view';
	/**
	 * @var string имя представления по умолчанию, если GET-параметр
	 * {@link viewParam} не определен. По умолчанию - 'index'. Должно быть в
	 * формате 'path.to.view'
	 * @see basePath
	 */
	public $defaultView='index';
	/**
	 * @var string имя генерируемого представления. Данное свойство
	 * устанавливается, когда распознается запрошенное представление
	 */
	public $view;
	/**
	 * @var string базовый путь представлений. По умолчанию - 'pages'. Базовый
	 * путь будет добавляться в начало всем пользовательским представлениям.
	 * Например, если запрос пользователя имеет вид <code>tutorial.chap1</code>,
	 * то соответствующее имя представления - <code>pages/tutorial/chap1</code>,
	 * при условии базового пути вида <code>pages</code>. Реальный файл
	 * представления определяется методом {@link CController::getViewFile}
	 * @see CController::getViewFile
	 */
	public $basePath='pages';
	/**
	 * @var mixed имя макета, применяемого для представлений. Данное имя будет
	 * связано со свойством {@link CController::layout} перед генерацией
	 * представления. По умолчанию - null, т.е., используется макет
	 * контроллера. Если имеет значение false, то макет не применяется
	 */
	public $layout;
	/**
	 * @var boolean должно ли представление генерироваться как PHP-скрипт или
	 * статичный текст. По умолчанию - false, т.е., представление генерируется
	 * как PHP-скрипт
	 */
	public $renderAsText=false;

	private $_viewPath;


	/**
	 * Возвращает имя представления, запрошенного пользователем. Если
	 * пользователь не определил какое-либо представление, будет возвращено
	 * представление, заданное свойством {@link defaultView}
	 * @return string имя представления (в формате 'path.to.view'),
	 * запрошенного пользователем
	 */
	public function getRequestedView()
	{
		if($this->_viewPath===null)
		{
			if(!empty($_GET[$this->viewParam]))
				$this->_viewPath=$_GET[$this->viewParam];
			else
				$this->_viewPath=$this->defaultView;
		}
		return $this->_viewPath;
	}

	/**
	 * Разрешает представление, заданное пользователем, в верное имя
	 * представления
	 * @param string $viewPath представление, заданное пользователем в формате
	 * 'path.to.view'
	 * @return string определенное представление в формате 'path/to/view'
	 * @throw CHttpException вызывается, если представление, заданное
	 * пользователем, неверно
	 */
	protected function resolveView($viewPath)
	{
		// start with a word char and have word chars, dots and dashes only
		if(preg_match('/^\w[\w\.\-]*$/',$viewPath))
		{
			$view=strtr($viewPath,'.','/');
			if(!empty($this->basePath))
				$view=$this->basePath.'/'.$view;
			if($this->getController()->getViewFile($view)!==false)
			{
				$this->view=$view;
				return;
			}
		}
		throw new CHttpException(404,Yii::t('yii','The requested view "{name}" was not found.',
			array('{name}'=>$viewPath)));
	}

	/**
	 * Выполняет событие. Данный метод отображает запрошенное
	 * пользователем представление
	 * @throws CHttpException вызывается, если представление неверно
	 */
	public function run()
	{
		$this->resolveView($this->getRequestedView());
		$controller=$this->getController();
		if($this->layout!==null)
		{
			$layout=$controller->layout;
			$controller->layout=$this->layout;
		}

		$this->onBeforeRender($event=new CEvent($this));
		if(!$event->handled)
		{
			if($this->renderAsText)
			{
				$text=file_get_contents($controller->getViewFile($this->view));
				$controller->renderText($text);
			}
			else
				$controller->render($this->view);
			$this->onAfterRender(new CEvent($this));
		}

		if($this->layout!==null)
			$controller->layout=$layout;
	}

	/**
	 * Вызывается перед выполнением метода генерации. Обработчик события
	 * {@link CEvent::handled} может возвратить значение true для
	 * остановки дальнейшей генерации представления
	 * @param CEvent $event параметр события
	 */
	public function onBeforeRender($event)
	{
		$this->raiseEvent('onBeforeRender',$event);
	}

	/**
	 * Вызывается сразу после выполнения метода генерации
	 * @param CEvent $event параметр события
	 */
	public function onAfterRender($event)
	{
		$this->raiseEvent('onAfterRender',$event);
	}
}