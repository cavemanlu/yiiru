<?php
/**
 * Файл класса CFileCacheDependency.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Компонент CFileCacheDependency представляет собой зависимость, основанную на времени последней модификации файла.
 *
 * Компонент CFileCacheDependency выполняет проверку зависимости, основываясь на времени последней модификации файла,
 * определенного свойством {@link fileName}.
 * Зависимость является неизменной только в случае, если время последней модификации файла остается неизменным.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CFileCacheDependency.php 2799 2011-01-01 19:31:13Z qiang.xue $
 * @package system.caching.dependencies
 * @since 1.0
 */
class CFileCacheDependency extends CCacheDependency
{
	/**
	 * @var string имя файла, время последней модификации которого используется для
	 * проверки изменения зависимости.
	 */
	public $fileName;

	/**
	 * Конструктор.
	 * @param string $fileName имя файла, время последней модификации которого используется для проверки изменения зависимости.
	 */
	public function __construct($fileName=null)
	{
		$this->fileName=$fileName;
	}

	/**
	 * Генерирует данные, необходимые для определения изменения зависимости.
	 * Метод возвращает время последней модификации файла.
	 * @return mixed данные, необходимые для определения изменения зависимости
	 */
	protected function generateDependentData()
	{
		if($this->fileName!==null)
			return @filemtime($this->fileName);
		else
			throw new CException(Yii::t('yii','CFileCacheDependency.fileName cannot be empty.'));
	}
}
