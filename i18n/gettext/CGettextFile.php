<?php
/**
 * Файл класса CGettextFile.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * CGettextFile - это базовый класс, представляющий файлы сообщений Gettext.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CGettextFile.php 2798 2011-01-01 19:29:03Z qiang.xue $
 * @package system.i18n.gettext
 * @since 1.0
 */
abstract class CGettextFile extends CComponent
{
	/**
	 * Загружает сообщение из файла.
	 * @param string $file путь к файлу
	 * @param string $context контекст сообщения
	 * @return array перевод сообщения (исходное сообщение => переведенное сообщение)
	 */
	abstract public function load($file,$context);
	/**
	 * Сохраняет сообщения в файл.
	 * @param string $file путь к файлу
	 * @param array $messages перевод сообщений (идентификатор сообщения => переведенное сообщение).
	 * Примечание: если сообщение имеет контекст, то идентификатор сообщения должен быть с префиксом
	 * в виде контекста и символом-разделителем - chr(4)
	 */
	abstract public function save($file,$messages);
}
