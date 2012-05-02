<?php
/**
 * Файл класса CMsCommandBuilder.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Christophe Boulain <Christophe.Boulain@gmail.com>
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CMssqlCommandBuilder предоставляет базовые методы для создания команд запросов для таблиц базы данных Mssql.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Christophe Boulain <Christophe.Boulain@gmail.com>
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id: CMssqlCommandBuilder.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.db.schema.mssql
 */
class CMssqlCommandBuilder extends CDbCommandBuilder
{
	/**
	 * Создает команду COUNT(*) для отдельной таблицы.
	 * Переопределяет родительскую реализацию для удаления из критерия выражение COUNT при его наличии
	 * @param mixed $table схема таблицы ({@link CDbTableSchema}) или имя таблицы (строка)
	 * @param CDbCriteria $criteria критерий запроса
	 * @param string $alias псевдоним первичной таблицы. По умолчанию - 't'
	 * @return CDbCommand команда запроса
	 */
	public function createCountCommand($table,$criteria,$alias='t')
	{
		$criteria->order='';
		return parent::createCountCommand($table, $criteria,$alias);
	}

	/**
	 * Создает команду SELECT для отдельной таблицы.
	 * Переопределяет родительскую реализацию для проверки наличия выражения ORDER BY в случае
	 * наличия определенного смещения
	 * @param mixed $table схема таблицы ({@link CDbTableSchema}) или имя таблицы (строка)
	 * @param CDbCriteria $criteria критерий запроса
	 * @param string $alias псевдоним первичной таблицы. По умолчанию - 't'
	 * @return CDbCommand команда запроса
	 */
	public function createFindCommand($table,$criteria,$alias='t')
	{
		$criteria=$this->checkCriteria($table,$criteria);
		return parent::createFindCommand($table,$criteria,$alias);

	}

	/**
	 * Создает команду UPDATE для отдельной таблицы.
	 * Переопределяет родительскую реализацию, т.к. mssql не позволяет обновлять столбец идентификаторов (identity column)
	 * @param mixed $table схема таблицы ({@link CDbTableSchema}) или имя таблицы (строка)
	 * @param array $data список обновляемых столбцов (имя => значение)
	 * @param CDbCriteria $criteria критерий запроса
	 * @return CDbCommand команда обновления
	 */
	public function createUpdateCommand($table,$data,$criteria)
	{
		$criteria=$this->checkCriteria($table,$criteria);
		$fields=array();
		$values=array();
		$bindByPosition=isset($criteria->params[0]);
		$i=0;
		foreach($data as $name=>$value)
		{
			if(($column=$table->getColumn($name))!==null)
			{
				if ($table->sequenceName !== null && $column->isPrimaryKey === true) continue;
				if ($column->dbType === 'timestamp') continue;
				if($value instanceof CDbExpression)
				{
					$fields[]=$column->rawName.'='.$value->expression;
					foreach($value->params as $n=>$v)
						$values[$n]=$v;
				}
				else if($bindByPosition)
				{
					$fields[]=$column->rawName.'=?';
					$values[]=$column->typecast($value);
				}
				else
				{
					$fields[]=$column->rawName.'='.self::PARAM_PREFIX.$i;
					$values[self::PARAM_PREFIX.$i]=$column->typecast($value);
					$i++;
				}
			}
		}
		if($fields===array())
			throw new CDbException(Yii::t('yii','No columns are being updated for table "{table}".',
				array('{table}'=>$table->name)));
		$sql="UPDATE {$table->rawName} SET ".implode(', ',$fields);
		$sql=$this->applyJoin($sql,$criteria->join);
		$sql=$this->applyCondition($sql,$criteria->condition);
		$sql=$this->applyOrder($sql,$criteria->order);
		$sql=$this->applyLimit($sql,$criteria->limit,$criteria->offset);

		$command=$this->getDbConnection()->createCommand($sql);
		$this->bindValues($command,array_merge($values,$criteria->params));

		return $command;
	}

	/**
	 * Создает команду DELETE для отдельной таблицы. Переопределяет родительскую реализацию
	 * для проверки наличия выражения ORDER BY в случае наличия определенного смещения
	 * @param mixed $table схема таблицы ({@link CDbTableSchema}) или имя таблицы (строка)
	 * @param CDbCriteria $criteria критерий запроса
	 * @return CDbCommand команда удаления
	 */
	public function createDeleteCommand($table,$criteria)
	{
		$criteria=$this->checkCriteria($table, $criteria);
		return parent::createDeleteCommand($table, $criteria);
	}

	/**
	 * Создает команду UPDATE, инкрементирующую/декрементирующую некоторые столбцы.
	 * Переопределяет родительскую реализацию для проверки наличия выражения ORDER BY
	 * в случае наличия определенного смещения
	 * @param mixed $table схема таблицы ({@link CDbTableSchema}) или имя таблицы (строка)
	 * @param array $counters обновляемые счетчики (индексированные по именам столбцов счетчики инкремента/декремента)
	 * @param CDbCriteria $criteria критерий запроса
	 * @return CDbCommand команда инкремента/декремента
	 * @throws CException вызывается, если счетчик не определен
	 */
	public function createUpdateCounterCommand($table,$counters,$criteria)
	{
		$criteria=$this->checkCriteria($table, $criteria);
		return parent::createUpdateCounterCommand($table, $counters, $criteria);
	}

	/**
	 * Метод портирован из фреймворка Prado.
	 *
	 * Переопределяет родительскую реализацию. Изменяет SQL-выражение для поддержки параметров $limit and $offset.
	 * Идея применения лимита строк и смещения в модификации SQL-запроса "на лету" с
	 * учетом ряда предположений о структуре SQL-запроса.
	 * Модификация производится с учетом замечаний из 
	 * {@link http://troels.arvin.dk/db/rdbms/#select-limit-offset}
	 *
	 * <code>
	 * SELECT * FROM (
	 *  SELECT TOP n * FROM (
	 *    SELECT TOP z columns      -- (z=n+skip)
	 *    FROM tablename
	 *    ORDER BY key ASC
	 *  ) AS FOO ORDER BY key DESC -- ('FOO' может быть любым)
	 * ) AS BAR ORDER BY key ASC    -- ('BAR' может быть любым)
	 * </code>
	 *
	 * <b>Для изменения SQL-выражения используется регулярное выражение. Результирующий SQL-запрос
	 * может быть поврежден, если входной SQL-запрос является сложным.</b>
	 * Должны учитываться следующие ограничения
	 *
	 * <ul>
	 *   <li>
	 * <b>запятые НЕ</b> должны использоваться как часть выражения сортировки 
	 * или идентификатора. Запятые могут исползоваться только в качестве разделителя
	 * выражений сортировки;
	 *  </li>
	 *  <li>
	 * в выражении ORDER BY имя столбца НЕ должно быть с именем таблицы или представления.
	 * Используйте псевдоним столбца или столбце индекса;
	 * </li>
	 * <li>
	 * за выражением ORDER BY не должно следовать других выражений, например COMPUTE или FOR.
	 * </li>
	 *
	 * @param string $sql строка SQL-запроса
	 * @param integer $limit максимальное количество строк, -1 для игнорирования лимита
	 * @param integer $offset смещение строк, -1 для игнорирования смещения
	 * @return string SQL-запрос с лимитом и смещением
	 *
	 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
	 */
	public function applyLimit($sql, $limit, $offset)
	{
		$limit = $limit!==null ? intval($limit) : -1;
		$offset = $offset!==null ? intval($offset) : -1;
		if ($limit > 0 && $offset <= 0) //just limit
			$sql = preg_replace('/^([\s(])*SELECT( DISTINCT)?(?!\s*TOP\s*\()/i',"\\1SELECT\\2 TOP $limit", $sql);
		else if($limit > 0 && $offset > 0)
			$sql = $this->rewriteLimitOffsetSql($sql, $limit,$offset);
		return $sql;
	}

	/**
	 * Переписывает SQL-выражение для применения параметров $limit и $offset
	 * (при их наличии и условии $offset > 0) для базы данных MSSQL.
	 * См. {@link http://troels.arvin.dk/db/rdbms/#select-limit-offset}
	 * @param string $sql SQL-запрос
	 * @param integer $limit $limit > 0
	 * @param integer $offset $offset > 0
	 * @return sql измененный SQL-запрос с параметрами limit и offset
	 *
	 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
	 */
	protected function rewriteLimitOffsetSql($sql, $limit, $offset)
	{
		$fetch = $limit+$offset;
		$sql = preg_replace('/^([\s(])*SELECT( DISTINCT)?(?!\s*TOP\s*\()/i',"\\1SELECT\\2 TOP $fetch", $sql);
		$ordering = $this->findOrdering($sql);
		$orginalOrdering = $this->joinOrdering($ordering, '[__outer__]');
		$reverseOrdering = $this->joinOrdering($this->reverseDirection($ordering), '[__inner__]');
		$sql = "SELECT * FROM (SELECT TOP {$limit} * FROM ($sql) as [__inner__] {$reverseOrdering}) as [__outer__] {$orginalOrdering}";
		return $sql;
	}

	/**
	 * Основан на синтаксе {@link http://msdn2.microsoft.com/en-us/library/aa259187(SQL.80).aspx}
	 *
	 * @param string $sql $sql
	 * @return array выражение сортировки в качесве ключа и направление сортировки в качестве значения
	 *
	 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
	 */
	protected function findOrdering($sql)
	{
		if(!preg_match('/ORDER BY/i', $sql))
			return array();
		$matches=array();
		$ordering=array();
		preg_match_all('/(ORDER BY)[\s"\[](.*)(ASC|DESC)?(?:[\s"\[]|$|COMPUTE|FOR)/i', $sql, $matches);
		if(count($matches)>1 && count($matches[2]) > 0)
		{
			$parts = explode(',', $matches[2][0]);
			foreach($parts as $part)
			{
				$subs=array();
				if(preg_match_all('/(.*)[\s"\]](ASC|DESC)$/i', trim($part), $subs))
				{
					if(count($subs) > 1 && count($subs[2]) > 0)
					{
						$name='';
						foreach(explode('.', $subs[1][0]) as $p)
						{
							if($name!=='')
								$name.='.';
							$name.='[' . trim($p, '[]') . ']';
						}
						$ordering[$name] = $subs[2][0];
					}
					//else what?
				}
				else
					$ordering[trim($part)] = 'ASC';
			}
		}

		// replacing column names with their alias names
		foreach($ordering as $name => $direction)
		{
			$matches = array();
			$pattern = '/\s+'.str_replace(array('[',']'), array('\[','\]'), $name).'\s+AS\s+(\[[^\]]+\])/i';
			preg_match($pattern, $sql, $matches);
			if(isset($matches[1]))
			{
				$ordering[$matches[1]] = $ordering[$name];
				unset($ordering[$name]);
			}
		}

		return $ordering;
	}

	/**
	 * @param array $orders сортировка, полученная с помощью метода findOrdering()
	 * @param string $newPrefix новый префикс таблицы для столбцов сортировки
	 * @return string соединенные условия сортировки
	 *
	 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
	 */
	protected function joinOrdering($orders, $newPrefix)
	{
		if(count($orders)>0)
		{
			$str=array();
			foreach($orders as $column => $direction)
				$str[] = $column.' '.$direction;
			$orderBy = 'ORDER BY '.implode(', ', $str);
			return preg_replace('/\s+\[[^\]]+\]\.(\[[^\]]+\])/i', ' '.$newPrefix.'.\1', $orderBy);
		}
	}

	/**
	 * @param array $orders оригинальное выражение сортировки
	 * @return array выражение с противоположным направлением сортировки
	 *
	 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
	 */
	protected function reverseDirection($orders)
	{
		foreach($orders as $column => $direction)
			$orders[$column] = strtolower(trim($direction))==='desc' ? 'ASC' : 'DESC';
		return $orders;
	}


	/**
	 * Проверяет, имеет ли критерий выражение ORDER BY при наличии смещения или лимита строк (offset/limit).
	 * @param CMssqlTableSchema $table схема таблицы
	 * @param CDbCriteria $criteria критерий
	 * @return CDbCrireria измененный критерий
	 */
	protected function checkCriteria($table, $criteria)
	{
		if ($criteria->offset > 0 && $criteria->order==='')
		{
			$criteria->order=is_array($table->primaryKey)?implode(',',$table->primaryKey):$table->primaryKey;
		}
		return $criteria;
	}

	/**
	 * Генерирует SQL-выражение для выбора строк по определенным значениям композитного ключа
	 * @param CDbTableSchema $table схема таблицы
	 * @param array $values список значений первичного ключа для выборки
	 * @param string $prefix префикс столбца (с точкой на конце)
	 * @return string SQL-выражение выборки
	 */
	protected function createCompositeInCondition($table,$values,$prefix)
	{
		$vs=array();
		foreach($values as $value)
		{
			$c=array();
			foreach($value as $k=>$v)
				$c[]=$prefix.$table->columns[$k]->rawName.'='.$v;
			$vs[]='('.implode(' AND ',$c).')';
		}
		return '('.implode(' OR ',$vs).')';
	}
}
