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
		 * @param integer $max_lenght
		 */
		public static function maxLenght($actual, $param_name, $max_lenght = null)
		{
			$lenght = mb_strlen($actual, self::$_encoding);
			$result = $lenght <= $max_lenght;
			$comments = $result ? null : self::STRING_TOO_LONG;

			return array($result, $comments);
		}
		
		/**
		 * Проверка минимальной длины строки $actual
		 *
		 * @param mixed $actual Актуальное значение
		 * @param string $param_name Имя анализируемого параметра, отправленного в callback
		 * @param integer $min_lenght
		 */
		public static function minLenght($actual, $param_name, $min_lenght = null)
		{
			$lenght = mb_strlen($actual, self::$_encoding);
			$result = $lenght >= $min_lenght;
			$comments = $result ? null : self::STRING_TOO_SHORT;

			return array($result, $comments);
		}
		
		/**
		 * Соответствует ли $actual указанной длине строки
		 *
		 * @param mixed $actual Актуальное значение
		 * @param string $param_name Имя анализируемого параметра, отправленного в callback
		 * @param integer $expected_lenght
		 */
		public static function lenght($actual, $param_name, $expected_lenght = null)
		{
			$lenght = mb_strlen($actual, self::$_encoding);
			$result = $lenght == $expected_lenght;
			$comments = $result
				? null
				: (
					$lenght < $expected_lenght
						? self::STRING_TOO_SHORT
						: self::STRING_TOO_LONG
				)
			;
		
			return array($result, $comments);
		}
	}