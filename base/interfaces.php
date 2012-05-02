<?php
/**
 * Файл содержит интерфейсы ядра для фреймворка Yii.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * IApplicationComponent - это интерфейс, который должен быть реализован всеми компонентами приложения.
 *
 * После завершения конфигурации приложения вызывается метод {@link init()} каждого
 * загруженного компонента приложения.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: interfaces.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.base
 * @since 1.0
 */
interface IApplicationComponent
{
	/**
	 * Инициализирует компонент приложения.
	 * Метод вызывается после завершения конфигурации приложения.
	 */
	public function init();
	/**
	 * @return boolean был ли выполнен метод {@link init()}
	 */
	public function getIsInitialized();
}

/**
 * ICache - это интерфейс, который должен быть реализован компонентами кэша.
 *
 * Данный интерфейс должен реализовываться классами, поддерживающими функцию кэширования.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: interfaces.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.caching
 * @since 1.0
 */
interface ICache
{
	/**
	 * Получает значение из кэша по определенному ключу.
	 * @param string $id ключ, идентифицирующий кэшированное значение
	 * @return mixed сохранненое в кэше значение; false, если значения в кэше нет или у него истек срок годности
	 */
	public function get($id);
	/**
	 * Получает несколько значений из кэша по определенному ключу.
	 * Некоторые кэш-хранилища (такие, как memcache, apc) позволяют получать за раз несколько значений,
	 * что может улучшить производительность за счет уменьшения издержек соединения.
	 * Если кэш-хранилище не поддерживает данную функцию, данный метод будет сымитирован.
	 * @param array $ids список ключей, идентифицирующих значения кэша
	 * @return array список кэшированных значений, соответствующих определенным ключам.
	 * Возвращается массив пар (ключ, значение). Если значения нет в кэше или его срок
	 * годности истек, соответствующее значение массива будет равно значению false
	 */
	public function mget($ids);
	/**
	 * Сохраняет значение, идентифицируемое по ключу, в кэше.
	 * Если кэш уже содержит такой ключ, существующее значение и срок годности будут заменены на новые.
	 *
	 * @param string $id ключ, идентифицирующий кэшируемое значение
	 * @param mixed $value кэшируемое значение
	 * @param integer $expire количество секунд, через которое истечет срок годности кэшируемого значения. 0 означает бесконечный срок годности
	 * @param ICacheDependency $dependency зависимость кэшируемого элемента. Если зависимость изменяется, элемент помечается как недействительный
	 * @return boolean true, если значение успешно сохранено в кэше, иначе - false
	 */
	public function set($id,$value,$expire=0,$dependency=null);
	/**
	 * Сохраняет в кэш значение, идентифицируемое ключом, если кэш не содержит данный ключ.
	 * Если такой ключ уже содержится в кэше, ничего не будет выполнено.
	 * @param string $id ключ, идентифицирующий кэшируемое значение
	 * @param mixed $value кэшируемое значение
	 * @param integer $expire количество секунд, через которое истечет срок годности кэшируемого значения. 0 означает бесконечный срок годности
	 * @param ICacheDependency $dependency зависимость кэшируемого элемента. Если зависимость изменяется, элемент помечается как недействительный
	 * @return boolean true, если значение успешно сохранено в кэше, иначе - false
	 */
	public function add($id,$value,$expire=0,$dependency=null);
	/**
	 * Удаляет из кэша значение по определенному ключу.
	 * @param string $id ключ удаляемого значения
	 * @return boolean не было ли ошибок при удалении; true - успешное удаление
	 */
	public function delete($id);
	/**
	 * Удаляет все значения из кэша.
	 * Будьте осторожны при выполнении данной операции, если кэш доступен в нескольких приложениях.
	 * Классы-потомки могут реализовать этот метод для создания операции очистки.
	 * @return boolean whether the flush operation was successful.
	 */
	public function flush();
}

/**
 * ICacheDependency - это интерфейс, который должен быть реализован классами зависимостей кэша.
 *
 * Данный интерфейс должен реализовываться классами, используемыми в качестве зависимостей кэша.
 *
 * Объекты, реализующие данный интерфейс, должны быть сериализуемы и десереализумы.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: interfaces.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.caching
 * @since 1.0
 */
interface ICacheDependency
{
	/**
	 * Выполняет зависимость, генерируя и сохраняя данные, связанные с зависимостью.
	 * Метод вызывается кэшем перед записью в него данных.
	 */
	public function evaluateDependency();
	/**
	 * @return boolean изменилась ли зависимость
	 */
	public function getHasChanged();
}


/**
 * IStatePersister - это интерфейс, который должен быть реализован классами постоянного состояния.
 *
 * Данный интерфейс должен реализовываться всеми классами постоянного состояния (такими, как {@link CStatePersister})
 *
 * @version $Id: interfaces.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.base
 * @since 1.0
 */
interface IStatePersister
{
	/**
	 * Загружает данные состояния из постоянного хранилища.
	 * @return mixed данные состояния
	 */
	public function load();
	/**
	 * Сохраняет состояние приложения в постоянное хранилище.
	 * @param mixed $state данные состояния
	 */
	public function save($state);
}


/**
 * IFilter - это интерфейс, который должен быть реализован фильтрами действий.
 *
 * @version $Id: interfaces.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.base
 * @since 1.0
 */
interface IFilter
{
	/**
	 * Выполняет фильтрацию.
	 * Метод должен реализовываться для выполнения реальной фильтрации.
	 * Если фильтр хочет, чтобы выполнение действия продолжалось, он должен вызвать метод
	 * <code>$filterChain->run()</code>.
	 * @param CFilterChain $filterChain цепочка фильтров, в которой находится фильтр
	 */
	public function filter($filterChain);
}


/**
 * IAction - это интерфейс, который должен быть реализован действиями контроллера.
 *
 * @version $Id: interfaces.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.base
 * @since 1.0
 */
interface IAction
{
	/**
	 * @return string идентификатор действия
	 */
	public function getId();
	/**
	 * @return CController экземпляр контроллера
	 */
	public function getController();
}


/**
 * Интерфейс IWebServiceProvider может быть реализован классами провайдеров веб-служб.
 *
 * Если реализован данный интерфейс, экземпляр провайдера сможет перехватывать
 * вызов удаленного метода (например, для целей журналирования или аутентификации).
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: interfaces.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.base
 * @since 1.0
 */
interface IWebServiceProvider
{
	/**
	 * Метод вызывается перед вызовом запрошенного удаленного метода.
	 * @param CWebService $service запрашиваемый в данный момент веб-сервис
	 * @return boolean должен ли выполнится удаленный метод
	 */
	public function beforeWebMethod($service);
	/**
	 * Метод вызывается после вызова запрошенного удаленного метода.
	 * @param CWebService $service запрашиваемый в данный момент веб-сервис
	 */
	public function afterWebMethod($service);
}


/**
 * Интерфейс IViewRenderer реализуется классом рендерера представления.
 *
 * Рендерер (генератор) представления - это компонент {@link CWebApplication::viewRenderer viewRenderer}
 * приложения, заменяющий логику рендера представлений по умолчанию, реализованную в
 * {@link CBaseController}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: interfaces.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.base
 * @since 1.0
 */
interface IViewRenderer
{
	/**
	 * Рендерит файл представления.
	 * @param CBaseController $context контроллер или виджет, рендерящий файл представления
	 * @param string $file путь к файлу представления
	 * @param mixed $data данные, передаваемые в представление
	 * @param boolean $return должен ли возвращаться результат рендера
	 * @return mixed результат рендера; null, если результат не требуется
	 */
	public function renderFile($context,$file,$data,$return);
}


/**
 * Интерфейс IUserIdentity реализуется классом идентификации пользователя.
 *
 * Идентификация представляет способ аутентификации пользователя и получения
 * информации, требуемой для однозначного определения пользователя. Обычно используется
 * с {@link CWebApplication::user пользовательским компонентом приложения}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: interfaces.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.base
 * @since 1.0
 */
interface IUserIdentity
{
	/**
	 * Аутентифицирует пользователя.
	 * Информация, требуемая для аутентификации пользователя,
	 * обычно получается в конструкторе.
	 * @return boolean успешна ли аутентификации
	 */
	public function authenticate();
	/**
	 * Возвращает значение, показывающее, аутентифицирован ли пользователь.
	 * @return boolean аутентифицирован ли пользователь
	 */
	public function getIsAuthenticated();
	/**
	 * Возвращает значение, однозначно определяющее пользователя.
	 * @return mixed значение, однозначно определяющее пользователя (например, значение первичного ключа)
	 */
	public function getId();
	/**
	 * Возвращает отображаемое имя пользователя (например, имя пользователя).
	 * @return string отображаемое имя пользователя
	 */
	public function getName();
	/**
	 * Возвращает дополнительную пользовательскую информацию, которая должна быть постоянной во время сессии пользователя.
	 * @return array дополнительная пользовательская информация, которая должна быть постоянной во время сессии пользователя (кроме {@link id})
	 */
	public function getPersistentStates();
}


/**
 * Интерфейс IWebUser реализуется {@link CWebApplication::user пользовательским компонентом приложения}.
 *
 * Пользовательский компонент приложения представляет информацию, идентифицирующую текущего пользователя.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: interfaces.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.base
 * @since 1.0
 */
interface IWebUser
{
	/**
	 * Возвращает значение, уникально определяющее пользователя.
	 * @return mixed значение, уникально определяющее пользователя (например, значение первичного ключа)
	 */
	public function getId();
	/**
	 * Возвращает отображаемое имя пользователя (например, имя пользователя).
	 * @return string отображаемое имя пользователя
	 */
	public function getName();
	/**
	 * Возвращает значение, показывающее, является ли пользователь гостем (не аутентифицирован).
	 * @return boolean является ли пользователь гостем (не аутентифицирован)
	 */
	public function getIsGuest();
	/**
	 * Выполняет проверку доступа для данного пользователя.
	 * @param string $operation имя проверяемой операции
	 * @param array $params пары имя-значение, которые будут переданы в бизнес-правила, ассоциированные
	 * с задачами и ролями, заданными для пользователя
	 * @return boolean может ли данный пользователь выполнять операции
	 */
	public function checkAccess($operation,$params=array());
}


/**
 * Интерфейс IAuthManager реализуется компонентом приложения менеджера аутентификации.
 *
 * В основном менеджер аутентификации отвечает за обеспечение контроля доступа, основанного на ролях (RBAC).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: interfaces.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.base
 * @since 1.0
 */
interface IAuthManager
{
	/**
	 * Выполняет проверку возможности доступа для определенного пользователя.
	 * @param string $itemName имя проверяемой на доступ операции
	 * @param mixed $userId идентификатор пользователя. Должен быть либо целым числом либо строкой,
	 * представляющей уникальный идентификатор пользователя. См. {@link IWebUser::getId}.
	 * @param array $params пары имя-значение, которые будут переданы в бизнес-правила, ассоциированные
	 * с задачами и ролями, заданными для пользователя
	 * @return boolean может ли данный пользователь выполнять операции
	 */
	public function checkAccess($itemName,$userId,$params=array());

	/**
	 * Создает элемент авторизации.
	 * Элемент авторизации представляет разрешительные действия (например, создание записи).
	 * Он имеет три типа: операция (operation), задача (task) и роль (role).
	 * Элементы авторизации образуют иерархию. Более высокие уровни элементов наследуют разрешения от
	 * более низких уровней.
	 * @param string $name имя элемента. Должен быть уникальным идентификатором
	 * @param integer $type тип элемента (0: операция, 1: задача, 2: роль).
	 * @param string $description описание элемента
	 * @param string $bizRule бизнес-правило, ассоциированное с элементом. Это блок
	 * PHP-кода, выполняемого при вызове метода {@link checkAccess} для элемента
	 * @param mixed $data дополнительные данные, ассоциированные с элементом
	 * @return CAuthItem элемент авторизации
	 * @throws CException вызывается, если элемент с таким именем уже существует
	 */
	public function createAuthItem($name,$type,$description='',$bizRule=null,$data=null);
	/**
	 * Удаляет определенный элемент авторизации.
	 * @param string $name имя удаляемого элемента
	 * @return boolean находился ли элемент в хранилище и был ли удален
	 */
	public function removeAuthItem($name);
	/**
	 * Возвращает элементы авторизации по определенным типу и пользователю.
	 * @param integer $type тип элемента (0: оперция, 1: задача, 2: роль). По умолчанию - null,
	 * т.е. возвращаются все элементы независимо от их типа
	 * @param mixed $userId идентификатор пользователя. По умолчанию - null, т.е. возвращаются все элементы,
	 * даже если они не заданы для пользователя
	 * @return array элементы авторизации определенного типа
	 */
	public function getAuthItems($type=null,$userId=null);
	/**
	 * Возвращает элементы авторизации по определенному имени.
	 * @param string $name имя элемента
	 * @return CAuthItem элемент авторизации. Null, если элемент не может быть найден
	 */
	public function getAuthItem($name);
	/**
	 * Сохраняет элемент авторизации в постоянное хранилище.
	 * @param CAuthItem $item сохраняемый элемент
	 * @param string $oldName старое имя элемента. Если null, то имя элемента не меняется
	 */
	public function saveAuthItem($item,$oldName=null);

	/**
	 * Добавляет к элементу дочерний элемент.
	 * @param string $itemName имя родительского элемента
	 * @param string $childName имя дочернего элемента
	 * @throws CException вызывается, если родитель или дочерний элемент не существует или замечен цикл в иерархии
	 */
	public function addItemChild($itemName,$childName);
	/**
	 * Удаляет дочерний элемент от его родителя.
	 * Примечание: сам дочерний элемент не удаляется, удаляется только связь родитель-потомок.
	 * @param string $itemName имя родительского элемента
	 * @param string $childName имя элемента-потомка
	 * @return boolean успешно ли удаление
	 */
	public function removeItemChild($itemName,$childName);
	/**
	 * Возвращает значение, показывающее, существует ли дочерний элемент для данного родителя.
	 * @param string $itemName имя родительского элемента
	 * @param string $childName имя элемента-потомка
	 * @return boolean существует ли дочерний элемент
	 */
	public function hasItemChild($itemName,$childName);
	/**
	 * Возвращает дочерние элементы определенного элемента.
	 * @param mixed $itemName имя родительского элемента. Может быть строкой или массивом.
	 * Массив представляет список имен элементов
	 * @return array все дочерние элементы определенного элемента
	 */
	public function getItemChildren($itemName);

	/**
	 * Присваивает элемент авторизации пользователю.
	 * @param string $itemName имя элемента
	 * @param mixed $userId идентификатор пользователя (см. {@link IWebUser::getId})
	 * @param string $bizRule бизнес-правило, выполняемое при вызове метода {@link checkAccess}
	 * для данного конкретного элемента авторизации
	 * @param mixed $data дополнительные данные, ассоциированные с данным присваиванием
	 * @return CAuthAssignment информация присваивания авторизации
	 * @throws CException вызывается, если элемент не существует или уже присвоен пользователю
	 */
	public function assign($itemName,$userId,$bizRule=null,$data=null);
	/**
	 * Удаляет элемент авторизации от пользователя.
	 * @param string $itemName имя элемента
	 * @param mixed $userId идентификатор пользователя (см. {@link IWebUser::getId})
	 * @return boolean успешно ли удаление
	 */
	public function revoke($itemName,$userId);
	/**
	 * Возвращает значение, показывающее, присвоен ли элемент пользователю.
	 * @param string $itemName имя элемента
	 * @param mixed $userId идентификатор пользователя (см. {@link IWebUser::getId})
	 * @return boolean присвоен ли элемент авторизации пользователю
	 */
	public function isAssigned($itemName,$userId);
	/**
	 * Возвращает информацию элемента привязки (item assignment).
	 * @param string $itemName имя элемента
	 * @param mixed $userId идентификатор пользователя (см. {@link IWebUser::getId})
	 * @return CAuthAssignment информация элемента привязки (item assignment). 
	 * Если элемент не присвоен пользователю, то возвращается значение null
	 */
	public function getAuthAssignment($itemName,$userId);
	/**
	 * Возвращает элементы привязки определенного пользователя.
	 * @param mixed $userId идентификатор пользователя (см. {@link IWebUser::getId})
	 * @return array элементы привязки (item assignment) пользователя. Если элементов,
	 * присвоенных пользователю, нет, возвращается пустой массив
	 */
	public function getAuthAssignments($userId);
	/**
	 * Сохраняет изменения элемента привязки.
	 * @param CAuthAssignment $assignment изменяемый элемент привязки
	 */
	public function saveAuthAssignment($assignment);

	/**
	 * Удаляет все данные авторизации.
	 */
	public function clearAll();
	/**
	 * Удаляет все элементы привязки.
	 */
	public function clearAuthAssignments();

	/**
	 * Сохраняет данные авторизации в постоянное хранилище.
	 * Если внесены изменения в данные авторизации, убедитесь, что данный метод вызван
	 * для сохранения измененных данных в постоянное хранилище.
	 */
	public function save();

	/**
	 * Выполняет бизнес-правило.
	 * Бизнес-правило - это кусок PHP кода, выполняемый при вызове метода {@link checkAccess}.
	 * @param string $bizRule выполняемое бизнес-правило
	 * @param array $params дополнительные параметры, передаваемые в бизнес-правило при выполнении
	 * @param mixed $data дополнительные данные, ассоциированные с соответствующими элементом авторизации или привязкой
	 * @return boolean возвращает ли результат выполнения бизнес-правила значение true.
	 * Если бизнес-правило пусто, также возвращается значение true
	 */
	public function executeBizRule($bizRule,$params,$data);
}


/**
 * Интерфейс IBehavior реализуется всеми классами поведения.
 *
 * Поведение - это способ расширить компонент дополнительными методами, определенными в классе поведения
 * и не доступными в классе компонента.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: interfaces.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.base
 */
interface IBehavior
{
	/**
	 * Присоединяет объект поведения к компоненту.
	 * @param CComponent $component компонент, к которому присоединяется поведение
	 */
	public function attach($component);
	/**
	 * Отсоединяет объект поведения от компонента.
	 * @param CComponent $component компонент, от которого отсоединяется поведение
	 */
	public function detach($component);
	/**
	 * @return boolean включено ли данное поведение
	 */
	public function getEnabled();
	/**
	 * @param boolean $value включено ли данное поведение
	 */
	public function setEnabled($value);
}

/**
 * Интерфейс IWidgetFactory должен быть реализован классом фабрики виджетов.
 *
 * Если фабрика виджетов доступна, то она будет использована для создания запрашиваемого
 * виджета вызовом метода {@link CBaseController::createWidget}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: interfaces.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.web
 * @since 1.1
 */
interface IWidgetFactory
{
	/**
	 * Создает новый виджет с переданным именем и начальными параметрами.
	 * @param CBaseController $owner контроллер-владелец нового виджета
	 * @param string $className имя класса виджета. Может быть псевдонимом пути (например, system.web.widgets.COutputCache)
	 * @param array $properties начальные значения параметров виджета (имя=>значение)
	 * @return CWidget созданный виджет с параметрами, проинициализированными начальными значениями
	 */
	public function createWidget($owner,$className,$properties=array());
}

/**
 * Интерфейс IDataProvider должен быть реализован классами провайдеров данных.
 *
 * Провайдеры данных - компоненты, которые могут передавать данные виджетам, таким как таблица (data grid), список(data list).
 * Кроме передаваемых данных, провайдеры поддерживают пагинацию и сортировку.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: interfaces.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.web
 * @since 1.1
 */
interface IDataProvider
{
	/**
	 * @return string уникальный идентификатор, отличающий данный провайдер данных от других провайдеров данных
	 */
	public function getId();
	/**
	 * Возвращает количество элементов данных на текущей странице.
	 * Эквивалентно <code>count($provider->getData())</code>.
	 * Если параметр {@link pagination} установлен в значение false, возвращается значение, равное {@link totalItemCount}.
	 * @param boolean $refresh должно ли быть пересчитано количество элементов данных
	 * @return integer количество элементов данных на текущей странице
	 */
	public function getItemCount($refresh=false);
	/**
	 * Возвращает общее количество элементов.
	 * Если параметр {@link pagination} установлен в значение false, возвращается значение, равное {@link itemCount}.
	 * @param boolean $refresh должно ли быть пересчитано общее количество элементов данных
	 * @return integer обще количество элементов данных
	 */
	public function getTotalItemCount($refresh=false);
	/**
	 * Возвращает доступные в данный момент элементы данных.
	 * @param boolean $refresh должны ли быть данные перезагружены из постоянного хранилища
	 * @return array список доступных в данный момент в данном провайдере данных элементов данных
	 */
	public function getData($refresh=false);
	/**
	 * Возвращает значения ключей, ассоциированных с элементами данных.
	 * @param boolean $refresh должны ли ключи быть пересчитаны
	 * @return array список значений ключей, соответствующих {@link data}. Каждый элемент данных в свойстве {@link data}
	 * уникально идентифицируем соответствующим значением ключа в массиве
	 */
	public function getKeys($refresh=false);
	/**
	 * @return CSort the объект сортировки. Если возвращается значение false, то сортировка отключена
	 */
	public function getSort();
	/**
	 * @return CPagination объект пагинации. Если возвращается значение false, то пагинация отключена
	 */
	public function getPagination();
}