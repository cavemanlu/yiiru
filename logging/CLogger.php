<?php
/**
 * Файл класса CLogger
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Компонент CLogger записывает сообщения журнала в памяти.
 *
 * Класс CLogger реализует методы для получения собщений с различными
 * условиями фильтрации, включающими в себя фильтры уровней и категорий
 * журнала.
 *
 * @property array $logs список сообщений. Каждый элемент массива представляет
 * собой одно сообщение со следующей структурой:
 * array(
 *   [0] => сообщение (string)
 *   [1] => уровень (string)
 *   [2] => категория (string)
 *   [3] => время (float, получено функцией microtime(true))
 * );
 * @property float $executionTime общее время, затраченное на данный запрос
 * @property integer $memoryUsage объем памяти, используемый данным приложением
 * (в байтах)
 * @property array $profilingResults результаты профилирования
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CLogger.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.logging
 * @since 1.0
 */
class CLogger extends CComponent
{
	const LEVEL_TRACE='trace';
	const LEVEL_WARNING='warning';
	const LEVEL_ERROR='error';
	const LEVEL_INFO='info';
	const LEVEL_PROFILE='profile';

	/**
	 * @var integer какое количество сообщений должно журналироваться прежде,
	 * чем они будут отправлены по месту назначения и удалены из памяти. По
	 * умолчанию - 10 000, т.е. каждые 10 000 сообщений будет автоматически
	 * вызываться метод {@link flush}. Если установлено значение 0, то
	 * сообщения никогда не будут автоматически удаляться
	 * @since 1.1.0
	 */
	public $autoFlush=10000;
	/**
	 * @var boolean данное свойство будет передано в качестве параметра в метод {@link flush()} при
	 * его вызове в методе {@link log()} из-за достижения предела, установленного свойством {@link autoFlush}.
	 * По умолчанию - false, т.е., отфильтрованные сообщения остаются в памяти после вызова метода
	 * {@link flush()}. Если значение свойства - true, то отфильтрованные сообщения будут писаться
	 * при каждом вызове метода {@link flush()} в методе {@link log()}
	 * @since 1.1.8
	 */
	public $autoDump=false;
	/**
	 * @var array сообщения журнала
	 */
	private $_logs=array();
	/**
	 * @var integer количество сообщений журнала
	 */
	private $_logCount=0;
	/**
	 * @var array уровни для фильтрации (используется при фильтрации)
	 */
	private $_levels;
	/**
	 * @var array категории для фильтрации (используется при фильтрации)
	 */
	private $_categories;
	/**
	 * @var array результат профилирования (категория, отметка => время в секундах)
	 */
	private $_timings;
	/**
	* @var boolean выполнять журналирование (вывод журнала), иначе считать, что
	* продолжается накопление новых сообщений журнала
	* @since 1.1.9
	*/
	private $_processing = false;

	/**
	 * Записывает сообщение в журнал.
	 * Записываемые данным методом сообщения могут быть получены методом {@link getLogs}.
	 * @param string $message журналируемое сообщение
	 * @param string $level уровень сообщения (например,  'Trace', 'Warning', 'Error'). Регистронезависим.
	 * @param string $category категория сообщения (например, 'system.web'). Регистронезависимо.
	 * @see getLogs
	 */
	public function log($message,$level='info',$category='application')
	{
		$this->_logs[]=array($message,$level,$category,microtime(true));
		$this->_logCount++;
		if($this->autoFlush>0 && $this->_logCount>=$this->autoFlush && !$this->_processing)
		{
			$this->_processing=true;
			$this->flush($this->autoDump);
			$this->_processing=false;
		}
	}

	/**
	 * Получает сообщения журнала.
	 *
	 * Сообщения могут быть отфильтрованы по уровням и/или категориям журнала.
	 * Фильтр уровней определен списком уровней, разделенных запятой или пробелом
	 * (например, 'trace, error'). Фильтр категорий определяется похожим образом
	 * (например, 'system, system.web'). Различие в том, что в фильтре категорий
	 * вы можете использовать такие шаблоны как 'system.*' для отображения всех категорий,
	 * начинающихся с 'system'.
	 *
	 * Если фильтр уровней не определен, то используются все уровни.
	 * То же самое и с категориями.
	 *
	 * Фильтры уровней и категорий являются комбинационными, т.е, возвращены будут только
	 * сообщения, удовлетворяющие условиям обоих фильтров.
	 *
	 * @param string $levels фильтр уровней
	 * @param string $categories фильтр категорий
	 * @return array список сообщений. Каждый элемент массива представляет собой одно сообщение
	 * со следующей структурой:
	 * array(
	 *   [0] => сообщение (string)
	 *   [1] => уровень (string)
	 *   [2] => категория (string)
	 *   [3] => время (float, получено функцией microtime(true))
	 * );
	 */
	public function getLogs($levels='',$categories='')
	{
		$this->_levels=preg_split('/[\s,]+/',strtolower($levels),-1,PREG_SPLIT_NO_EMPTY);
		$this->_categories=preg_split('/[\s,]+/',strtolower($categories),-1,PREG_SPLIT_NO_EMPTY);
		if(empty($levels) && empty($categories))
			return $this->_logs;
		else if(empty($levels))
			return array_values(array_filter(array_filter($this->_logs,array($this,'filterByCategory'))));
		else if(empty($categories))
			return array_values(array_filter(array_filter($this->_logs,array($this,'filterByLevel'))));
		else
		{
			$ret=array_values(array_filter(array_filter($this->_logs,array($this,'filterByLevel'))));
			return array_values(array_filter(array_filter($ret,array($this,'filterByCategory'))));
		}
	}

	/**
	 * Функция фильтрации по категориям, используемая методом {@link getLogs}
	 * @param array $value элемент, подвергаемый фильтрации
	 * @return array прошедший фильтрацию элемент; false, если элемент не прошел фильтрацию.
	 */
	private function filterByCategory($value)
	{
		foreach($this->_categories as $category)
		{
			$cat=strtolower($value[2]);
			if($cat===$category || (($c=rtrim($category,'.*'))!==$category && strpos($cat,$c)===0))
				return $value;
		}
		return false;
	}

	/**
	 * Функция фильтрации по уровням, используемая методом {@link getLogs}
	 * @param array $value элемент, подвергаемый фильтрации
	 * @return array valid прошедший фильтрацию элемент; false, если элемент не прошел фильтрацию.
	 */
	private function filterByLevel($value)
	{
		return in_array(strtolower($value[1]),$this->_levels)?$value:false;
	}

	/**
	 * Возвращает общее время, затраченное на данный запрос.
	 * Метод подсчитывает разницу между текущим временем и временем, определенным
	 * в константе YII_BEGIN_TIME.
	 * Чтобы оценить время выполнения более точно, константа должна быть установлена
	 * как можно раньше (лучше всего - в начале скрипта точки входа)
	 * @return float общее время, затраченное на данный запрос
	 */
	public function getExecutionTime()
	{
		return microtime(true)-YII_BEGIN_TIME;
	}

	/**
	 * Возвращает объем памяти, используемый данным приложением.
	 * Метод использует PHP-функцию memory_get_usage().
	 * Если она недоступна, метод попытается использовать программы операционной
	 * системы для определения объема памяти. Значение 0 возвращается в случае, если объем памяти
	 * не удалось определить.
	 * @return integer объем памяти, используемый данным приложением (в байтах)
	 */
	public function getMemoryUsage()
	{
		if(function_exists('memory_get_usage'))
			return memory_get_usage();
		else
		{
			$output=array();
			if(strncmp(PHP_OS,'WIN',3)===0)
			{
				exec('tasklist /FI "PID eq ' . getmypid() . '" /FO LIST',$output);
				return isset($output[5])?preg_replace('/[\D]/','',$output[5])*1024 : 0;
			}
			else
			{
				$pid=getmypid();
				exec("ps -eo%mem,rss,pid | grep $pid", $output);
				$output=explode("  ",$output[0]);
				return isset($output[1]) ? $output[1]*1024 : 0;
			}
		}
	}

	/**
	 * Возвращает результаты профилирования.
	 * Результаты могут быть отфильтрованы по метке и/или категории.
	 * Если фильтры не установлены, то возвращенные результаты будут представлять собой массив,
	 * каждый элемент которого - массив вида array($token,$category,$time).
	 * Если фильтр установлен, результаты будут массивом таймингов.
	 * @param string $token фильтр меток. По умолчанию - null - фильтрации по меткам нет.
	 * @param string $category фильтр категорий. По умолчанию - null - фильтрации по категориям нет.
	 * @param boolean $refresh должно ли быть проведено обновление вычислений внутренних таймингов.
	 * Если установлено в false, то только в первый вызов данного метода будет проведено вычисление внутренних таймингов.
	 * @return array результаты профилирования
	 */
	public function getProfilingResults($token=null,$category=null,$refresh=false)
	{
		if($this->_timings===null || $refresh)
			$this->calculateTimings();
		if($token===null && $category===null)
			return $this->_timings;
		$results=array();
		foreach($this->_timings as $timing)
		{
			if(($category===null || $timing[1]===$category) && ($token===null || $timing[0]===$token))
				$results[]=$timing[2];
		}
		return $results;
	}

	private function calculateTimings()
	{
		$this->_timings=array();

		$stack=array();
		foreach($this->_logs as $log)
		{
			if($log[1]!==CLogger::LEVEL_PROFILE)
				continue;
			list($message,$level,$category,$timestamp)=$log;
			if(!strncasecmp($message,'begin:',6))
			{
				$log[0]=substr($message,6);
				$stack[]=$log;
			}
			else if(!strncasecmp($message,'end:',4))
			{
				$token=substr($message,4);
				if(($last=array_pop($stack))!==null && $last[0]===$token)
				{
					$delta=$log[3]-$last[3];
					$this->_timings[]=array($message,$category,$delta);
				}
				else
					throw new CException(Yii::t('yii','CProfileLogRoute found a mismatching code block "{token}". Make sure the calls to Yii::beginProfile() and Yii::endProfile() be properly nested.',
						array('{token}'=>$token)));
			}
		}

		$now=microtime(true);
		while(($last=array_pop($stack))!==null)
		{
			$delta=$now-$last[3];
			$this->_timings[]=array($last[0],$last[2],$delta);
		}
	}

	/**
	 * Удаляет все записанные сообщения из памяти.
	 * Метод вызывает событие {@link onFlush}.
	 * Присоединенные обработчики событий могут обработать сообщения журнала
	 * перед их удалением
	 * @param boolean $dumpLogs проводить ли процесс журналирования немедленно
	 * как только сообщение передано в маршрут журналирования (сбрасывать
	 * сообщения в журнал)
	 * @since 1.1.0
	 */
	public function flush($dumpLogs=false)
	{
		$this->onFlush(new CEvent($this, array('dumpLogs'=>$dumpLogs)));
		$this->_logs=array();
		$this->_logCount=0;
	}

	/**
	 * Вызывает событие <code>onFlush</code>.
	 * @param CEvent $event параметр события
	 * @since 1.1.0
	 */
	public function onFlush($event)
	{
		$this->raiseEvent('onFlush', $event);
	}
}
