<?php
/**
 * Файл класса CActiveRecord.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CActiveRecord это базовый класс для классов, представляющих связанные
 * данные.
 *
 * Он реализует шаблон проектирования active record (популярнaя техника Object-Relational Mapping, ORM).
 * За деталями работы класса обратитесь к соответствующему разделу
 * {@link http://www.yiiframework.com/doc/guide/database.ar руководства}.
 *
 * @property CDbCriteria $dbCriteria критерий запроса, ассоциированный с данной
 * моделью. Данный критерий в основном используется для функционала
 * {@link scopes именованных групп условий} для сбора различных определений
 * критериев
 * @property CActiveRecordMetaData $metaData метаданные данного AR-класса
 * @property CDbConnection $dbConnection соединение БД, используемое данным
 * AR-экземпляром
 * @property CDbTableSchema $tableSchema метаданные таблицы, к которой
 * относится данный AR-экземпляр
 * @property CDbCommandBuilder $commandBuilder построитель запросов,
 * используемый данным AR-экземпляром
 * @property array $attributes значения атрибутов, индексированные по именам
 * атрибутов
 * @property boolean $isNewRecord является ли новой данная запись и должна ли
 * она быть вставлена при вызове метода {@link save}. Данное свойство
 * автоматически устанавливается в конструкторе и методе
 * {@link populateRecord}. По умолчанию - false, но устанавливается в значение
 * true, если экземпляр создается с помощью оператора 'new'
 * @property mixed $primaryKey значение первичного ключа. Если ключ составной,
 * то возвращается массив (имя столбца => значение столбца). Если первичный
 * ключ не определен, возвращается null
 * @property mixed $oldPrimaryKey старое значение первичного ключа. Если
 * первичный ключ является составным, то возвращается массив (имя столбца =>
 * значение столбца). Если первичный ключ не определен, то возвращается null
 * @property string $tableAlias псевдоним таблицы по умолчанию
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CActiveRecord.php 3533 2012-01-08 22:07:55Z mdomba $
 * @package system.db.ar
 * @since 1.0
 */
abstract class CActiveRecord extends CModel
{
	const BELONGS_TO='CBelongsToRelation';
	const HAS_ONE='CHasOneRelation';
	const HAS_MANY='CHasManyRelation';
	const MANY_MANY='CManyManyRelation';
	const STAT='CStatRelation';

	/**
	 * @var CDbConnection соединение БД по умолчанию для всех active
	 * record-классов. По умолчанию - это компонент приложения 'db'
	 * @see getDbConnection
	 */
	public static $db;

	private static $_models=array();			// class name => model

	private $_md;								// meta data
	private $_new=false;						// whether this instance is new or not
	private $_attributes=array();				// attribute name => attribute value
	private $_related=array();					// attribute name => related objects
	private $_c;								// query criteria (used by finder only)
	private $_pk;								// old primary key value
	private $_alias='t';						// the table alias being used for query


	/**
	 * Конструктор
	 * @param string $scenario имя сценария. За деталями о параметре обратитесь
	 * к свойству {@link CModel::scenario}
	 */
	public function __construct($scenario='insert')
	{
		if($scenario===null) // internally used by populateRecord() and model()
			return;

		$this->setScenario($scenario);
		$this->setIsNewRecord(true);
		$this->_attributes=$this->getMetaData()->attributeDefaults;

		$this->init();

		$this->attachBehaviors($this->behaviors());
		$this->afterConstruct();
	}

	/**
	 * Инициализирует данную модель.
	 * Данный метод вызывается, когда экземпляр AR-объекта создан и его
	 * свойство {@link scenario} установлено. Вы можете переопределить данный
	 * метод для предоставления кода, необходимого для инициализации модели
	 * (например, установка начальных значений свойств)
	 */
	public function init()
	{
	}

	/**
	 * Устанавливает параметры кэширования запроса. Это короткая ссылка на
	 * метод {@link CDbConnection::cache()}. Данный метод изменяет параметры
	 * кэширования запроса в экземпляре {@link dbConnection соединения БД}
	 * @param integer $duration время в секундах, в течение которого результат
	 * запроса, хранящийся в кэше, считается валидным. Если равно 0, то
	 * кэширование отключено
	 * @param CCacheDependency $dependency зависимость, используемая при
	 * сохранении результата запроса в кэш
	 * @param integer $queryCount количество SQL-запросов, которые должны быть
	 * кэшированы после вызова данного метода. По умолчанию - 1, т.е., будет
	 * кэширован следующий SQL-запрос
	 * @return CActiveRecord данный active record-объект
	 * @since 1.1.7
	 */
	public function cache($duration, $dependency=null, $queryCount=1)
	{
		$this->getDbConnection()->cache($duration, $dependency, $queryCount);
		return $this;
	}

	/**
	 * "Магический" метод PHP - sleep. Данный метод гарантирует, что метаданные
	 * модели будут установлены в null
	 * @return array
	 */
	public function __sleep()
	{
		$this->_md=null;
		return array_keys((array)$this);
	}

	/**
	 * "Магический" метод PHP - геттер (getter). Данный метод переопределен,
	 * чтобы к атрибутам AR-объекта можно было иметь доступ как к свойствам
	 * @param string $name имя свойства
	 * @return mixed значение свойства
	 * @see getAttribute
	 */
	public function __get($name)
	{
		if(isset($this->_attributes[$name]))
			return $this->_attributes[$name];
		else if(isset($this->getMetaData()->columns[$name]))
			return null;
		else if(isset($this->_related[$name]))
			return $this->_related[$name];
		else if(isset($this->getMetaData()->relations[$name]))
			return $this->getRelated($name);
		else
			return parent::__get($name);
	}

	/**
	 * "Магический" метод PHP - сеттер (setter). Данный метод переопределен,
	 * чтобы к атрибутам AR-объекта можно было иметь доступ как к свойствам
	 * @param string $name имя свойства
	 * @param mixed $value значение свойства
	 */
	public function __set($name,$value)
	{
		if($this->setAttribute($name,$value)===false)
		{
			if(isset($this->getMetaData()->relations[$name]))
				$this->_related[$name]=$value;
			else
				parent::__set($name,$value);
		}
	}

	/**
	 * Проверяет, существует ли свойство. Данный метод переопределяет
	 * родительскую реализацию, проверяя, существует ли атрибут с переданным
	 * именем
	 * @param string $name имя свойства или события
	 * @return boolean существует ли свойство
	 */
	public function __isset($name)
	{
		if(isset($this->_attributes[$name]))
			return true;
		else if(isset($this->getMetaData()->columns[$name]))
			return false;
		else if(isset($this->_related[$name]))
			return true;
		else if(isset($this->getMetaData()->relations[$name]))
			return $this->getRelated($name)!==null;
		else
			return parent::__isset($name);
	}

	/**
	 * Удаляет свойство компонента по переданному имени. Данный метод
	 * переопределяет родительскую реализацию удалением определенного атрибута
	 * AR-объекта
	 * @param string $name имя свойства или события
	 */
	public function __unset($name)
	{
		if(isset($this->getMetaData()->columns[$name]))
			unset($this->_attributes[$name]);
		else if(isset($this->getMetaData()->relations[$name]))
			unset($this->_related[$name]);
		else
			parent::__unset($name);
	}

	/**
	 * Вызывает именованный метод, не являющийся методом класса. Данный метод
	 * переопределяет родительскую реализацию, чтобы использовать функционал
	 * групп условий (scope)
	 * @param string $name имя метода
	 * @param array $parameters параметры метода
	 * @return mixed возвращаемое методом значение
	 */
	public function __call($name,$parameters)
	{
		if(isset($this->getMetaData()->relations[$name]))
		{
			if(empty($parameters))
				return $this->getRelated($name,false);
			else
				return $this->getRelated($name,false,$parameters[0]);
		}

		$scopes=$this->scopes();
		if(isset($scopes[$name]))
		{
			$this->getDbCriteria()->mergeWith($scopes[$name]);
			return $this;
		}

		return parent::__call($name,$parameters);
	}

	/**
	 * Возвращает связанные записи. Данный метод возвращает связанные записи
	 * данной записи. Если тип связи - HAS_ONE или BELONGS_TO, то возвращается
	 * одиночный объект или null, если объект не существует. Если тип связи -
	 * HAS_MANY или MANY_MANY, то возвращается массив объектов или пустой
	 * массив
	 * @param string $name имя связи (см. {@link relations})
	 * @param boolean $refresh перезагружать ли связанные объекты из базы
	 * данных. По умолчанию - false
	 * @param array $params дополнительные параметры, настраивающие условия
	 * запроса как это определено в описании связи
	 * @return mixed связанный(е) объект(ы)
	 * @throws CDbException вызывается, если связь не определена в {@link relations списке связей}
	 */
	public function getRelated($name,$refresh=false,$params=array())
	{
		if(!$refresh && $params===array() && (isset($this->_related[$name]) || array_key_exists($name,$this->_related)))
			return $this->_related[$name];

		$md=$this->getMetaData();
		if(!isset($md->relations[$name]))
			throw new CDbException(Yii::t('yii','{class} does not have relation "{name}".',
				array('{class}'=>get_class($this), '{name}'=>$name)));

		Yii::trace('lazy loading '.get_class($this).'.'.$name,'system.db.ar.CActiveRecord');
		$relation=$md->relations[$name];
		if($this->getIsNewRecord() && !$refresh && ($relation instanceof CHasOneRelation || $relation instanceof CHasManyRelation))
			return $relation instanceof CHasOneRelation ? null : array();

		if($params!==array()) // dynamic query
		{
			$exists=isset($this->_related[$name]) || array_key_exists($name,$this->_related);
			if($exists)
				$save=$this->_related[$name];
			$r=array($name=>$params);
		}
		else
			$r=$name;
		unset($this->_related[$name]);

		$finder=new CActiveFinder($this,$r);
		$finder->lazyFind($this);

		if(!isset($this->_related[$name]))
		{
			if($relation instanceof CHasManyRelation)
				$this->_related[$name]=array();
			else if($relation instanceof CStatRelation)
				$this->_related[$name]=$relation->defaultValue;
			else
				$this->_related[$name]=null;
		}

		if($params!==array())
		{
			$results=$this->_related[$name];
			if($exists)
				$this->_related[$name]=$save;
			else
				unset($this->_related[$name]);
			return $results;
		}
		else
			return $this->_related[$name];
	}

	/**
	 * Возвращает значение, показывающее, загружен(ы) ли связанный(е) объект(ы)
	 * определенной именованной связи
	 * @param string $name имя связи
	 * @return boolean значение, показывающее, загружен(ы) ли связанный(е) объект(ы)
	 */
	public function hasRelated($name)
	{
		return isset($this->_related[$name]) || array_key_exists($name,$this->_related);
	}

	/**
	 * Возвращает критерий запроса, ассоциированный с данной моделью
	 * @param boolean $createIfNull создавать ли экземпляр критерия, если он не
	 * существует. По умолчанию - true
	 * @return CDbCriteria критерий запроса, ассоциированный с данной моделью.
	 * Данный критерий в основном используется для функционала
	 * {@link scopes именованных групп условий} для сбора различных определений
	 * критериев
	 */
	public function getDbCriteria($createIfNull=true)
	{
		if($this->_c===null)
		{
			if(($c=$this->defaultScope())!==array() || $createIfNull)
				$this->_c=new CDbCriteria($c);
		}
		return $this->_c;
	}

	/**
	 * Устанавливает критерий запроса для данной модели
	 * @param CDbCriteria $criteria критерий запроса
	 * @since 1.1.3
	 */
	public function setDbCriteria($criteria)
	{
		$this->_c=$criteria;
	}

	/**
	 * Возвращает именованную группу условий по умолчанию, которая должна быть
	 * неявно применена для всех запросов данной модели. Примечание: группа
	 * условий по умолчанию применяется только для SELECT-запросов. Она
	 * игнорируется для запросов INSERT, UPDATE и DELETE. Данная реализация по
	 * умолчанию просто возвращает пустой массив. Можно переопределить данный
	 * метод, если требуется, чтобы модель использовала некоторый критерий по
	 * умолчанию (например, если должны возвращаться только активные записи)
	 * @return array критерий запроса. Используется в качестве параметра
	 * конструктора экземпляра класса {@link CDbCriteria}
	 */
	public function defaultScope()
	{
		return array();
	}

	/**
	 * Сбрасывает все примененные группы условий и критерии, включая группу
	 * условий по умолчанию
	 *
	 * @return CActiveRecord данный AR-объект
	 * @since 1.1.2
	 */
	public function resetScope()
	{
		$this->_c=new CDbCriteria();
		return $this;
	}

	/**
	 * Возвращает статическую модель определенного AR-класса. Требуется для
	 * вызова методов уровня класса (похожи на статические методы класса).
	 * Каждый класс-постомок должен переопределять метод так, как показано
	 * далее:
	 * <pre>
	 * public static function model($className=__CLASS__)
	 * {
	 *     return parent::model($className);
	 * }
	 * </pre>
	 *
	 * @param string $className имя active record-класса
	 * @return CActiveRecord экземпляр active record-модели
	 */
	public static function model($className=__CLASS__)
	{
		if(isset(self::$_models[$className]))
			return self::$_models[$className];
		else
		{
			$model=self::$_models[$className]=new $className(null);
			$model->_md=new CActiveRecordMetaData($model);
			$model->attachBehaviors($model->behaviors());
			return $model;
		}
	}

	/**
	 * Возвращает метаданные данного AR-класса
	 * @return CActiveRecordMetaData метаданные данного AR-класса
	 */
	public function getMetaData()
	{
		if($this->_md!==null)
			return $this->_md;
		else
			return $this->_md=self::model(get_class($this))->_md;
	}

	/**
	 * Обновляет метаданные данного AR-класса. Метод полезен, если схема
	 * таблицы изменена и требуется использовать самую свежую версию схемы
	 * таблицы. Убедитесь, что метод {@link CDbSchema::refresh} вызван перед
	 * вызовом данного метода. Иначе будет использоваться старая версия схемы
	 * таблицы
	 */
	public function refreshMetaData()
	{
		$finder=self::model(get_class($this));
		$finder->_md=new CActiveRecordMetaData($finder);
		if($this!==$finder)
			$this->_md=$finder->_md;
	}

	/**
	 * Возвращает имя ассоциированной с данной моделью таблицы БД. По умолчанию
	 * данный метод возвращает имя класса модели в качесвте имени таблицы.
	 * Можно переопределить данный метод, если имена таблиц не соответствуют
	 * данному соглашению
	 * @return string имя таблицы
	 */
	public function tableName()
	{
		return get_class($this);
	}

	/**
	 * Возвращает первичный ключ ассоциированной с данной моделью таблицы БД.
	 * Данный метод предназначен для переопределения в случае, когда в таблице
	 * не определен первичный ключ (для некоторых баз данных). Если в таблице
	 * уже определн первичный ключ, нет необходимости переопределять данный
	 * метод. Реализация по умолчанию просто возвращает null, т.е., первичный
	 * ключ определен в базе данных
	 * @return mixed первичный ключ ассоциированной с данной моделью таблицы
	 * БД. Если ключ - это одиночный столбец, то возвращается имя столбца; если
	 * ключ является составным, содержащим несколько столбцов, должен
	 * возвращаться массив имен столбцов ключа
	 */
	public function primaryKey()
	{
	}

	/**
	 * Данный метод должен быть переопределен для описания связанных объектов.
	 *
	 * Существует 4 типа связи между двумя active record-объектами:
	 * <ul>
	 * <li>BELONGS_TO: например, член команды входит в команду;</li>
	 * <li>HAS_ONE: например, пользователь имеет только один профиль;</li>
	 * <li>HAS_MANY: например, команда состоит из нескольких членов;</li>
	 * <li>MANY_MANY: например, член команды имеет несколько навыков, а
	 * конкретный навык может быть у нескольких членов.</li>
	 * </ul>
	 *
	 * Кроме вышеописанных типов существует специальный тип STAT, используемый
	 * для выполнения статистических запросов (или аггрегирующих запросов).
	 * Он запрашивает аггрегирующую информацию о связанных объектах, такую как
	 * количество комментариев к статье, средний рейтинг товара и др.
	 *
	 * Каждая связь определяется в данном методе в виде массива следующей структуры:
	 * <pre>
	 * 'varName'=>array('relationType', 'className', 'foreign_key', ...дополнительные опции)
	 * </pre>,
	 * где 'varName' - имя свойства, через которое доступен связанный объект
	 * (или объекты); 'relationType' - тип связи, может быть значением одной из
	 * следующих констант: self::BELONGS_TO, self::HAS_ONE, self::HAS_MANY и
	 * self::MANY_MANY; 'className' - имя active record-класса связанного
	 * объекта (объектов); 'foreign_key' - внешний ключ, по которому происходит
	 * связь между AR-объектами. Примечание: для составного ключа имена
	 * столбцов могут быть либо перечислены в одной строке и разделены
	 * запятыми, либо записаны в массив вида array('key1','key2'). В случае,
	 * если необходимо определить свое соответствие PK->FK, то можно определить
	 * его в виде array('fk'=>'pk'). Для составного ключа это будет массив вида
	 * array('fk_c1'=>'pk_с1','fk_c2'=>'pk_c2'). Для внешних ключей,
	 * используемых в отношениях MANY_MANY, также должна быть определена
	 * таблица соединения (например, 'join_table(fk1, fk2)').
	 * 
	 * Дополнительные опции могут быть определены в виде пар имя-значение в остальных элементах массива:
	 * <ul>
	 * <li>'select': string|array, список выбираемых столбцов. По умолчанию -
	 * '*', т.е., выбираются все столбцы. Имена столбцов не должны допускать
	 * неоднозначности, если они появляются в выражении БД (например,
	 * COUNT(relationName.name) AS name_count);</li>
	 * <li>'condition': string, выражение WHERE. По умолчанию пусто. Примечание:
	 * описания столбцов не должны допускать неоднозначности, используя префикс
	 * 'relationName.' (например, relationName.age&gt;20);</li>
	 * <li>'order': string, выражение ORDER BY. По умолчанию пусто. Примечание:
	 * описания столбцов не должны допускать неоднозначности, используя префикс
	 * 'relationName.' (например, relationName.age DESC);</li>
	 * <li>'with': string|array, список дочерних связанных объектов, которые
	 * должны быть загружены вместе с данным объектом. Примечание: 
	 * используется только при "ленивой" загрузке, но не при "жадной"; (@TODO проверить перевод)</li>
	 * <li>'joinType': тип связи. По умочанию - 'LEFT OUTER JOIN';</li>
	 * <li>'alias': псевдоним таблицы, ассоциированной с данной связью. По
	 * умолчанию - null, т.е., в качестве псевдонима таблицы используется имя
	 * связи;</li>
     * <li>'params': параметры, связываемые с SQL-выражением. Должны быть
	 * переданы в виде массива пар имя-значение;</li>
	 * <li>'on': выражение ON. Данное условие будет добавлено к условию соединения
	 * с помощью оператора AND;</li>
	 * <li>'index': имя столбца, значение которого должно использоваться в
	 * качестве ключей массива связанных объектов. Опция доступна только для
	 * связей HAS_MANY и MANY_MANY;</li>
	 * <li>'scopes': применяемые группы условий. Если используется одна группа,
	 * то задавать опцию можно в виде 'scopes'=>'scopeName', если несколько
	 * групп - в виде 'scopes'=>array('scopeName1','scopeName2'). Опция доступна
	 * с версии  1.1.9.</li>
	 * </ul>
	 *
	 * Следующие опции доступны для некоторых связей при "ленивой" загрузке:
	 * <ul>
	 * <li>'group': string, выражение GROUP BY. По умолчанию пусто. Примечание:
	 * описания столбцов не должны допускать неоднозначности, используя префикс
	 * 'relationName.' (например, relationName.age). Опция применяется только
	 * для связей HAS_MANY и MANY_MANY;</li>
	 * <li>'having': string, выражение HAVING. По умолчанию пусто. Примечание:
	 * описания столбцов не должны допускать неоднозначности, используя префикс
	 * 'relationName.' (например, relationName.age). Опция применяется только
	 * для связей HAS_MANY и MANY_MANY;</li>
	 * <li>'limit': тимит количества возвращаемых строк. Опция применяется
	 * только для связи BELONGS_TO;</li>
	 * <li>'offset': смещение возвращаемых строк.  Опция применяется
	 * только для связи BELONGS_TO;</li>
	 * <li>'through': имя связи модели, используемой в качестве моста при
	 * получении получении данных. Может устанавливаться только для связей
	 * HAS_ONE и HAS_MANY. Опция доступна с версии 1.1.7.</li>
	 * </ul>
	 *
	 * Ниже приведен пример, описывающий связанные объекты для AR-класса 'Post':
	 * <pre>
	 * return array(
	 *     'author'=>array(self::BELONGS_TO, 'User', 'author_id'),
	 *     'comments'=>array(self::HAS_MANY, 'Comment', 'post_id', 'with'=>'author', 'order'=>'create_time DESC'),
	 *     'tags'=>array(self::MANY_MANY, 'Tag', 'post_tag(post_id, tag_id)', 'order'=>'name'),
	 * );
	 * </pre>
	 *
	 * @return array список описаний связанных объектов. По умолчанию - пустой массив
	 */
	public function relations()
	{
		return array();
	}

	/**
	 * Возвращает описания именованных групп условий.
	 * Именованная группа условий представляет собой критерий запроса, который
	 * может соединяться в цепочку с другими группами условий и применяться к
	 * запросу. Данный метод может быть переопределен классами-потомками для
	 * объявления именованных групп условий конкретных AR-классов. Например,
	 * следующий код объявляет две именованных групп условий: 'recently' и
	 * 'published'.
	 * <pre>
	 * return array(
	 *     'published'=>array(
	 *           'condition'=>'status=1',
	 *     ),
	 *     'recently'=>array(
	 *           'order'=>'create_time DESC',
	 *           'limit'=>5,
	 *     ),
	 * );
	 * </pre>
	 * Если эти группы условий объявлены в модели 'Post', то можно выполнять
	 * следующие запросы:
	 * <pre>
	 * $posts=Post::model()->published()->findAll();
	 * $posts=Post::model()->published()->recently()->findAll();
	 * $posts=Post::model()->published()->with('comments')->findAll();
	 * </pre>
	 * Примечание: последний запрос является реляционным
	 *
	 * @return array определение групп условий. Ключи массива - имена групп
	 * условий, а значения - соответствующие определения групп условий. Каждое
	 * определение групп условий представляет собой массив, ключи которого
	 * должны быть свойствами класса {@link CDbCriteria}
	 */
	public function scopes()
	{
		return array();
	}

	/**
	 * Возвращает список все имен атрибутов модели.
	 * Реализация по умолчанию возвращает все имена столбцов таблицы,
	 * ассоциированной с данным AR-классом
	 * @return array список имен атрибутов
	 */
	public function attributeNames()
	{
		return array_keys($this->getMetaData()->columns);
	}

	/**
	 * Возвращает текстовую метку определенного атрибута. Данный метод
	 * переопределеяет родительскую реализацию поддержкой возвращения
	 * меток, определенных в реляционном объекте. В частности, если имя
	 * атрибута представлено в форме "post.author.name", то данный метод
	 * будет выводить метку атрибута "name" связи "author"
	 * @param string $attribute имя атрибута
	 * @return string текстовая метка атрибута
	 * @see generateAttributeLabel
	 * @since 1.1.4
	 */
	public function getAttributeLabel($attribute)
	{
		$labels=$this->attributeLabels();
		if(isset($labels[$attribute]))
			return $labels[$attribute];
		else if(strpos($attribute,'.')!==false)
		{
			$segs=explode('.',$attribute);
			$name=array_pop($segs);
			$model=$this;
			foreach($segs as $seg)
			{
				$relations=$model->getMetaData()->relations;
				if(isset($relations[$seg]))
					$model=CActiveRecord::model($relations[$seg]->className);
				else
					break;
			}
			return $model->getAttributeLabel($name);
		}
		else
			return $this->generateAttributeLabel($attribute);
	}

	/**
	 * Возвращает соединение БД, используемое данным active record-экземпляром.
	 * По умолчанию, используется компонент приложения "db". Можно
	 * переопределить данный метод, если требуется использовать другое
	 * соединение БД
	 * @return CDbConnection соединение БД, используемое данным AR-экземпляром
	 */
	public function getDbConnection()
	{
		if(self::$db!==null)
			return self::$db;
		else
		{
			self::$db=Yii::app()->getDb();
			if(self::$db instanceof CDbConnection)
				return self::$db;
			else
				throw new CDbException(Yii::t('yii','Active Record requires a "db" CDbConnection application component.'));
		}
	}

	/**
	 * Возвращает именованную связь, объявленную для данного AR-класса
	 * @param string $name имя связи
	 * @return CActiveRelation именованная связь, объявленная для данного
	 * AR-класса. Null, если связь с данным имененм не существует
	 */
	public function getActiveRelation($name)
	{
		return isset($this->getMetaData()->relations[$name]) ? $this->getMetaData()->relations[$name] : null;
	}

	/**
	 * Возвращает метаданные таблицы, к которой относится данный AR-экземпляр
	 * @return CDbTableSchema метаданные таблицы, к которой относится данный AR-экземпляр
	 */
	public function getTableSchema()
	{
		return $this->getMetaData()->tableSchema;
	}

	/**
	 * Возвращает построитель запросов, используемый данным AR-экземпляром
	 * @return CDbCommandBuilder построитель запросов, используемый данным AR-экземпляром
	 */
	public function getCommandBuilder()
	{
		return $this->getDbConnection()->getSchema()->getCommandBuilder();
	}

	/**
	 * Проверяет, имеет ли данный AR-экземпляр именованный атрибут
	 * @param string $name имя атрибута
	 * @return boolean имеет ли данный AR-экземпляр именованный атрибут (столбец таблицы)
	 */
	public function hasAttribute($name)
	{
		return isset($this->getMetaData()->columns[$name]);
	}

	/**
	 * Возвращает значение именованного атрибута. Если данный AR-экземпляр
	 * является новой записью и атрибут прежде не был установлен, то
	 * возвращается значение столбца по умолчанию. Если данный AR-экземпляр
	 * является результатом запроса и атрибут не загружен, то возвращается
	 * значение null. Также можно использовать выражение $this->AttributeName
	 * для получение значения атрибута
	 * @param string $name имя атрибута
	 * @return mixed значение атрибута. Null, если атрибут не установлен или не
	 * существует
	 * @see hasAttribute
	 */
	public function getAttribute($name)
	{
		if(property_exists($this,$name))
			return $this->$name;
		else if(isset($this->_attributes[$name]))
			return $this->_attributes[$name];
	}

	/**
	 * Устанавливает значение именованного атрибута. Для установки значения
	 * атрибута также можно использовать выражение $this->AttributeName
	 * @param string $name имя атрибута
	 * @param mixed $value значение атрибута
	 * @return boolean существует ли атрибут и успешно ли выполнено присвоение
	 * значения атрибуту
	 * @see hasAttribute
	 */
	public function setAttribute($name,$value)
	{
		if(property_exists($this,$name))
			$this->$name=$value;
		else if(isset($this->getMetaData()->columns[$name]))
			$this->_attributes[$name]=$value;
		else
			return false;
		return true;
	}

	/**
	 * Не вызывайте данный метод. Он используется классом {@link CActiveFinder}
	 * для создания и заполнения данными связанных объектов. Данный метод
	 * добавляет связанные объекты в данный AR-экземпляр
	 * @param string $name имя атрибута
	 * @param mixed $record связанная запись
	 * @param mixed $index значение индекса коллекции связанных объектов. Если
	 * имеет значение true, то используется целочисленный индекс, отсчитываемый
	 * с нуля. Если имеет значение false, то это значит, что тип связи -
	 * HAS_ONE или BELONGS_TO и индекс не требуется
	 */
	public function addRelatedRecord($name,$record,$index)
	{
		if($index!==false)
		{
			if(!isset($this->_related[$name]))
				$this->_related[$name]=array();
			if($record instanceof CActiveRecord)
			{
				if($index===true)
					$this->_related[$name][]=$record;
				else
					$this->_related[$name][$index]=$record;
			}
		}
		else if(!isset($this->_related[$name]))
			$this->_related[$name]=$record;
	}

	/**
	 * Возвращает значения всех атрибутов-столбцов. Примечание: связанные
	 * объекты не возвращаются
	 * @param mixed $names имена атрибутов, чьи значения требуется возвратить.
	 * Если имеет значение true (по умолчанию), то возвращаются значения всех
	 * атрибутов, включая те, которые не были загружены из БД (для таких
	 * атрибутов возвращается значние null). Если имеет значение null, то
	 * возвращаются значения всех атрибутов кроме тех, что не были загружены из БД
	 * @return array значения атрибутов, индексированные по именам атрибутов
	 */
	public function getAttributes($names=true)
	{
		$attributes=$this->_attributes;
		foreach($this->getMetaData()->columns as $name=>$column)
		{
			if(property_exists($this,$name))
				$attributes[$name]=$this->$name;
			else if($names===true && !isset($attributes[$name]))
				$attributes[$name]=null;
		}
		if(is_array($names))
		{
			$attrs=array();
			foreach($names as $name)
			{
				if(property_exists($this,$name))
					$attrs[$name]=$this->$name;
				else
					$attrs[$name]=isset($attributes[$name])?$attributes[$name]:null;
			}
			return $attrs;
		}
		else
			return $attributes;
	}

	/**
	 * Сохраняет текущую запись.
	 *
	 * Если свойство {@link isNewRecord} имеет значение true, то запись
	 * вставляется в БД как строка (обычно, когда запись создается с
	 * использованием оператора 'new'). Иначе, происходит обновление
	 * соответствующей строки в таблице БД (обычно, когда запись получена с
	 * использованием одного из 'find' методов).
	 *
	 * Перед сохранением записи происходит валидация значений атрибутов. Если
	 * валидация не пройдена, то запись не будет сохранена. Для получения
	 * ошибок валидации необходимо вызвать метод {@link getErrors()}.
	 *
	 * Если запись сохранена с помощью вставки, его свойство {@link isNewRecord}
	 * будет установлено в значение false, а свойство {@link scenario} - в
	 * значение 'update'. Также, если первичный ключ является автоинкрементным
	 * и он не был установлен перед вставкой, то значение первичного ключа
	 * примет значение автоматически сгенерированное в БД
	 * @param boolean $runValidation выполнять ли валидацию перед сохранением
	 * записи. Если валидация не пройдена, то запись не сохраняется в БД
	 * @param array $attributes список сохраняемых атрибутов. По умолчанию -
	 * null, т.е., сохраняются все атрибуты, загруженные из БД
	 * @return boolean успешно ли завершилось сохранение записи
	 */
	public function save($runValidation=true,$attributes=null)
	{
		if(!$runValidation || $this->validate($attributes))
			return $this->getIsNewRecord() ? $this->insert($attributes) : $this->update($attributes);
		else
			return false;
	}

	/**
	 * Возвращает значение true, если данная запись является новой, иначе
	 * возвращает значение false
	 * @return boolean является ли новой данная запись и должна ли она быть
	 * вставлена при вызове метода {@link save}. Данное свойство автоматически
	 * устанавливается в конструкторе и методе {@link populateRecord}. По
	 * умолчанию - false, но устанавливается в значение true, если экземпляр
	 * создается с помощью оператора 'new'
	 */
	public function getIsNewRecord()
	{
		return $this->_new;
	}

	/**
	 * УСтанавливает значение, показывающее, является ли новой текущая запись
	 * @param boolean $value является ли новой данная запись и должна ли она
	 * быть вставлена при вызове метода {@link save}
	 * @see getIsNewRecord
	 */
	public function setIsNewRecord($value)
	{
		$this->_new=$value;
	}

	/**
	 * Данное событие вызывается перед сохранением записи. Установка свойства
	 * {@link CModelEvent::isValid} в значение false останавливает нормальный
	 * процесс выполнения метода {@link save()}
	 * @param CModelEvent $event параметр события
	 */
	public function onBeforeSave($event)
	{
		$this->raiseEvent('onBeforeSave',$event);
	}

	/**
	 * Данное событие вызывается после сохранения записи
	 * @param CModelEvent $event параметр события
	 */
	public function onAfterSave($event)
	{
		$this->raiseEvent('onAfterSave',$event);
	}

	/**
	 * Данное событие вызывается перед удалением записи. Установка свойства
	 * {@link CModelEvent::isValid} в значение false останавливает нормальный
	 * процесс выполнения метода {@link save()}
	 * @param CModelEvent $event параметр события
	 */
	public function onBeforeDelete($event)
	{
		$this->raiseEvent('onBeforeDelete',$event);
	}

	/**
	 * Данное событие вызывается после удаления записи
	 * @param CModelEvent $event параметр события
	 */
	public function onAfterDelete($event)
	{
		$this->raiseEvent('onAfterDelete',$event);
	}

	/**
	 * Данное событие вызывается перед вызовом метода поиска записей. В данном
	 * событии свойство {@link CModelEvent::criteria} содержит критерий
	 * запроса, передаваемый параметром в соответствующий метод поиска. Для
	 * доступа к критерию запроса, определенному в группах условий используется
	 * метод {@link getDbCriteria()}. Можно изменять критерий так, как это
	 * необходимо
	 * @param CModelEvent $event параметр события
	 * @see beforeFind
	 */
	public function onBeforeFind($event)
	{
		$this->raiseEvent('onBeforeFind',$event);
	}

	/**
	 * Данное свойство вызывается после выполнения метода поиска и создания
	 * объектов найденных записей
	 * @param CModelEvent $event параметр события
	 */
	public function onAfterFind($event)
	{
		$this->raiseEvent('onAfterFind',$event);
	}

	/**
	 * Данный метод вызывается перед сохранением записи (после валидации, если
	 * она используется). Реализация по умолчанию вызывает событие
	 * {@link onBeforeSave}. Можно переопределить данный метод для выполнения
	 * некоторой подготовительной работы для сохранения записи. Для определения
	 * того, как должна сохраняться запись - вставкой или обновлением,
	 * используется свойство {@link isNewRecord}. Для правильного вызова
	 * событий необходимо убедиться, что родительская реализация вызывается в
	 * данном методе
	 * @return boolean должно ли быть выполенено сохранение. По умолчанию - true
	 */
	protected function beforeSave()
	{
		if($this->hasEventHandler('onBeforeSave'))
		{
			$event=new CModelEvent($this);
			$this->onBeforeSave($event);
			return $event->isValid;
		}
		else
			return true;
	}

	/**
	 * Данный метод вызывается после успешного сохранения записи. Реализация по
	 * умолчанию вызывает событие {@link onAfterSave}. Можно переопределить
	 * данный метод для выполнения некоторых функций после сохранения записи.
	 * Для правильного вызова событий необходимо убедиться, что родительская
	 * реализация вызывается в данном методе
	 */
	protected function afterSave()
	{
		if($this->hasEventHandler('onAfterSave'))
			$this->onAfterSave(new CEvent($this));
	}

	/**
	 * Данный метод вызывается перед удалением записи. Реализация по умолчанию
	 * вызывает событие {@link onBeforeDelete}. Можно переопределить данный
	 * метод для выполнения некоторой подготовительной работы для удаления
	 * записи. Для правильного вызова событий необходимо убедиться, что
	 * родительская реализация вызывается в данном методе
	 * @return boolean должна ли запись быть удалена. По умолчанию - true
	 */
	protected function beforeDelete()
	{
		if($this->hasEventHandler('onBeforeDelete'))
		{
			$event=new CModelEvent($this);
			$this->onBeforeDelete($event);
			return $event->isValid;
		}
		else
			return true;
	}

	/**
	 * Данный метод вызывается после удаления записи. Реализация по умолчанию
	 * вызывает событие {@link onAfterDelete}. Можно переопределить
	 * данный метод для выполнения некоторых функций после удаления записи.
	 * Для правильного вызова событий необходимо убедиться, что
	 * родительская реализация вызывается в данном методе
	 */
	protected function afterDelete()
	{
		if($this->hasEventHandler('onAfterDelete'))
			$this->onAfterDelete(new CEvent($this));
	}

	/**
	 * Данный метод вызывается перед выполнением метода поиска записей. Список
	 * методов поиска - {@link find}, {@link findAll}, {@link findByPk},
	 * {@link findAllByPk}, {@link findByAttributes} и
	 * {@link findAllByAttributes}. Реализация по умолчанию вызывает событие
	 * {@link onBeforeFind}. При переопределении метода для правильного вызова
	 * событий необходимо убедиться, что родительская реализация вызывается в
	 * данном методе. Начиная с версии 1.1.5 данный метод может быть вызван со
	 * скрытым параметром {@link CDbCriteria}, представляющим текущий критерий
	 * запроса в качестве передаваемого в метод поиска параметра
	 */
	protected function beforeFind()
	{
		if($this->hasEventHandler('onBeforeFind'))
		{
			$event=new CModelEvent($this);
			// для обратной совместимости
			$event->criteria=func_num_args()>0 ? func_get_arg(0) : null;
			$this->onBeforeFind($event);
		}
	}

	/**
	 * Данный метод вызывается после создания каждой записи, полученной методом
	 * поиска. Реализация по умолчанию вызывает событие {@link onAfterFind}.
	 * Можно переопределить данный метод для выполнения некоторых функций после
	 * создания найденной записи. Для правильного вызова событий необходимо
	 * убедиться, что родительская реализация вызывается в данном методе
	 */
	protected function afterFind()
	{
		if($this->hasEventHandler('onAfterFind'))
			$this->onAfterFind(new CEvent($this));
	}

	/**
	 * Вызывает метод {@link beforeFind}.
	 * Данный метод предназначен для внутреннего использования
	 */
	public function beforeFindInternal()
	{
		$this->beforeFind();
	}

	/**
	 * Вызывает метод {@link afterFind}.
	 * Данный метод предназначен для внутреннего использования
	 */
	public function afterFindInternal()
	{
		$this->afterFind();
	}

	/**
	 * Вставляет строку в таблицу на основе атрибутов записи. Если первичный
	 * ключ является автоинкрементным и его значение перед вставкой равно null,
	 * то после вставки его значение примет реальное значение вставленной
	 * строки. Примечание: в данном методе валидация не выполняется. Можно
	 * вызвать метода {@link validate} для выполнения валидации. После успешной
	 * вставки записи в БД, ее свойство {@link isNewRecord} будет установлено в
	 * значение false, а свойство {@link scenario} - в значение 'update'
	 * @param array $attributes список сохраняемых атрибутов. По умолчанию -
	 * null, т.е., сохраняются все атрибуты, загруженные из БД
	 * @return boolean являются ли атрибуты валидными и успешно ли вставлена
	 * запись в БД
	 * @throws CException вызывается, если запись не является новой
	 */
	public function insert($attributes=null)
	{
		if(!$this->getIsNewRecord())
			throw new CDbException(Yii::t('yii','The active record cannot be inserted to database because it is not new.'));
		if($this->beforeSave())
		{
			Yii::trace(get_class($this).'.insert()','system.db.ar.CActiveRecord');
			$builder=$this->getCommandBuilder();
			$table=$this->getMetaData()->tableSchema;
			$command=$builder->createInsertCommand($table,$this->getAttributes($attributes));
			if($command->execute())
			{
				$primaryKey=$table->primaryKey;
				if($table->sequenceName!==null)
				{
					if(is_string($primaryKey) && $this->$primaryKey===null)
						$this->$primaryKey=$builder->getLastInsertID($table);
					else if(is_array($primaryKey))
					{
						foreach($primaryKey as $pk)
						{
							if($this->$pk===null)
							{
								$this->$pk=$builder->getLastInsertID($table);
								break;
							}
						}
					}
				}
				$this->_pk=$this->getPrimaryKey();
				$this->afterSave();
				$this->setIsNewRecord(false);
				$this->setScenario('update');
				return true;
			}
		}
		return false;
	}

	/**
	 * Обновляет строку, представляющую данную запись. ВСе загруженные атрибуты
	 * будут сохранены в БД. Примечание: в данном методе валидация не
	 * выполняется. Для выполнения валидации можно вызвать метод
	 * {@link validate}
	 * @param array $attributes список сохраняемых атрибутов. По умолчанию -
	 * null, т.е., сохраняются все атрибуты, загруженные из БД
	 * @return boolean успешно ли выполнено обновление
	 * @throws CException вызывается, если запись является новой
	 */
	public function update($attributes=null)
	{
		if($this->getIsNewRecord())
			throw new CDbException(Yii::t('yii','The active record cannot be updated because it is new.'));
		if($this->beforeSave())
		{
			Yii::trace(get_class($this).'.update()','system.db.ar.CActiveRecord');
			if($this->_pk===null)
				$this->_pk=$this->getPrimaryKey();
			$this->updateByPk($this->getOldPrimaryKey(),$this->getAttributes($attributes));
			$this->_pk=$this->getPrimaryKey();
			$this->afterSave();
			return true;
		}
		else
			return false;
	}

	/**
	 * Сохраняет выбранные атрибуты по списку. В отличие от метода
	 * {@link save}, данный метод только сохраняет определенные атрибуты
	 * существующей строки и НЕ вызывает ни метод {@link beforeSave} ни метод
	 * {@link afterSave}. Также необходимо заметить, что данный метод не
	 * выполняет ни фильтрацию ни валидацию атрибутов. Поэтому не стоит
	 * использовать данный метод с непроверенными данными (такими, как
	 * введенные пользователем данные). В качестве альтернативного варианта
	 * можно рассмотреть следующий пример:
	 * <pre>
	 * $postRecord=Post::model()->findByPk($postID);
	 * $postRecord->attributes=$_POST['post'];
	 * $postRecord->save();
	 * </pre>
	 * @param array $attributes обновляемые атрибуты. Каждый элемент
	 * представляет собой имя атрибута или значение атрибута, индексированное
	 * по его имени. Во втором случае атрибут записи будет соответствено
	 * изменен перед сохранением
	 * @return boolean успешно ли выполнено обновление
	 * @throws CException вызывается, если запись новая или произошла ошибка БД
	 */
	public function saveAttributes($attributes)
	{
		if(!$this->getIsNewRecord())
		{
			Yii::trace(get_class($this).'.saveAttributes()','system.db.ar.CActiveRecord');
			$values=array();
			foreach($attributes as $name=>$value)
			{
				if(is_integer($name))
					$values[$value]=$this->$value;
				else
					$values[$name]=$this->$name=$value;
			}
			if($this->_pk===null)
				$this->_pk=$this->getPrimaryKey();
			if($this->updateByPk($this->getOldPrimaryKey(),$values)>0)
			{
				$this->_pk=$this->getPrimaryKey();
				return true;
			}
			else
				return false;
		}
		else
			throw new CDbException(Yii::t('yii','The active record cannot be updated because it is new.'));
	}

	/**
	 * Сохраняет один или несколько счетчиков столбцов для текущего AR-объекта.
	 * Примечание: данный метод отличается от метода {@link updateCounters}
	 * тем, что он только сохраняет текущий AR-объект. Пример использования:
	 * <pre>
	 * $postRecord=Post::model()->findByPk($postID);
	 * $postRecord->saveCounters(array('view_count'=>1));
	 * </pre>
	 * Для уменьшения счетчика используется отрицательное значение
	 * @param array $counters обновляемые счетчики (имя столбца => прибавляемое
	 * значение)
	 * @return boolean успешно ли выполнено сохранение
	 * @see updateCounters
	 * @since 1.1.8
	 */
	public function saveCounters($counters)
	{
		Yii::trace(get_class($this).'.saveCounters()','system.db.ar.CActiveRecord');
		$builder=$this->getCommandBuilder();
		$table=$this->getTableSchema();
		$criteria=$builder->createPkCriteria($table,$this->getOldPrimaryKey());
		$command=$builder->createUpdateCounterCommand($this->getTableSchema(),$counters,$criteria);
		if($command->execute())
		{
			foreach($counters as $name=>$value)
				$this->$name=$this->$name+$value;
			return true;
		}
		else
			return false;
	}

	/**
	 * Удаляет строку, соответствующую данному AR-объекту
	 * @return boolean успешно ли выполнено удаление
	 * @throws CException вызывается, если запись является новой
	 */
	public function delete()
	{
		if(!$this->getIsNewRecord())
		{
			Yii::trace(get_class($this).'.delete()','system.db.ar.CActiveRecord');
			if($this->beforeDelete())
			{
				$result=$this->deleteByPk($this->getPrimaryKey())>0;
				$this->afterDelete();
				return $result;
			}
			else
				return false;
		}
		else
			throw new CDbException(Yii::t('yii','The active record cannot be deleted because it is new.'));
	}

	/**
	 * Перезагружает данные текущего AR-объекта самыми последними значениями
	 * @return boolean существует ли еще строка в БД. Если возвращается true,
	 * то свежайшие данные будут загружены в текущий AR-объект
	 */
	public function refresh()
	{
		Yii::trace(get_class($this).'.refresh()','system.db.ar.CActiveRecord');
		if(!$this->getIsNewRecord() && ($record=$this->findByPk($this->getPrimaryKey()))!==null)
		{
			$this->_attributes=array();
			$this->_related=array();
			foreach($this->getMetaData()->columns as $name=>$column)
			{
				if(property_exists($this,$name))
					$this->$name=$record->$name;
				else
					$this->_attributes[$name]=$record->$name;
			}
			return true;
		}
		else
			return false;
	}

	/**
	 * Сравнивает текущий AR-объект с другим. Сравнение проводится по имени
	 * таблицы и значениям первичных ключей двух сравниваемых AR-объектов
	 * @param CActiveRecord $record AR-объект для сравнения
	 * @return boolean ссылаются ли два AR-объекта на одну и ту же строку в
	 * таблице БД
	 */
	public function equals($record)
	{
		return $this->tableName()===$record->tableName() && $this->getPrimaryKey()===$record->getPrimaryKey();
	}

	/**
	 * Возвращает значение первичного ключа
	 * @return mixed значение первичного ключа. Если ключ составной, то
	 * возвращается массив (имя столбца => значение столбца). Если первичный
	 * ключ не определен, возвращается null
	 */
	public function getPrimaryKey()
	{
		$table=$this->getMetaData()->tableSchema;
		if(is_string($table->primaryKey))
			return $this->{$table->primaryKey};
		else if(is_array($table->primaryKey))
		{
			$values=array();
			foreach($table->primaryKey as $name)
				$values[$name]=$this->$name;
			return $values;
		}
		else
			return null;
	}

	/**
	 * Устанавливает значение первичного ключа. После вызова данного метода
	 * старый первичный ключ может быть получен из свойства {@link oldPrimaryKey}
	 * @param mixed $value новое значение первичного ключа. Если первичнй ключ
	 * составной, новое значение должно передаваться массивом (имя столбца =>
	 * значение столбца)
	 * @since 1.1.0
	 */
	public function setPrimaryKey($value)
	{
		$this->_pk=$this->getPrimaryKey();
		$table=$this->getMetaData()->tableSchema;
		if(is_string($table->primaryKey))
			$this->{$table->primaryKey}=$value;
		else if(is_array($table->primaryKey))
		{
			foreach($table->primaryKey as $name)
				$this->$name=$value[$name];
		}
	}

	/**
	 * Возвращает старое значение первичного ключа. Это значение первичного
	 * ключа, загруженное в AR-объект после выполнения метода поиска (например,
	 * find(), findAll()). Значение остается неизменным даже если атрибуту
	 * первичного ключа вручную присвоено другое значение
	 * @return mixed старое значение первичного ключа. Если первичный ключ
	 * является составным, то возвращается массив (имя столбца => значение
	 * столбца). Если первичный ключ не определен, то возвращается null
	 * @since 1.1.0
	 */
	public function getOldPrimaryKey()
	{
		return $this->_pk;
	}

	/**
	 * Устанавливает старое значение первичного ключа
	 * @param mixed $value старое значение первичного ключа
	 * @since 1.1.3
	 */
	public function setOldPrimaryKey($value)
	{
		$this->_pk=$value;
	}

	/**
	 * Выполняет запрос к БД и заполняет AR-объекты данными, полученными в
	 * результате запроса. Данный метод в основном используется внутренним
	 * механизмом другими методами, выполняющими запросы к БД (например,
	 * findAll())
	 * @param CDbCriteria $criteria критерий запроса
	 * @param boolean $all возвращать ли все данные
	 * @return mixed AR-объекты, заполненные данными результата запроса
	 * @since 1.1.7
	 */
	protected function query($criteria,$all=false)
	{
        $this->beforeFind();
		$this->applyScopes($criteria);
		if(empty($criteria->with))
		{
			if(!$all)
				$criteria->limit=1;
			$command=$this->getCommandBuilder()->createFindCommand($this->getTableSchema(),$criteria);
			return $all ? $this->populateRecords($command->queryAll(), true, $criteria->index) : $this->populateRecord($command->queryRow());
		}
		else
		{
			$finder=new CActiveFinder($this,$criteria->with);
			return $finder->query($criteria,$all);
		}
	}

	/**
	 * Применяет именованные группы условий к переданному критерию. Данный
	 * метод сливает критерий, содержащийся в свойстве {@link dbCriteria}, с
	 * переданным в качестве параметра критерием, а затем сбрасывает свойство
	 * {@link dbCriteria} в null
	 * @param CDbCriteria $criteria критерий запроса. Данный параметр может
	 * быть изменен слиянием с {@link dbCriteria}
	 */
	public function applyScopes(&$criteria)
	{
		if(!empty($criteria->scopes))
		{
			$scs=$this->scopes();
			$c=$this->getDbCriteria();
			foreach((array)$criteria->scopes as $k=>$v)
			{
				if(is_integer($k))
				{
					if(is_string($v))
					{
						if(isset($scs[$v]))
						{
							$c->mergeWith($scs[$v],true);
							continue;
						}
						$scope=$v;
						$params=array();
					}
					else if(is_array($v))
					{
						$scope=key($v);
						$params=current($v);
					}
				}
				else if(is_string($k))
				{
					$scope=$k;
					$params=$v;
				}

				call_user_func_array(array($this,$scope),(array)$params);
			}
		}

		if(isset($c) || ($c=$this->getDbCriteria(false))!==null)
		{
			$c->mergeWith($criteria);
			$criteria=$c;
			$this->_c=null;
		}
	}

	/**
	 * Возвращает псевдоним таблицы, используемый в методах поиска. В
	 * реляционых запросах возвращаемый псевдоним может варьироваться в
	 * зависимости от определения связи. Также, псевдоним таблицы по умолчанию,
	 * установленный методом {@link setTableAlias} может быть переопределен
	 * применяемыми группами условий
	 * @param boolean $quote заключать ли псевдоним в кавычки
	 * @param boolean $checkScopes проверять ли, что псевдоним таблицы
	 * опеределен в применяемых группах условий. Данный параметр должен быть
	 * установлен в значение false при вызове данного метода из метода
	 * {@link defaultScope}, иначе возможен бесконечный цикл
	 * @return string псевдоним таблицы по умолчанию
	 * @since 1.1.1
	 */
	public function getTableAlias($quote=false, $checkScopes=true)
	{
		if($checkScopes && ($criteria=$this->getDbCriteria(false))!==null && $criteria->alias!='')
			$alias=$criteria->alias;
		else
			$alias=$this->_alias;
		return $quote ? $this->getDbConnection()->getSchema()->quoteTableName($alias) : $alias;
	}

	/**
	 * Устанавливает псевдоним таблицы для использования в запросах
	 * @param string $alias псевдоним таблицы для использования в запросах.
	 * Псевдоним НЕ будет заключен в кавычки
	 * @since 1.1.3
	 */
	public function setTableAlias($alias)
	{
		$this->_alias=$alias;
	}

	/**
	 * Находит одну запись в БД по определенным условиям
	 * @param mixed $condition условия запроса или критерий. Если передана
	 * строка, то она считается условием запроса (выражением WHERE); если
	 * передан массив, то он считается начальными значениями для создания
	 * объекта {@link CDbCriteria критерия}; в ином случае, должен быть передан
	 * экземпляр класса {@link CDbCriteria}
	 * @param array $params передаваемые в SQL-выражение параметры. Использутся
	 * только если первый параметр является строкой (условие запроса). Иначе
	 * для установки параметров необходимо использовать свойство
	 * {@link CDbCriteria::params}
	 * @return CActiveRecord найденная запись. Null, если запись не найдена
	 */
	public function find($condition='',$params=array())
	{
		Yii::trace(get_class($this).'.find()','system.db.ar.CActiveRecord');
		$criteria=$this->getCommandBuilder()->createCriteria($condition,$params);
		return $this->query($criteria);
	}

	/**
	 * Находит все записи в БД, удовлетворяющие определенным условиям. За
	 * детальным описанием параметров $condition и $params обратитесь к
	 * методу {@link find()}
	 * @param mixed $condition условие запроса или критерий
	 * @param array $params передаваемые в SQL-выражение параметры
	 * @return array список записей, удовлетворяющих определенным условиям.
	 * Если записи не найдены, возвращается пустой массив
	 */
	public function findAll($condition='',$params=array())
	{
		Yii::trace(get_class($this).'.findAll()','system.db.ar.CActiveRecord');
		$criteria=$this->getCommandBuilder()->createCriteria($condition,$params);
		return $this->query($criteria,true);
	}

	/**
	 * Находит одну запись в БД по определенному первичному ключу. За
	 * детальным описанием параметров $condition и $params обратитесь к
	 * методу {@link find()}
	 * @param mixed $pk значение(я) первичного ключа. Для поиска по нескольким
	 * первичных ключей используется массив. В случае составного ключа, каждое
	 * значение ключа должно быть массивом (имя столбца => значение столбца)
	 * @param mixed $condition условие запроса или критерий
	 * @param array $params передаваемые в SQL-выражение параметры
	 * @return CActiveRecord найденная запись. Null, если запись не найдена
	 */
	public function findByPk($pk,$condition='',$params=array())
	{
		Yii::trace(get_class($this).'.findByPk()','system.db.ar.CActiveRecord');
		$prefix=$this->getTableAlias(true).'.';
		$criteria=$this->getCommandBuilder()->createPkCriteria($this->getTableSchema(),$pk,$condition,$params,$prefix);
		return $this->query($criteria);
	}

	/**
	 * Находит все записи в БД по определенным первичным ключам. За
	 * детальным описанием параметров $condition и $params обратитесь к
	 * методу {@link find()}
	 * @param mixed $pk значение(я) первичного ключа. Для поиска по нескольким
	 * первичных ключей используется массив. В случае составного ключа, каждое
	 * значение ключа должно быть массивом (имя столбца => значение столбца)
	 * @param mixed $condition условие запроса или критерий
	 * @param array $params передаваемые в SQL-выражение параметры
	 * @return array список найденных записей. Если записи не найдены,
	 * возвращается пустой массив
	 */
	public function findAllByPk($pk,$condition='',$params=array())
	{
		Yii::trace(get_class($this).'.findAllByPk()','system.db.ar.CActiveRecord');
		$prefix=$this->getTableAlias(true).'.';
		$criteria=$this->getCommandBuilder()->createPkCriteria($this->getTableSchema(),$pk,$condition,$params,$prefix);
		return $this->query($criteria,true);
	}

	/**
	 * Находит одну запись в БД по определенным значениям атриботов. За
	 * детальным описанием параметров $condition и $params обратитесь к
	 * методу {@link find()}
	 * @param array $attributes список значений атрибутов (индексированный по
	 * именам атрибутов), которым должна соответствовать запись.
	 * Значение атрибута может быть массивом, используемым для генерации
	 * условия IN
	 * @param mixed $condition условие запроса или критерий
	 * @param array $params передаваемые в SQL-выражение параметры
	 * @return CActiveRecord найденная запись. Null, если запись не найдена
	 */
	public function findByAttributes($attributes,$condition='',$params=array())
	{
		Yii::trace(get_class($this).'.findByAttributes()','system.db.ar.CActiveRecord');
		$prefix=$this->getTableAlias(true).'.';
		$criteria=$this->getCommandBuilder()->createColumnCriteria($this->getTableSchema(),$attributes,$condition,$params,$prefix);
		return $this->query($criteria);
	}

	/**
	 * Находит все записи в БД по определенным значениям атрибутов. За
	 * детальным описанием параметров $condition и $params обратитесь к
	 * методу {@link find()}
	 * @param array $attributes список значений атрибутов (индексированный по
	 * именам атрибутов), которым должна соответствовать запись.
	 * Значение атрибута может быть массивом, используемым для генерации
	 * условия IN
	 * @param mixed $condition условие запроса или критерий
	 * @param array $params передаваемые в SQL-выражение параметры
	 * @return CActiveRecord найденная запись. Null, если запись не найдена
	 */
	public function findAllByAttributes($attributes,$condition='',$params=array())
	{
		Yii::trace(get_class($this).'.findAllByAttributes()','system.db.ar.CActiveRecord');
		$prefix=$this->getTableAlias(true).'.';
		$criteria=$this->getCommandBuilder()->createColumnCriteria($this->getTableSchema(),$attributes,$condition,$params,$prefix);
		return $this->query($criteria,true);
	}

	/**
	 * Находит одну запись в БД по определенному SQL-выражению
	 * @param string $sql SQL-выражение
	 * @param array $params передаваемые в SQL-выражение параметры
	 * @return CActiveRecord найденная запись. Null, если запись не найдена
	 */
	public function findBySql($sql,$params=array())
	{
		Yii::trace(get_class($this).'.findBySql()','system.db.ar.CActiveRecord');
		$this->beforeFind();
		if(($criteria=$this->getDbCriteria(false))!==null && !empty($criteria->with))
		{
			$this->_c=null;
			$finder=new CActiveFinder($this,$criteria->with);
			return $finder->findBySql($sql,$params);
		}
		else
		{
			$command=$this->getCommandBuilder()->createSqlCommand($sql,$params);
			return $this->populateRecord($command->queryRow());
		}
	}

	/**
	 * Находит все записи в БД по определенному SQL-выражению
	 * @param string $sql SQL-выражение
	 * @param array $params передаваемые в SQL-выражение параметры
	 * @return array список найденных записей. Если записи не найдены,
	 * возвращается пустой массив
	 */
	public function findAllBySql($sql,$params=array())
	{
		Yii::trace(get_class($this).'.findAllBySql()','system.db.ar.CActiveRecord');
		$this->beforeFind();
		if(($criteria=$this->getDbCriteria(false))!==null && !empty($criteria->with))
		{
			$this->_c=null;
			$finder=new CActiveFinder($this,$criteria->with);
			return $finder->findAllBySql($sql,$params);
		}
		else
		{
			$command=$this->getCommandBuilder()->createSqlCommand($sql,$params);
			return $this->populateRecords($command->queryAll());
		}
	}

	/**
	 * Находит число строк, удовлетворяющих определенному условию запроса. За
	 * детальным описанием параметров $condition и $params обратитесь к
	 * методу {@link find()}
	 * @param mixed $condition условие запроса или критерий
	 * @param array $params передаваемые в SQL-выражение параметры
	 * @return string число строк, удовлетворяющих определенному условию
	 * запроса. Примечание: возвращается строка для сохранения максимальной
	 * точности
	 */
	public function count($condition='',$params=array())
	{
		Yii::trace(get_class($this).'.count()','system.db.ar.CActiveRecord');
		$builder=$this->getCommandBuilder();
		$criteria=$builder->createCriteria($condition,$params);
		$this->applyScopes($criteria);

		if(empty($criteria->with))
			return $builder->createCountCommand($this->getTableSchema(),$criteria)->queryScalar();
		else
		{
			$finder=new CActiveFinder($this,$criteria->with);
			return $finder->count($criteria);
		}
	}

	/**
	 * Находит число строк, имеющих определенные значения атрибутов. За
	 * детальным описанием параметров $condition и $params обратитесь к
	 * методу {@link find()}
	 * @param array $attributes список значений атрибутов (индексированных по
	 * именам атрибутов), которому должны соответствовать записи. Значение
	 * атрибута может быть массивом, используемым для генерации условий IN
	 * @param mixed $condition условие запроса или критерий
	 * @param array $params передаваемые в SQL-выражение параметры
	 * @return string число строк, имеющих определенные значения атрибутов.
	 * Примечание: возвращается строка для сохранения максимальной точности
	 * @since 1.1.4
	 */
	public function countByAttributes($attributes,$condition='',$params=array())
	{
		Yii::trace(get_class($this).'.countByAttributes()','system.db.ar.CActiveRecord');
		$prefix=$this->getTableAlias(true).'.';
		$builder=$this->getCommandBuilder();
		$criteria=$builder->createColumnCriteria($this->getTableSchema(),$attributes,$condition,$params,$prefix);
		$this->applyScopes($criteria);

		if(empty($criteria->with))
			return $builder->createCountCommand($this->getTableSchema(),$criteria)->queryScalar();
		else
		{
			$finder=new CActiveFinder($this,$criteria->with);
			return $finder->count($criteria);
		}
	}

	/**
	 * Находит число строк по переданному SQL-выражению. Эквивалентно вызову
	 * метода {@link CDbCommand::queryScalar} с определенным SQL-выражением и
	 * параметрами
	 * @param string $sql SQL-выражение
	 * @param array $params передаваемые в SQL-выражение параметры
	 * @return string число строк, определяемое по переданному SQL-выражению.
	 * Примечание: возвращается строка для сохранения максимальной точности
	 */
	public function countBySql($sql,$params=array())
	{
		Yii::trace(get_class($this).'.countBySql()','system.db.ar.CActiveRecord');
		return $this->getCommandBuilder()->createSqlCommand($sql,$params)->queryScalar();
	}

	/**
	 * Проверяет, существует ли строка, удовлетворяющая определенным условиям.
	 * За детальным описанием параметров $condition и $params обратитесь к
	 * методу {@link find()}
	 * @param mixed $condition условие запроса или критерий
	 * @param array $params передаваемые в SQL-выражение параметры
	 * @return boolean существует ли строка, удовлетворяющая определенным условиям
	 */
	public function exists($condition='',$params=array())
	{
		Yii::trace(get_class($this).'.exists()','system.db.ar.CActiveRecord');
		$builder=$this->getCommandBuilder();
		$criteria=$builder->createCriteria($condition,$params);
		$table=$this->getTableSchema();
		$criteria->select='1';
		$criteria->limit=1;
		$this->applyScopes($criteria);

		if(empty($criteria->with))
			return $builder->createFindCommand($table,$criteria)->queryRow()!==false;
		else
		{
			$criteria->select='*';
			$finder=new CActiveFinder($this,$criteria->with);
			return $finder->count($criteria)>0;
		}
	}

	/**
	 * Определяет, какие связанные объекты должны загружаться с помощью
	 * "жадной" загрузки. Метод принимаер различное количество параметров.
	 * Каждый параметр опеределяет имя связи или дочерней связи. Например,
	 * <pre>
	 * // находит все записи вместе с их авторами и комментариям
	 * Post::model()->with('author','comments')->findAll();
	 * // находит все записи вместе с их авторами и профилями авторов
	 * Post::model()->with('author','author.profile')->findAll();
	 * </pre>
	 * Связи должны быть объявлены в методе {@link relations()}.
	 *
	 * По умолчанию для выполнения реляционного запроса используются опции,
	 * определенные в методе {@link relations()}. Для того, чтобы настроить
	 * опции "на лету", необходимо передать в метод with() параметр в виде
	 * массива, ключи которого являются именами связей, а значения -
	 * соответствующими опциями запроса. Например,
	 * <pre>
	 * Post::model()->with(array(
	 *     'author'=>array('select'=>'id, name'),
	 *     'comments'=>array('condition'=>'approved=1', 'order'=>'create_time'),
	 * ))->findAll();
	 * </pre>
	 *
	 * @return CActiveRecord данный AR-объект
	 */
	public function with()
	{
		if(func_num_args()>0)
		{
			$with=func_get_args();
			if(is_array($with[0]))  // the parameter is given as an array
				$with=$with[0];
			if(!empty($with))
				$this->getDbCriteria()->mergeWith(array('with'=>$with));
		}
		return $this;
	}

	/**
	 * Устанавливает свойство {@link CDbCriteria::together} в значение true.
	 * Используется только в реляционных запросах. За деталями обратитесь к
	 * описанию свойства {@link CDbCriteria::together}
	 * @return CActiveRecord данный AR-объект
	 * @since 1.1.4
	 */
	public function together()
	{
		$this->getDbCriteria()->together=true;
		return $this;
	}

	/**
	 * Обновляет запись с определенным(и) первичным(и) ключом(ами). За
	 * детальным описанием параметров $condition и $params обратитесь к
	 * методу {@link find()}. Примечание: атрибуты не проверяются на
	 * безопасность и НЕ проходят валидацию
	 * @param mixed $pk значение(я) первичного(ых) ключа(ей). Для задания
	 * нескольких первичных ключей используется массив. В случае составного
	 * ключа каждое значение ключа должно быть массивом (имя столбца =>
	 * значение столбца)
	 * @param array $attributes список обновляемых атрибутов (имя => значение)
	 * @param mixed $condition условие запроса или критерий
	 * @param array $params передаваемые в SQL-выражение параметры
	 * @return integer число обновленных строк
	 */
	public function updateByPk($pk,$attributes,$condition='',$params=array())
	{
		Yii::trace(get_class($this).'.updateByPk()','system.db.ar.CActiveRecord');
		$builder=$this->getCommandBuilder();
		$table=$this->getTableSchema();
		$criteria=$builder->createPkCriteria($table,$pk,$condition,$params);
		$command=$builder->createUpdateCommand($table,$attributes,$criteria);
		return $command->execute();
	}

	/**
	 * Обновляет записи, соответствующие определенным условиям. За детальным
	 * описанием параметров $condition и $params обратитесь к методу
	 * {@link find()}. Примечание: атрибуты не проверяются на безопасность и НЕ
	 * проходят валидацию
	 * @param array $attributes список обновляемых атрибутов (имя => значение)
	 * @param mixed $condition условие запроса или критерий
	 * @param array $params передаваемые в SQL-выражение параметры
	 * @return integer число обновленных строк
	 */
	public function updateAll($attributes,$condition='',$params=array())
	{
		Yii::trace(get_class($this).'.updateAll()','system.db.ar.CActiveRecord');
		$builder=$this->getCommandBuilder();
		$criteria=$builder->createCriteria($condition,$params);
		$command=$builder->createUpdateCommand($this->getTableSchema(),$attributes,$criteria);
		return $command->execute();
	}

	/**
	 * Обновляет один или несколько столбцов счетчиков по определенным
	 * условиям. За детальным описанием параметров $condition и $params
	 * обратитесь к методу {@link find()}
	 * @param array $counters обновляемые счетчики (имя столбца => значение инкремента)
	 * @param mixed $condition условие запроса или критерий
	 * @param array $params передаваемые в SQL-выражение параметры
	 * @return integer число обновленных строк
	 * @see saveCounters
	 */
	public function updateCounters($counters,$condition='',$params=array())
	{
		Yii::trace(get_class($this).'.updateCounters()','system.db.ar.CActiveRecord');
		$builder=$this->getCommandBuilder();
		$criteria=$builder->createCriteria($condition,$params);
		$command=$builder->createUpdateCounterCommand($this->getTableSchema(),$counters,$criteria);
		return $command->execute();
	}

	/**
	 * Удаляет строку(и) по определенному(ым) первичному(ым) ключу(ам). За
	 * детальным описанием параметров $condition и $params обратитесь к методу
	 * {@link find()}
	 * @param mixed $pk значение(я) первичного(ых) ключа(ей). Для задания
	 * нескольких первичных ключей используется массив. В случае составного
	 * ключа каждое значение ключа должно быть массивом (имя столбца =>
	 * значение столбца)
	 * @param mixed $condition условие запроса или критерий
	 * @param array $params передаваемые в SQL-выражение параметры
	 * @return integer число удаленных строк
	 */
	public function deleteByPk($pk,$condition='',$params=array())
	{
		Yii::trace(get_class($this).'.deleteByPk()','system.db.ar.CActiveRecord');
		$builder=$this->getCommandBuilder();
		$criteria=$builder->createPkCriteria($this->getTableSchema(),$pk,$condition,$params);
		$command=$builder->createDeleteCommand($this->getTableSchema(),$criteria);
		return $command->execute();
	}

	/**
	 * Удаляет строки по определенным условиям. За детальным описанием
	 * параметров $condition и $params обратитесь к методу {@link find()}
	 * @param mixed $condition условие запроса или критерий
	 * @param array $params передаваемые в SQL-выражение параметры
	 * @return integer число удаленных строк
	 */
	public function deleteAll($condition='',$params=array())
	{
		Yii::trace(get_class($this).'.deleteAll()','system.db.ar.CActiveRecord');
		$builder=$this->getCommandBuilder();
		$criteria=$builder->createCriteria($condition,$params);
		$command=$builder->createDeleteCommand($this->getTableSchema(),$criteria);
		return $command->execute();
	}

	/**
	 * Удаляет строки, соответствующие определенным значениям атрибутов. За
	 * детальным описанием параметров $condition и $params обратитесь к методу
	 * {@link find()}
	 * @param array $attributes список значений атрибутов (индексируемых по
	 * именам атрибутов), которым должны соответствовать записи.
	 * Значение атрибута может быть массивом, используемым для генерации
	 * условия IN
	 * @param mixed $condition условие запроса или критерий
	 * @param array $params передаваемые в SQL-выражение параметры
	 * @return integer число удаленных строк
	 */
	public function deleteAllByAttributes($attributes,$condition='',$params=array())
	{
		Yii::trace(get_class($this).'.deleteAllByAttributes()','system.db.ar.CActiveRecord');
		$builder=$this->getCommandBuilder();
		$table=$this->getTableSchema();
		$criteria=$builder->createColumnCriteria($table,$attributes,$condition,$params);
		$command=$builder->createDeleteCommand($table,$criteria);
		return $command->execute();
	}

	/**
	 * Создает AR-объект с переданными атрибутами. Данный метод используется
	 * методами поиска
	 * @param array $attributes значения атрибутов (имя столбца => значение
	 * столбца)
	 * @param boolean $callAfterFind вызывать ли метод {@link afterFind} после
	 * создания объекта
	 * @return CActiveRecord созданный AR-объект. Класс объекта такой же как и
	 * класс модели. Если входные данные имеют значение false, то возвращается
	 * значение null
	 */
	public function populateRecord($attributes,$callAfterFind=true)
	{
		if($attributes!==false)
		{
			$record=$this->instantiate($attributes);
			$record->setScenario('update');
			$record->init();
			$md=$record->getMetaData();
			foreach($attributes as $name=>$value)
			{
				if(property_exists($record,$name))
					$record->$name=$value;
				else if(isset($md->columns[$name]))
					$record->_attributes[$name]=$value;
			}
			$record->_pk=$record->getPrimaryKey();
			$record->attachBehaviors($record->behaviors());
			if($callAfterFind)
				$record->afterFind();
			return $record;
		}
		else
			return null;
	}

	/**
	 * Создает на основе входных данных список AR-объектов. Данный метод
	 * используется методами поиска
	 * @param array $data список значений атрибутов для AR-объектов
	 * @param boolean $callAfterFind вызывать ли метод {@link afterFind} после
	 * создания объекта
	 * @param string $index имя атрибута, значение которого будет
	 * использоваться в качестве индекса результирующего массива. Если задано
	 * значение null, то массив будет индексирован целыми числами, начиная с
	 * нуля
	 * @return array список AR-объектов
	 */
	public function populateRecords($data,$callAfterFind=true,$index=null)
	{
		$records=array();
		foreach($data as $attributes)
		{
			if(($record=$this->populateRecord($attributes,$callAfterFind))!==null)
			{
				if($index===null)
					$records[]=$record;
				else
					$records[$record->$index]=$record;
			}
		}
		return $records;
	}

	/**
	 * Создает экземпляр класса данной модели. Данный метод вызывается методами
	 * {@link populateRecord} и {@link populateRecords}. Можно переопределить
	 * данный метод, если создаваемый экземпляр зависит от записываемых
	 * атрибутов. Например, созданием записи на основе значения столбца можно
	 * реализовать паттерн наследования с единой таблицей
	 * @param array $attributes список атрибутов для AR-объекта
	 * @return CActiveRecord AR-объект
	 */
	protected function instantiate($attributes)
	{
		$class=get_class($this);
		$model=new $class(null);
		return $model;
	}

	/**
	 * Показывает, существует ли элемент в определенном смещении. Данный метод
	 * требуется интерфейсом ArrayAccess
	 * @param mixed $offset проверяемое смещение
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return $this->__isset($offset);
	}
}


/**
 * Класс CBaseActiveRelation - это базовый класс для всех активных связей.
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CActiveRecord.php 3533 2012-01-08 22:07:55Z mdomba $
 * @package system.db.ar
 */
class CBaseActiveRelation extends CComponent
{
	/**
	 * @var string имя связи
	 */
	public $name;
	/**
	 * @var string имя связанного active record-класса
	 */
	public $className;
	/**
	 * @var mixed внешний ключ данной связи
	 */
	public $foreignKey;
	/**
	 * @var mixed список имен выбираемых столбцов (массив или строка
	 * разделенных запятыми имен). Не заключайте в кавычки и не используйте
	 * префикс в именах столбцов, если они используются в выражении. В данном
	 * случае, для имен столбцов вы должны использовать префикс 'relationName.'
	 */
	public $select='*';
	/**
	 * @var string выражение WHERE. Для классов-потомков класса
	 * {@link CActiveRelation} имена столбцов, указанные в условии, должны
	 * устранять неоднозначность, используя префикс 'relationName.'
	 */
	public $condition='';
	/**
	 * @var array параметры, передаваемые в условие. Ключи массива- это метки
	 * параметров, а соответствующие значения массива - значения параметров
	 */
	public $params=array();
	/**
	 * @var string выражение GROUP BY. Для классов-потомков класса
	 * {@link CActiveRelation} имена столбцов, указанные в условии, должны
	 * устранять неоднозначность, используя префикс 'relationName.'
	 */
	public $group='';
	/**
	 * @var string как проводить соединение с другими таблицами. Относится к
	 * условию JOIN в SQL-выражении. Например, <code>'LEFT JOIN users ON users.id=authorID'</code>
	 * @since 1.1.3
	 */
	public $join='';
	/**
	 * @var string выражение HAVING. Для классов-потомков класса
	 * {@link CActiveRelation} имена столбцов, указанные в условии, должны
	 * устранять неоднозначность, используя префикс 'relationName.'
	 */
	public $having='';
	/**
	 * @var string выражение ORDER BY. Для классов-потомков класса
	 * {@link CActiveRelation} имена столбцов, указанные в условии, должны
	 * устранять неоднозначность, используя префикс 'relationName.'
	 */
	public $order='';

	/**
	 * Конструктор.
	 * @param string $name имя связи
	 * @param string $className имя связанного active record-класса
	 * @param string $foreignKey внешний ключ данной связи
	 * @param array $options дополнительные опции (имя => значение). Ключи
	 * должны быть именами свойств данного класса
	 */
	public function __construct($name,$className,$foreignKey,$options=array())
	{
		$this->name=$name;
		$this->className=$className;
		$this->foreignKey=$foreignKey;
		foreach($options as $name=>$value)
			$this->$name=$value;
	}

	/**
	 * Сливает данную связь с динамически определенным критерием
	 * @param array $criteria динамически определенный критерий
	 * @param boolean $fromScope происходит ли слияние критерия из группы
	 * условий (scopes)
	 */
	public function mergeWith($criteria,$fromScope=false)
	{
		if($criteria instanceof CDbCriteria)
			$criteria=$criteria->toArray();
		if(isset($criteria['select']) && $this->select!==$criteria['select'])
		{
			if($this->select==='*')
				$this->select=$criteria['select'];
			else if($criteria['select']!=='*')
			{
				$select1=is_string($this->select)?preg_split('/\s*,\s*/',trim($this->select),-1,PREG_SPLIT_NO_EMPTY):$this->select;
				$select2=is_string($criteria['select'])?preg_split('/\s*,\s*/',trim($criteria['select']),-1,PREG_SPLIT_NO_EMPTY):$criteria['select'];
				$this->select=array_merge($select1,array_diff($select2,$select1));
			}
		}

		if(isset($criteria['condition']) && $this->condition!==$criteria['condition'])
		{
			if($this->condition==='')
				$this->condition=$criteria['condition'];
			else if($criteria['condition']!=='')
				$this->condition="({$this->condition}) AND ({$criteria['condition']})";
		}

		if(isset($criteria['params']) && $this->params!==$criteria['params'])
			$this->params=array_merge($this->params,$criteria['params']);

		if(isset($criteria['order']) && $this->order!==$criteria['order'])
		{
			if($this->order==='')
				$this->order=$criteria['order'];
			else if($criteria['order']!=='')
				$this->order=$criteria['order'].', '.$this->order;
		}

		if(isset($criteria['group']) && $this->group!==$criteria['group'])
		{
			if($this->group==='')
				$this->group=$criteria['group'];
			else if($criteria['group']!=='')
				$this->group.=', '.$criteria['group'];
		}

		if(isset($criteria['join']) && $this->join!==$criteria['join'])
		{
			if($this->join==='')
				$this->join=$criteria['join'];
			else if($criteria['join']!=='')
				$this->join.=' '.$criteria['join'];
		}

		if(isset($criteria['having']) && $this->having!==$criteria['having'])
		{
			if($this->having==='')
				$this->having=$criteria['having'];
			else if($criteria['having']!=='')
				$this->having="({$this->having}) AND ({$criteria['having']})";
		}
	}
}


/**
 * Класс CStatRelation представляет статистический реляционный запрос.
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CActiveRecord.php 3533 2012-01-08 22:07:55Z mdomba $
 * @package system.db.ar
 */
class CStatRelation extends CBaseActiveRelation
{
	/**
	 * @var string статистическое выражение. По умолчанию - 'COUNT(*)', т.е.,
	 * число дочерних объектов
	 */
	public $select='COUNT(*)';
	/**
	 * @var mixed значение по умолчанию, присваиваемое записям, которые не
	 * получают результат статистического запроса. По умолчанию - 0
	 */
	public $defaultValue=0;

	/**
	 * Сливает данную связь с динамически определенным критерием
	 * @param array $criteria динамически определенный критерий
	 * @param boolean $fromScope происходит ли слияние критерия из группы
	 * условий (scopes)
	 */
	public function mergeWith($criteria,$fromScope=false)
	{
		if($criteria instanceof CDbCriteria)
			$criteria=$criteria->toArray();
		parent::mergeWith($criteria,$fromScope);

		if(isset($criteria['defaultValue']))
			$this->defaultValue=$criteria['defaultValue'];
	}
}


/**
 * Класс CActiveRelation - это базовый класс, представляющий активные связи,
 * возвращающие связанные объекты
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CActiveRecord.php 3533 2012-01-08 22:07:55Z mdomba $
 * @package system.db.ar
 * @since 1.0
 */
class CActiveRelation extends CBaseActiveRelation
{
	/**
	 * @var string тип соединения. По умолчанию - 'LEFT OUTER JOIN'
	 */
	public $joinType='LEFT OUTER JOIN';
	/**
	 * @var string условие ON, добавляется в условие соединения с помощью
	 * оператора AND
	 */
	public $on='';
	/**
	 * @var string псевоним таблицы, к которой относится данная связь. По
	 * умолчанию - null, т.е., псевдоним будет таким же, как имя связи
	 */
	public $alias;
	/**
	 * @var string|array определяет, какие связанные объекты должны быть
	 * загружены с помощью "жадной" загрузки, когда подгружается данный
	 * связанный объект. За деталями о свойстве обратитесь к описанию метода
	 * {@link CActiveRecord::with()}
	 */
	public $with=array();
	/**
	 * @var boolean должна ли данная таблица быть соединена с первичной
	 * таблицей. При установке данного свойства в значение false таблица,
	 * ассоциированная с данной связью будет в отдельном выражении JOIN. Если
	 * установлено в значение true, то соответствующая таблица будет ВСЕГДА
	 * соединяться вместе с первичной таблицей, и не имеет значения, есть ли
	 * ограничение для первичной таблицы или нет. Если данное свойство не
	 * установлено, соответствующая таблица будет соединяться с первичной
	 * таблицей только когда нет ограничения для первичной таблицы
	 */
	public $together;
	/**
	 * @var mixed используемые группы условий. Может быть установлено в виде
	 * строки или массива:
	 * <ul>
	 * <li>Одна группа: 'scopes'=>'scopeName'.</li>
	 * <li>Несколько групп: 'scopes'=>array('scopeName1','scopeName2').</li>
	 * </ul>
	 * @since 1.1.9
	 */
	 public $scopes;

	/**
	 * Сливает данную связь с динамически определенным критерием
	 * @param array $criteria динамически определенный критерий
	 * @param boolean $fromScope происходит ли слияние критерия из группы
	 * условий (scopes)
	 */
	public function mergeWith($criteria,$fromScope=false)
	{
		if($criteria instanceof CDbCriteria)
			$criteria=$criteria->toArray();
		if($fromScope)
		{
			if(isset($criteria['condition']) && $this->on!==$criteria['condition'])
			{
				if($this->on==='')
					$this->on=$criteria['condition'];
				else if($criteria['condition']!=='')
					$this->on="({$this->on}) AND ({$criteria['condition']})";
			}
			unset($criteria['condition']);
		}

		parent::mergeWith($criteria);

		if(isset($criteria['joinType']))
			$this->joinType=$criteria['joinType'];

		if(isset($criteria['on']) && $this->on!==$criteria['on'])
		{
			if($this->on==='')
				$this->on=$criteria['on'];
			else if($criteria['on']!=='')
				$this->on="({$this->on}) AND ({$criteria['on']})";
		}

		if(isset($criteria['with']))
			$this->with=$criteria['with'];

		if(isset($criteria['alias']))
			$this->alias=$criteria['alias'];

		if(isset($criteria['together']))
			$this->together=$criteria['together'];
	}
}


/**
 * Класс CBelongsToRelation представляет параметры, определяющие связь BELONGS_TO.
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CActiveRecord.php 3533 2012-01-08 22:07:55Z mdomba $
 * @package system.db.ar
 * @since 1.0
 */
class CBelongsToRelation extends CActiveRelation
{
}


/**
 * Класс CHasOneRelation представляет параметры, определяющие связь HAS_ONE.
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CActiveRecord.php 3533 2012-01-08 22:07:55Z mdomba $
 * @package system.db.ar
 * @since 1.0
 */
class CHasOneRelation extends CActiveRelation
{
	/**
	 * @var string имя связи, используемой в качестве моста к данной связи. По
	 * умолчанию - null, т.е., мост не используется
	 * @since 1.1.7
	 */
	public $through;
}


/**
 * Класс CHasManyRelation представляет параметры, определяющие связь HAS_MANY.
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CActiveRecord.php 3533 2012-01-08 22:07:55Z mdomba $
 * @package system.db.ar
 * @since 1.0
 */
class CHasManyRelation extends CActiveRelation
{
	/**
	 * @var integer ограничение выбираемых строк. Имеет значени только при
	 * "ленивой" загрузке данного связанного объекта. По умолчанию равно -1,
	 * т.е., ограничения нет
	 */
	public $limit=-1;
	/**
	 * @var integer смещение выбираемых строк. Имеет значени только при
	 * "ленивой" загрузке данного связанного объекта. По умолчанию равно -1,
	 * т.е., смещение не учитывается
	 */
	public $offset=-1;
	/**
	 * @var string имя столбца, который должен использоваться в качестве ключа
	 * для хранения связанных объектов. По умолчанию - null, т.е., используется
	 * целочисленные идентификаторы, отсчитываемые с нуля
	 */
	public $index;
	/**
	 * @var string имя связи, используемой в качестве моста к данной связи. По
	 * умолчанию - null, т.е., мост не используется
	 * @since 1.1.7
	 */
	public $through;

	/**
	 * Сливает данную связь с динамически определенным критерием
	 * @param array $criteria динамически определенный критерий
	 * @param boolean $fromScope происходит ли слияние критерия из группы
	 * условий (scopes)
	 */
	public function mergeWith($criteria,$fromScope=false)
	{
		if($criteria instanceof CDbCriteria)
			$criteria=$criteria->toArray();
		parent::mergeWith($criteria,$fromScope);
		if(isset($criteria['limit']) && $criteria['limit']>0)
			$this->limit=$criteria['limit'];

		if(isset($criteria['offset']) && $criteria['offset']>=0)
			$this->offset=$criteria['offset'];

		if(isset($criteria['index']))
			$this->index=$criteria['index'];
	}
}


/**
 * Класс CManyManyRelation представляет параметры, определяющие связь MANY_MANY.
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CActiveRecord.php 3533 2012-01-08 22:07:55Z mdomba $
 * @package system.db.ar
 * @since 1.0
 */
class CManyManyRelation extends CHasManyRelation
{
}


/**
 * Класс CActiveRecordMetaData представляет метаданные для Active Record-класса.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CActiveRecord.php 3533 2012-01-08 22:07:55Z mdomba $
 * @package system.db.ar
 * @since 1.0
 */
class CActiveRecordMetaData
{
	/**
	 * @var CDbTableSchema информация о схеме таблицы
	 */
	public $tableSchema;
	/**
	 * @var array столбцы таблицы
	 */
	public $columns;
	/**
	 * @var array список связей
	 */
	public $relations=array();
	/**
	 * @var array значения атрибутов по умолчанию
	 */
	public $attributeDefaults=array();

	private $_model;

	/**
	 * Конструктор
	 * @param CActiveRecord $model экземпляр модели
	 */
	public function __construct($model)
	{
		$this->_model=$model;

		$tableName=$model->tableName();
		if(($table=$model->getDbConnection()->getSchema()->getTable($tableName))===null)
			throw new CDbException(Yii::t('yii','The table "{table}" for active record class "{class}" cannot be found in the database.',
				array('{class}'=>get_class($model),'{table}'=>$tableName)));
		if($table->primaryKey===null)
		{
			$table->primaryKey=$model->primaryKey();
			if(is_string($table->primaryKey) && isset($table->columns[$table->primaryKey]))
				$table->columns[$table->primaryKey]->isPrimaryKey=true;
			else if(is_array($table->primaryKey))
			{
				foreach($table->primaryKey as $name)
				{
					if(isset($table->columns[$name]))
						$table->columns[$name]->isPrimaryKey=true;
				}
			}
		}
		$this->tableSchema=$table;
		$this->columns=$table->columns;

		foreach($table->columns as $name=>$column)
		{
			if(!$column->isPrimaryKey && $column->defaultValue!==null)
				$this->attributeDefaults[$name]=$column->defaultValue;
		}

		foreach($model->relations() as $name=>$config)
		{
			$this->addRelation($name,$config);
		}
	}

	/**
	 * Добавляет связь. Параметр $config - это массив с тремя элементами: тип
	 * связи, имя active record-класса связи и внешний ключ
	 *
	 * @throws CDbException
	 * @param string $name имя связи
	 * @param array $config параметры связи
     * @return void
	 * @since 1.1.2
	 */
	public function addRelation($name,$config)
	{
		if(isset($config[0],$config[1],$config[2]))  // relation class, AR class, FK
			$this->relations[$name]=new $config[0]($name,$config[1],$config[2],array_slice($config,3));
		else
			throw new CDbException(Yii::t('yii','Active record "{class}" has an invalid configuration for relation "{relation}". It must specify the relation type, the related active record class and the foreign key.', array('{class}'=>get_class($this->_model),'{relation}'=>$name)));
	}

	/**
	 * Проверяет, существует ли связь с определенным именем
	 *
	 * @param string $name имя связи
	 * @return boolean существует ли связь с определенным именем
	 * @since 1.1.2
	 */
	public function hasRelation($name)
	{
		return isset($this->relations[$name]);
	}

	/**
	 * Удаляет связь по определенному имени
	 *
	 * @param string $name имя удаляемой связи
	 * @return void
	 * @since 1.1.2
	 */
	public function removeRelation($name)
	{
		unset($this->relations[$name]);
	}
}
