<?php
/**
 * Файл класса CModelBehavior.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CModelBehavior - это базовый класс для поведений, присоединяемых к
 * моделям. Модель должна наследовать класс {@link CModel} или его
 * классы-потомки
 *
 * @property CModel $owner модель, к которой присоединено данное поведение
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.base
 */
class CModelBehavior extends CBehavior
{
	/**
	 * Объявляет события и их обработчики.
	 * Реализация по умолчанию возвращает события 'onAfterConstruct',
	 * 'onBeforeValidate' и 'onAfterValidate' и имена методов их обработки.
	 * Если вы переопределяете данный метод, убедитесь в том, что результат
	 * родителя будет совмещен с возвращаемым значением
	 * @return array события (ключи массива) и соответствующие
	 * методы-обработчики событий (значения массива)
	 * @see CBehavior::events
	 */
	protected function events()
	{
		return array(
			'onAfterConstruct'=>'afterConstruct',
			'onBeforeValidate'=>'beforeValidate',
			'onAfterValidate'=>'afterValidate',
		);
	}

	/**
	 * Реагирует на событие {@link CModel::onAfterConstruct}.
	 * Для того, чтобы обрабатывать соответствующее событие в
	 * {@link CBehavior::owner собственнике (owner)}, необходимо переопределить
	 * данный метод и сделать его публичным 
	 * @param CModelEvent $event параметр события
	 */
	protected function afterConstruct($event)
	{
	}

	/**
	 * Реагирует на событие {@link CModel::onBeforeValidate}.
	 * Для того, чтобы обрабатывать соответствующее событие в
	 * {@link CBehavior::owner собственнике (owner)}, необходимо переопределить
	 * данный метод и сделать его публичным. Можно установить свойство
	 * {@link CModelEvent::isValid} в значение false для прекращения выполнения
	 * процесса валидации.
	 * @param CModelEvent $event параметр события
	 */
	protected function beforeValidate($event)
	{
	}

	/**
	 * Реагирует на событие {@link CModel::onAfterValidate} event.
	 * Для того, чтобы обрабатывать соответствующее событие в
	 * {@link CBehavior::owner собственнике (owner)}, необходимо переопределить
	 * данный метод и сделать его публичным 
	 * @param CEvent $event параметр события
	 */
	protected function afterValidate($event)
	{
	}
}
