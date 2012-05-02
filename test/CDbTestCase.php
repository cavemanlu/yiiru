<?php
/**
 * Файл класса CDbTestCase.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('system.test.CTestCase');

/**
 * Класс CDbTestCase - это базовый класс для тестовых данных с функциями, относящимися к базам данных.
 *
 * CDbTestCase обеспечивает управление фикстурами БД при помощи компонента {@link CDbFixtureManager}.
 * Объявляя свойство {@link fixtures}, можно быть уверенным, что указанные таблицы будут
 * иметь ожидаемое состояние фикстуры при выполнении каждого тестового метода.
 * Кроме того, CDbTestCase предоставляет два способа для доступа к данным фикстур.
 *
 * Например, предположим, что мы объявляем {@link fixtures} так:
 * <pre>
 * public $fixtures=array(
 *     'posts' => 'Post',
 *     'comments' => 'Comment',
 * );
 * </pre>
 *
 * Мы можем иметь доступ к строкам данных фикстуры, используя <code>$this->posts</code>: 
 * <code>$this->posts['first post']</code>. Мы также можум получить экземпляр ActiveRecord,
 * соответствующий строке данным фикстуры, используя <code>$this->posts('first post')</code>.
 * Примечание: здесь 'first post' - ключ к строке в исходных данных фикстуры.
 *
 * @property CDbFixtureManager $fixtureManager менеджер фикстур базы данных
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CDbTestCase.php 3426 2011-10-25 00:01:09Z alexander.makarow $
 * @package system.test
 * @since 1.1
 */
abstract class CDbTestCase extends CTestCase
{
	/**
	 * @var array список фикстур, которые должны быть загружены перед выполнением каждого тестового метода.
	 * Ключи массива - это имена фикстур, а значения - имя либо AR-класса либо
	 * таблицы. Если это имя таблицы, то оно должно начинаться с двоеточия (например, 'Post'
	 * означает, что это AR-класс, а ':post' - имя таблицы).
	 * По умолчанию - false - фикстуры не будут использоваться.
	 */
	protected $fixtures=false;

	/**
	 * Магический метод PHP.
	 * Метод переопределяется так, чтобы именованные данные фикстуры могли быть доступны как обычное свойство.
	 * @param string $name имя свойства
	 * @return mixed значение свойства
	 */
	public function __get($name)
	{
		if(is_array($this->fixtures) && ($rows=$this->getFixtureManager()->getRows($name))!==false)
			return $rows;
		else
			throw new Exception("Unknown property '$name' for class '".get_class($this)."'.");
	}

	/**
	 * Магический метод PHP.
	 * Метод переопределяется так, чтобы именованные ActiveRecord-экземпляры фикстуры
	 * могли быть доступны в терминах вызова метода.
	 * @param string $name имя метода
	 * @param string $params параметры метода
	 * @return mixed значение свойства
	 */
	public function __call($name,$params)
	{
		if(is_array($this->fixtures) && isset($params[0]) && ($record=$this->getFixtureManager()->getRecord($name,$params[0]))!==false)
			return $record;
		else
			throw new Exception("Unknown method '$name' for class '".get_class($this)."'.");
	}

	/**
	 * @return CDbFixtureManager менеджер фикстур базы данных
	 */
	public function getFixtureManager()
	{
		return Yii::app()->getComponent('fixture');
	}

	/**
	 * @param string $name имя фикстуры (значение ключа в списке фикстур {@link fixtures})
	 * @return array данные именованной фикстуры
	 */
	public function getFixtureData($name)
	{
		return $this->getFixtureManager()->getRows($name);
	}

	/**
	 * @param string $name имя фикстуры (значение ключа в списке фикстур {@link fixtures})
	 * @param string $alias псевдоним строки данных фикстуры
	 * @return CActiveRecord экземпляр ActiveRecord, соответствующий определенному псевдониму в именованной фикстуре.
	 * Возвращается значение false, если такой фикстуры или записи не обнаружено
	 */
	public function getFixtureRecord($name,$alias)
	{
		return $this->getFixtureManager()->getRecord($name,$alias);
	}

	/**
	 * Устанавливает фикстуру перед выполнением тестового метода.
	 * Если вы переопределяете данный метод, будьте уверены, что вызывается реализация предка.
	 * В противном случае, фикстуры базы данных не будут управляться должным образом.
	 */
	protected function setUp()
	{
		parent::setUp();
		if(is_array($this->fixtures))
			$this->getFixtureManager()->load($this->fixtures);
	}
}