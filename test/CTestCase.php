<?php
/**
 * Файл класса CTestCase.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
 
require_once('PHPUnit/Runner/Version.php');
require_once('PHPUnit/Autoload.php');

/**
 * Класс CTestCase - это базовый класс для всех классов тестовых данных.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CTestCase.php 2997 2011-02-23 13:51:40Z alexander.makarow $
 * @package system.test
 * @since 1.1
 */
abstract class CTestCase extends PHPUnit_Framework_TestCase
{
}
