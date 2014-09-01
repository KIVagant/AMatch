<?php
	/**
	 * callback-методы для валидации массивов
	 *
	 * @package AMatch
	 * @author KIVagant <KIVagant@gmail.com>
	 * @see AMatch
	 */
	class AMatchArray
	{
		/**
		 * Классическая проверка на пустоту (провалит array( 0 => '' ))
		 * @var integer
		 */
		const FLAG_EMPTY_CLASSIC = 1;

		/**
		 * Если есть только один элемент — он не должен быть пустым, в т.ч. пустым массивом
		 * @var integer
		 */
		const FLAG_EMPTY_FIRST_ELEMENT = 2;

		/**
		 * Хотя бы один элемент массива не должен быть пустым, в том числе во вложенных массивах
		 * @var integer
		 */
		const FLAG_EMPTY_SOME_ELEMENT = 3;

		protected static function _isEmptyFirstElement($array)
		{
			if (is_array($array) && count($array) == 0) {

				return true;
			} elseif (is_array($array) && count($array) == 1) {
				$first_element = array_pop($array);

				return self::_isEmptyFirstElement($first_element);
			} elseif (!is_array($array) && empty($array)) {

				return true;
			}

			return false;
		}

		protected static function _isEmptyRecursive($array)
		{
			if (empty($array)) {
				return true;
			}
			if (is_array($array)) {
				foreach ($array as $value) {
					if (is_array($value)) {
	
						return self::_isEmptyRecursive($value);
					} elseif (!empty($value)) {
	
						return false;
					}
				}
			}

			return true;
		}

		/**
		 * Проверка массива на пустоту
		 *
		 * @param mixed $actual Актуальное значение
		 * @param string $param_name Имя анализируемого параметра, отправленного в callback
		 * @param integer $max_length
		 * @param integer $flag Принцип проверки на пустоту
		 * @return array [result|comments]
		 */
		public static function isEmpty($actual, $param_name, $flag = 1)
		{
			switch ($flag) {
				case self::FLAG_EMPTY_FIRST_ELEMENT:
					$result = self::_isEmptyFirstElement($actual);
					$comments = $result ? null : AMatchStatus::EMPTY_ARRAY_FIRST_ELEMENT;
					break;
				case self::FLAG_EMPTY_SOME_ELEMENT:
					$result = self::_isEmptyRecursive($actual);
					$comments = $result ? null : AMatchStatus::EMPTY_ARRAY_SOME_ELEMENT;
					break;
				case self::FLAG_EMPTY_CLASSIC:
				default:
					$result = empty($actual);
					$comments = $result ? null : AMatchStatus::EMPTY_ARRAY_CLASSIC;
					break;
			}
			$comments_conditions = array(null, __METHOD__);

			return array($result, $comments, $comments_conditions);
		}

		/**
		 * Проверка массива на НЕ-пустоту
		 *
		 * @param mixed $actual Актуальное значение
		 * @param string $param_name Имя анализируемого параметра, отправленного в callback
		 * @param integer $max_length
		 * @param integer $flag Принцип проверки на пустоту
		 * @return array [result|comments]
		 */
		public static function isNotEmpty($actual, $param_name, $flag = 1)
		{
			switch ($flag) {
				case self::FLAG_EMPTY_FIRST_ELEMENT:
					$result = !self::_isEmptyFirstElement($actual);
					$comments = $result ? null : AMatchStatus::NON_EMPTY_ARRAY_FIRST_ELEMENT;
					break;
				case self::FLAG_EMPTY_SOME_ELEMENT:
					$result = !self::_isEmptyRecursive($actual);
					$comments = $result ? null : AMatchStatus::NON_EMPTY_ARRAY_SOME_ELEMENT;
					break;
				case self::FLAG_EMPTY_CLASSIC:
				default:
					$result = !empty($actual);
					$comments = $result ? null : AMatchStatus::NON_EMPTY_ARRAY_CLASSIC;
					break;
			}
			$comments_conditions = array(null, __METHOD__);
			
			return array($result, $comments, $comments_conditions);
		}

		/**
		 * Массив должен содержать только значения с типом int (longint) или быть пустым
		 *
		 * @param array $actual Актуальное значение
		 * @param string $param_name Имя анализируемого параметра, отправленного в callback
		 * @return array [result|comments]
		 */
		public static function onlyIntegerValues($actual, $param_name)
		{
			$result = true;
			$bad_key = $bad_value = null;
			if (is_array($actual)) {
				foreach ($actual as $k => $v) {
					if (!((is_string($v) || is_numeric($v)) && preg_match('/^-?\d+$/', $v))) {
						$bad_key = $k;
						$bad_value = $v;
						$result = false;
						break;
					}
				}
				$comments = $result ? null : AMatchStatus::ARRAY_OF_INTS_REQUIRED;
				$comments_conditions = $bad_key
					? array($bad_key . '=>' . $bad_value, __METHOD__)
					: array(null, __METHOD__);
			} else {
				$result = false;
				$comments = AMatchStatus::KEY_TYPE_NOT_VALID;
				$comments_conditions = array('array', __METHOD__);
			}

			return array($result, $comments, $comments_conditions);
		}

		/**
		 * Массив должен содержать ключи только с типом int (longint) или быть пустым
		 *
		 * @todo Добавить флаги последовательностей
		 * @param array $actual Актуальное значение
		 * @param string $param_name Имя анализируемого параметра, отправленного в callback
		 * @return array [result|comments]
		 */
		public static function onlyIntegerKeys($actual, $param_name)
		{
			$result = true;
			$bad_key = $bad_value = null;
			if (is_array($actual)) {
				foreach ($actual as $k => $v) {
					if (!((is_string($k) || is_numeric($k)) && preg_match('/^-?\d+$/', $k))) {
						$bad_key = $k;
						$bad_value = $v;
						$result = false;
						break;
					}
				}
				$comments = $result ? null : AMatchStatus::ARRAY_OF_INTS_KEYS_REQUIRED;
				$comments_conditions = $bad_key
				? array($bad_key . '=>' . $bad_value, __METHOD__)
				: array(null, __METHOD__);
			} else {
				$result = false;
				$comments = AMatchStatus::KEY_TYPE_NOT_VALID;
				$comments_conditions = array('array', __METHOD__);
			}

			return array($result, $comments, $comments_conditions);
		}
	}