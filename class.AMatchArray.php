<?php
	/**
	 * callback-методы для валидации массивов
	 *
	 * @package AMatch
	 * @author KIVagant
	 * @see AMatch
	 */
	class AMatchArray
	{
		const ACTUAL_IS_NOT_ARRAY = 'Array needed.';
		const ARRAY_OF_INTS_REQUIRED = 'The array must contain only items of type integer.';

		const EMPTY_ARRAY_CLASSIC = 'Array must be empty.';
		const EMPTY_ARRAY_FIRST_ELEMENT = 'The array must be empty or contain a one empty element.';
		const EMPTY_ARRAY_SOME_ELEMENT = 'All elements in array must be empty (recursive).';

		const NON_EMPTY_ARRAY_CLASSIC = 'Array must be non-empty.';
		const NON_EMPTY_ARRAY_FIRST_ELEMENT = 'The array must contain a non-empty element or more than one element.';
		const NON_EMPTY_ARRAY_SOME_ELEMENT = 'At least one element of the array must be non-empty.';
		
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
					$comments = $result ? null : self::EMPTY_ARRAY_FIRST_ELEMENT;
					break;
				case self::FLAG_EMPTY_SOME_ELEMENT:
					$result = self::_isEmptyRecursive($actual);
					$comments = $result ? null : self::EMPTY_ARRAY_SOME_ELEMENT;
					break;
				case self::FLAG_EMPTY_CLASSIC:
				default:
					$result = empty($actual);
					$comments = $result ? null : self::EMPTY_ARRAY_CLASSIC;
					break;
			}

			return array($result, $comments);
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
					$comments = $result ? null : self::NON_EMPTY_ARRAY_FIRST_ELEMENT;
					break;
				case self::FLAG_EMPTY_SOME_ELEMENT:
					$result = !self::_isEmptyRecursive($actual);
					$comments = $result ? null : self::NON_EMPTY_ARRAY_SOME_ELEMENT;
					break;
				case self::FLAG_EMPTY_CLASSIC:
				default:
					$result = !empty($actual);
					$comments = $result ? null : self::NON_EMPTY_ARRAY_CLASSIC;
					break;
			}
			
			return array($result, $comments);
		}

		/**
		 * Массив должен содержать только значения с типом int или быть пустым
		 *
		 * @param array $actual Актуальное значение
		 * @param string $param_name Имя анализируемого параметра, отправленного в callback
		 * @return array [result|comments]
		 */
		public static function onlyIntegerValues($actual, $param_name)
		{
			$result = true;
			if (is_array($actual)) {
				foreach ($actual as $k => $v) {
					if (!((is_string($v) || is_numeric($v)) && preg_match('/^-?\d+$/', $v))) {
						$result = false;
						break;
					}
				}
				$comments = $result ? null : self::ARRAY_OF_INTS_REQUIRED;
			} else {
				$result = false;
				$comments = self::ACTUAL_IS_NOT_ARRAY;
			}

			return array($result, $comments);
		}
	}