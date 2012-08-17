<?php
	/**
	 * callback-методы для валидации строк
	 *
	 * @package AMatch
	 * @author KIVagant
	 * @see AMatch
	 */
	class AMatchString
	{
		protected static $_encoding = 'UTF-8';
		const STRING_TOO_SHORT = 'Text is too short';
		const STRING_TOO_LONG = 'Text is too long';

		/**
		 * Поменять кодировку по-умолчанию
		 * @param string $encoding
		 */
		public static function setEncoding($encoding)
		{
			self::$_encoding = $encoding;
		}

		/**
		 * Проверка максимальной длины строки $actual
		 *
		 * @param mixed $actual Актуальное значение
		 * @param string $param_name Имя анализируемого параметра, отправленного в callback
		 * @param integer $max_length
		 */
		public static function maxLength($actual, $param_name, $max_length = null)
		{
			$length = mb_strlen($actual, self::$_encoding);
			$result = $length <= $max_length;
			$comments = $result ? null : self::STRING_TOO_LONG;

			return array($result, $comments);
		}
		
		/**
		 * Проверка минимальной длины строки $actual
		 *
		 * @param mixed $actual Актуальное значение
		 * @param string $param_name Имя анализируемого параметра, отправленного в callback
		 * @param integer $min_length
		 */
		public static function minLength($actual, $param_name, $min_length = null)
		{
			$length = mb_strlen($actual, self::$_encoding);
			$result = $length >= $min_length;
			$comments = $result ? null : self::STRING_TOO_SHORT;

			return array($result, $comments);
		}
		
		/**
		 * Соответствует ли $actual указанной длине строки
		 *
		 * @param mixed $actual Актуальное значение
		 * @param string $param_name Имя анализируемого параметра, отправленного в callback
		 * @param integer $expected_length
		 */
		public static function length($actual, $param_name, $expected_length = null)
		{
			$length = mb_strlen($actual, self::$_encoding);
			$result = $length == $expected_length;
			$comments = $result
				? null
				: (
					$length < $expected_length
						? self::STRING_TOO_SHORT
						: self::STRING_TOO_LONG
				)
			;
		
			return array($result, $comments);
		}
	}