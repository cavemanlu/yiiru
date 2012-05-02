<?php
/**
 * Файл класса CDbLogRoute.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */


/**
 * Компонент CDbLogRoute сохраняет сообщения журнала в таблице базы данных.
 *
 * Для определения таблицы БД для хранения сообщений журнала, установите в свойстве {@link logTableName}
 * имя таблицы и в свойстве {@link connectionID} идентификатор компонента приложения {@link CDbConnection}.
 * Если они не установлены, будет создана база данных SQLite3 'log-YiiVersion.db'
 * в директории времени выполнения приложения.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CDbLogRoute.php 3069 2011-03-14 00:28:38Z qiang.xue $
 * @package system.logging
 * @since 1.0
 */
class CDbLogRoute extends CLogRoute
{
	/**
	 * @var string идентификатор компонента приложения CDbConnection. Если не установлен, будет автоматически
	 * создана и будет использоваться база SQLite. Файл базы данных SQLite -
	 * <code>protected/runtime/log-YiiVersion.db</code>.
	 */
	public $connectionID;
	/**
	 * @var string имя таблицы БД, в которой хранятся сообщения журнала. По умолчанию - 'YiiLog'.
	 * Если свойство {@link autoCreateLogTable} установлено в значение false и вы хотите создать таблицу
	 * вручную, вы должны быть уверены, что таблица имеет следующую структуру:
	 * <pre>
	 *  (
	 *		id       INTEGER NOT NULL PRIMARY KEY,
	 *		level    VARCHAR(128),
	 *		category VARCHAR(128),
	 *		logtime  INTEGER,
	 *		message  TEXT
	 *   )
	 * </pre>
	 * Помните, что столбец 'id' должен быть создан как автоинкрементный.
	 * В MySQL должно быть <code>id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY</code>;
	 * В PostgreSQL - <code>id SERIAL PRIMARY KEY</code>.
	 * @see autoCreateLogTable
	 */
	public $logTableName='YiiLog';
	/**
	 * @var boolean должна ли таблица БД для хранения сообщений журнала создаваться автоматически. По умолчанию - true.
	 * @see logTableName
	 */
	public $autoCreateLogTable=true;
	/**
	 * @var CDbConnection экземпляр соединения БД
	 */
	private $_db;

	/**
	 * Инициализирует маршрут.
	 * Метод вызывается после создания маршрута менеджером маршрутов.
	 */
	public function init()
	{
		parent::init();

		if($this->autoCreateLogTable)
		{
			$db=$this->getDbConnection();
			$sql="DELETE FROM {$this->logTableName} WHERE 0=1";
			try
			{
				$db->createCommand($sql)->execute();
			}
			catch(Exception $e)
			{
				$this->createLogTable($db,$this->logTableName);
			}
		}
	}

	/**
	 * Создает в БД таблицу для хранения сообщений журнала.
	 * @param CDbConnection $db соединение БД
	 * @param string $tableName имя создаваемой таблицы
	 */
	protected function createLogTable($db,$tableName)
	{
		$driver=$db->getDriverName();
		if($driver==='mysql')
			$logID='id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY';
		else if($driver==='pgsql')
			$logID='id SERIAL PRIMARY KEY';
		else
			$logID='id INTEGER NOT NULL PRIMARY KEY';

		$sql="
CREATE TABLE $tableName
(
	$logID,
	level VARCHAR(128),
	category VARCHAR(128),
	logtime INTEGER,
	message TEXT
)";
		$db->createCommand($sql)->execute();
	}

	/**
	 * @return CDbConnection экземпляр соединения БД
	 * @throws CException вызывается, если {@link connectionID} не указывает на допустимый компонент приложения.
	 */
	protected function getDbConnection()
	{
		if($this->_db!==null)
			return $this->_db;
		else if(($id=$this->connectionID)!==null)
		{
			if(($this->_db=Yii::app()->getComponent($id)) instanceof CDbConnection)
				return $this->_db;
			else
				throw new CException(Yii::t('yii','CDbLogRoute.connectionID "{id}" does not point to a valid CDbConnection application component.',
					array('{id}'=>$id)));
		}
		else
		{
			$dbFile=Yii::app()->getRuntimePath().DIRECTORY_SEPARATOR.'log-'.Yii::getVersion().'.db';
			return $this->_db=new CDbConnection('sqlite:'.$dbFile);
		}
	}

	/**
	 * Сохраняет сообщения журнала в БД.
	 * @param array $logs список сообщений журнала
	 */
	protected function processLogs($logs)
	{
		$sql="
INSERT INTO {$this->logTableName}
(level, category, logtime, message) VALUES
(:level, :category, :logtime, :message)
";
		$command=$this->getDbConnection()->createCommand($sql);
		foreach($logs as $log)
		{
			$command->bindValue(':level',$log[1]);
			$command->bindValue(':category',$log[2]);
			$command->bindValue(':logtime',(int)$log[3]);
			$command->bindValue(':message',$log[0]);
			$command->execute();
		}
	}
}
