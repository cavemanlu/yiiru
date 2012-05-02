<?php
/**
 * Файл класса CWebLogRoute.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Объект класса CWebLogRoute показывает содержимое журнала на веб-странице.
 *
 * Содержимое журнала может отображаться либо в конце текущей страницы либо
 * в окне консоли FireBug (если свойство {@link showInFireBug} установлено в true).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CWebLogRoute.php 3588 2012-02-17 21:44:26Z qiang.xue@gmail.com $
 * @package system.logging
 * @since 1.0
 */
class CWebLogRoute extends CLogRoute
{
	/**
	 * @var boolean должно ли содержимое журнала отображаться в окне консоли
	 * FireBug вместо окна браузера. По умолчанию установлено в false.
	 */
	public $showInFireBug=false;

	/**
	 * @var boolean должно ли игнорироваться журналирование в FireBug'е для ajax-вызовов. По умолчанию - true.
	 * Данная настройка должна использоваться осторожно, т.к. ajax-вызов возвращает в качестве результирующих данных все выходные данные.
	 * Например, если ajax-вызов ожидает результат типа json, то любые выходные данные журнала будут вызывать ошибку выполнения ajax-вызова.
	 */
	public $ignoreAjaxInFireBug=true;

	/**
	 * @var boolean должен ли журнал быть проигнорирован в FireBug для вызовов
	 * Flash/Flex. По умолчанию - true. Данное значени нужно импользовать с
	 * осторожностью, т.к. вызовы Flash/Flex возвращают контент как
	 * результирующие данные. Например, если вызов Flash/Flex call ожидает
	 * результат типа XML, то любой контент из журнала будет являться причиной
	 * неуспешности вызова
	 * Flash/Flex
	 * @since 1.1.11
	 */
	public $ignoreFlashInFireBug=true;

	/**
	 * Отображает сообщения журнала.
	 * @param array $logs список сообщений журнала
	 */
	public function processLogs($logs)
	{
		$this->render('log',$logs);
	}

	/**
	 * Рендерит представление.
	 * @param string $view имя представления (имя файла без расширения). Предполагается,
	 * что файл находится в каталоге framework/data/views.
	 * @param array $data данные, передающиеся в представление
	 */
	protected function render($view,$data)
	{
		$app=Yii::app();
		$isAjax=$app->getRequest()->getIsAjaxRequest();
		$isFlash=$app->getRequest()->getIsFlashRequest();

		if($this->showInFireBug)
		{
			// do not output anything for ajax and/or flash requests if needed
			if($isAjax && $this->ignoreAjaxInFireBug || $isFlash && $this->ignoreFlashInFireBug)
				return;
			$view.='-firebug';
		}
		else if(!($app instanceof CWebApplication) || $isAjax || $isFlash)
			return;

		$viewFile=YII_PATH.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.$view.'.php';
		include($app->findLocalizedFile($viewFile,'en'));
	}
}

