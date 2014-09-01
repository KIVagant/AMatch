<?php

	/**
	 * Класс-реестр всех статусов сопоставлений и их расшифровок
	 * При создании плагинов рекомендуется добавлять сюда их коды и расшифровки
	 *
	 * @package AMatch
	 * @see AMatch
	 * @author KIVagant <KIVagant@gmail.com>
	 */
	class AMatchStatus
	{
		// Ошибки
		const BAD_STATUSES_CLASS = 0;
		const KEY_NOT_EXISTS = 1;
		const CONDITION_IS_UNKNOWN = 2;
		const KEY_TYPE_NOT_VALID = 3;
		const KEY_CONDITION_NOT_VALID = 4;
		const EXPECTED_NOT_IS_ARRAY = 5;
		const UNKNOWN_PARAMETERS_LIST = 6;
		const ACTUAL_NOT_IS_ARRAY = 7;
		const CALLBACK_NOT_CALLABLE = 8;
		const CALLBACK_NOT_VALID = 9;
		const MATCHING_DATA_NOT_ARRAY = 10;
		
		// Успешные условия
		const KEY_NOT_EXISTS_OPTIONAL = 100;
		const KEY_EXISTS = 101;
		const KEY_VALID_FULLY = 102;
		const KEY_TYPE_VALID = 103;
		const KEY_CONDITION_VALID = 104;
		const ALL_PARAMETERS_CHECKED = 105;
		
		/*
		 * PLUGIN STATUSES
		 */

		// AMatchString
		const STRING_TOO_SHORT = 'str1';
		const STRING_TOO_LONG = 'str2';
		const REGEXP_FAILURE = 'str3';
		const STRING_IS_NOT_EMAIL = 'str4';
		const ACTUAL_IS_NOT_STRING = 'str5';
		
		// AMatchArray
		const ACTUAL_IS_NOT_ARRAY = 'arr1';
		const ARRAY_OF_INTS_REQUIRED = 'arr2';
		const ARRAY_OF_INTS_KEYS_REQUIRED = 'arr9';

		const EMPTY_ARRAY_CLASSIC = 'arr3';
		const EMPTY_ARRAY_FIRST_ELEMENT = 'arr4';
		const EMPTY_ARRAY_SOME_ELEMENT = 'arr5';

		const NON_EMPTY_ARRAY_CLASSIC = 'arr6';
		const NON_EMPTY_ARRAY_FIRST_ELEMENT = 'arr7';
		const NON_EMPTY_ARRAY_SOME_ELEMENT = 'arr8';
		
		/**
		 * Комментарии к ошибкам
		 *
		 * @var integer
		 */
		protected $_result_comments = array();

		public function __construct()
		{
			$this->_fillComments();
		}

		/**
		 * Наполнить массив комментариев.
		 * Можно переопределить в наследнике и получить собственный маппинг ошибок
		 */
		protected function _fillComments()
		{
			$this->_result_comments[self::BAD_STATUSES_CLASS] = 'AMatch statuses class must be instance of AMatchStatus';
			$this->_result_comments[self::KEY_NOT_EXISTS] = 'Expected parameter does not exist in the array of parameters';
			$this->_result_comments[self::CONDITION_IS_UNKNOWN] = 'Condition is unknown';
			$this->_result_comments[self::KEY_TYPE_NOT_VALID] = 'Expected parameter type is not valid';
			$this->_result_comments[self::KEY_CONDITION_NOT_VALID] = 'Condition is not valid';
			$this->_result_comments[self::EXPECTED_NOT_IS_ARRAY] = 'Expected not is array';
			$this->_result_comments[self::UNKNOWN_PARAMETERS_LIST] = 'Unknown parameters in the input data';
			$this->_result_comments[self::ACTUAL_NOT_IS_ARRAY] = 'Actual not is array';
			$this->_result_comments[self::CALLBACK_NOT_CALLABLE] = 'Expected value not is callable';
			$this->_result_comments[self::CALLBACK_NOT_VALID] = 'Callable method return bad result';
			$this->_result_comments[self::MATCHING_DATA_NOT_ARRAY] = 'Incoming data is not an array';

			//
			$this->_result_comments[self::KEY_NOT_EXISTS_OPTIONAL] = 'OK. Expected optional parameter does not exist';
			$this->_result_comments[self::KEY_EXISTS] = 'OK. Expected parameter exist in the array of parameters';
			$this->_result_comments[self::KEY_VALID_FULLY] = 'OK. Expected parameter is fully valid';
			$this->_result_comments[self::KEY_TYPE_VALID] = 'OK. Expected parameter type is valid';
			$this->_result_comments[self::KEY_CONDITION_VALID] = 'OK. Condition is valid';
			$this->_result_comments[self::ALL_PARAMETERS_CHECKED] = 'The array does not contains unknown parameters';

			/*
			 * PLUGIN STATUSES
			 */
			// AMatchString
			$this->_result_comments[self::STRING_TOO_SHORT] = 'String is too short';
			$this->_result_comments[self::STRING_TOO_LONG] = 'String is too long';
			$this->_result_comments[self::REGEXP_FAILURE] = 'The string does not match the regular expression';
			$this->_result_comments[self::STRING_IS_NOT_EMAIL] = 'Incorrect email';
			$this->_result_comments[self::ACTUAL_IS_NOT_STRING] = 'String required';

			// AMatchArray
			$this->_result_comments[self::ACTUAL_IS_NOT_ARRAY] = 'Array required';
			$this->_result_comments[self::ARRAY_OF_INTS_REQUIRED] = 'The array must contain only items of type integer';
			$this->_result_comments[self::ARRAY_OF_INTS_KEYS_REQUIRED] = 'The array must contain only keys of type integer';
			$this->_result_comments[self::EMPTY_ARRAY_CLASSIC] = 'Array must be empty';
			$this->_result_comments[self::EMPTY_ARRAY_FIRST_ELEMENT] = 'The array must be empty or contain a one empty element';
			$this->_result_comments[self::EMPTY_ARRAY_SOME_ELEMENT] = 'All elements in array must be empty';
			$this->_result_comments[self::NON_EMPTY_ARRAY_CLASSIC] = 'Array must be non-empty';
			$this->_result_comments[self::NON_EMPTY_ARRAY_FIRST_ELEMENT] = 'The array must contain a non-empty element or more than one element';
			$this->_result_comments[self::NON_EMPTY_ARRAY_SOME_ELEMENT] = 'At least one element of the array must be non-empty';
		}

		/**
		 * Получить комментарий по коду статуса
		 *
		 * @param mixed $status_code
		 * @return string
		 */
		public function getComment($status_code)
		{
			return array_key_exists($status_code, $this->_result_comments)
				? $this->_result_comments[$status_code]
				: $status_code;
		}
	}