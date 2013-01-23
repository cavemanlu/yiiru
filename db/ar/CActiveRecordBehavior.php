<?php
/**
 * Файл класса CActiveRecordBehavior.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CActiveRecordBehavior - это базовый класс поведений, присоединяемых к
 * объектам класса {@link CActiveRecord}. В сравнении с классом
 * {@link CModelBehavior}, CActiveRecordBehavior присоединяет больше событий к
 * объекту класса {@link CActiveRecord}.
 *
 * @property CActiveRecord $owner AR-экземпляр, к которому присоединено данное
 * поведение
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.db.ar
 */
class CActiveRecordBehavior extends CModelBehavior
{
	/**
	 * Определяет события и соответствующие методы-обработчики событий. При
	 * переопределении данного метода убедитесь, что результат выполнения
	 * родительского метода сливается с необходимым значением
	 * @return array события (кючи массива) и соответсвующие методы-обработчики
	 * событий (значения массива)
	 * @see CBehavior::events
	 */
	public function events()
	{
		return array_merge(parent::events(), array(
			'onBeforeSave'=>'beforeSave',
			'onAfterSave'=>'afterSave',
			'onBeforeDelete'=>'beforeDelete',
			'onAfterDelete'=>'afterDelete',
			'onBeforeFind'=>'beforeFind',
			'onAfterFind'=>'afterFind',
		));
	}

	/**
	 * Реагирует на событие {@link CActiveRecord::onBeforeSave}. Для обработки
	 * соответствующего события {@link CBehavior::owner владельца} необходимо
	 * переопределить данный метод и сделать его публичным. Можно установить
	 * параметр {@link CModelEvent::isValid} в значение false для выхода из
	 * процесса сохранения
	 * @param CModelEvent $event параметр события
	 */
	protected function beforeSave($event)
	{
	}

	/**
	 * Реагирует на событие {@link CActiveRecord::onAfterSave}. Для обработки
	 * соответствующего события {@link CBehavior::owner владельца} необходимо
	 * переопределить данный метод и сделать его публичным.
	 * @param CModelEvent $event параметр события
	 */
	protected function afterSave($event)
	{
	}

	/**
	 * Реагирует на событие {@link CActiveRecord::onBeforeDelete}. Для обработки
	 * соответствующего события {@link CBehavior::owner владельца} необходимо
	 * переопределить данный метод и сделать его публичным. Можно установить
	 * параметр {@link CModelEvent::isValid} в значение false для выхода из
	 * процесса удаления
	 * @param CModelEvent $event параметр события
	 */
	protected function beforeDelete($event)
	{
	}

	/**
	 * Реагирует на событие {@link CActiveRecord::onAfterDelete}. Для обработки
	 * соответствующего события {@link CBehavior::owner владельца} необходимо
	 * переопределить данный метод и сделать его публичным.
	 * @param CModelEvent $event параметр события
	 */
	protected function afterDelete($event)
	{
	}

	/**
	 * Реагирует на событие {@link CActiveRecord::onBeforeFind}. Для обработки
	 * соответствующего события {@link CBehavior::owner владельца} необходимо
	 * переопределить данный метод и сделать его публичным.
	 * @param CModelEvent $event параметр события
	 */
	protected function beforeFind($event)
	{
	}

	/**
	 * Реагирует на событие {@link CActiveRecord::onAfterFind}. Для обработки
	 * соответствующего события {@link CBehavior::owner владельца} необходимо
	 * переопределить данный метод и сделать его публичным.
	 * @param CModelEvent $event параметр события
	 */
	protected function afterFind($event)
	{
	}
}
