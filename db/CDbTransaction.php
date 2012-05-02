<?php
/**
 * Файл класса CDbTransaction.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CDbTransaction преставляет транзакцию базы данных.
 *
 * Обычно создается вызовом метода {@link CDbConnection::beginTransaction}.
 *
 * Следующий код показывает типичный сценарий работы с транзакциями:
 * <pre>
 * $transaction=$connection->beginTransaction();
 * try
 * {
 *    $connection->createCommand($sql1)->execute();
 *    $connection->createCommand($sql2)->execute();
 *    //.... выполнение других SQL-запросов
 *    $transaction->commit();
 * }
 * catch(Exception $e)
 * {
 *    $transaction->rollBack();
 * }
 * </pre>
 *
 * @property CDbConnection $connection соединение БД для данной транзакции
 * @property boolean $active активна ли данная транзакция
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CDbTransaction.php 3426 2011-10-25 00:01:09Z alexander.makarow $
 * @package system.db
 * @since 1.0
 */
class CDbTransaction extends CComponent
{
	private $_connection=null;
	private $_active;

	/**
	 * Конструктор
	 * @param CDbConnection $connection соединение БД, ассоциированное с данной транзакцией
	 * @see CDbConnection::beginTransaction
	 */
	public function __construct(CDbConnection $connection)
	{
		$this->_connection=$connection;
		$this->_active=true;
	}

	/**
	 * Подтверждает транзакцию
	 * @throws CException вызывается, если транзакция или соединение БД не активны
	 */
	public function commit()
	{
		if($this->_active && $this->_connection->getActive())
		{
			Yii::trace('Committing transaction','system.db.CDbTransaction');
			$this->_connection->getPdoInstance()->commit();
			$this->_active=false;
		}
		else
			throw new CDbException(Yii::t('yii','CDbTransaction is inactive and cannot perform commit or roll back operations.'));
	}

	/**
	 * Откатывает транзакцию (отменяет)
	 * @throws CException вызывается, если транзакция или соединение БД не активны
	 */
	public function rollback()
	{
		if($this->_active && $this->_connection->getActive())
		{
			Yii::trace('Rolling back transaction','system.db.CDbTransaction');
			$this->_connection->getPdoInstance()->rollBack();
			$this->_active=false;
		}
		else
			throw new CDbException(Yii::t('yii','CDbTransaction is inactive and cannot perform commit or roll back operations.'));
	}

	/**
	 * @return CDbConnection соединение БД для данной транзакции
	 */
	public function getConnection()
	{
		return $this->_connection;
	}

	/**
	 * @return boolean активна ли данная транзакция
	 */
	public function getActive()
	{
		return $this->_active;
	}

	/**
	 * @param boolean $value активна ли данная транзакция
	 */
	protected function setActive($value)
	{
		$this->_active=$value;
	}
}
