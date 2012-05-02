<?php
/**
 * Файл класса CDbCache
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CDbCache реализует кэш-компонент приложения, сохраняя кэшируемые данные в БД.
 *
 * Компонент CDbCache сохраняет кэшируемые данные в таблице БД с именем, определяемым свойством {@link cacheTableName}.
 * Если таблица не существует, она будет автоматически создана.
 * Установив свойство {@link autoCreateCacheTable} в false, вы можете создать таблицу вручную.
 *
 * CDbCache основывается на {@link http://www.php.net/manual/en/ref.pdo.php PDO} при доступе к БД.
 * По умолчанию спользуется БД SQLite3 в рабочей директории приложения.
 * Вы также можете определить свойство {@link connectionID} для использования компонента приложения БД
 * для доступа к БД.
 *
 * Обратитесь к документации {@link CCache} за информацией об обычных операциях кэша, поддерживаемых компонентом CDbCache.
 *
 * @property integer $gCProbability вероятность (частей на миллион) выполнения
 * "сбора мусора" (GC) при сохранении части данных в кэше. По умолчанию - 100,
 * что означает 0.01% шанс
 * @property CDbConnection $dbConnection экземпляр соединения БД
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CDbCache.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.caching
 * @since 1.0
 */
class CDbCache extends CCache
{
	/**
	 * @var string идентификатор компонента приложения {@link CDbConnection}. Если не установлен,
	 * БД SQLite3 будет автоматически создана. Файл БД SQLite - 
	 * <code>protected/runtime/cache-YiiVersion.db</code>.
	 */
	public $connectionID;
	/**
	 * @var string имя таблицы БД, хранящей кэш. По умолчанию - 'YiiCache'.
	 * Примечание: если свойство {@link autoCreateCacheTable} установлено в false и вы хотите создать
	 * таблицу БД вручную, вы должны создать таблицу со следующей структурой:
	 * <pre>
	 * (id CHAR(128) PRIMARY KEY, expire INTEGER, value BLOB)
	 * </pre>
	 * Примечание: некоторые СУБД могут не поддерживать тип BLOB. В этом случае, замените 'BLOB' на подходящий
	 * бинарный тип данных (например, LONGBLOB в MySQL, BYTEA в PostgreSQL.)
	 * @see autoCreateCacheTable
	 */
	public $cacheTableName='YiiCache';
	/**
	 * @var boolean должна ли таблица БД создаваться автоматически, если не существует. По умолчанию - true.
	 * Если у вас уже есть таблица, рекомендуется установить данное свойство в значение false для увеличения производительности.
	 * @see cacheTableName
	 */
	public $autoCreateCacheTable=true;
	/**
	 * @var CDbConnection экземпляр соединения БД
	 */
	private $_db;
	private $_gcProbability=100;
	private $_gced=false;

	/**
	 * Инициализирует данный компонент приложения.
	 * Метод требуется интерфейсом {@link IApplicationComponent}.
	 * Гарантирует существование таблицы для хранения кэша.
	 * Также удаляет просроченные данные из кэша.
	 */
	public function init()
	{
		parent::init();

		$db=$this->getDbConnection();
		$db->setActive(true);

		if($this->autoCreateCacheTable)
		{
			$sql="DELETE FROM {$this->cacheTableName} WHERE expire>0 AND expire<".time();
			try
			{
				$db->createCommand($sql)->execute();
			}
			catch(Exception $e)
			{
				$this->createCacheTable($db,$this->cacheTableName);
			}
		}
	}

	/**
	 * @return integer вероятность (частей на миллион) выполнения "сбора мусора" (GC) при сохранении
	 * части данных в кэше. По умолчанию - 100, что означает 0.01% шанс
	 */
	public function getGCProbability()
	{
		return $this->_gcProbability;
	}

	/**
	 * @param integer $value вероятность (частей на миллион) выполнения "сбора мусора" (GC) при сохранении
	 * части данных в кэше. По умолчанию - 100, что означает 0.01% шанс.
	 * Число должно быть в диапазоне 0 и 1000000. Значение 0 означает, что сбор мусора производиться не будет
	 */
	public function setGCProbability($value)
	{
		$value=(int)$value;
		if($value<0)
			$value=0;
		if($value>1000000)
			$value=1000000;
		$this->_gcProbability=$value;
	}

	/**
	 * Создает в БД таблицу для хранения кэша.
	 * @param CDbConnection $db соединение БД
	 * @param string $tableName имя создаваемой таблицы
	 */
	protected function createCacheTable($db,$tableName)
	{
		$driver=$db->getDriverName();
		if($driver==='mysql')
			$blob='LONGBLOB';
		else if($driver==='pgsql')
			$blob='BYTEA';
		else
			$blob='BLOB';
		$sql=<<<EOD
CREATE TABLE $tableName
(
	id CHAR(128) PRIMARY KEY,
	expire INTEGER,
	value $blob
)
EOD;
		$db->createCommand($sql)->execute();
	}

	/**
	 * @return CDbConnection экземпляр соединения БД
	 * @throws CException вызывается, если {@link connectionID} не указывает на доступный компонент приложения
	 */
	public function getDbConnection()
	{
		if($this->_db!==null)
			return $this->_db;
		else if(($id=$this->connectionID)!==null)
		{
			if(($this->_db=Yii::app()->getComponent($id)) instanceof CDbConnection)
				return $this->_db;
			else
				throw new CException(Yii::t('yii','CDbCache.connectionID "{id}" is invalid. Please make sure it refers to the ID of a CDbConnection application component.',
					array('{id}'=>$id)));
		}
		else
		{
			$dbFile=Yii::app()->getRuntimePath().DIRECTORY_SEPARATOR.'cache-'.Yii::getVersion().'.db';
			return $this->_db=new CDbConnection('sqlite:'.$dbFile);
		}
	}

	/**
	 * Устанавливает соединение БД, используемое компонентом кэша
	 * @param CDbConnection $value экземпляр соединения БД
	 * @since 1.1.5
	 */
	public function setDbConnection($value)
	{
		$this->_db=$value;
	}

	/**
	 * Получает значение из кэша по определенному ключу.
	 * Метод переопределяет реализацию класса-родителя
	 * @param string $key уникальный ключ, идентифицирующий кэшированное значение
	 * @return string хранимое в кэше значение; false, если значения в кэше нет или его срок годности истек
	 */
	protected function getValue($key)
	{
		$time=time();
		$sql="SELECT value FROM {$this->cacheTableName} WHERE id='$key' AND (expire=0 OR expire>$time)";
		$db=$this->getDbConnection();
		if($db->queryCachingDuration>0)
		{
			$duration=$db->queryCachingDuration;
			$db->queryCachingDuration=0;
			$result=$db->createCommand($sql)->queryScalar();
			$db->queryCachingDuration=$duration;
			return $result;
		}
		else
			return $db->createCommand($sql)->queryScalar();
	}

	/**
	 * Получает из кэша несколько значений с определенными ключами
	 * @param array $keys список ключей, идентифицирующих кэшированные значения
	 * @return array список кэшированных значений, индексированный по ключам
	 */
	protected function getValues($keys)
	{
		if(empty($keys))
			return array();

		$ids=implode("','",$keys);
		$time=time();
		$sql="SELECT id, value FROM {$this->cacheTableName} WHERE id IN ('$ids') AND (expire=0 OR expire>$time)";

		$db=$this->getDbConnection();
		if($db->queryCachingDuration>0)
		{
			$duration=$db->queryCachingDuration;
			$db->queryCachingDuration=0;
			$rows=$db->createCommand($sql)->queryAll();
			$db->queryCachingDuration=$duration;
		}
		else
			$rows=$db->createCommand($sql)->queryAll();

		$results=array();
		foreach($keys as $key)
			$results[$key]=false;
		foreach($rows as $row)
			$results[$row['id']]=$results[$row['value']];
		return $results;
	}

	/**
	 * Сохраняет в кэше значение, идентифицируемое ключом.
	 * Метод переопределяет реализацию класса-родителя
	 * @param string $key ключ, идентифицирующий кэшируемое значение
	 * @param string $value кэшируемое значение
	 * @param integer $expire количество секунд срока годности кэшируемого значения. 0 - без срока годности
	 * @return boolean true, если значение успешно сохранено в кэше, иначе false
	 */
	protected function setValue($key,$value,$expire)
	{
		$this->deleteValue($key);
		return $this->addValue($key,$value,$expire);
	}

	/**
	 * Сохраняет в кэше значение, идентифицируемое ключом, если кэш не содержит данный ключ.
	 * Метод переопределяет реализацию класса-родителя.
	 * @param string $key ключ, идентифицирующий кэшируемое значение
	 * @param string $value кэшируемое значение
	 * @param integer $expire количество секунд срока годности кэшируемого значения. 0 - без срока годности
	 * @return boolean true, если значение успешно сохранено в кэше, иначе false
	 */
	protected function addValue($key,$value,$expire)
	{
		if(!$this->_gced && mt_rand(0,1000000)<$this->_gcProbability)
		{
			$this->gc();
			$this->_gced=true;
		}

		if($expire>0)
			$expire+=time();
		else
			$expire=0;
		$sql="INSERT INTO {$this->cacheTableName} (id,expire,value) VALUES ('$key',$expire,:value)";
		try
		{
			$command=$this->getDbConnection()->createCommand($sql);
			$command->bindValue(':value',$value,PDO::PARAM_LOB);
			$command->execute();
			return true;
		}
		catch(Exception $e)
		{
			return false;
		}
	}

	/**
	 * Удаляет из кеша значение по определенному ключу.
	 * Метод переопределяет реализацию класса-родителя.
	 * @param string $key ключ удаляемого значения
	 * @return boolean true, если в процессе удаления не произошло ошибок
	 */
	protected function deleteValue($key)
	{
		$sql="DELETE FROM {$this->cacheTableName} WHERE id='$key'";
		$this->getDbConnection()->createCommand($sql)->execute();
		return true;
	}

	/**
	 * Удаляет значения данных с истёкшим сроком годности
	 */
	protected function gc()
	{
		$this->getDbConnection()->createCommand("DELETE FROM {$this->cacheTableName} WHERE expire>0 AND expire<".time())->execute();
	}

	/**
	 * Удаляет все значения из кэша.
	 * Это реализация метода, объявленного в классе-родителе
	 * @return boolean успешно ли выполнилась операция очистки
	 * @since 1.1.5
	 */
	protected function flushValues()
	{
		$this->getDbConnection()->createCommand("DELETE FROM {$this->cacheTableName}")->execute();
		return true;
	}
}
