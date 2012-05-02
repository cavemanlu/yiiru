<?php
/**
 * Файл класса CProfileLogRoute.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Компонент CProfileLogRoute отображает результаты профилирования на
 * веб-странице.
 *
 * Профилирование выполняется вызовом методов {@link YiiBase::beginProfile()} и
 * {@link YiiBase::endProfile()}, которые отмечают начало и конец блока кода.
 *
 * Компонент CProfileLogRoute поддерживает два типа отчетов установкой свойства
 * {@link setReport report}:
 * <ul>
 * <li>summary: список времени выполнения каждого блока кода;</li>
 * <li>callstack: список помеченных блоков кода в иерархии представления,
 * отображающей их последовательность вызовов.</li>
 * </ul>
 *
 * @property string $report установленный тип отображения отчета
 * профилирования. По умолчанию - 'summary'
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CProfileLogRoute.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.logging
 * @since 1.0
 */
class CProfileLogRoute extends CWebLogRoute
{
	/**
	 * @var boolean собирать ли результаты в соответствии с их токенами
	 * профилирования. Если значение равно false, результаты будут собраны по
	 * категориям. По умолчанию - true. Примечание: свойство имеет эффект
	 * только в случае, если свойство {@link report} установлено в значение
	 * 'summary'
	 */
	public $groupByToken=true;
	/**
	 * @var string тип отображения отчета профилирования
	 */
	private $_report='summary';

	/**
	 * Инициализирует маршрут.
	 * Метод вызывается после создания маршрута менеджером маршрутов
	 */
	public function init()
	{
		$this->levels=CLogger::LEVEL_PROFILE;
	}

	/**
	 * @return string установленный тип отображения отчета профилирования. По
	 * умолчанию - 'summary'
	 */
	public function getReport()
	{
		return $this->_report;
	}

	/**
	 * @param string $value установить тип отображения отчета профилирования.
	 * Допустимые значения - 'summary' и 'callstack'
	 */
	public function setReport($value)
	{
		if($value==='summary' || $value==='callstack')
			$this->_report=$value;
		else
			throw new CException(Yii::t('yii','CProfileLogRoute.report "{report}" is invalid. Valid values include "summary" and "callstack".',
				array('{report}'=>$value)));
	}

	/**
	 * Отображает сообщения журнала
	 * @param array $logs список собщений журнала
	 */
	public function processLogs($logs)
	{
		$app=Yii::app();
		if(!($app instanceof CWebApplication) || $app->getRequest()->getIsAjaxRequest())
			return;

		if($this->getReport()==='summary')
			$this->displaySummary($logs);
		else
			$this->displayCallstack($logs);
	}

	/**
	 * Отображает стек вызовов процедур профилирования
	 * @param array $logs список собщений журнала
	 */
	protected function displayCallstack($logs)
	{
		$stack=array();
		$results=array();
		$n=0;
		foreach($logs as $log)
		{
			if($log[1]!==CLogger::LEVEL_PROFILE)
				continue;
			$message=$log[0];
			if(!strncasecmp($message,'begin:',6))
			{
				$log[0]=substr($message,6);
				$log[4]=$n;
				$stack[]=$log;
				$n++;
			}
			else if(!strncasecmp($message,'end:',4))
			{
				$token=substr($message,4);
				if(($last=array_pop($stack))!==null && $last[0]===$token)
				{
					$delta=$log[3]-$last[3];
					$results[$last[4]]=array($token,$delta,count($stack));
				}
				else
					throw new CException(Yii::t('yii','CProfileLogRoute found a mismatching code block "{token}". Make sure the calls to Yii::beginProfile() and Yii::endProfile() be properly nested.',
						array('{token}'=>$token)));
			}
		}
		// remaining entries should be closed here
		$now=microtime(true);
		while(($last=array_pop($stack))!==null)
			$results[$last[4]]=array($last[0],$now-$last[3],count($stack));
		ksort($results);
		$this->render('profile-callstack',$results);
	}

	/**
	 * Отображает суммарный отчет результатов профилирования
	 * @param array $logs список собщений журнала
	 */
	protected function displaySummary($logs)
	{
		$stack=array();
		foreach($logs as $log)
		{
			if($log[1]!==CLogger::LEVEL_PROFILE)
				continue;
			$message=$log[0];
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
					if(!$this->groupByToken)
						$token=$log[2];
					if(isset($results[$token]))
						$results[$token]=$this->aggregateResult($results[$token],$delta);
					else
						$results[$token]=array($token,1,$delta,$delta,$delta);
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
			$token=$this->groupByToken ? $last[0] : $last[2];
			if(isset($results[$token]))
				$results[$token]=$this->aggregateResult($results[$token],$delta);
			else
				$results[$token]=array($token,1,$delta,$delta,$delta);
		}

		$entries=array_values($results);
		$func=create_function('$a,$b','return $a[4]<$b[4]?1:0;');
		usort($entries,$func);

		$this->render('profile-summary',$entries);
	}

	/**
	 * Собирает результаты отчета
	 * @param array $result результат журнала для данного блока кода
	 * @param float $delta время, затраченное на данный блок кода
	 * @return array
	 */
	protected function aggregateResult($result,$delta)
	{
		list($token,$calls,$min,$max,$total)=$result;
		if($delta<$min)
			$min=$delta;
		else if($delta>$max)
			$max=$delta;
		$calls++;
		$total+=$delta;
		return array($token,$calls,$min,$max,$total);
	}
}