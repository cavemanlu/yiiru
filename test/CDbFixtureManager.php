<?php
/**
 * Файл класса CDbFixtureManager.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Компонент CDbFixtureManager управляет фикстурами базы данных в процессе тестирования.
 *
 * Фикстура представляет собой список строк для определенной таблицы. Для тестового метода,
 * использование фикстур означает, что в начале метода имеет только строки, переданные в фикстуре.
 * Таким образом, состояние таблицы предсказуемо.
 *
 * Фикстура представлена PHP-скриптом с именем (без суффикса) таким же, как и имя
 * таблицы (если требуется имя схемы, оно должно предварять имя таблицы).
 * PHP-скрипт возвращает массив, представляющий список строк таблицы.
 * Каждая строка - это ассоциативный массив значений столбцов индексированный по именам столбцов.
 *
 * Фикстура может быть ассоциирована со скриптом инициализации, находящимся в той же директории, что и фикстура
 * и имеющим имя вида "TableName.init.php". Срипт инициализации используется для
 * инициализации таблицы перед вставкой данныйх фикстуры в нее.
 * Если скрипт инициализации не существует, таблица будет очищена.
 *
 * Фикстуры должны храниться в директории, заданной свойсвтом {@link basePath}.
 * Директория может содержать файл с именем "init.php", который единожды выполняется
 * для инициализации базы данных. Если такой файл не существует, все доступные фикстуры будут
 * загружены в базу данных.
 *
 * @property CDbConnection $dbConnection соединение БД
 * @property array $fixtures информация доступных фикстур (имя таблицы => файл фикстуры)
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CDbFixtureManager.php 3426 2011-10-25 00:01:09Z alexander.makarow $
 * @package system.test
 * @since 1.1
 */
class CDbFixtureManager extends CApplicationComponent
{
	/**
	 * @var string имя скрипта инициализации, выполняющимся перед запуском всего тестового набора.
	 * По умолчанию - 'init.php'. Если скрипт не существует, каждая таблица с файлом фикстуры будет сброшена.
	 */
	public $initScript='init.php';
	/**
	 * @var string суффикс скрипта инициализации фикстуры.
	 * Для таблицы, ассоциированной со скриптом с именем вида 'TableName.значение данного свойства',
	 * каждый раз перед сбросом этой таблицы выполняется данный скрипт.
	 */
	public $initScriptSuffix='.init.php';
	/**
	 * @var string базовый путь к директории, содержащей все фикстуры. По умолчанию - null, т.е.
	 * используется путь 'protected/tests/fixtures'.
	 */
	public $basePath;
	/**
	 * @var string идентификатор соединения БД. По умолчанию - 'db'.
	 * Примечание: данные базы могут быть удалены или модифицированы в процессе тестирования.
	 * Убедитесь, что у вас есть резервная копия базы данных.
	 */
	public $connectionID='db';
	/**
	 * @var array список схем базы данных, в которых могут находиться тестовые таблицы.
	 * По умолчанию - array(''), т.е. используется схема по умолчанию (пустая строка означает схему по умолчанию).
	 * В основном. свойство используется для включения и выключения проверки целостности базы,
	 * чтобы данные фикстур могли быть заполнены в базу без каких-либо проблем.
	 */
	public $schemas=array('');

	private $_db;
	private $_fixtures;
	private $_rows;				// имя фикстуры, псевдоним строки => строка
	private $_records;			// имя фикстуры, псевдоним строки => запись (или имя класса)


	/**
	 * Инициализирует компонент приложения.
	 */
	public function init()
	{
		parent::init();
		if($this->basePath===null)
			$this->basePath=Yii::getPathOfAlias('application.tests.fixtures');
		$this->prepare();
	}

	/**
	 * Возвращает соединение БД, используемое для загрузки фикстур.
	 * @return CDbConnection соединение БД
	 */
	public function getDbConnection()
	{
		if($this->_db===null)
		{
			$this->_db=Yii::app()->getComponent($this->connectionID);
			if(!$this->_db instanceof CDbConnection)
				throw new CException(Yii::t('yii','CDbTestFixture.connectionID "{id}" is invalid. Please make sure it refers to the ID of a CDbConnection application component.',
					array('{id}'=>$this->connectionID)));
		}
		return $this->_db;
	}

	/**
	 * Подготавливает фикстуры к цельному тесту.
	 * Метод вызывается в методе {@link init}. Он исполняет скрипт инициализации БД при наличии такового.
	 * В противном случае, загружаются все доступные фикстуры.
	 */
	public function prepare()
	{
		$initFile=$this->basePath . DIRECTORY_SEPARATOR . $this->initScript;

		$this->checkIntegrity(false);

		if(is_file($initFile))
			require($initFile);
		else
		{
			foreach($this->getFixtures() as $tableName=>$fixturePath)
			{
				$this->resetTable($tableName);
				$this->loadFixture($tableName);
			}
		}
		$this->checkIntegrity(true);
	}

	/**
	 * Сбрасывает таблицы к состоянию, которое не содержит данных фикстуры.
	 * Если существует скрипт с именем вида "tests/fixtures/TableName.init.php",
	 * он будет исполнен. Иначе будет вызван метод {@link truncateTable} для очищения
	 * таблицы и сброса последовательности первичного ключа.
	 * @param string $tableName имя таблицы
	 */
	public function resetTable($tableName)
	{
		$initFile=$this->basePath . DIRECTORY_SEPARATOR . $tableName . $this->initScriptSuffix;
		if(is_file($initFile))
			require($initFile);
		else
			$this->truncateTable($tableName);
	}

	/**
	 * Загружает фикстуру для определенной таблицы.
	 * Метод вставляет строки, переданные в фикстуру, в соответствующую таблицу.
	 * Загруженные строки возвращаются методом.
	 * Если таблица имеет автоинкрементный первичный ключ, каждая строка будет содержать обновленное значение этого ключа.
	 * Если фикстура не существует, метод возвратит значение false.
	 * Примечание: вы можете захотеть вызвать метод {@link resetTable} перед вызовом данного метода,
	 * чтобы таблица была пуста перед вставкой данных.
	 * @param string $tableName имя таблицы
	 * @return array строки загруженной фикстуры, индексированные по псевдонимам строк (если существуют).
	 * Если таблица не имеет фикстуры, возвращается значение false.
	 */
	public function loadFixture($tableName)
	{
		$fileName=$this->basePath.DIRECTORY_SEPARATOR.$tableName.'.php';
		if(!is_file($fileName))
			return false;

		$rows=array();
		$schema=$this->getDbConnection()->getSchema();
		$builder=$schema->getCommandBuilder();
		$table=$schema->getTable($tableName);

		foreach(require($fileName) as $alias=>$row)
		{
			$builder->createInsertCommand($table,$row)->execute();
			$primaryKey=$table->primaryKey;
			if($table->sequenceName!==null)
			{
				if(is_string($primaryKey) && !isset($row[$primaryKey]))
					$row[$primaryKey]=$builder->getLastInsertID($table);
				else if(is_array($primaryKey))
				{
					foreach($primaryKey as $pk)
					{
						if(!isset($row[$pk]))
						{
							$row[$pk]=$builder->getLastInsertID($table);
							break;
						}
					}
				}
			}
			$rows[$alias]=$row;
		}
		return $rows;
	}

	/**
	 * Возвращает информацию доступных фикстур.
	 * Метод будет искать все файлы в директории {@link basePath}.
	 * Если имя файла такое же как и имя таблицы, то файл считается хранилищем данных фикстуры для данной таблицы.
	 * @return array информация доступных фикстур (имя таблицы => файл фикстуры)
	 */
	public function getFixtures()
	{
		if($this->_fixtures===null)
		{
			$this->_fixtures=array();
			$schema=$this->getDbConnection()->getSchema();
			$folder=opendir($this->basePath);
			$suffixLen=strlen($this->initScriptSuffix);
			while($file=readdir($folder))
			{
				if($file==='.' || $file==='..' || $file===$this->initScript)
					continue;
				$path=$this->basePath.DIRECTORY_SEPARATOR.$file;
				if(substr($file,-4)==='.php' && is_file($path) && substr($file,-$suffixLen)!==$this->initScriptSuffix)
				{
					$tableName=substr($file,0,-4);
					if($schema->getTable($tableName)!==null)
						$this->_fixtures[$tableName]=$path;
				}
			}
			closedir($folder);
		}
		return $this->_fixtures;
	}

	/**
	 * Включает или отключает проверку целостности базы данных.
	 * Метод может использоваться для временного отключения проверки внешних связей.
	 * @param boolean $check должна ли проверяться целостность базы данных
	 */
	public function checkIntegrity($check)
	{
		foreach($this->schemas as $schema)
			$this->getDbConnection()->getSchema()->checkIntegrity($check,$schema);
	}

	/**
	 * Удаляет все строки из определенной таблицы и сбрасывает последовательность её первичного ключа, если он есть.
	 * Перед вызовом данного метода вам может понадобиться вызвать метод {@link checkIntegrity}
	 * для временного отключения проверки целостности базы данных
	 * @param string $tableName имя таблицы
	 */
	public function truncateTable($tableName)
	{
		$db=$this->getDbConnection();
		$schema=$db->getSchema();
		if(($table=$schema->getTable($tableName))!==null)
		{
			$db->createCommand('DELETE FROM '.$table->rawName)->execute();
			$schema->resetSequence($table,1);
		}
		else
			throw new CException("Table '$tableName' does not exist.");
	}

	/**
	 * Очищает все таблицы в определенной схеме БД.
	 * Перед вызовом данного метода вам может понадобиться вызвать метод {@link checkIntegrity}
	 * для временного отключения проверки целостности базы данных
	 * @param string $schema имя схемы. По умолчанию - пустая строка, т.е. схема по умолчанию.
	 * @see truncateTable
	 */
	public function truncateTables($schema='')
	{
		$tableNames=$this->getDbConnection()->getSchema()->getTableNames($schema);
		foreach($tableNames as $tableName)
			$this->truncateTable($tableName);
	}

	/**
	 * Загружает определенные фикстуры.
	 * Для кажой фикстуры сбрасывается вызовом метода {@link resetTable} и затем
	 * заполняется данными фикстуры соответствующая таблица.
	 * Загруженные данные фикстуры могут быть в дальнейшем получены методами {@link getRows}
	 * и {@link getRecord}.
	 * Примечание: если таблица не имеет данных фикстуры, метод {@link resetTable} все равно
	 * будет вызываться для сброса таблицы.
	 * @param array $fixtures загружаемые фикстуры. Ключи массива - имена фикстур, а значения -
	 * имена либо AR-классов либо таблиц.
	 * Если это имя таблицы, то оно должно начинаться с двоеточия (например, 'Post'
	 * означает, что это AR-класс, а ':Post' - имя таблицы).
	 * По умолчанию - false - фикстуры не будут использоваться.
	 */
	public function load($fixtures)
	{
		$schema=$this->getDbConnection()->getSchema();
		$schema->checkIntegrity(false);

		$this->_rows=array();
		$this->_records=array();
		foreach($fixtures as $fixtureName=>$tableName)
		{
			if($tableName[0]===':')
			{
				$tableName=substr($tableName,1);
				unset($modelClass);
			}
			else
			{
				$modelClass=Yii::import($tableName,true);
				$tableName=CActiveRecord::model($modelClass)->tableName();
				if(($prefix=$this->getDbConnection()->tablePrefix)!==null)
					$tableName=preg_replace('/{{(.*?)}}/',$prefix.'\1',$tableName);
			}
			$this->resetTable($tableName);
			$rows=$this->loadFixture($tableName);
			if(is_array($rows) && is_string($fixtureName))
			{
				$this->_rows[$fixtureName]=$rows;
				if(isset($modelClass))
				{
					foreach(array_keys($rows) as $alias)
						$this->_records[$fixtureName][$alias]=$modelClass;
				}
			}
		}

		$schema->checkIntegrity(true);
	}

	/**
	 * Возвращает строки данных фикстуры.
	 * Строки будут иметь обновленные значения первичного ключа, если этот ключ является автоинкрементным.
	 * @param string $name имя фикстуры
	 * @return array строки данных фикстуры. Если данных фикстуры нет, возвращается значение false.
	 */
	public function getRows($name)
	{
		if(isset($this->_rows[$name]))
			return $this->_rows[$name];
		else
			return false;
	}

	/**
	 * Возвращает определенный экземпляр ActiveRecord в данных фикстуры.
	 * @param string $name имя фикстуры
	 * @param string $alias псевдоним строки данных фикстуры
	 * @return CActiveRecord экземпляр ActiveRecord. Если такой строки фикстуры нет, возвращается значение false.
	 */
	public function getRecord($name,$alias)
	{
		if(isset($this->_records[$name][$alias]))
		{
			if(is_string($this->_records[$name][$alias]))
			{
				$row=$this->_rows[$name][$alias];
				$model=CActiveRecord::model($this->_records[$name][$alias]);
				$key=$model->getTableSchema()->primaryKey;
				if(is_string($key))
					$pk=$row[$key];
				else
				{
					foreach($key as $k)
						$pk[$k]=$row[$k];
				}
				$this->_records[$name][$alias]=$model->findByPk($pk);
			}
			return $this->_records[$name][$alias];
		}
		else
			return false;
	}
}