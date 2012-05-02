<?php
/**
 * Файл класса CDbCriteria.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CDbCriteria представляет критерии запроса, такие как условия, порядок выборки,
 * количество выбираемых строк и начальный номер строки и т.д.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CDbCriteria.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.db.schema
 * @since 1.0
 */
class CDbCriteria extends CComponent
{
	const PARAM_PREFIX=':ycp';
	/**
	 * @var integer глобальный счетчик анонимных связанных параметров.
	 * Данный счетчик используется для генерации имен анонимных параметров
	 */
	public static $paramCount=0;
	/**
	 * @var mixed выбираемые столбцы. Добавляется в оператор SELECT в SQL-выражении.
	 * Свойство может быть либо строкой (разделенные запятыми имена столбцов) либо
	 * массивом имен столбцов. По умолчанию - '*', т.е., выбираются все столбцы
	 */
	public $select='*';
	/**
	 * @var boolean выбирать ли только неповторяющиеся строки данных. Если равно true,
	 * оператор SELECT будет изменен на SELECT DISTINCT
	 */
	public $distinct=false;
	/**
	 * @var string условие запроса. Добавляется в оператор WHERE в SQL-выражении.
	 * Например, <code>age>31 AND team=1</code>
	 */
	public $condition='';
	/**
	 * @var array список значений параметров запроса, индексированных по меткам параметров.
	 * Например, <code>array(':name'=>'Dan', ':age'=>31)</code>.
	 */
	public $params=array();
	/**
	 * @var integer максимальное количество возвращаемых записей. Если меньше 0, то ограничений нет
	 */
	public $limit=-1;
	/**
	 * @var integer смещение (начиная с нуля), с которого будут возвращаться записи. Если меньше 0, то выборка начинается от начала
	 */
	public $offset=-1;
	/**
	 * @var string правило сортировки результатов запроса. Добавляется в оператор ORDER BY в SQL-выражении
	 */
	public $order='';
	/**
	 * @var string правило группировки результатов запроса. Добавляется в оператор GROUP BY в SQL-выражении.
	 * Например, <code>'projectID, teamID'</code>
	 */
	public $group='';
	/**
	 * @var string правило объединения с другими таблицами. Добавляется в оператор JOIN в SQL-выражении.
	 * Например, <code>'LEFT JOIN users ON users.id=authorID'</code>.
	 */
	public $join='';
	/**
	 * @var string выражение, выполняемое с оператором GROUP BY.
	 * Например, <code>'SUM(revenue)<50000'</code>
	 */
	public $having='';
	/**
	 * @var mixed реляционный критерий запроса. Используется для получения связанных объектов в режиме "жадной" загрузки.
	 * Свойство имеет значение только если критерий передан в качестве
	 * параметра в один из следующих методов объекта класса CActiveRecord:
	 * <ul>
	 * <li>{@link CActiveRecord::find()};</li>
	 * <li>{@link CActiveRecord::findAll()};</li>
	 * <li>{@link CActiveRecord::findByPk()};</li>
	 * <li>{@link CActiveRecord::findAllByPk()};</li>
	 * <li>{@link CActiveRecord::findByAttributes()};</li>
	 * <li>{@link CActiveRecord::findAllByAttributes()};</li>
	 * <li>{@link CActiveRecord::count()}.</li>
	 * </ul>
	 * Значение свойства будет использовано в качестве параметра метода {@link CActiveRecord::with()}
	 * для выполнения "жадной" загрузки. Обратитесь к описанию метода {@link CActiveRecord::with()} за
	 * подробностями настройки данного параметра
	 * @since 1.1.0
	 */
	public $with;
	/**
	 * @var string псевдоним таблицы. Если не установлен, используется строка
	 * 't'
	 */
	public $alias;
	/**
	 * @var boolean должны ли внешние таблицы быть связаны с первичой таблицей
	 * в одном SQL-запросе. Свойство используется только в Active
	 * Record-запросах для связей HAS_MANY и MANY_MANY.
	 *
	 * Если данное свойство установлено в значение true, то для реляционного
	 * AR-запроса будет выполнен лишь один SQL-запрос, даже если первичная
	 * таблица лимитирована (установлено ограничение количества записей) и
	 * связь между внешней и первичной таблицами является связью
	 * многие-к-одному.
	 *
	 * Если данное свойство установлено в значение false, для каждой связи
	 * HAS_MANY будет выполнен отдельный SQL-запрос.
	 *
	 * Если данное свойство не установлено при лимитировании первичной таблицы
	 * или использовании постраничной разбивки, то для каждой связи HAS_MANY
	 * будет выполнен отдельный SQL-запрос, иначе будет выполнен единственный
	 * SQL-запрос
	 *
	 * @since 1.1.4
	 */
	public $together;
	/**
	 * @var string имя AR-атрибута, значение которого должно использоваться в
	 * качестве индекса массива результата запроса.
	 * По умолчанию - null, т.е., массив результата будет иметь целочисленные
	 * индексы, начинающиеся с нуля
	 * @since 1.1.5
	 */
	public $index;
	/**
     * @var mixed применяемая группа условий.
	 *
	 * Свойство имеет значение только если критерий передан в качестве параметра
	 * в один из следующих методов объекта класса CActiveRecord:
     * <ul>
     * <li>{@link CActiveRecord::find()};</li>
     * <li>{@link CActiveRecord::findAll()};</li>
     * <li>{@link CActiveRecord::findByPk()};</li>
     * <li>{@link CActiveRecord::findAllByPk()};</li>
     * <li>{@link CActiveRecord::findByAttributes()};</li>
     * <li>{@link CActiveRecord::findAllByAttributes()};</li>
     * <li>{@link CActiveRecord::count()}.</li>
     * </ul>
	 *
     * Может быть установлено одним из следующих способов:
     * <ul>
     * <li>Одна группа условий: $criteria->scopes='scopeName';</li>
     * <li>Несколько групп условий: $criteria->scopes=array('scopeName1','scopeName2');</li>
     * <li>Группа условий с параметрами: $criteria->scopes=array('scopeName'=>array($params));</li>
	 * <li>Несколько групп условий с параметрами: $criteria->scopes=array('scopeName1'=>array($params1),'scopeName2'=>array($params2));</li>
	 * <li>Несколько групп условий с одним именем и разными параметрами: array(array('scopeName'=>array($params1)),array('scopeName'=>array($params2)));</li>
     * </ul>
     * @since 1.1.7
     */
	public $scopes;

	/**
	 * Конструктор.
	 * @param array $data начальные значения параметров критерия (индексированы по именам свойств)
	 */
	public function __construct($data=array())
	{
		foreach($data as $name=>$value)
			$this->$name=$value;
	}

	/**
	 * Переименовывает (remaps) параметры критерия при десериализации для
	 * предотвращения коллизий имен
	 * @since 1.1.9
	 */
	public function __wakeup()
	{
		$map=array();
		$params=array();
		foreach($this->params as $name=>$value)
		{
			$newName=self::PARAM_PREFIX.self::$paramCount++;
			$map[$name]=$newName;
			$params[$newName]=$value;
		}
		$this->condition=strtr($this->condition,$map);
		$this->params=$params;
	}

	/**
	 * Добавляет условие к уже имеющемуся ({@link condition}).
	 * Новое условие и имеющееся будут соединены определенным оператором, по умолчанию -
	 * это оператор 'AND'. Новое условие может быть массивом. В этом случае все элементы массива
	 * будут соединены оператором. Данный метод обрабатывает случай, когда существующее условие пусто.
	 * После вызова метода, свойство {@link condition} будет изменено
	 * @param mixed $condition новое условие. Может быть либо строкой либо массивом строк
	 * @param string $operator оператор соединения отдельных условий. По умолчанию - 'AND'
	 * @return CDbCriteria объект критерия
	 */
	public function addCondition($condition,$operator='AND')
	{
		if(is_array($condition))
		{
			if($condition===array())
				return $this;
			$condition='('.implode(') '.$operator.' (',$condition).')';
		}
		if($this->condition==='')
			$this->condition=$condition;
		else
			$this->condition='('.$this->condition.') '.$operator.' ('.$condition.')';
		return $this;
	}

	/**
	 * Добавляет условие поиска к уже имеющемуся ({@link condition}).
	 * Условие поиска и имеющееся будут соединены определенным оператором, по умолчанию -
	 * оператор 'AND'. Условие поиска генерируется с использованием SQL-оператора LIKE
	 * с переданным именем и словом для поиска
	 * @param string $column имя столбца (или допустимое SQL-выражение)
	 * @param string $keyword слово для поиска. This interpretation of the keyword is affected by the next parameter.
	 * @param boolean $escape экранировать ли слово для поиска, если оно содержит символы % или _.
	 * Если данный параметр имеет значение true (dпо умолчанию), специальные символы % (обозначает 0 или более символов)
	 * и _ (обозначает 1 символ) будут экранированы, а слово для поиска будет окружено символом % с обоих концов.
	 * Если данный параметр имеет значение false, слово по умолчанию будет напрямую использоваться для поиска соответствий
	 * без каких-либо изменений.
	 * @param string $operator оператор, используемый для соединения нового условия с имеющимся. По умолчанию - 'AND'
	 * @param string $like оператор LIKE. По умолчанию - 'LIKE'. Можно установить в значение 'NOT LIKE'
	 * @return CDbCriteria объект критерия
	 */
	public function addSearchCondition($column,$keyword,$escape=true,$operator='AND',$like='LIKE')
	{
		if($keyword=='')
			return $this;
		if($escape)
			$keyword='%'.strtr($keyword,array('%'=>'\%', '_'=>'\_', '\\'=>'\\\\')).'%';
		$condition=$column." $like ".self::PARAM_PREFIX.self::$paramCount;
		$this->params[self::PARAM_PREFIX.self::$paramCount++]=$keyword;
		return $this->addCondition($condition, $operator);
	}

	/**
	 * Добавляет условие IN к уже имеющемуся ({@link condition}).
	 * Условие IN и имеющееся будут соединены определенным оператором, по умолчанию -
	 * оператор 'AND'. Условие IN генерируется с использованием SQL-оператора IN, требующим,
	 * чтобы значение определенного столбца находилось в переданном списке значений
	 * @param string $column имя столбца (или допустивое SQL-выражение)
	 * @param array $values список значений, среди которых должно быть значение столбца
	 * @param string $operator оператор, используемый для соединения нового условия с имеющимся. По умолчанию - 'AND'
	 * @return CDbCriteria объект критерия
	 */
	public function addInCondition($column,$values,$operator='AND')
	{
		if(($n=count($values))<1)
			return $this->addCondition('0=1',$operator); // 0=1 is used because in MSSQL value alone can't be used in WHERE
		if($n===1)
		{
			$value=reset($values);
			if($value===null)
				return $this->addCondition($column.' IS NULL');
			$condition=$column.'='.self::PARAM_PREFIX.self::$paramCount;
			$this->params[self::PARAM_PREFIX.self::$paramCount++]=$value;
		}
		else
		{
			$params=array();
			foreach($values as $value)
			{
				$params[]=self::PARAM_PREFIX.self::$paramCount;
				$this->params[self::PARAM_PREFIX.self::$paramCount++]=$value;
			}
			$condition=$column.' IN ('.implode(', ',$params).')';
		}
		return $this->addCondition($condition,$operator);
	}

	/**
	 * Добавляет условие NOT IN к уже имеющемуся ({@link condition}).
	 * Условие NOT IN и имеющееся будут соединены определенным оператором, по умолчанию -
	 * оператор 'AND'. Условие IN генерируется с использованием SQL-оператора IN, требующим,
	 * чтобы значение определенного столбца НЕ находилось в переданном списке значений
	 * @param string $column имя столбца (или допустивое SQL-выражение)
	 * @param array $values список значений, среди которых НЕ должно быть значение столбца
	 * @param string $operator оператор, используемый для соединения нового условия с имеющимся. По умолчанию - 'AND'
	 * @return CDbCriteria объект критерия
	 * @since 1.1.1
	 */
	public function addNotInCondition($column,$values,$operator='AND')
	{
		if(($n=count($values))<1)
			return $this;
		if($n===1)
		{
			$value=reset($values);
			if($value===null)
				return $this->addCondition($column.' IS NOT NULL');
			$condition=$column.'!='.self::PARAM_PREFIX.self::$paramCount;
			$this->params[self::PARAM_PREFIX.self::$paramCount++]=$value;
		}
		else
		{
			$params=array();
			foreach($values as $value)
			{
				$params[]=self::PARAM_PREFIX.self::$paramCount;
				$this->params[self::PARAM_PREFIX.self::$paramCount++]=$value;
			}
			$condition=$column.' NOT IN ('.implode(', ',$params).')';
		}
		return $this->addCondition($condition,$operator);
	}

	/**
	 * Добавляет условие для сравнения переданного списка со значениями столбцов.
	 * Данное условие и имеющееся будут соединены определенным оператором, по умолчанию -
	 * оператор 'AND'. Условие генерируется сравнением каждого столбца соответствующему значению
	 * @param array $columns список имен слобцов и сравниваемых значений (имя => значение)
	 * @param string $columnOperator оператор для соединения нескольких столбцов в условии сравнения. По умолчанию - 'AND'
	 * @param string $operator оператор, используемый для соединения нового условия с имеющимся. По умолчанию - 'AND'
	 * @return CDbCriteria объект критерия
	 */
	public function addColumnCondition($columns,$columnOperator='AND',$operator='AND')
	{
		$params=array();
		foreach($columns as $name=>$value)
		{
			if($value===null)
				$params[]=$name.' IS NULL';
			else
			{
				$params[]=$name.'='.self::PARAM_PREFIX.self::$paramCount;
				$this->params[self::PARAM_PREFIX.self::$paramCount++]=$value;
			}
		}
		return $this->addCondition(implode(" $columnOperator ",$params), $operator);
	}

	/**
	 * Добавляет выражение сравнения к свойству {@link condition}.
	 *
	 * Данный метод - это хелпер, добавляющий новое выражение сравнения к свойству {@link condition}.
	 * Действие производится сравнением столбца с переданным значением с
	 * использованием некоторого оператора сравнения.
	 *
	 * Оператор сравнения определеляется интеллектуально на основе первых нескольких символов переданного значения.
	 * В частности, распознаются следующие операторы, стоящие в начале переданного значения:
	 * <ul>
	 * <li><code>&lt;</code>: значение столбца должно быть меньше переданного значения;</li>
	 * <li><code>&gt;</code>: значение столбца должно быть больше переданного значения;</li>
	 * <li><code>&lt;=</code>: значение столбца должно быть меньше либо равно переданному значению;</li>
	 * <li><code>&gt;=</code>: значение столбца должно быть больше либо равно переданному значению;</li>
	 * <li><code>&lt;&gt;</code>: значение столбца не должно равняться переданному значению.
	 * Примечание: если параметр $partialMatch равен значению true, то значение не должно являться подстрокой
	 * значения столбца;</li>
	 * <li><code>=</code>: значение столбца должно равняться переданному значению;</li>
	 * <li>ни один из вышеперечисленных: значение столбца должно равняться переданному значению. Примечание:
	 * если параметр $partialMatch равен значению true, то значение столбца должно быть таким же, как переданое значение
	 * или быть подстрокой переданного значения.</li>
	 * </ul>
	 *
	 * Примечание: окружающие пробелы будут удалены из значения перед сравнением.
	 * Если значение пусто, выражение сравнения не будет добавлено к условию поиска.
	 *
	 * @param string $column имя столбца для поиска
	 * @param mixed $value значение, с которым производится сравнение. Если значение является строкой, то
	 * будет выполнено интеллектуальное сравнение. Если значение - это массив, то производится точное сравнение
	 * по каждому элементу массива. Если параметр пуст, то существующее условие поиска не будет изменено
	 * @param boolean $partialMatch надо ли проверять значение на частичное совпадение (используя операторы LIKE и NOT LIKE).
	 * По умолчанию - false, т.е., проверяется точное совпадение
	 * @param string $operator оператор, используемый для соединения нового условия с имеющимся. По умолчанию - 'AND'
	 * @param boolean $escape должно ли значение экранироваться, если свойство $partialMatch уставнолено в значение true и
	 * значение содержит символы % или _. Если данный параметр установлен в значение true (по умолчанию),
	 * то специальные символы % (соответствует 0 или более символам) и _ (соответствует единственному символу)
	 * будут экранированы и значение будет окружено символами % с обеих сторон. Если данный параметр имеет
	 * значение false, то значение будет использовано для поиска соответствий напрямую без изменений
	 * @return CDbCriteria объект критерия
	 * @since 1.1.1
	 */
	public function compare($column, $value, $partialMatch=false, $operator='AND', $escape=true)
	{
		if(is_array($value))
		{
			if($value===array())
				return $this;
			return $this->addInCondition($column,$value,$operator);
		}
		else
			$value="$value";

		if(preg_match('/^(?:\s*(<>|<=|>=|<|>|=))?(.*)$/',$value,$matches))
		{
			$value=$matches[2];
			$op=$matches[1];
		}
		else
			$op='';

		if($value==='')
			return $this;

		if($partialMatch)
		{
			if($op==='')
				return $this->addSearchCondition($column,$value,$escape,$operator);
			if($op==='<>')
				return $this->addSearchCondition($column,$value,$escape,$operator,'NOT LIKE');
		}
		else if($op==='')
			$op='=';

		$this->addCondition($column.$op.self::PARAM_PREFIX.self::$paramCount,$operator);
		$this->params[self::PARAM_PREFIX.self::$paramCount++]=$value;

		return $this;
	}

	/**
	 * Добавляет условие between (диапазон).
	 *
	 * Новое условие диапазон и имеющееся будут соединены определенным оператором, по умолчанию -
	 * оператор 'AND'. Если одно или оба значения пусты, то условие не будет добавлено к существующему.
	 * Данный метод обрабатывает случай, при котором существующее условие пусто.
	 * После вызова данного метода, свойство {@link condition} будет изменено
	 * @param string $column имя столбца поиска
	 * @param string $valueStart начальное значение диапазона
	 * @param string $valueEnd конечное значение диапазона
	 * @param string $operator оператор, используемый для соединения нового условия с имеющимся. По умолчанию - 'AND'
	 * @return CDbCriteria объект критерия
	 * @since 1.1.2
	 */
	public function addBetweenCondition($column,$valueStart,$valueEnd,$operator='AND')
	{
		if($valueStart==='' || $valueEnd==='')
			return $this;

		$paramStart=self::PARAM_PREFIX.self::$paramCount++;
		$paramEnd=self::PARAM_PREFIX.self::$paramCount++;
		$this->params[$paramStart]=$valueStart;
		$this->params[$paramEnd]=$valueEnd;
		$condition="$column BETWEEN $paramStart AND $paramEnd";

		if($this->condition==='')
			$this->condition=$condition;
		else
			$this->condition='('.$this->condition.') '.$operator.' ('.$condition.')';
		return $this;
	}

	/**
	 * Сливает критерий с другим.
	 * В основном, слияние делает результирующий критерий более ограничивающим.
	 * Например, если оба критерия имеют условия, вместе они будут слиты оператором 'AND'.
	 * Также, критерий, переданный в метод в качестве параметра, имеет преимущество в случае, когда
	 * пара опций не может быть слита (например, LIMIT, OFFSET)
	 * @param mixed $criteria критерий, с котором производится слияние. Может быть массивом или объектом класса CDbCriteria
	 * @param boolean $useAnd использовать ли оператор 'AND' для слияния условий и их опций. Если значение
	 * равно false, то будет использоваться оператор 'OR'. По умолчанию - true
	 */
	public function mergeWith($criteria,$useAnd=true)
	{
		$and=$useAnd ? 'AND' : 'OR';
		if(is_array($criteria))
			$criteria=new self($criteria);
		if($this->select!==$criteria->select)
		{
			if($this->select==='*')
				$this->select=$criteria->select;
			else if($criteria->select!=='*')
			{
				$select1=is_string($this->select)?preg_split('/\s*,\s*/',trim($this->select),-1,PREG_SPLIT_NO_EMPTY):$this->select;
				$select2=is_string($criteria->select)?preg_split('/\s*,\s*/',trim($criteria->select),-1,PREG_SPLIT_NO_EMPTY):$criteria->select;
				$this->select=array_merge($select1,array_diff($select2,$select1));
			}
		}

		if($this->condition!==$criteria->condition)
		{
			if($this->condition==='')
				$this->condition=$criteria->condition;
			else if($criteria->condition!=='')
				$this->condition="({$this->condition}) $and ({$criteria->condition})";
		}

		if($this->params!==$criteria->params)
			$this->params=array_merge($this->params,$criteria->params);

		if($criteria->limit>0)
			$this->limit=$criteria->limit;

		if($criteria->offset>=0)
			$this->offset=$criteria->offset;

		if($criteria->alias!==null)
			$this->alias=$criteria->alias;

		if($this->order!==$criteria->order)
		{
			if($this->order==='')
				$this->order=$criteria->order;
			else if($criteria->order!=='')
				$this->order=$criteria->order.', '.$this->order;
		}

		if($this->group!==$criteria->group)
		{
			if($this->group==='')
				$this->group=$criteria->group;
			else if($criteria->group!=='')
				$this->group.=', '.$criteria->group;
		}

		if($this->join!==$criteria->join)
		{
			if($this->join==='')
				$this->join=$criteria->join;
			else if($criteria->join!=='')
				$this->join.=' '.$criteria->join;
		}

		if($this->having!==$criteria->having)
		{
			if($this->having==='')
				$this->having=$criteria->having;
			else if($criteria->having!=='')
				$this->having="({$this->having}) $and ({$criteria->having})";
		}

		if($criteria->distinct>0)
			$this->distinct=$criteria->distinct;

		if($criteria->together!==null)
			$this->together=$criteria->together;

		if($criteria->index!==null)
			$this->index=$criteria->index;

		if(empty($this->scopes))
			$this->scopes=$criteria->scopes;
		else if(!empty($criteria->scopes))
		{
			$scopes1=(array)$this->scopes;
			$scopes2=(array)$criteria->scopes;
			foreach($scopes1 as $k=>$v)
			{
				if(is_integer($k))
					$scopes[]=$v;
				else if(isset($scopes2[$k]))
					$scopes[]=array($k=>$v);
				else
					$scopes[$k]=$v;
			}
			foreach($scopes2 as $k=>$v)
			{
				if(is_integer($k))
					$scopes[]=$v;
				else if(isset($scopes1[$k]))
					$scopes[]=array($k=>$v);
				else
					$scopes[$k]=$v;
			}
			$this->scopes=$scopes;
		}

		if(empty($this->with))
			$this->with=$criteria->with;
		else if(!empty($criteria->with))
		{
			$this->with=(array)$this->with;
			foreach((array)$criteria->with as $k=>$v)
			{
				if(is_integer($k))
					$this->with[]=$v;
				else if(isset($this->with[$k]))
				{
					$excludes=array();
					foreach(array('joinType','on') as $opt)
					{
						if(isset($this->with[$k][$opt]))
							$excludes[$opt]=$this->with[$k][$opt];
						if(isset($v[$opt]))
							$excludes[$opt]= ($opt==='on' && isset($excludes[$opt]) && $v[$opt]!==$excludes[$opt]) ?
								"($excludes[$opt]) AND $v[$opt]" : $v[$opt];
						unset($this->with[$k][$opt]);
						unset($v[$opt]);
					}
					$this->with[$k]=new self($this->with[$k]);
					$this->with[$k]->mergeWith($v,$useAnd);
					$this->with[$k]=$this->with[$k]->toArray();
					if (count($excludes)!==0)
						$this->with[$k]=CMap::mergeArray($this->with[$k],$excludes);
				}
				else
					$this->with[$k]=$v;
			}
		}
	}

	/**
	 * @return array представление критерия в виде массива
	 */
	public function toArray()
	{
		$result=array();
		foreach(array('select', 'condition', 'params', 'limit', 'offset', 'order', 'group', 'join', 'having', 'distinct', 'scopes', 'with', 'alias', 'index', 'together') as $name)
			$result[$name]=$this->$name;
		return $result;
	}
}
