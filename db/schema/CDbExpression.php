<?php
/**
 * Файл класса CDbExpression.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CDbExpression представляет выражение базы данных, не требующее экранирования.
 * CDbExpression в основном используется в объектах класса {@link CActiveRecord} как
 * значения атрибутов. При вставке или обновлении объекта класса {@link CActiveRecord},
 * значение атрибутов с типом CDbExpression будут непосредственно вставлены в соответствующее
 * SQL-выражение без экранирования символов. Типичное использование - установка атрибута в
 * выражение 'NOW()', при этом при сохранении записи соответствующий столбец будет заполнен 
 * текущим временем сервера базы данных.
 *
 * Начиная с версии 1.1.1 можно определять параметры, свзанные с выражением. Например,
 * если выражение имеет вид 'LOWER(:value)', то можно установить свойство
 * {@link params} в значение <code>array(':value'=>$value)</code>.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CDbExpression.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.db.schema
 */
class CDbExpression extends CComponent
{
	/**
	 * @var string выражение базы данных
	 */
	public $expression;
	/**
	 * @var array список параметров, которые должны быть привязаны к данному выражению.
	 * Ключи массива - метки, прасположенные в выражении, а значения - соответствующие значения
	 * параметров
	 * @since 1.1.1
	 */
	public $params=array();

	/**
	 * Конструктор
	 * @param string $expression выражение базы данных
	 * @param array $params параметры
	 */
	public function __construct($expression,$params=array())
	{
		$this->expression=$expression;
		$this->params=$params;
	}

	/**
	 * Строковый "магический" метод
	 * @return string выражение базы данных
	 */
	public function __toString()
	{
		return $this->expression;
	}
}