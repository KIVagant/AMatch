<?php
namespace KIVagant\AMatch;

	/**
	 * Класс для проверки содержимого массивов
	 *
	 * AMatch::runMatch(массив параметров)->ключ(значение, условие)->stopMatch();
	 *
	 * <code>
	 *
	 * // Условия со знаком ! в начале означает "обратное"
	 *
	 * // Флаги:
	 * STRICT_STRUCTURE — проверять избыточность структуры. Параметры, для которых не заданы правила валидации считаются лишними.
	 * FLAG_DONT_STOP_MATCHING — проверять весь массив до конца, даже если обнаружена ошибка.
	 * FLAG_SHOW_GOOD_COMMENTS — в комментарии выводить успешность
	 *
	 * // Примеры использования: см. детали в тесте AMatchTest.php
	 * AMatch::runMatch($actual_ar)->doc_id()->stopMatch(); // doc_id существует?
	 * AMatch::runMatch($actual_ar)->doc_id(13)->stopMatch(); // doc_id == 13?
	 * AMatch::runMatch($actual_ar)->doc_id(13, '===')->stopMatch(); // doc_id === 13?
	 * AMatch::runMatch($actual_ar)->doc_id(13, '>=')->stopMatch(); // doc_id >= 13?
	 * AMatch::runMatch($actual_ar)->doc_id(13, '<')->stopMatch(); // doc_id < 13?
	 * AMatch::runMatch($actual_ar)->doc_id(блабла, 'int')->stopMatch(); // is_int(doc_id)?
	 * AMatch::runMatch($actual_ar)->data(44, 'in_array')->stopMatch(); // есть ли в массиве data значение 44?
	 * AMatch::runMatch($actual_ar)->doc_id(array(13, 14), 'in_left_array')->stopMatch(); // есть ли в левом массиве значение doc_id?
	 * AMatch::runMatch($actual_ar)->doc_id(array(13, 14), 'in_expected_array')->stopMatch(); // см. выше
	 * AMatch::runMatch($actual_ar)->data(2, 'key')->stopMatch(); // есть ли в массиве data ключ 2?
	 * AMatch::runMatch($actual_ar)->data(2, 'key_exists')->stopMatch(); // есть ли в массиве data ключ 2?
	 * AMatch::runMatch($actual_ar)->my_object(SomeClass, 'instanceof')->stopMatch(); // является ли объект my_object экземпляром SomeClass
	 * // НЕ
	 * AMatch::runMatch($actual_ar)->doc_id(13, '!=')->stopMatch(); // doc_id != 13?
	 * AMatch::runMatch($actual_ar)->data('key3', '!key_exists')->stopMatch(); // в массиве нет ключа?
	 * AMatch::runMatch($actual_ar)->doc_id('', '!is_float')->stopMatch(); // это не дробное?
	 * AMatch::runMatch($actual_ar)->doc_id('', 'longint')->stopMatch(); // является длинным интом (в т.ч. отрицательным)
	 * // Работа с условиями и с CURRENT
	 * AMatch::runMatch($actual_ar)->doc_id(AMatch::CURRENT, true)->stopMatch(); // значение doc_id == true (или valid)
	 * AMatch::runMatch($actual_ar)->doc_id(AMatch::CURRENT, false)->stopMatch(); // значение doc_id == false (или invalid)
	 * AMatch::runMatch($actual_ar)->doc_id(0 == 1, false)->stopMatch(); // значение doc_id принимается, если входящее условие — ложь
	 * 	AMatch::runMatch($actual_ar)->doc_id(AMatch::CURRENT, 0 == 1)->stopMatch(); // аналог предыдущей записи
	 * AMatch::runMatch($actual_ar)->doc_id(0 < 1, true)->stopMatch(); // значение doc_id принимается, если входящее условие — правда
	 * 	AMatch::runMatch($actual_ar)->doc_id(AMatch::CURRENT, 0 < 1)->stopMatch(); // аналог предыдущей записи
	 * // Проверка необязательных параметров
	 * AMatch::runMatch($actual_ar)->sub_ar(AMatch::OPTIONAL)->stopMatch(); // необязательно присутствует с любым типом
	 * AMatch::runMatch($actual_ar)->sub_ar(AMatch::OPTIONAL, 'is_array')->stopMatch(); // отсутствует или является массивом
	 * и т.п.
	 *
	 * //Работа с callback на примере вложенного массива:
	 * function callbackMethod($ar) { return AMatch::runMatch($ar)->key()->stopMatch(); } // callback должен возвращать bool
	 * $result = AMatch::runMatch($actual_ar)->sub_ar('callbackMethod', 'callback')->stopMatch();
	 * $result = AMatch::runMatch($actual_ar)->title(13, 'AMatchString::minLength')->title(18, 'AMatchString::maxLength')->stopMatch(); // Длина строки не менее 13 и не более 18 символов
	 *
	 * //Пользовательские ошибки (третий параметр)
	 * $result = AMatch::runMatch($actual_ar)->doc_id(13, '<', 'Please, input correct document number!')->stopMatch();
	 *
	 * //Цепочки:
	 * AMatch::runMatch($actual_ar)->doc_id()->subject_id()->...->stopMatch();
	 * </code>
	 *
	 * @package AMatch
	 * @author KIVagant. Special thanks to Andrew Tereschenko, Andrew Lugovoi for ideas.
	 * @see AMatchTest
	 * @example examples/example.php Примеры использования с описанием
	 * @license GNU GPL v2 http://opensource.org/licenses/gpl-2.0.php
	 * @link https://github.com/KIVagant/AMatch
	 */
	class AMatch
	{
		/**
		 * Ключ, который добавится к списку статусов, если возникнет ошибка AMatchStatus::UNKNOWN_PARAMETERS_LIST
		 */
		const _UNKNOWN_PARAMETERS_LIST = 'Unknown parameters';

		/**
		 * Ожидаемое значение, означающее, что проверяемый параметр не обязателен
		 * (и все условия для него не обязательны, если он отсутствует)
		 * Неудовлетворительная валидация будет пропускаться
		 * @var string
		 */
		const OPTIONAL = '->optional';

		/**
		 * Использовать значение текущего ключа как ожидаемое для отправки в условие сопоставления
		 * @var string
		 */
		const CURRENT = '->actual_parameter';

		/**
		 * Нет флагов для runMatch
		 * @var integer
		 */
		const NO_FLAGS = 0;

		/**
		 * Проверять, что в проверяемом массиве отсутствуют ключи, не объявленные для сопоставления.
		 * Уникальная степень двойки для битмаски.
		 * @var integer
		 */
		const FLAG_STRICT_STRUCTURE = 1;

		/**
		 * Не останавливать сопоставление, даже если обнаружено условие несоответствия.
		 * Уникальная степень двойки для битмаски.
		 * @var integer
		 */
		const FLAG_DONT_STOP_MATCHING = 2;

		/**
		 * Показывать комментарии не только к проблемам, но и к прошедшим сопоставление ключам и условиям.
		 * Уникальная степень двойки для битмаски.
		 * @var integer
		 */
		const FLAG_SHOW_GOOD_COMMENTS = 4;

		/**
		 * Удалять из проверяемого массива ключи, не объявленные для сопоставления.
		 * Только вместе с FLAG_DONT_STOP_MATCHING
		 * Уникальная степень двойки для битмаски.
		 * @var integer
		 */
		const FLAG_FILTER_STRUCTURE = 8;

		/**
		 * Флаги, установленные для сопоставления массива (битовая маска)
		 * @var bitmask
		 */
		protected $_flags;

		/**
		 * Актуальный набор параметров, который нужно анализировать
		 *
		 * @var array
		 */
		protected $_actual_ar = array();

		/**
		 * Ключ, который сейчас анализируется
		 * @var string
		 */
		protected $_param_key = null;

		/**
		 * Список всех параметров, проходящих через сопоставление (для проверки ограниченной структуры)
		 * @var array
		 */
		protected $_params_keys_list = array();

		/**
		 * Список необязательных ключей - валидируются только если существуют
		 * @var array
		 */
		protected $_params_keys_list_optional = array();

		/**
		 * Массив условий для текущего ключа
		 * @var array
		 */
		protected $_conditions_ar = array();

		/**
		 * Результат сопоставлений
		 * @var bool|null
		 */
		protected $_result = null;

		/**
		 * Результаты выполнения сопоставлений (ошибки или успешные статусы)
		 * @var array
		 */
		protected $_result_ar = array();

		/**
		 * Условия, выполнявшиеся на момент комментария
		 * @var array
		 */
		protected $_comment_conditions_ar = array();

		/**
		 * Обратное условие
		 * @var bool
		 */
		protected $_opposite = false;

		/**
		 * Пользовательский код (или текст) ошибки (переданный извне) на конкретное условие сопоставления
		 * @var string
		 */
		protected $_user_error_status = null;

		/**
		 * Объект, содержащий коды статусов и их расшифровку
		 * @var AMatchStatus
		 */
		protected $_status_obj = null;

		/**
		 * Можно передать массив актуальных параметров прямо в конструктор
		 *
		 * @param bitmask $flags Флаги сопоставления
		 * @param array $actual_ar link
		 * @param AMatchStatus $statuses_mapping_object Объект-наследник AMatchStatus, переопределяющий комментарии к ошибкам
		 */
		public function __construct(&$actual_ar = array(), $flags = self::NO_FLAGS, $statuses_mapping_object = null)
		{
			$this->_flags = $flags;
			$this->_actual_ar = array();
			$this->_param_key = 'runMatch';

			// Требуем использовать для маппинга наследник AMatchStatus
			if (is_object($statuses_mapping_object) && $statuses_mapping_object instanceof AMatchStatus) {
				$this->_status_obj = $statuses_mapping_object;
			} else if (is_object($statuses_mapping_object)) {
				$this->_status_obj = new AMatchStatus();
				$this->_flags = self::NO_FLAGS;
				$this->_setFalseResult(AMatchStatus::BAD_STATUSES_CLASS, array(get_class($statuses_mapping_object)));
			} else {
				$this->_status_obj = new AMatchStatus();
			}
			if (!is_array($actual_ar)) {
				$this->_flags = self::NO_FLAGS;
				$this->_setFalseResult(AMatchStatus::MATCHING_DATA_NOT_ARRAY);
			} else {
				$this->_actual_ar = &$actual_ar;
				$this->_param_key = null;
			}
		}

		/**
		 * Сравнить актуальные параметры с запрашиваемыми
		 *
		 * @param array $actual_ar
		 * @param bitmask $flags Флаги сопоставления
		 * @param AMatchStatus $statuses_mapping_object Объект-наследник AMatchStatus, переопределяющий комментарии к ошибкам
		 * @return AMatch Возвращает объект для использования цепочки вызова
		 */
		public static function runMatch(&$actual_ar, $flags = self::NO_FLAGS, $statuses_mapping_object = null)
		{
			return new AMatch($actual_ar, $flags, $statuses_mapping_object);
		}

		/**
		 * Ошибка "Ключ не существует", если он не опционален
		 */
		protected function _keyNotExists()
		{
			$expected = array_key_exists(0, $this->_conditions_ar) ? $this->_conditions_ar[0] : null;

			// Если ожидаемое значение содержит флаг необязательного параметра или ранее какое-то из условий приняло этот флаг
			if ($expected === self::OPTIONAL || array_key_exists($this->_param_key, $this->_params_keys_list_optional)) {

				// Регистрируем в списке опциональных параметров
				$this->_params_keys_list_optional[$this->_param_key] = $this->_param_key;
				$this->_setTrueResult(AMatchStatus::KEY_NOT_EXISTS_OPTIONAL);
			} else {
				$this->_setFalseResult(AMatchStatus::KEY_NOT_EXISTS);
			}
		}

		/**
		 * Установить успешный результат условия
		 * @param string $result Комментарий к хорошему результату
		 * @param string $result Условие на момент комментария к хорошему результату
		 */
		protected function _setTrueResult($result, $result_conditions = null)
		{
			// Результат правдивый только если ранее не был установлен в другое значение
			$this->_result = is_null($this->_result) ? true : $this->_result;
			
			// Показывать комментарий к хорошему результату только если включён такой флаг
			if ($this->_haveFlag(self::FLAG_SHOW_GOOD_COMMENTS)) {
				// Хороший комментарий присоединяем только если ранее не было любого другого
				$this->_result_ar[$this->_param_key] = empty($this->_result_ar[$this->_param_key])
					? $result
					: $this->_result_ar[$this->_param_key];
				$this->_comment_conditions_ar[$this->_param_key] = empty($this->_comment_conditions_ar[$this->_param_key])
					? (
						$result_conditions
							? $result_conditions
							: $this->_conditions_ar
					) : $this->_comment_conditions_ar[$this->_param_key];
			}
		}

		/**
		 * Установить безуспешный результат условия (или успешный, если это опциональный параметр)
		 * @param string $result Комментарий к ошибке сопоставления
		 * * @param string $result Условие на момент комментария к ошибке сопоставления
		 */
		protected function _setFalseResult($result, $result_conditions = null)
		{
			if (array_key_exists($this->_param_key, $this->_params_keys_list_optional)) {
				$this->_setTrueResult(AMatchStatus::KEY_NOT_EXISTS_OPTIONAL, $result_conditions);
			} else {
				$this->_result = false;

				// Перебиваем ранние комментарии последней ошибкой
				$this->_result_ar[$this->_param_key] = empty($this->_user_error_status)
					? $result
					: $this->_user_error_status;
				$this->_comment_conditions_ar[$this->_param_key] = $result_conditions
					? $result_conditions
					: $this->_conditions_ar; // Перебиваем ранние условия для комментариев
			}
		}

		/**
		 * Сравнить два значения
		 *
		 * @param mixed $first одно
		 * @param mixed $second другое
		 * @param bool $with_type учитывать типы
		 * @param bool $opposite инвертировать результат (неравенство считать успехом сопоставления)
		 */
		protected function _validateTwoValues($first, $second, $with_type = false, $opposite = false)
		{
			if ($opposite) {
				if (!$with_type && $first != $second) {
					$this->_setTrueResult(AMatchStatus::KEY_CONDITION_VALID);
				} elseif ($with_type && $first !== $second) {
					$this->_setTrueResult(AMatchStatus::KEY_VALID_FULLY);
				} else {
					$this->_setFalseResult(AMatchStatus::KEY_CONDITION_NOT_VALID);
				}
			} else {
				if (!$with_type && $first == $second) {
					$this->_setTrueResult(AMatchStatus::KEY_CONDITION_VALID);
				} elseif ($with_type && $first === $second) {
					$this->_setTrueResult(AMatchStatus::KEY_VALID_FULLY);
				} else {
					$this->_setFalseResult(AMatchStatus::KEY_CONDITION_NOT_VALID);
				}
			}
		}

		/**
		 * Первое значение меньше
		 * @param mixed $first
		 * @param mixed $second
		 * @param bool $or_equal Или эквивалентно (<=)
		 */
		protected function _firstIsSmaller($first, $second, $or_equal = false)
		{
			if ($first < $second) {
				$this->_setTrueResult(AMatchStatus::KEY_CONDITION_VALID);
			} else {
				if ($or_equal) {
					$this->_validateTwoValues($first, $second);
				} else {
					$this->_setFalseResult(AMatchStatus::KEY_CONDITION_NOT_VALID);
				}
			}
		}

		/**
		 * Первое значение больше
		 * @param mixed $first
		 * @param mixed $second
		 * @param bool $or_equal Или эквивалентно (>=)
		 */
		protected function _firstIsBigger($first, $second, $or_equal = false)
		{
			if ($first > $second) {
				$this->_setTrueResult(AMatchStatus::KEY_CONDITION_VALID);
			} else {
				if ($or_equal) {
					$this->_validateTwoValues($first, $second);
				} else {
					$this->_setFalseResult(AMatchStatus::KEY_CONDITION_NOT_VALID);
				}
			}
		}

		/**
		 * Сообщить о результате сопоставления типов
		 * @param bool $bool успех/неуспех
		 */
		protected function _typeMsg($bool, $type)
		{
			$bool = ($this->_opposite) ? !$bool : $bool;
			if ($bool) {
				$this->_setTrueResult(AMatchStatus::KEY_TYPE_VALID);
			} else {
				$this->_setFalseResult(AMatchStatus::KEY_TYPE_NOT_VALID, array($type, $type));
			}
		}

		/**
		 * Сообщить о результате сопоставления
		 * @param bool $bool успех/неуспех
		 * @param bool $replace_result Собственный комментарий (из callback-методов, например)
		 * @param bool $replace_comment_conditions Собственные условия к комментариям (из callback-методов, например)
		 */
		protected function _conditionMsg($bool, $replace_result = null, $replace_comment_conditions = null)
		{
			$bool = ($this->_opposite) ? !$bool : $bool;
			if ($bool) {
				$result = $replace_result ? $replace_result : AMatchStatus::KEY_CONDITION_VALID;
				$this->_setTrueResult($result, $replace_comment_conditions);
			} else {
				$result = $replace_result ? $replace_result : AMatchStatus::KEY_CONDITION_NOT_VALID;
				$this->_setFalseResult($result, $replace_comment_conditions);
			}
		}

		/**
		 * Выполнить проверку условия сопоставления
		 *
		 * @param string $condition Условие
		 * @param mixed $expected Ожидаемое значение
		 * @param mixed $actual Актуальное значение
		 */
		protected function _conditionValidate($condition, $expected, $actual)
		{
			if ($condition === true) {
				$condition = 'true';
			} else if ($condition === false) {
				$condition = 'false';
			}
			switch ($condition) {
				case '=':
				case '==':
					$this->_validateTwoValues($expected, $actual, false, $this->_opposite);
					break;
				case '===':
					$this->_validateTwoValues($expected, $actual, true, $this->_opposite);
					break;
				case '<':
					if ($this->_opposite) {
						$this->_firstIsBigger($expected, $actual, true);
					} else {
						$this->_firstIsSmaller($expected, $actual);
					}
					break;
				case '<=':
					if ($this->_opposite) {
						$this->_firstIsBigger($expected, $actual);
					} else {
						$this->_firstIsSmaller($expected, $actual, true);
					}
					break;
				case '>':
					if ($this->_opposite) {
						$this->_firstIsSmaller($expected, $actual, true);
					} else {
						$this->_firstIsBigger($expected, $actual);
					}
					break;
				case '>=':
					if ($this->_opposite) {
						$this->_firstIsSmaller($expected, $actual);
					} else {
						$this->_firstIsBigger($expected, $actual, true);
					}
					break;
				case 'int':
				case 'intval':
				case 'integer':
				case 'is_int':
				case 'is_integer':
					$this->_typeMsg(is_int($actual), $condition);
					break;
				case 'long':
				case 'longint':
				case 'is_long':
				case 'is_longint':
					$this->_typeMsg(
						(is_numeric($actual) || is_string($actual)) && preg_match('/^-?\d+$/', $actual)
						, $condition
					); // большой, длинный, необрезанный
					break;
				case 'float':
				case 'floatval':
				case 'is_float':
				case 'double':
				case 'is_double':
				case 'real':
				case 'is_real':
					$this->_typeMsg($this->_isTrueFloat($actual), $condition);
					break;
				case 'array':
				case 'is_array':
					$this->_typeMsg(is_array($actual), $condition);
					break;
				case 'bool':
				case 'boolean':
				case 'is_bool':
				case 'is_boolean':
					$this->_typeMsg(is_bool($actual), $condition);
					break;
				case 'stringbool':
				case 'smartbool':
				case 'is_stringbool':
				case 'is_smartbool':
					$valid_bool = array('1', '0', 'true', 'false', true, false, 1, 0, '');
					$this->_typeMsg(in_array($actual, $valid_bool, true), $condition);
					break;
				case 'null':
				case 'is_null':
					$this->_typeMsg(is_null($actual), $condition);
					break;
				case 'numeric':
				case 'is_numeric':
					$this->_typeMsg(is_numeric($actual), $condition);
					break;
				case 'scalar':
				case 'is_scalar':
					$this->_typeMsg(is_scalar($actual), $condition);
					break;
				case 'string':
				case 'is_string':
					$this->_typeMsg(is_string($actual), $condition);
					break;
				case 'object':
				case 'is_object':
					$this->_typeMsg(is_object($actual), $condition);
					break;
				case 'instance':
				case 'instanceof':
                    $namespaced =  __NAMESPACE__ . '\\' . $expected;
					$this->_conditionMsg(is_object($actual) && ($actual instanceof $expected || $actual instanceof $namespaced));
					break;
				case 'subclass':
				case 'subclass_of':
				case 'is_subclass_of':
					$this->_conditionMsg(is_object($actual) && is_subclass_of($actual, $expected));
					break;
				case 'in_array':
				case 'in_actual_array':
					if (!is_array($actual)) {
						if ($this->_opposite) {
							$this->_setTrueResult(AMatchStatus::KEY_CONDITION_VALID);
						} else {
							$this->_setFalseResult(AMatchStatus::ACTUAL_NOT_IS_ARRAY);
						}
						break;
					}
					$this->_conditionMsg(in_array($expected, $actual));
					break;
				case 'in_left_array':
				case 'in_expected_array':
					if (!is_array($expected)) {
						if ($this->_opposite) {
							$this->_setTrueResult(AMatchStatus::KEY_CONDITION_VALID);
						} else {
							$this->_setFalseResult(AMatchStatus::EXPECTED_NOT_IS_ARRAY);
						}
						break;
					}
					
					$this->_conditionMsg(in_array($actual, $expected));
					break;
				case 'key':
				case 'key_exists':
					$this->_conditionMsg(array_key_exists($expected, $actual));
					break;
				case 'empty':
					$this->_conditionMsg(empty($actual));
					break;
				case 'valid':
				case 'true':
					$this->_conditionMsg($expected == true);
					break;
				case 'invalid':
				case 'false':
					$this->_conditionMsg($expected == false);
					break;
				case 'callback':

						// Ожидаемое значение в данном случае содержит callable
						// Пример вызова: ->some('MyClass::myfunc', 'callback')
						$this->_callCallback($expected, $actual);
					break;
				default:
					$this->_setFalseResult(AMatchStatus::CONDITION_IS_UNKNOWN);
					break;
			}
		}

		/**
		 * Вызвать пользовательские функции
		 *
		 * @param callable $callable Вызываемая функция
		 * @param mixed $actual Актуальное значение
		 * @param mixed $additional_params Параметры вызова
		 */
		protected function _callCallback($callable, $actual, $additional_params = null)
		{
			if (is_string($callable) && strstr($callable, '->') !== false) {
				$callback = explode('->', $callable);

				// Если передано что-то вроде Class->method->wtf
				if (count($callback) > 2 || empty($callback[0]) || empty($callback[1])) {
					$this->_setFalseResult(AMatchStatus::CALLBACK_NOT_CALLABLE);

					return false;
				} else {
					if (class_exists($callback[0])) {
                        $obj = new $callback[0];
                        $callable = array($obj, $callback[1]);
                    } else if (class_exists(__NAMESPACE__ . '\\' . $callback[0])) {
                        $class = __NAMESPACE__ . '\\' . $callback[0];
						$obj = new $class;
						$callable = array($obj, $callback[1]);
					} else {
						$this->_setFalseResult(AMatchStatus::CALLBACK_NOT_CALLABLE);

						return false;
					}
				}
			} elseif (is_string($callable) && strstr($callable, '::') !== false) {
                if (!is_callable($callable)) {
                    $callable = __NAMESPACE__ . '\\' . $callable;
			    }
            }

			if (is_callable($callable)) {
				$callback_result = call_user_func($callable, $actual, $this->_param_key, $additional_params);
				if (is_array($callback_result) && count($callback_result) <= 3) { // (bool, comments, comment_conditions)
					if (!empty($callback_result[1])) {
						if (!empty($callback_result[2])) {
							$this->_conditionMsg($callback_result[0], $callback_result[1], $callback_result[2]);
						} else {
							$this->_conditionMsg($callback_result[0], $callback_result[1]);
						}
					} else {
						$this->_conditionMsg($callback_result[0]);
					}
				} elseif (is_bool($callback_result)) {
					$this->_conditionMsg($callback_result);
				} else {
					$this->_setFalseResult(AMatchStatus::CALLBACK_NOT_VALID);
				}
			} else {
				$this->_setFalseResult(AMatchStatus::CALLBACK_NOT_CALLABLE);
			}
		}
		
		/**
		 * Проверить на требование обратного условия
		 * @param string $condition
		 */
		protected function opposite_check(&$condition)
		{
			$this->_opposite = false;
			if (is_string($condition) && substr($condition, 0, 1) == '!') {
				$condition = trim(substr($condition, 1));
				$this->_opposite = true;
			}
		}

		/**
		 * Ключ существует
		 */
		protected function _keyExists()
		{
			$actual = $this->_actual_ar[$this->_param_key];
			$expected = array_key_exists(0, $this->_conditions_ar) ? $this->_conditions_ar[0] : null;

			if ($expected === self::CURRENT) {
				$expected = $actual; // Для конструкций типа ->param(AMatch::CURRENT, 'true')
			} else if ($expected === self::OPTIONAL) { // Для конструкций типа ->param(AMatch::OPTIONAL, 'is_array') — ключ отсутствует или является массивом
				$expected = false;
			}

			if (empty($this->_conditions_ar)) {
				$this->_setTrueResult(AMatchStatus::KEY_EXISTS);

				// Передан один параметр — значит простое сравнение
			} elseif (count($this->_conditions_ar) == 1) {

				$with_type = is_null($expected); // Для null сразу проверяем с типизацией
				$this->_validateTwoValues($expected, $actual, $with_type);
			} else {
				$condition = $this->_conditions_ar[1];
				$this->opposite_check($condition);
				if ($condition === '') { // Передан '!', просто отменяем условие
					$with_type = is_null($expected); // Для null сразу проверяем с типизацией
					$this->_validateTwoValues($expected, $actual, $with_type, $this->_opposite);

				// Extended callback
				} elseif (
					is_string($condition)
						&& (strstr($condition, '->') !== false || strstr($condition, '::') !== false)
					|| is_array($condition) && is_callable($condition)
                    || is_array($condition) && class_exists(__NAMESPACE__. '\\' . $condition[0]) && is_callable(__NAMESPACE__. '\\' . $condition[0] . '::' . $condition[1], true, $condition)
				) {
					// Пример вызова: ->some($callback_arguments, 'MyClass::myfunc')->some($callback_arguments, 'MyClass->myfunc')->some($callback_arguments, array($this, 'myFunc'))
					$this->_callCallback($condition, $actual, $expected);
				} else {
					$this->_conditionValidate($condition, $expected, $actual);
				}
			}
		}

		/**
		 * Остановить сравнение и вернуть конечный результат
		 */
		public function stopMatch()
		{
			$this->_conditions_ar = array();
			// Проверка на ограниченность структуры
			if (
				($this->_result === true || $this->_haveFlag(self::FLAG_DONT_STOP_MATCHING))
				&& ($this->_haveFlag(self::FLAG_STRICT_STRUCTURE) || $this->_haveFlag(self::FLAG_FILTER_STRUCTURE))
			) {
				$actual_ar_keys = array_keys((array)$this->_actual_ar); // Массив присланных параметров
				$params_keys_list = array_keys($this->_params_keys_list); // Получаем уникальный нумерованный массив обработанных ранее ключей
				$diff = array_diff($actual_ar_keys, $params_keys_list); // Получаем ключи, присутствующие только в проверяемом массиве, но не имеющие условий валидации (избыточные)
				$this->_param_key = __FUNCTION__;
				if (empty($diff)) {
					$this->_setTrueResult(AMatchStatus::ALL_PARAMETERS_CHECKED);
				} else {
					if ($this->_haveFlag(self::FLAG_STRICT_STRUCTURE)) {
						$this->_setFalseResult(AMatchStatus::UNKNOWN_PARAMETERS_LIST);
						$this->_param_key = AMatch::_UNKNOWN_PARAMETERS_LIST;
						$this->_setFalseResult(implode("," , $diff));
					} elseif ($this->_haveFlag(self::FLAG_FILTER_STRUCTURE)) {
						foreach ($diff as $unknown_key) {
							unset($this->_actual_ar[$unknown_key]);
						}
					}
				}
			}

			return ($this->_result === null) ? true : $this->_result; // Ни одного условия не определено, следовательно массив считается корректным
		}

		/**
		 * Вернуть результаты выполнения сопоставлений
		 *
		 * @return string
		 */
		public function matchResults()
		{
		
			return $this->_result_ar;
		}

		/**
		 * Вернуть комментарий к результату
		 *
		 * @return string
		 */
		public function matchComments()
		{
			$comments_ar = array();
			foreach ($this->_result_ar as $param => $status_code) {
				$comments_ar[$param] = $this->_status_obj->getComment($status_code);
			}

			return $comments_ar;
		}

		/**
		 * Вернуть условия, к которым применены соответствующие комментарии
		 */
		public function matchCommentsConditions()
		{

			return $this->_comment_conditions_ar;
		}

		/**
		 *
		 * @param string $param_key Вызываемый метод, равный проверяемому ключу массива
		 * @param array $conditions_ar Параметры [ ожидаемое значение | условие сопоставления ]
		 */
		public function __call($param_key, $conditions_ar)
		{
			$this->_param_key = $param_key;
			$this->_params_keys_list[$param_key] = $param_key;
			$this->_conditions_ar = $conditions_ar;
			$this->_user_error_status = array_key_exists(2, $this->_conditions_ar) ? $this->_conditions_ar[2] : null;

			// Если в одном из предыдущих звеньях цепочки условие не было выполнено,
			// не анализируем следующие условия
			if ($this->_result === false && !$this->_haveFlag(self::FLAG_DONT_STOP_MATCHING)) {
				
				return $this;
			}
			if (array_key_exists($param_key, $this->_actual_ar)) {
				$this->_keyExists();
			} else {
				$this->_keyNotExists();
			}
			
			return $this;
		}

		/**
		 * Проверить существование определённого флага сопоставления
		 * @param integer $flag
		 */
		protected function _haveFlag($flag)
		{
			return ($this->_flags & $flag) > 0;
		}

		/**
		 * Является ли значение — float (в том числе допустимы integer & 0)
		 * @param mixed $val
		 * @link http://ua.php.net/manual/en/function.is-float.php#107917
		 * @return bool
		 */
		protected function _isTrueFloat($val)
		{
			$pattern = '/^[-+]?(((\d+)\.?(\d+)?)|\.\d+)([eE]?[+-]?\d+)?$/';
			/*@TODO: $pattern = '/^[-+]?(?>\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?$/'; — test speed difference*/

			return (!is_bool($val) && (is_float($val) || preg_match($pattern, trim($val))));
		}
	}