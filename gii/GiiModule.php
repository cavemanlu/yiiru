<?php
/**
 * Файл класса GiiModule.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('system.gii.CCodeGenerator');
Yii::import('system.gii.CCodeModel');
Yii::import('system.gii.CCodeFile');
Yii::import('system.gii.CCodeForm');

/**
 * GiiModule - это модуль, предоставляющий возможности генерации кода в виде веб-страницы.
 *
 * Для использования модуля GiiModule вы должны включить его в конфигурации приложения подобным образом:
 * <pre>
 * return array(
 *     ......
 *     'modules'=>array(
 *         'gii'=>array(
 *             'class'=>'system.gii.GiiModule',
 *             'password'=>***выберите пароль***
 *         ),
 *     ),
 * )
 * </pre>
 *
 * Из-за того, что модуль GiiModule генерирует новые файлы кода на сервере, вы должны использовать его
 * только на своей машине для разработки. Для предотвращения использования модуля другими людьми, вам
 * необходимо установить секретный пароль в конфигурации. Позже, когды вы будете пытаться получить доступ
 * к модулю через браузер, вам будет предложено ввести правильный пароль.
 *
 * По умолчанию доступ к модулю GiiModule позволен только с локальной машины. Вы можете сконфигурировать
 * свойство {@link ipFilters}, если хотите открыть доступ к модулю с других машин.
 *
 * С использованием вышеприведенной конфигурации вы можете получить доступ к модулю GiiModule из своего браузера
 * по следующему URL-адресу:
 *
 * http://localhost/path/to/index.php?r=gii
 *
 * Если ваше приложение использует формат URL-адресов в виде пути с некоторыми настроенными URL-правилами,
 * то вам может понадобиться добавить следующие URL-адреса в вашу конфигурацию приложения для доступа к модулю GiiModule:
 * <pre>
 * 'components'=>array(
 *     'urlManager'=>array(
 *         'urlFormat'=>'path',
 *         'rules'=>array(
 *             'gii'=>'gii',
 *             'gii/<controller:\w+>'=>'gii/<controller>',
 *             'gii/<controller:\w+>/<action:\w+>'=>'gii/<controller>/<action>',
 *             ...другие правила...
 *         ),
 *     )
 * )
 * </pre>
 *
 * Теперь можно получить доступ к модулю по адресу:
 * http://localhost/path/to/index.php/gii
 *
 * @property string $assetsUrl базовый URL, содержащий все публикуемые файлы ресурсов модуля gii
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: GiiModule.php 3426 2011-10-25 00:01:09Z alexander.makarow $
 * @package system.gii
 * @since 1.1.2
 */
class GiiModule extends CWebModule
{
	/**
	 * @var string пароль, используемый для доступа к модулю GiiModule.
	 * Если данное свойство установлено в значение false, то к модулю GiiModule можно получить доступ без пароля
	 * (НЕ ДЕЛАЙТЕ ЭТОГО, ЕСЛИ НЕ ПОНИМАЕТЕ ПОСЛЕДСТВИЙ!!!)
	 */
	public $password;
	/**
	 * @var array фильтр IP-адресов, определяющий, с каких IP-адреса допустимо обращаться к модулю GiiModule.
	 * Каждый элемент представляет отдельный фильтр. Фильтр может быть либо IP-адресом либо
	 * адресом с подстановочным символом (например, 192.168.0.*) для представления сегмента сети.
	 * Если вы хотите предоставить доступ к gii со всех IP-адресов, вы можете установить данное свойство в значение false
	 * (НЕ ДЕЛАЙТЕ ЭТОГО, ЕСЛИ НЕ ПОНИМАЕТЕ ПОСЛЕДСТВИЙ!!!)
	 * Значение по умолчанию - array('127.0.0.1', '::1'), т.е., модуль GiiModule доступен только на локальной
	 * машине (localhost)
	 */
	public $ipFilters=array('127.0.0.1','::1');
	/**
	 * @var array список псевдонимов путей к директориям, содержащим генераторы кода.
	 * Директория, определяемая одним псевдонимом пути может сожержать множество генераторов
	 * кода, каждый из которых находится в поддиректории с именем генератора кода.
	 * По умолчанию имеет значение array('application.gii')
	 */
	public $generatorPaths=array('application.gii');
	/**
	 * @var integer права, устанавливаемые для новых сгенерированных файлов.
	 * Данное значение будет использовано PHP-функцией chmod.
	 * По умолчанию - 0666, т.е., файл доступен на чтение, запись и выполнение всеми пользователями
	 */
	public $newFileMode=0666;
	/**
	 * @var integer права, устанавливаемые для новой сгенерированной директории.
	 * Данное значение будет использовано PHP-функцией chmod.
	 * По умолчанию - 0777, т.е., директория доступна на чтение, запись и выполнение всеми пользователями
	 */
	public $newDirMode=0777;

	private $_assetsUrl;

	/**
	 * Инициализирует модуль gii
	 */
	public function init()
	{
		parent::init();
		Yii::app()->setComponents(array(
			'errorHandler'=>array(
				'class'=>'CErrorHandler',
				'errorAction'=>$this->getId().'/default/error',
			),
			'user'=>array(
				'class'=>'CWebUser',
				'stateKeyPrefix'=>'gii',
				'loginUrl'=>Yii::app()->createUrl($this->getId().'/default/login'),
			),
		), false);
		$this->generatorPaths[]='gii.generators';
		$this->controllerMap=$this->findGenerators();
	}

	/**
	 * @return string базовый URL, содержащий все публикуемые файлы ресурсов модуля gii
	 */
	public function getAssetsUrl()
	{
		if($this->_assetsUrl===null)
			$this->_assetsUrl=Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias('gii.assets'));
		return $this->_assetsUrl;
	}

	/**
	 * @param string $value базовый URL, содержащий все публикуемые файлы ресурсов модуля gii
	 */
	public function setAssetsUrl($value)
	{
		$this->_assetsUrl=$value;
	}

	/**
	 * Выполняет проверку доступа к gii.
	 * Метод проверяет IP-адрес и пароль пользователя на допустимость доступа пользователя к
	 * действиям, отличным от "default/login" и "default/error"
	 * @param CController $controller контроллер, к которому происходит обращение
	 * @param CAction $action действие, к которому происходит обращение
	 * @return boolean должно ли действие быть выполнено
	 */
	public function beforeControllerAction($controller, $action)
	{
		if(parent::beforeControllerAction($controller, $action))
		{
			$route=$controller->id.'/'.$action->id;
			if(!$this->allowIp(Yii::app()->request->userHostAddress) && $route!=='default/error')
				throw new CHttpException(403,"You are not allowed to access this page.");

			$publicPages=array(
				'default/login',
				'default/error',
			);
			if($this->password!==false && Yii::app()->user->isGuest && !in_array($route,$publicPages))
				Yii::app()->user->loginRequired();
			else
				return true;
		}
		return false;
	}

	/**
	 * Проверяет, допустим ли пользователь фильтром {@link ipFilters}
	 * @param string $ip IP-адрес пользователя
	 * @return boolean допустим ли пользователь фильтром {@link ipFilters}
	 */
	protected function allowIp($ip)
	{
		if(empty($this->ipFilters))
			return true;
		foreach($this->ipFilters as $filter)
		{
			if($filter==='*' || $filter===$ip || (($pos=strpos($filter,'*'))!==false && !strncmp($ip,$filter,$pos)))
				return true;
		}
		return false;
	}

	/**
	 * Находит все доступные генераторы кода и их шаблоны кода
	 * @return array
	 */
	protected function findGenerators()
	{
		$generators=array();
		$n=count($this->generatorPaths);
		for($i=$n-1;$i>=0;--$i)
		{
			$alias=$this->generatorPaths[$i];
			$path=Yii::getPathOfAlias($alias);
			if($path===false || !is_dir($path))
				continue;

			$names=scandir($path);
			foreach($names as $name)
			{
				if($name[0]!=='.' && is_dir($path.'/'.$name))
				{
					$className=ucfirst($name).'Generator';
					if(is_file("$path/$name/$className.php"))
					{
						$generators[$name]=array(
							'class'=>"$alias.$name.$className",
						);
					}

					if(isset($generators[$name]) && is_dir("$path/$name/templates"))
					{
						$templatePath="$path/$name/templates";
						$dirs=scandir($templatePath);
						foreach($dirs as $dir)
						{
							if($dir[0]!=='.' && is_dir($templatePath.'/'.$dir))
								$generators[$name]['templates'][$dir]=strtr($templatePath.'/'.$dir,array('/'=>DIRECTORY_SEPARATOR,'\\'=>DIRECTORY_SEPARATOR));
						}
					}
				}
			}
		}
		return $generators;
	}
}