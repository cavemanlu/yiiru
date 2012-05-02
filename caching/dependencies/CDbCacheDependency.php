<?php
/**
 * Файл класса CDbCacheDependency.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Компонент CDbCacheDependency представляет собой зависимость, основанную на результате SQL запроса.
 *
 * Если результат запроса (скалярный) изменился, зависимость рассматривается как изменненная.
 * Для определения SQL выражения установите свойство {@link sql}.
 * Свойство {@link connectionID} определяет идентификатор компонента приложения {@link CDbConnection}.
 * Это соединение БД, используемое для выполнения запроса.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CDbCacheDependency.php 3204 2011-05-05 21:36:32Z alexander.makarow $
 * @package system.caching.dependencies
 * @since 1.0
 */
class CDbCacheDependency extends CCacheDependency
{
	/**
	 * @var string идентификатор компонента приложения {@link CDbConnection}. По умолчанию - 'db'.
	 */
	public $connectionID='db';
	/**
	 * @var string SQL выражение, результат которого используется для
	 * проверки изменения зависимости.
	 * Примечание: SQL запрос должен возвращать единственное значение.
	 */
	public $sql;
	/**
	 * @var array массив параметров (имя=>значение), связываемых с SQL выражением, определенным параметром {@link sql}.
	 * @since 1.1.4
	 */
	public $params;

	private $_db;

	/**
	 * Конструктор.
	 * @param string $sql SQL выражение, результат которого используется для проверки изменения зависимости.
	 */
	public function __construct($sql=null)
	{
		$this->sql=$sql;
	}

	/**
	 * Магический метод PHP.
	 * Метод гарантирует, что экземпляр базы данных установлен в null, потому что он содержит обработчик ресурса
	 * @return array
	 */
	public function __sleep()
	{
		$this->_db=null;
		return array_keys((array)$this);
	}

	/**
	 * Генерирует данные, необходимые для определения изменения зависимости.
	 * Метод возвращает результат запроса
	 * @return mixed данные, необходимые для определения изменения зависимости
	 */
	protected function generateDependentData()
	{
		if($this->sql!==null)
		{
			$db=$this->getDbConnection();
			$command=$db->createCommand($this->sql);
			if(is_array($this->params))
			{
				foreach($this->params as $name=>$value)
					$command->bindValue($name,$value);
			}
			if($db->queryCachingDuration>0)
			{
				// temporarily disable and re-enable query caching
				$duration=$db->queryCachingDuration;
				$db->queryCachingDuration=0;
				$result=$command->queryRow();
				$db->queryCachingDuration=$duration;
			}
			else
				$result=$command->queryRow();
			return $result;
		}
		else
			throw new CException(Yii::t('yii','CDbCacheDependency.sql cannot be empty.'));
	}

	/**
	 * @return CDbConnection экземпляр соединения БД
	 * @throws CException вызывается, если {@link connectionID} не указывает на действительный компонент приложения
	 */
	protected function getDbConnection()
	{
		if($this->_db!==null)
			return $this->_db;
		else
		{
			if(($this->_db=Yii::app()->getComponent($this->connectionID)) instanceof CDbConnection)
				return $this->_db;
			else
				throw new CException(Yii::t('yii','CDbCacheDependency.connectionID "{id}" is invalid. Please make sure it refers to the ID of a CDbConnection application component.',
					array('{id}'=>$this->connectionID)));
		}
	}
}
