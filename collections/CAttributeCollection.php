<?php
/**
 * Файл содержит класс, реализующий функции коллекции атрибутов.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */


/**
 * Класс CAttributeCollection реализует коллекцию для хранения имен и значений атрибутов.
 *
 * Помимо функциональности, предоставляемой классом {@link CMap}, класс CAttributeCollection
 * позволяет получать и устанавливать значения атрибутов как получение и установку свойств.
 * Например, следующий код правомерен для объекта класса CAttributeCollection:
 * <pre>
 * $collection->text='text'; // то же самое, что:  $collection->add('text','text');
 * echo $collection->text;   // то же самое, что:  echo $collection->itemAt('text');
 * </pre>
 *
 * Чувствительность к регистру имен атрибутов может настраиваться переключением свойства
 * {@link caseSensitive} коллекции.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CAttributeCollection.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.collections
 * @since 1.0
 */
class CAttributeCollection extends CMap
{
	/**
	 * @var boolean регистрозависимость ключей. По умолчанию false.
	 */
	public $caseSensitive=false;

	/**
	 * Возвращает значение свойства или список обработчиков события по имени свойства или события.
	 * Переопределяет родительский метод, возвращая значение ключа, если таковой существует в коллекции.
	 * @param string $name имя свойства или события
	 * @return mixed значение свойства или список обработчиков события
	 * @throws CException вызывается, если свойство или событие не определены.
	 */
	public function __get($name)
	{
		if($this->contains($name))
			return $this->itemAt($name);
		else
			return parent::__get($name);
	}

	/**
	 * Устанавливает значение свойства компонента.
	 * Переопределяет родительский метод, добавляя новую пару ключ-значение в коллекцию.
	 * @param string $name имя свойства или события
	 * @param mixed $value значение свойства или обработчика события
	 * @throws CException вызывается, если свойство не определено или является свойством только дя чтения.
	 */
	public function __set($name,$value)
	{
		$this->add($name,$value);
	}

	/**
	 * Проверяет, нулевое ли значение свойства.
	 * Переопределяет родительский метод, проверяя, существует ли ключ в коллекции и его значение ненулевое
	 * @param string $name имя свойства или события
	 * @return boolean нулевое ли значение свойства
	 */
	public function __isset($name)
	{
		if($this->contains($name))
			return $this->itemAt($name)!==null;
		else
			return parent::__isset($name);
	}

	/**
	 * Удаляет свойство компонента.
	 * Переопределяет родительский метод, очищая значение определенного ключа.
	 * @param string $name имя свойства или события
	 */
	public function __unset($name)
	{
		$this->remove($name);
	}

	/**
	 * Возвращает элемент по определенному ключу.
	 * Переопределяет родительский метод, сначала преобразовывая ключ в нижнему регистру,
	 * если свойство {@link caseSensitive} равно false.
	 * @param mixed $key ключ
	 * @return mixed элемент; null, если элемент не найден
	 */
	public function itemAt($key)
	{
		if($this->caseSensitive)
			return parent::itemAt($key);
		else
			return parent::itemAt(strtolower($key));
	}

	/**
	 * Добавляет элемент в коллекцию.
	 * Переопределяет родительский метод, сначала преобразовывая ключ в нижнему регистру,
	 * если свойство {@link caseSensitive} равно false.
	 * @param mixed $key ключ
	 * @param mixed $value значение
	 */
	public function add($key,$value)
	{
		if($this->caseSensitive)
			parent::add($key,$value);
		else
			parent::add(strtolower($key),$value);
	}

	/**
	 * Удаляет элемент из коллекции по его ключу.
	 * Переопределяет родительский метод, сначала преобразовывая ключ в нижнему регистру,
	 * если свойство {@link caseSensitive} равно false.
	 * @param mixed $key ключ удаляемого элемента
	 * @return mixed удаленное значение; null, если такого ключа не существует.
	 */
	public function remove($key)
	{
		if($this->caseSensitive)
			return parent::remove($key);
		else
			return parent::remove(strtolower($key));
	}

	/**
	 * Содержит ли коллекция элемент с определенным ключом.
	 * Переопределяет родительский метод, сначала преобразовывая ключ в нижнему регистру,
	 * если свойство {@link caseSensitive} равно false.
	 * @param mixed $key ключ
	 * @return boolean содержит ли коллекция элемент с определенным ключом
	 */
	public function contains($key)
	{
		if($this->caseSensitive)
			return parent::contains($key);
		else
			return parent::contains(strtolower($key));
	}

	/**
	 * Определено ли свойство.
	 * Переопределяет родительский метод, возвращая значение true, если коллекция содержит именованый ключ.
	 * @param string $nameимя свойства
	 * @return boolean определено ли свойство
	 */
	public function hasProperty($name)
	{
		return $this->contains($name) || parent::hasProperty($name);
	}

	/**
	 * Может ли свойство быть прочитано.
	 * Переопределяет родительский метод, возвращая значение true, если коллекция содержит именованый ключ
	 * @param string $name имя свойства
	 * @return boolean может ли свойство быть прочитано
	 */
	public function canGetProperty($name)
	{
		return $this->contains($name) || parent::canGetProperty($name);
	}

	/**
	 * Может ли свойство быть записано.
	 * Переопределяет родительский метод, возвращая значение true,
	 * поскольку вы всегда можете добавить новое значение в коллекцию.
	 * @param string $name имя свойства
	 * @return boolean true
	 */
	public function canSetProperty($name)
	{
		return true;
	}
}
