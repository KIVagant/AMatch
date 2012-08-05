<?php
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
	 *
	 * //Цепочки:
	 * AMatch::runMatch($actual_ar)->doc_id()->subject_id()->...->stopMatch();
	 * </code>
	 *
	 * @package AMatch
	 * @author KIVagant. Special thanks to Andrew Tereschenko, Andrew Lugovoi for ideas.
	 * @see AMatchTest
	 *
	 */
	class AMatch
	{
		// Успешные условия
		const KEY_NOT_EXISTS_OPTIONAL = 'OK. Expected optional parameter does not exist';
		const KEY_EXISTS = 'OK. Expected parameter exist in the array of parameters';
		const KEY_VALID_FULLY = 'OK. Expected parameter is fully valid';
		const KEY_TYPE_VALID = 'OK. Expected parameter type is valid';
		const KEY_CONDITION_VALID = 'OK. Condition is valid';
		const ALL_PARAMETERS_CHECKED = 'The array does not contains unknown parameters';

		// Ошибки
		const KEY_NOT_EXISTS = 'Expected parameter does not exist in the array of parameters';
		const CONDITION_IS_UNKNOWN = 'Condition is unknown';
		const KEY_TYPE_NOT_VALID = 'Expected parameter type is not valid';
		const KEY_CONDITION_NOT_VALID = 'Condition is not valid';
		const EXPECTED_NOT_IS_ARRAY = 'Expected not is array';
		const UNKNOWN_PARAMETERS_LIST = 'Unknown parameters in the input data';
		const ACTUAL_NOT_IS_ARRAY = 'Actual not is array';
		const CALLBACK_NOT_CALLABLE = 'Expected value not is callable';
		const CALLBACK_NOT_VALID = 'Callable method return bad result';
		const MATCHING_DATA_NOT_ARRAY = 'Incoming data is not an array';
		const _UNKNOWN_PARAMETERS_LIST = 'Unknown parameters:';
		const OPTIONAL_SKIPPED = 'Optional parameter, skipped bad condition result';

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
		 * Не останавливать сопоставление даже если обнаружено условие несоответствия.
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
		 * Комментарий к результату. Статический, чтобы был доступен извне
		 *
		 * @var array key=>comment
		 */
		protected $_comment_ar = array();

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
		 * Можно передать массив актуальных параметров прямо в конструктор
		 *
		 * @param bitmask $flags Флаги сопоставления
		 * @param array $actual_ar
		 */
		public function __construct($actual_ar = array(), $flags = self::NO_FLAGS)
		{
			$this->_flags = $flags;
			if (!is_array($actual_ar)) {
				$this->_param_key = 'runMatch';
				$this->_setFalseResult(self::MATCHING_DATA_NOT_ARRAY);
				$this->_actual_ar = array();
			} else {
				$this->_actual_ar = $actual_ar;
			}
		}

		/**
		 * Сравнить актуальные параметры с запрашиваемыми
		 *
		 * @param array $actual_ar
		 * @param bitmask $flags Флаги сопоставления
		 * @return AMatch Возвращает объект для использования цепочки вызова
		 */
		public static function runMatch($actual_ar, $flags = self::NO_FLAGS)
		{
			return new AMatch($actual_ar, $flags);
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

				// Регистрируем в списке актуальных параметров для прохождения проверки на неполноту структуры
				$this->_actual_ar[$this->_param_key] = null;
				$this->_setTrueResult(self::KEY_NOT_EXISTS_OPTIONAL);
			} else {
				$this->_setFalseResult(self::KEY_NOT_EXISTS);
			}
		}

		/**
		 * Установить успешный результат условия
		 * @param string $comment Комментарий к хорошему результату
		 * @param string $comment Условие на момент комментария к хорошему результату
		 */
		protected function _setTrueResult($comment, $comment_conditions = null)
		{
			// Результат правдивый только если ранее не был установлен в другое значение
			$this->_result = is_null($this->_result) ? true : $this->_result;
			
			// Показывать комментарий к хорошему результату только если включён такой флаг
			if ($this->_haveFlag(self::FLAG_SHOW_GOOD_COMMENTS)) {
				// Хороший комментарий присоединяем только если ранее не было любого другого
				$this->_comment_ar[$this->_param_key] = empty($this->_comment_ar[$this->_param_key])
					? $comment
					: $this->_comment_ar[$this->_param_key];
				$this->_comment_conditions_ar[$this->_param_key] = empty($this->_comment_conditions_ar[$this->_param_key])
					? (
						$comment_conditions
							? $comment_conditions
							: $this->_conditions_ar
					) : $this->_comment_conditions_ar[$this->_param_key];
			}
		}

		/**
		 * Установить безуспешный результат условия (или успешный, если это опциональный параметр)
		 * @param string $comment Комментарий к ошибке сопоставления
		 * * @param string $comment Условие на момент комментария к ошибке сопоставления
		 */
		protected function _setFalseResult($comment, $comment_conditions = null)
		{
			if (isset($this->_params_keys_list_optional[$this->_param_key])) {
				$this->_result = true;
				if ($this->_haveFlag(self::FLAG_SHOW_GOOD_COMMENTS)) {
					$this->_comment_ar[$this->_param_key] = self::OPTIONAL_SKIPPED; // Перебиваем ранние комментарии
					$this->_comment_conditions_ar[$this->_param_key] = $comment_conditions
						? $comment_conditions
						: $this->_conditions_ar; // Перебиваем ранние условия для комментариев
				}
			} else {
				$this->_result = false;
				$this->_comment_ar[$this->_param_key] = $comment; // Перебиваем ранние комментарии
				$this->_comment_conditions_ar[$this->_param_key] = $comment_conditions
					? $comment_conditions
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
					$this->_setTrueResult(self::KEY_CONDITION_VALID);
				} elseif ($with_type && $first !== $second) {
					$this->_setTrueResult(self::KEY_VALID_FULLY);
				} else {
					$this->_setFalseResult(self::KEY_CONDITION_NOT_VALID);
				}
			} else {
				if (!$with_type && $first == $second) {
					$this->_setTrueResult(self::KEY_CONDITION_VALID);
				} elseif ($with_type && $first === $second) {
					$this->_setTrueResult(self::KEY_VALID_FULLY);
				} else {
					$this->_setFalseResult(self::KEY_CONDITION_NOT_VALID);
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
				$this->_setTrueResult(self::KEY_CONDITION_VALID);
			} else {
				if ($or_equal) {
					$this->_validateTwoValues($first, $second);
				} else {
					$this->_setFalseResult(self::KEY_CONDITION_NOT_VALID);
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
				$this->_setTrueResult(self::KEY_CONDITION_VALID);
			} else {
				if ($or_equal) {
					$this->_validateTwoValues($first, $second);
				} else {
					$this->_setFalseResult(self::KEY_CONDITION_NOT_VALID);
				}
			}
		}

		/**
		 * Сообщить о результате сопоставления типов
		 * @param bool $bool успех/неуспех
		 */
		protected function _typeMsg($bool)
		{
			$bool = ($this->_opposite) ? !$bool : $bool;
			if ($bool) {
				$this->_setTrueResult(self::KEY_TYPE_VALID);
			} else {
				$this->_setFalseResult(self::KEY_TYPE_NOT_VALID);
			}
		}

		/**
		 * Сообщить о результате сопоставления
		 * @param bool $bool успех/неуспех
		 * @param bool $replace_comment Собственный комментарий (из callback-методов, например)
		 * @param bool $replace_comment_conditions Собственные условия к комментариям (из callback-методов, например)
		 */
		protected function _conditionMsg($bool, $replace_comment = null, $replace_comment_conditions = null)
		{
			$bool = ($this->_opposite) ? !$bool : $bool;
			if ($bool) {
				$comment = $replace_comment ? $replace_comment : self::KEY_CONDITION_VALID;
				$this->_setTrueResult($comment, $replace_comment_conditions);
			} else {
				$comment = $replace_comment ? $replace_comment : self::KEY_CONDITION_NOT_VALID;
				$this->_setFalseResult($comment, $replace_comment_conditions);
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
				case 'long':
				case 'is_long':
					$this->_typeMsg(is_int($actual));
					break;
				case 'float':
				case 'floatval':
				case 'is_float':
				case 'double':
				case 'is_double':
				case 'real':
				case 'is_real':
					$this->_typeMsg(is_float($actual));
					break;
				case 'array':
				case 'is_array':
					$this->_typeMsg(is_array($actual));
					break;
				case 'bool':
				case 'boolean':
				case 'is_bool':
				case 'is_boolean':
					$this->_typeMsg(is_array($actual));
					break;
				case 'null':
				case 'is_null':
					$this->_typeMsg(is_null($actual));
					break;
				case 'numeric':
				case 'is_numeric':
					$this->_typeMsg(is_numeric($actual));
					break;
				case 'scalar':
				case 'is_scalar':
					$this->_typeMsg(is_scalar($actual));
					break;
				case 'string':
				case 'is_string':
					$this->_typeMsg(is_string($actual));
					break;
				case 'object':
				case 'is_object':
					$this->_typeMsg(is_object($actual));
					break;
				case 'instance':
				case 'instanceof':
					$this->_conditionMsg(is_object($actual) && ($actual instanceof $expected));
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
							$this->_setTrueResult(self::KEY_CONDITION_VALID);
						} else {
							$this->_setFalseResult(self::ACTUAL_NOT_IS_ARRAY);
						}
						break;
					}
					$this->_conditionMsg(in_array($expected, $actual));
					break;
				case 'in_left_array':
				case 'in_expected_array':
					if (!is_array($expected)) {
						if ($this->_opposite) {
							$this->_setTrueResult(self::KEY_CONDITION_VALID);
						} else {
							$this->_setFalseResult(self::EXPECTED_NOT_IS_ARRAY);
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
					if (is_callable($expected)) {
						$callback_result = call_user_func($expected, $actual);
						if (is_array($callback_result) && count($callback_result) <= 3) { // (bool, comments, comment_conditions)
							if (isset($callback_result[1]) && is_array($callback_result[1])) {
								if (isset($callback_result[2]) && is_array($callback_result[2])) {
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
							$this->_setFalseResult(self::CALLBACK_NOT_VALID);
						}
					} else {
						$this->_setFalseResult(self::CALLBACK_NOT_CALLABLE);
					}
					break;
				default:
					$this->_setFalseResult(self::CONDITION_IS_UNKNOWN);
					break;
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
				$this->_setTrueResult(self::KEY_EXISTS);

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
				&& $this->_haveFlag(self::FLAG_STRICT_STRUCTURE)
			) {
				$actual_ar_keys = array_keys((array)$this->_actual_ar); // Массив присланных параметров
				$params_keys_list = array_keys($this->_params_keys_list); // Получаем уникальный нумерованный массив обработанных ранее ключей
				$diff = array_diff($actual_ar_keys, $params_keys_list); // Получаем ключи, присутствующие только в проверяемом массиве, но не имеющие условий валидации (избыточные)
				$this->_param_key = __FUNCTION__;
				if (empty($diff)) {
					$this->_setTrueResult(self::ALL_PARAMETERS_CHECKED);
				} else {
					$this->_setFalseResult(self::UNKNOWN_PARAMETERS_LIST);
					$this->_param_key = self::_UNKNOWN_PARAMETERS_LIST;
					$this->_setFalseResult(implode("," , $diff));
				}
			}

			return $this->_result;
		}

		/**
		 * Вернуть комментарий к результату
		 *
		 * @return string
		 */
		public function matchComments()
		{
		
			return $this->_comment_ar;
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
	}