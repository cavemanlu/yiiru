<?php
/**
 * Файл класса CLogFilter
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * CLogFilter проводит предварительную обработку сообщений прежде, чем они будут обработаны журнальным маршрутизатором.
 *
 * CLogFilter предназначен для использования журнальным маршрутом для предварительной обработки
 * журналируемых сообщений пржде, чем они будут обработаны маршрутизатором.
 * По умолчанию реализация класса CLogFilter добавляет дополнительную информацию к
 * журналируемым сообщениям. В частности, установка в свойстве {@link logVars}
 * предопределенных PHP-переменных, таких как $_SERVER, $_POST и т.д. позволяет сохранить
 * их в сообщении журнала, что может помочь в выявлении/отладке встречающихся проблем.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CLogFilter.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.logging
 */
class CLogFilter extends CComponent
{
	/**
	 * @var boolean должен ли добавляться к каждому сообщению журнала префикс в
	 * виде идентификатора сессии текущего пользователя. По умолчанию - false.
	 */
	public $prefixSession=false;
	/**
	 * @var boolean должен ли добавляться к каждому сообщению журнала префикс в
	 * виде {@link CWebUser::name имени} и {@link CWebUser::id идентификатора} текущего пользователя. По умолчанию - false.
	 */
	public $prefixUser=false;
	/**
	 * @var boolean должны ли журналироваться имя и идентификатор текущего пользователя. По умолчанию - true.
	 * Данное свойство эффективно только если свойство {@link showContext} установлено в значение true.
	 */
	public $logUser=true;
	/**
	 * @var array список предопределенных PHP-переменных, которые должны быть журналированы.
	 * Примечание: переменные должны быть доступны в $GLOBALS. Иначе они не будут журналированы.
	 */
	public $logVars=array('_GET','_POST','_FILES','_COOKIE','_SESSION','_SERVER');


	/**
	 * Проводит фильтрацию переданных сообщений журнала.
	 * Главный метод класса CLogFilter. Обрабатывает сообщения журнала, добавляя контекстную информацию и т.п.
	 * @param array $logs сообщения журнала
	 * @return array
	 */
	public function filter(&$logs)
	{
		if (!empty($logs))
		{
		if(($message=$this->getContext())!=='')
			array_unshift($logs,array($message,CLogger::LEVEL_INFO,'application',YII_BEGIN_TIME));
		$this->format($logs);
		}
		return $logs;
	}

	/**
	 * Форматирует сообщения журнала.
	 * Реализация по умолчанию добавляет к каждому сообщению префикс в виде идентификатора сессии,
	 * если свойство {@link prefixSession} установлено в значение true, либо префикс в виде имени и
	 * идентификатора текущего пользователя, если свойство {@link prefixUser} установлено в значение true.
	 * @param array $logs сообщения журнала
	 */
	protected function format(&$logs)
	{
		$prefix='';
		if($this->prefixSession && ($id=session_id())!=='')
			$prefix.="[$id]";
		if($this->prefixUser && ($user=Yii::app()->getComponent('user',false))!==null)
			$prefix.='['.$user->getName().']['.$user->getId().']';
		if($prefix!=='')
		{
			foreach($logs as &$log)
				$log[0]=$prefix.' '.$log[0];
		}
	}

	/**
	 * Генерирует контекстную информацию для журналирования.
	 * Реализация по умлочанию собирает информацию пользователя, системные переменные и т.д.
	 * @return string контекстная информация. Если строка пуста, значит контекстной информации нет.
	 */
	protected function getContext()
	{
		$context=array();
		if($this->logUser && ($user=Yii::app()->getComponent('user',false))!==null)
			$context[]='User: '.$user->getName().' (ID: '.$user->getId().')';

		foreach($this->logVars as $name)
		{
			if(!empty($GLOBALS[$name]))
				$context[]="\${$name}=".var_export($GLOBALS[$name],true);
		}

		return implode("\n\n",$context);
	}
}