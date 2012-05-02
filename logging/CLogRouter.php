<?php
/**
 * Файл класса CLogRouter.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Экземпляр класса CLogRouter управляет журнальными маршрутами, которые
 * пишут сообщения журнала в различные места назначения (файл, окно браузера, БД, консоль FireBug).
 *
 * Например, файловый журнальный маршрут {@link CFileLogRoute} пишет сообщения журнала
 * в файлы журнала. Журнальный маршрут электронной почты {@link CEmailLogRoute} отправляет сообщения
 * по определенным адресам email. За подробностями о различных журнальных маршрутах обратитесь к классу {@link CLogRoute}.
 *
 * Журнальные маршрут могут быть настроены в конфигурации приложения следующим образом:
 * <pre>
 * array(
 *     'preload'=>array('log'), // предзагрузка компонента журналирования при старте приложения
 *     'components'=>array(
 *         'log'=>array(
 *             'class'=>'CLogRouter',
 *             'routes'=>array(
 *                 array(
 *                     'class'=>'CFileLogRoute',
 *                     'levels'=>'trace, info',
 *                     'categories'=>'system.*',
 *                 ),
 *                 array(
 *                     'class'=>'CEmailLogRoute',
 *                     'levels'=>'error, warning',
 *                     'emails'=>array('admin@example.com'),
 *                 ),
 *             ),
 *         ),
 *     ),
 * )
 * </pre>
 *
 * Вы можете определить несколько маршрутов с различными условиями фильтрации и
 * местами назначения, даже если маршруты имеют одинаковый тип.
 *
 * @property array $routes текущие инициализированные маршруты
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CLogRouter.php 3426 2011-10-25 00:01:09Z alexander.makarow $
 * @package system.logging
 * @since 1.0
 */
class CLogRouter extends CApplicationComponent
{
	private $_routes=array();

	/**
	 * Инициализирует данный компонент приложения.
	 * Метод требуется интерфейсом IApplicationComponent.
	 */
	public function init()
	{
		parent::init();
		foreach($this->_routes as $name=>$route)
		{
			$route=Yii::createComponent($route);
			$route->init();
			$this->_routes[$name]=$route;
		}
		Yii::getLogger()->attachEventHandler('onFlush',array($this,'collectLogs'));
		Yii::app()->attachEventHandler('onEndRequest',array($this,'processLogs'));
	}

	/**
	 * @return array текущие инициализированные маршруты
	 */
	public function getRoutes()
	{
		return new CMap($this->_routes);
	}

	/**
	 * @param array $config список конфигурации маршрута. Каждый элемент массива представляет собой
	 * конфигурацию для отдельного маршрута и имеет следующую структуру массива:
	 * <ul>
	 * <li>класс: определяет имя класса или псевдоним для класса маршрута.</li>
	 * <li>пары имя-значени: начальные значения свойств маршрута.</li>
	 * </ul>
	 */
	public function setRoutes($config)
	{
		foreach($config as $name=>$route)
			$this->_routes[$name]=$route;
	}

	/**
	 * Собирает сообщения журнала от регистратора.
	 * Метод является обработчиком события {@link CLogger::onFlush}.
	 * @param CEvent $event параметр события
	 */
	public function collectLogs($event)
	{
		$logger=Yii::getLogger();
		$dumpLogs=isset($event->params['dumpLogs']) && $event->params['dumpLogs'];
		foreach($this->_routes as $route)
		{
			if($route->enabled)
				$route->collectLogs($logger,$dumpLogs);
		}
	}

	/**
	 * Собирает и обрабатывает сообщения журнала от регистратора.
	 * Метод является обработчиком события {@link CApplication::onEndRequest}.
	 * @param CEvent $event параметр события
	 * @since 1.1.0
	 */
	public function processLogs($event)
	{
		$logger=Yii::getLogger();
		foreach($this->_routes as $route)
		{
			if($route->enabled)
				$route->collectLogs($logger,true);
		}
	}
}
