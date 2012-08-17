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
		const STRING_TOO_SHORT = 'String is too short';
		const STRING_TOO_LONG = 'String is too long';
		const REGEXP_FAILURE = 'The string does not match the regular expression';
		const STRING_IS_NOT_EMAIL = 'Incorrect email';

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
			$comments_conditions = array($max_length, __METHOD__);

			return array($result, $comments, $comments_conditions);
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
			$comments_conditions = array($min_length, __METHOD__);

			return array($result, $comments, $comments_conditions);
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
			$comments_conditions = array($expected_length, __METHOD__);
		
			return array($result, $comments, $comments_conditions);
		}

		/**
		 * Проверка на соответствие строки регулярному выражению
		 *
		 * @param mixed $actual Актуальное значение
		 * @param string $param_name Имя анализируемого параметра, отправленного в callback
		 * @param string $regexp Регулярное выражение
		 * @return array [result|comments]
		 */
		public static function pregMatch($actual, $param_name, $regexp)
		{
			$result = is_string($actual) && preg_match($regexp, $actual);
			$comments = $result ? null : self::REGEXP_FAILURE;
			$comments_conditions = array($regexp, __METHOD__);
		
			return array($result, $comments, $comments_conditions);
		}

		/**
		 * Является ли строка email-ом
		 *
		 * @param mixed $actual Актуальное значение
		 * @param string $param_name Имя анализируемого параметра, отправленного в callback
		 * @param string $regexp Собственное регулярное выражение вместо проверки на FILTER_VALIDATE_EMAIL
		 * @return array [result|comments]
		 */
		public static function isEmail($actual, $param_name, $regexp = null)
		{
			if (empty($regexp)) {
				$result = filter_var($actual, FILTER_VALIDATE_EMAIL);
			} else {
				$result = is_string($actual) && preg_match($regexp, $actual);
			}
			$comments = $result ? null : self::STRING_IS_NOT_EMAIL;
			$comments_conditions = array($actual, __METHOD__);
		
			return array($result, $comments, $comments_conditions);
		}
	}