<?php
/**
 * Файл класса CLogRoute.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * CLogRoute - это базовый класс для всех классов журнальных маршрутов.
 *
 * Объект маршрута получает сообщения журнала от регистратора и отправляет их в некоторое место, например,
 * в файлы, на email-адреса и т.д.
 * Полученные сообщения могут быть отфильтрованы перед отправкой их по назначению.
 * Фильтры включают в себя фильтры уровней и категорий журнала.
 *
 * Для определения фильтра уровней, установите свойство {@link levels},
 * которое принимает строку, с именами желаемых уровней, разделенными запятой (например, 'Error, Debug').
 * Для определения фильтра категорий, установите свойство {@link categories},
 * которое принимает строку, с именами желаемых категорий, разделенными запятой (например, 'System.Web, System.IO').
 *
 * Фильтры уровней и категорий являются комбинационными, т.е, возвращены будут только
 * сообщения, удовлетворяющие условиям обоих фильтров.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CLogRoute.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.logging
 * @since 1.0
 */
abstract class CLogRoute extends CComponent
{
	/**
	 * @var boolean активен ли данный маршрут журнала. По умолчанию - true
	 */
	public $enabled=true;
	/**
	 * @var string список уровней, разделенных запятой или пробелом. По умолчанию пусто, что означает - все уровни.
	 */
	public $levels='';
	/**
	 * @var string список категорий, разделенных запятой или пробелом. По умолчанию пусто, что означает - все категории.
	 */
	public $categories='';
	/**
	 * @var mixed дополнительный фильтр (например, {@link CLogFilter}), применяемый в сообщениям журнала.
	 * Значение данного свойства будет передано методу {@link Yii::createComponent} для создания
	 * объекта фильтра журнала. Поэтому, оно может быть либо строкой, представляющей имя класса фильтра
	 * или массивом, представляющим конфигурацию фильтра.
	 * В общем, класс фильтра журнала должен быть классом {@link CLogFilter} или его наследником.
	 * По умолчанию - null, что значит - без использования фильтра
	 */
	public $filter;
	/**
	 * @var array журналы, собранные данным журнальным маршрутом
	 * @since 1.1.0
	 */
	public $logs;


	/**
	 * Инициализирует маршрут.
	 * Метод вызывается после создания маршрута менеджером маршрутов.
	 */
	public function init()
	{
	}

	/**
	 * Форматирует сообщение журнала согласно переданным параметрам.
	 * @param string $message сообщение
	 * @param integer $level уровень сообщения
	 * @param string $category категория сообщения
	 * @param integer $time временная отметка
	 * @return string отформатированное сообщение
	 */
	protected function formatLogMessage($message,$level,$category,$time)
	{
		return @date('Y/m/d H:i:s',$time)." [$level] [$category] $message\n";
	}

	/**
	 * Получает отфильтрованные сообщения журнала из регистратора сообщений журнала для дальнейшей обработки.
	 * @param CLogger $logger экземпляр регистратора сообщений журнала
	 * @param boolean $processLogs обрабатывать ли сообщения журнала после их сбора из регистратора
	 */
	public function collectLogs($logger, $processLogs=false)
	{
		$logs=$logger->getLogs($this->levels,$this->categories);
		$this->logs=empty($this->logs) ? $logs : array_merge($this->logs,$logs);
		if($processLogs && !empty($this->logs))
		{
			if($this->filter!==null)
				Yii::createComponent($this->filter)->filter($this->logs);
			$this->processLogs($this->logs);
			$this->logs=array();
		}
	}

	/**
	 * Обрабатывает сообщения журнала и отправляет их по определенному назначению.
	 * Классы-потомки должны реализовать этот метод.
	 * @param array $logs список сообщений. Каждый элемент массива представляет собой одно сообщение
	 * со следующей структурой:
	 * array(
	 *   [0] => сообщение (string)
	 *   [1] => уровень (string)
	 *   [2] => категория (string)
	 *   [3] => время (float, получено функцией microtime(true));
	 */
	abstract protected function processLogs($logs);
}
