<?php
/**
 * Файл содержит компонент приложения для обработки ошибок.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('CHtml',true);

/**
 * Компонент CErrorHandler обрабатывает неулавливаемые ошибки и исключения PHP.
 *
 * Он отображает эти ошибки, используя соответствующее представление, основанное на
 * характере ошибки и режиме работы приложения.
 * Он также выбирает наиболее предпочтительный язык для отображения ошибок.
 *
 * CErrorHandler использует два набора представлений:
 * <ul>
 * <li>представления для разработки с именами вида <code>exception.php</code>;
 * <li>производственные (production) представления с именами вида <code>error&lt;StatusCode&gt;.php</code>;
 * </ul>
 * где &lt;StatusCode&gt; - код ошибки HTTP (например, error500.php).
 * Локализованные представления именуются так же, но хранятся в поддиректории
 * с именем кода языка (например, ru/error500.php).
 *
 * Представления для разработки отображаются, когда приложения работает в режиме отладки
 * (т.е. YII_DEBUG имеет значение true). В этих представлениях отображается детализированная
 * информация ошибки с исходным кодом. Производственные представления показываются конечному пользователю
 * и используются в рабочем приложении.
 * Для целей безопасности, они отображают только сообщения об ошибке без подробной информации.
 *
 * CErrorHandler ищет шаблоны представлений в следующих местах в таком порядке:
 * <ol>
 * <li><code>themes/ThemeName/views/system</code>: когда активны темы.</li>
 * <li><code>protected/views/system</code></li>
 * <li><code>framework/views</code></li>
 * </ol>
 * Если представление не найдено в директории, оно будет искаться в следующей.
 *
 * Свойство {@link maxSourceLines} может быть изменено для установки количества строк
 * исходного кода, отображаемых в представлениях разработки.
 *
 * CErrorHandler - это компонент ядра приложения, доступный методом {@link CApplication::getErrorHandler()}.
 *
 * @property array $error детали ошибки. Null, если ошибки нет
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CErrorHandler.php 3540 2012-01-16 10:17:01Z mdomba $
 * @package system.base
 * @since 1.0
 */
class CErrorHandler extends CApplicationComponent
{
	/**
	 * @var integer максимальное количество строк исходного кода, отображаемых в представлениях. По умолчанию - 25.
	 */
	public $maxSourceLines=25;

	/**
	 * @var integer максимальное число отображаемых строк трассированного кода. По умолчанию - 10
	 * @since 1.1.6
	 */
	public $maxTraceSourceLines = 10;
	/**
	 * @var string информация администратора приложения (может быть имененм или ссылкой email).
	 * Отображается на странице ошибки конечным пользователям. По умолчанию - 'the webmaster'.
	 */
	public $adminInfo='the webmaster';
	/**
	 * @var boolean запрещать ли вывод любой существующей страницы перед выводом ошибки. По умолчанию - true.
	 */
	public $discardOutput=true;
	/**
	 * @var string маршрут (например, 'site/error') до действия контроллера, используемого для отображения внешних ошибок.
	 * Внутри действия можно получить информацию ошибки посредством Yii::app()->errorHandler->error.
	 * Свойство по умолчанию имеет значение null, т.е. обрабатывать отображение ошибок будет компонент CErrorHandler
	 */
	public $errorAction;

	private $_error;

	/**
	 * Обрабатывает событие ошибки/исключения.
	 * Метод вызывается приложением всякий раз, когда оно перехватывает исключение или ошибку PHP.
	 * @param CEvent $event событие, содержащее информацию об ошибке/исключении
	 */
	public function handle($event)
	{
		// set event as handled to prevent it from being handled by other event handlers
		$event->handled=true;

		if($this->discardOutput)
		{
			// the following manual level counting is to deal with zlib.output_compression set to On
			for($level=ob_get_level();$level>0;--$level)
			{
				@ob_end_clean();
			}
		}

		if($event instanceof CExceptionEvent)
			$this->handleException($event->exception);
		else // CErrorEvent
			$this->handleError($event);
	}

	/**
	 * Возвращает детали обрабатываемой ошибки.
	 * Ошибка возвращается в виде массива со следующей информацией:
	 * <ul>
	 * <li>code - код ошибки HTTP (например, 403, 500);</li>
	 * <li>type - тип ошибки (например, 'CHttpException', 'PHP Error');</li>
	 * <li>message - сообщение об ошибке;</li>
	 * <li>file - имя файла скрипта PHP, к котором возникла ошибка;</li>
	 * <li>line - номер строки кода, в которой возникла ошибка;</li>
	 * <li>trace - стек вызова ошибки;</li>
	 * <li>source - исходный код, в котором возникла ошибка.</li>
	 * </ul>
	 * @return array детали ошибки. Null, если ошибки нет
	 */
	public function getError()
	{
		return $this->_error;
	}

	/**
	 * Обрабатывает исключение
	 * @param Exception $exception перехваченное исключение
	 */
	protected function handleException($exception)
	{
		$app=Yii::app();
		if($app instanceof CWebApplication)
		{
			if(($trace=$this->getExactTrace($exception))===null)
			{
				$fileName=$exception->getFile();
				$errorLine=$exception->getLine();
			}
			else
			{
				$fileName=$trace['file'];
				$errorLine=$trace['line'];
			}

			$trace = $exception->getTrace();

			foreach($trace as $i=>$t)
			{
				if(!isset($t['file']))
					$trace[$i]['file']='unknown';

				if(!isset($t['line']))
					$trace[$i]['line']=0;

				if(!isset($t['function']))
					$trace[$i]['function']='unknown';

				unset($trace[$i]['object']);
			}

			$this->_error=$data=array(
				'code'=>($exception instanceof CHttpException)?$exception->statusCode:500,
				'type'=>get_class($exception),
				'errorCode'=>$exception->getCode(),
				'message'=>$exception->getMessage(),
				'file'=>$fileName,
				'line'=>$errorLine,
				'trace'=>$exception->getTraceAsString(),
				'traces'=>$trace,
			);

			if(!headers_sent())
				header("HTTP/1.0 {$data['code']} ".get_class($exception));
			if($exception instanceof CHttpException || !YII_DEBUG)
				$this->render('error',$data);
			else
			{
				if($this->isAjaxRequest())
					$app->displayException($exception);
				else
					$this->render('exception',$data);
			}
		}
		else
			$app->displayException($exception);
	}

	/**
	 * Обрабатывает ошибку PHP.
	 * @param CErrorEvent $event событие ошибки PHP
	 */
	protected function handleError($event)
	{
		$trace=debug_backtrace();
		// skip the first 3 stacks as they do not tell the error position
		if(count($trace)>3)
			$trace=array_slice($trace,3);
		$traceString='';
		foreach($trace as $i=>$t)
		{
			if(!isset($t['file']))
				$trace[$i]['file']='unknown';

			if(!isset($t['line']))
				$trace[$i]['line']=0;

			if(!isset($t['function']))
				$trace[$i]['function']='unknown';

			$traceString.="#$i {$trace[$i]['file']}({$trace[$i]['line']}): ";
			if(isset($t['object']) && is_object($t['object']))
				$traceString.=get_class($t['object']).'->';
			$traceString.="{$trace[$i]['function']}()\n";

			unset($trace[$i]['object']);
		}

		$app=Yii::app();
		if($app instanceof CWebApplication)
		{
			switch($event->code)
			{
				case E_WARNING:
					$type = 'PHP warning';
					break;
				case E_NOTICE:
					$type = 'PHP notice';
					break;
				case E_USER_ERROR:
					$type = 'User error';
					break;
				case E_USER_WARNING:
					$type = 'User warning';
					break;
				case E_USER_NOTICE:
					$type = 'User notice';
					break;
				case E_RECOVERABLE_ERROR:
					$type = 'Recoverable error';
					break;
				default:
					$type = 'PHP error';
			}
			$this->_error=$data=array(
				'code'=>500,
				'type'=>$type,
				'message'=>$event->message,
				'file'=>$event->file,
				'line'=>$event->line,
				'trace'=>$traceString,
				'traces'=>$trace,
			);
			if(!headers_sent())
				header("HTTP/1.0 500 PHP Error");
			if($this->isAjaxRequest())
				$app->displayError($event->code,$event->message,$event->file,$event->line);
			else if(YII_DEBUG)
				$this->render('exception',$data);
			else
				$this->render('error',$data);
		}
		else
			$app->displayError($event->code,$event->message,$event->file,$event->line);
	}

	/**
	 * Показывает, является ли текущий запрос ajax-запросом (XMLHttpRequest)
	 * @return boolean является ли текущий запрос ajax-запросом (XMLHttpRequest)
	 */
	protected function isAjaxRequest()
	{
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest';
	}

	/**
	 * Возвращает точный трассированный путь до точки возникновения проблемы
	 * @param Exception $exception неперехваченное исключение
	 * @return array точный трассированный путь до точки возникновения проблемы
	 */
	protected function getExactTrace($exception)
	{
		$traces=$exception->getTrace();

		foreach($traces as $trace)
		{
			// property access exception
			if(isset($trace['function']) && ($trace['function']==='__get' || $trace['function']==='__set'))
				return $trace;
		}
		return null;
	}

	/**
	 * Рендерит представление.
	 * @param string $view имя представления (имя файла без расширения).
	 * Обратитесь к {@link getViewFile}, чтобы узнать, где находится файл представления в соответствии с его именем.
	 * @param array $data передаваемые в представление данные
	 */
	protected function render($view,$data)
	{
		if($view==='error' && $this->errorAction!==null)
			Yii::app()->runController($this->errorAction);
		else
		{
			// additional information to be passed to view
			$data['version']=$this->getVersionInfo();
			$data['time']=time();
			$data['admin']=$this->adminInfo;
			include($this->getViewFile($view,$data['code']));
		}
	}

	/**
	 * Определяет используемый файл представления.
	 * @param string $view имя представления (либо 'exception' либо 'error')
	 * @param integer $code код ошибки HTTP
	 * @return string путь к файлу представления
	 */
	protected function getViewFile($view,$code)
	{
		$viewPaths=array(
			Yii::app()->getTheme()===null ? null :  Yii::app()->getTheme()->getSystemViewPath(),
			Yii::app() instanceof CWebApplication ? Yii::app()->getSystemViewPath() : null,
			YII_PATH.DIRECTORY_SEPARATOR.'views',
		);

		foreach($viewPaths as $i=>$viewPath)
		{
			if($viewPath!==null)
			{
				 $viewFile=$this->getViewFileInternal($viewPath,$view,$code,$i===2?'en_us':null);
				 if(is_file($viewFile))
				 	 return $viewFile;
			}
		}
	}

	/**
	 * Ищет представление в определенной директории.
	 * @param string $viewPath директория, содержащая представление
	 * @param string $view имя представления (либо 'exception' либо 'error')
	 * @param integer $code код ошибки HTTP
	 * @param string $srcLanguage язык представления
	 * @return string путь к файлу представления
	 */
	protected function getViewFileInternal($viewPath,$view,$code,$srcLanguage=null)
	{
		$app=Yii::app();
		if($view==='error')
		{
			$viewFile=$app->findLocalizedFile($viewPath.DIRECTORY_SEPARATOR."error{$code}.php",$srcLanguage);
			if(!is_file($viewFile))
				$viewFile=$app->findLocalizedFile($viewPath.DIRECTORY_SEPARATOR.'error.php',$srcLanguage);
		}
		else
			$viewFile=$viewPath.DIRECTORY_SEPARATOR."exception.php";
		return $viewFile;
	}

	/**
	 * Возвращает информацию о версии сервера.
	 * Если приложение в производственном режиме, возвращается пустая строка
	 * @return string информация о версии сервера. Если приложение в производственном режиме, возвращается пустая строка
	 */
	protected function getVersionInfo()
	{
		if(YII_DEBUG)
		{
			$version='<a href="http://www.yiiframework.com/">Yii Framework</a>/'.Yii::getVersion();
			if(isset($_SERVER['SERVER_SOFTWARE']))
				$version=$_SERVER['SERVER_SOFTWARE'].' '.$version;
		}
		else
			$version='';
		return $version;
	}

	/**
	 * Конвертирует массив аргументов в его строковое представление
	 *
	 * @param array $args конвертируемый массив аргументов
	 * @return string массив аргументов в его строковом представлении
	 */
	protected function argumentsToString($args)
	{
		$count=0;

		$isAssoc=$args!==array_values($args);
		
		foreach($args as $key => $value)
		{
			$count++;
			if($count>=5)
			{
				if($count>5)
					unset($args[$key]);
				else
					$args[$key]='...';
				continue;
			}

			if(is_object($value))
				$args[$key] = get_class($value);
			else if(is_bool($value))
				$args[$key] = $value ? 'true' : 'false';
			else if(is_string($value))
			{
				if(strlen($value)>64)
					$args[$key] = '"'.substr($value,0,64).'..."';
				else
					$args[$key] = '"'.$value.'"';
			}
			else if(is_array($value))
				$args[$key] = 'array('.$this->argumentsToString($value).')';
			else if($value===null)
				$args[$key] = 'null';
			else if(is_resource($value))
				$args[$key] = 'resource';

			if(is_string($key))
			{
				$args[$key] = '"'.$key.'" => '.$args[$key];
			}
			else if($isAssoc)
			{
				$args[$key] = $key.' => '.$args[$key];
			}
		}
		$out = implode(", ", $args);

		return $out;
	}

	/**
	 * Возвращает значение, показывающее, является ли стек вызова кодом приложения
	 * @param array $trace данные для трассировки
	 * @return boolean является ли стек вызова кодом приложения
	 */
	protected function isCoreCode($trace)
	{
		if(isset($trace['file']))
		{
			$systemPath=realpath(dirname(__FILE__).'/..');
			return $trace['file']==='unknown' || strpos(realpath($trace['file']),$systemPath.DIRECTORY_SEPARATOR)===0;
		}
		return false;
	}

	/**
	 * Генерирует отображение строк исходного кода, окружающих строку с ошибкой.
	 * @param string $file путь к файлу исходного кода
	 * @param integer $line номер строки с ошибкой
	 * @param integer $maxLines максимальное число отображаемых строк
	 * @return string результат рендера
	 */
	protected function renderSourceCode($file,$errorLine,$maxLines)
	{
		$errorLine--;	// adjust line number to 0-based from 1-based
		if($errorLine<0 || ($lines=@file($file))===false || ($lineCount=count($lines))<=$errorLine)
			return '';

		$halfLines=(int)($maxLines/2);
		$beginLine=$errorLine-$halfLines>0 ? $errorLine-$halfLines:0;
		$endLine=$errorLine+$halfLines<$lineCount?$errorLine+$halfLines:$lineCount-1;
		$lineNumberWidth=strlen($endLine+1);

		$output='';
		for($i=$beginLine;$i<=$endLine;++$i)
		{
			$isErrorLine = $i===$errorLine;
			$code=sprintf("<span class=\"ln".($isErrorLine?' error-ln':'')."\">%0{$lineNumberWidth}d</span> %s",$i+1,CHtml::encode(str_replace("\t",'    ',$lines[$i])));
			if(!$isErrorLine)
				$output.=$code;
			else
				$output.='<span class="error">'.$code.'</span>';
		}
		return '<div class="code"><pre>'.$output.'</pre></div>';
	}
}
