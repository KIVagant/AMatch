<?php
	require_once('class.AMatch.php');
	require_once('class.AMatchString.php');
	require_once('class.AMatchArray.php');

	/**
	 * PHPUnit Test Class for AMatch
	 * @package AMatch
	 * @author KIVagant
	 * @see AMatch
	 */
	class AMatchTest extends PHPUnit_Framework_TestCase
	{

		/**
		 * Набор параметров, отправляемых на анализ
		 * @var array
		 */
		public static $actual_params = array(
			'doc_id' => 133,
			'subject_id' => '64',
			'parent_id' => 32,
			'title' => 'Actual document',
			'empty_key' => false,
			'empty_key2' => 'false',
			'longlong' => '-1417234879143578612343412341252314123',
			'data' => array(
				'key1' => 'data1',
				'key2' => 'data2',
				'key3' => 'data3',
				'key4' => false,
			),
		);
		
		public function testNotArray()
		{
			$result = AMatch::runMatch('bad input data')->doc_id('', 'fukaka');
			$this->assertFalse($result->stopMatch());
			$this->assertEquals(array(
			'runMatch' => AMatch::MATCHING_DATA_NOT_ARRAY
			), $result->matchComments());
		}

		public function testBadCondition()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params)->doc_id('', 'fukaka');
			$this->assertFalse($result->stopMatch());
			$this->assertEquals(array(
				'doc_id' => AMatch::CONDITION_IS_UNKNOWN
			), $result->matchComments());
		}

		public function testBadKeyNotExists()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params)->bad_key();
			$this->assertFalse($result->stopMatch());
			$this->assertEquals(array(
				'bad_key' => AMatch::KEY_NOT_EXISTS
			),
				$result->matchComments());
		}

		public function testKeyExists()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)->doc_id();
			$this->assertTrue($result->stopMatch());
			$this->assertEquals(array(
				'doc_id' => AMatch::KEY_EXISTS
			), $result->matchComments());
		}

		public function testLastKeyNotExists()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)->doc_id()->parent_id()->bad_key()
				;
			$expected_ar = array(
				'doc_id' => AMatch::KEY_EXISTS,
				'parent_id' => AMatch::KEY_EXISTS,
				'bad_key' => AMatch::KEY_NOT_EXISTS
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());
		}

		public function testValidAndNotValidCondition()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)->doc_id(133)->subject_id(20);
			$expected_ar = array(
				'doc_id' => AMatch::KEY_CONDITION_VALID,
				'subject_id' => AMatch::KEY_CONDITION_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)->data(AMatchTest::$actual_params['data']);
			$expected_ar = array(
				'data' => AMatch::KEY_CONDITION_VALID,
			);
			$this->assertTrue($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)->data(array(
				1,
				2
			))
				;
			$expected_ar = array(
				'data' => AMatch::KEY_CONDITION_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());
		}

		public function testFullyValidation()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)->doc_id(133, '===')->subject_id(64,
				'===');
			$expected_ar = array(
				'doc_id' => AMatch::KEY_VALID_FULLY,
				'subject_id' => AMatch::KEY_CONDITION_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)->data(AMatchTest::$actual_params['data'], '===')
				;
			$expected_ar = array(
				'data' => AMatch::KEY_VALID_FULLY,
			);
			$this->assertTrue($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());
		}

		public function testLargerAndSmaller()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)
			->doc_id(120, '<')
			->subject_id(80, '>');
			$expected_ar = array(
				'doc_id' => AMatch::KEY_CONDITION_VALID,
				'subject_id' => AMatch::KEY_CONDITION_VALID,
			);
			$this->assertTrue($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());
		}

		public function testSmallerOrEqual()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)
				->doc_id(133, '=')
				->subject_id(64, '<=')
				->parent_id(32, '>=');
			$expected_ar = array(
				'doc_id' => AMatch::KEY_CONDITION_VALID,
				'subject_id' => AMatch::KEY_CONDITION_VALID,
				'parent_id' => AMatch::KEY_CONDITION_VALID,
			);
			$this->assertTrue($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());
		}

		public function testTypesValid()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)
				->doc_id(false, 'integer')
				->subject_id(false, 'string')
				->parent_id(false, 'integer')
				->title(false, 'string')
				->data(false, 'is_array')
				->empty_key(false, 'bool')
				->empty_key(false, 'smartbool')
				->empty_key2(false, 'smartbool')
				->longlong(false, 'longint')
				;
			$expected_ar = array(
				'doc_id' => AMatch::KEY_TYPE_VALID,
				'subject_id' => AMatch::KEY_TYPE_VALID,
				'parent_id' => AMatch::KEY_TYPE_VALID,
				'title' => AMatch::KEY_TYPE_VALID,
				'data' => AMatch::KEY_TYPE_VALID,
				'empty_key' => AMatch::KEY_TYPE_VALID,
				'empty_key2' => AMatch::KEY_TYPE_VALID,
				'longlong' => AMatch::KEY_TYPE_VALID,
			);
			$this->assertTrue($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());
		}

		public function testTypesNotValid()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params)->doc_id(false, 'string');
			$expected_ar = array(
				'doc_id' => AMatch::KEY_TYPE_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)->title(false, 'array');
			$expected_ar = array(
				'title' => AMatch::KEY_TYPE_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)->data(false, 'object');
			$expected_ar = array(
				'data' => AMatch::KEY_TYPE_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)->parent_id(false, 'smartbool');
			$expected_ar = array(
				'parent_id' => AMatch::KEY_TYPE_NOT_VALID,
			);

			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());
		}

		public function testInArrayAndKeyExists()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)->data('data3', 'in_array');
			$expected_ar = array(
				'data' => AMatch::KEY_CONDITION_VALID,
			);
			$this->assertTrue($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)->data('key2', 'key_exists');
			$expected_ar = array(
				'data' => AMatch::KEY_CONDITION_VALID,
			);
			$this->assertTrue($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());
			
			//
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)->doc_id(array(132, 133, 134), 'in_expected_array');
			$expected_ar = array(
				'doc_id' => AMatch::KEY_CONDITION_VALID,
			);
			$this->assertTrue($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());
		}

		public function testNotInArrayAndKeyNotExists()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params)->data('data16', 'in_array');
			$expected_ar = array(
				'data' => AMatch::KEY_CONDITION_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)->data('key7', 'key_exists');
			$expected_ar = array(
				'data' => AMatch::KEY_CONDITION_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());
			
			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)->doc_id(array(10, 11, 1000), 'in_expected_array');
			$expected_ar = array(
				'doc_id' => AMatch::KEY_CONDITION_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());
		}
		public function testActualAndExpectedNotIsArrays()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params)->doc_id(false, 'in_array');
			$expected_ar = array(
				'doc_id' => AMatch::ACTUAL_NOT_IS_ARRAY,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)->doc_id(false, 'in_expected_array');
			$expected_ar = array(
				'doc_id' => AMatch::EXPECTED_NOT_IS_ARRAY,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());
		}
		public function testInstanceOf()
		{
			$actual_obj = new AMatch(array());
			$result = AMatch::runMatch(array('my_obj' => $actual_obj), AMatch::FLAG_SHOW_GOOD_COMMENTS)->my_obj('AMatch', 'instanceof');
			$expected_ar = array(
				'my_obj' => AMatch::KEY_CONDITION_VALID,
			);
			$this->assertTrue($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());
			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)->doc_id('AMatch', 'instanceof');
			$expected_ar = array(
				'doc_id' => AMatch::KEY_CONDITION_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());
			unset($actual_obj);
		}

		public function testOppositeConditions()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)
			->doc_id(24, '!=')
			->subject_id(array(65, 33), '!in_expected_array')
			->parent_id(1, '!is_string')
			->empty_key(null, '!')
			->data('key9', '!key_exists')
			->data('', '!longint')
			->title('', '!longint')
			;
			$expected_ar = array(
				'doc_id' => AMatch::KEY_CONDITION_VALID,
				'subject_id' => AMatch::KEY_CONDITION_VALID,
				'parent_id' => AMatch::KEY_TYPE_VALID,
				'empty_key' => AMatch::KEY_VALID_FULLY,
				'data' => AMatch::KEY_CONDITION_VALID,
				'title' => AMatch::KEY_TYPE_VALID,
			);
			$this->assertTrue($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)
			->doc_id(133, '!>')
			->empty_key('', '!is_float')
			->data('data2', '!in_array')
			;
			$expected_ar = array(
				'doc_id' => AMatch::KEY_CONDITION_VALID,
				'empty_key' => AMatch::KEY_TYPE_VALID,
				'data' => AMatch::KEY_CONDITION_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)
			->doc_id(133, '!<=')
			;
			$expected_ar = array(
				'doc_id' => AMatch::KEY_CONDITION_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)
			->data('key3', '!key_exists')
			;
			$expected_ar = array(
				'data' => AMatch::KEY_CONDITION_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());
		}

		public function testTrueFalseValidInvalid()
		{
			// проверки на справедливые условия:
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)
			->doc_id(1 > 0, 'valid')
			->doc_id(1 > 0, 'true')
			->doc_id(1 > 0, true)
			->doc_id(1 == 0, 'invalid')
			->doc_id(1 == 0, 'false')
			->doc_id(1 == 0, false)
			->doc_id(AMatch::CURRENT, 'valid') // значение doc_id == true
			->doc_id(AMatch::CURRENT, 'true')
			->doc_id(AMatch::CURRENT, true)
			->empty_key(AMatch::CURRENT, 'invalid') // значение empty_key == false
			->empty_key(AMatch::CURRENT, 'false')
			->empty_key(AMatch::CURRENT, false)
			;

			$expected_ar = array(
				'doc_id' => AMatch::KEY_CONDITION_VALID,
				'empty_key' => AMatch::KEY_CONDITION_VALID,
			);

			$this->assertTrue($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());

			// ложные утверждения:
			$result = AMatch::runMatch(AMatchTest::$actual_params)
			->parent_id(AMatch::CURRENT, false)
			;

			$expected_ar = array(
				'parent_id' => AMatch::KEY_CONDITION_NOT_VALID,
			);

			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)
			->empty_key(AMatch::CURRENT, true)
			;

			$expected_ar = array(
			'empty_key' => AMatch::KEY_CONDITION_NOT_VALID,
			);

			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)
			->empty_key(0, true)
			;

			$expected_ar = array(
			'empty_key' => AMatch::KEY_CONDITION_NOT_VALID,
			);

			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)
			->empty_key(1, false)
			;

			$expected_ar = array(
			'empty_key' => AMatch::KEY_CONDITION_NOT_VALID,
			);

			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());
		}

		/**
		 * Проверка требования жесткой структуры (отсутствие недопустимых параметров)
		 */
		public function testStrictStructure()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_STRICT_STRUCTURE | AMatch::FLAG_SHOW_GOOD_COMMENTS)
			->subject_id(64, '>=')
			->empty_key(AMatch::OPTIONAL, false)
			->bad_key(AMatch::OPTIONAL)
			;

			$expected_ar = array(
			'subject_id' => AMatch::KEY_CONDITION_VALID,
			'empty_key' => AMatch::KEY_CONDITION_VALID,
			'bad_key' => AMatch::KEY_NOT_EXISTS_OPTIONAL,
			'stopMatch' => AMatch::UNKNOWN_PARAMETERS_LIST,
			AMatch::_UNKNOWN_PARAMETERS_LIST => 'doc_id,parent_id,title,empty_key2,longlong,data',
			);

			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());

			$result = AMatch::runMatch(array('a' => 1, 'b' => null), AMatch::FLAG_STRICT_STRUCTURE | AMatch::FLAG_SHOW_GOOD_COMMENTS)
			->a(true)
			->b(false)
			;
			
			$expected_ar = array(
			'a' => AMatch::KEY_CONDITION_VALID,
			'b' => AMatch::KEY_CONDITION_VALID,
			'stopMatch' => AMatch::ALL_PARAMETERS_CHECKED,
			);
			$this->assertTrue($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());
		}

		public function testCombineFlags()
		{
			$flags = AMatch::FLAG_STRICT_STRUCTURE | AMatch::FLAG_SHOW_GOOD_COMMENTS | AMatch::FLAG_DONT_STOP_MATCHING;
			$result = AMatch::runMatch(AMatchTest::$actual_params, $flags)
			->bad_key(AMatch::OPTIONAL) // true Опциональный, отсутствующий
			->bad_key('14', '<') // Если есть, то более 14
			->missed_key('', 'is_array') // false Обязательный, отсутствующий
			->empty_key(true) // false Обязательный, не фальш
			->data(AMatch::OPTIONAL) // Необязательный параметр
			->data('key2', 'key_exists') // true Существование ключа
			->data('key1', '!key_exists') // false Требование отсутствие ключа
			->data('key15', 'key_exists') // false Существование ключа
			->parent_id('', 'is_float') // false Требуется float
			;
			
			$expected_ar = array(
			'bad_key' => AMatch::OPTIONAL_SKIPPED,
			'missed_key' => AMatch::KEY_NOT_EXISTS,
			'empty_key' => AMatch::KEY_CONDITION_NOT_VALID,
			'data' => AMatch::KEY_CONDITION_NOT_VALID,
			'parent_id' => AMatch::KEY_TYPE_NOT_VALID,
			'stopMatch' => AMatch::UNKNOWN_PARAMETERS_LIST,
			AMatch::_UNKNOWN_PARAMETERS_LIST => 'doc_id,subject_id,title,empty_key2,longlong',
			);

			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());

			$expected_conditions_ar = array(
				'bad_key' => array('14', '<'),
				'missed_key' => array('', 'is_array'),
				'empty_key' => array(true),
				'data' => array('key15', 'key_exists'),
				'parent_id' => array('', 'is_float'),
				'stopMatch' => array(),
				AMatch::_UNKNOWN_PARAMETERS_LIST => array()
			);
			$this->assertEquals($expected_conditions_ar, $result->matchCommentsConditions());
		}

		/**
		 * Простой каллбек со статусом bool
		 * @param array $sub_ar
		 * @param array $param_name Имя анализируемого параметра, отправленного в callback
		 * @return bool
		 */
		public function _callbackMethod($sub_ar, $param_name)
		{
			return AMatch::runMatch($sub_ar)->key1()->key2()->key15(AMatch::OPTIONAL)->stopMatch();
		}

		/**
		 * Расширенный каллбек с возвратом комментариев к ошибке
		 * @param array $sub_ar
		 * @param array $param_name Имя анализируемого параметра, отправленного в callback
		 * @return array
		 */
		public function _callbackMethodWithComments($sub_ar, $param_name)
		{
			$flags = AMatch::FLAG_SHOW_GOOD_COMMENTS | AMatch::FLAG_DONT_STOP_MATCHING;
			$result = AMatch::runMatch($sub_ar, $flags)->key1()->key2()->key15(AMatch::OPTIONAL);

			return array($result->stopMatch(), $result->matchComments(), $result->matchCommentsConditions());
		}

		public function testCallbackSimple()
		{
			$flags = AMatch::FLAG_SHOW_GOOD_COMMENTS | AMatch::FLAG_DONT_STOP_MATCHING;
			$result = AMatch::runMatch(AMatchTest::$actual_params, $flags)
			->data(array($this, '_callbackMethod'), 'callback') // true
			->subject_id(array($this, '_callbackMethod'), 'callback'); // false

			$expected_ar = array(
			'data' => AMatch::KEY_CONDITION_VALID,
			'subject_id' => AMatch::KEY_CONDITION_NOT_VALID,
			);

			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());
		}
	
		public function testCallbackWithComments()
		{
			$flags = AMatch::FLAG_SHOW_GOOD_COMMENTS | AMatch::FLAG_DONT_STOP_MATCHING;

			// Каллбек с возвращаемыми комментариями
			$result = AMatch::runMatch(AMatchTest::$actual_params, $flags)
			->data(array($this, '_callbackMethodWithComments'), 'callback') // true
			->subject_id(array($this, '_callbackMethodWithComments'), 'callback'); // false

			$expected_ar = array(
				'data' => array(
						'key1' => AMatch::KEY_EXISTS,
						'key2' => AMatch::KEY_EXISTS,
						'key15' => AMatch::KEY_NOT_EXISTS_OPTIONAL,
				),
				'subject_id' => array(
						'runMatch' => AMatch::MATCHING_DATA_NOT_ARRAY,
						'key1' => AMatch::KEY_NOT_EXISTS,
						'key2' => AMatch::KEY_NOT_EXISTS,
						'key15' => AMatch::KEY_NOT_EXISTS_OPTIONAL,
				)
			);
			
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchComments());

			$expected_conditions_ar = array(
				'data' => array(
						'key1' => array(),
						'key2' => array(),
						'key15' => array(AMatch::OPTIONAL)
				),
				'subject_id' => array(
						'runMatch' => array(),
						'key1' => array(),
						'key2' => array(),
						'key15' => array(AMatch::OPTIONAL)
				)
			);
			$this->assertEquals($expected_conditions_ar, $result->matchCommentsConditions());
		}
		
		/**
		 * @dataProvider _pluginsDataProvider
		 * @param string $array Тестируемый массив
		 * @param string $param_name Имя тестируемого ключа
		 * @param callable $callback Вызываемый пользовательский метод
		 * @param mixed $expected_value Ожидаемое значение (аргумент пользовательского метода)
		 * @param bool $expected_result Ожидаемый результат выполнения
		 * @param array $expected_comments Комментарии в случае неудачи
		 */
		public function testCallbackPlugins($array, $param_name, $callback, $expected_value, $expected_result, $expected_comments = array())
		{
			$result = AMatch::runMatch($array)->$param_name($expected_value, $callback);
			if ($expected_result) {
				$this->assertTrue($result->stopMatch());
			} else {
				$this->assertFalse($result->stopMatch());
				$this->assertEquals($expected_comments, $result->matchComments());
			}
		}

		public static function _pluginsDataProvider()
		{
			return array(
				array(self::$actual_params, 'title', 'AMatchString::minLength', 15, true),
				array(self::$actual_params, 'title', 'AMatchString::maxLength', 15, true),
				array(self::$actual_params, 'title', 'AMatchString::length', 15, true),
				array(self::$actual_params, 'title', 'AMatchString::minLength', 16, false, array('title' => 'Text is too short')),
				array(self::$actual_params, 'title', 'AMatchString::maxLength', 14, false, array('title' => 'Text is too long')),
				array(self::$actual_params, 'title', 'AMatchString::length', 16, false, array('title' => 'Text is too short')),
				array(self::$actual_params, 'title', 'AMatchString::length', 14, false, array('title' => 'Text is too long')),
			
				//
				array( array('classic' => array()), 'classic', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_CLASSIC, true),
				array( array('first' => array()), 'first', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_FIRST_ELEMENT, true),
				array( array('first' => array(array())), 'first', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_FIRST_ELEMENT, true),
				array( array('first' => array(array(array(array())))), 'first', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_FIRST_ELEMENT, true),
				array( array('first' => array('')), 'first', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_FIRST_ELEMENT, true),
				array( array('first' => array(0)), 'first', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_FIRST_ELEMENT, true),
				array( array('some' => array()), 'some', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_SOME_ELEMENT, true),
				array( array('some' => array(0, array(0, array(0)))), 'some', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_SOME_ELEMENT, true),

				//
				array( array('classic' => array(array())), 'classic', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_CLASSIC, false, array('classic' => AMatchArray::EMPTY_ARRAY_CLASSIC)),
				array( array('classic' => array('')), 'classic', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_CLASSIC, false, array('classic' => AMatchArray::EMPTY_ARRAY_CLASSIC)),
				array( array('first' => array(array(), array())), 'first', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_FIRST_ELEMENT, false, array('first' => AMatchArray::EMPTY_ARRAY_FIRST_ELEMENT)),
				array( array('first' => array(array(array(array(), array())))), 'first', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_FIRST_ELEMENT, false, array('first' => AMatchArray::EMPTY_ARRAY_FIRST_ELEMENT)),
				array( array('first' => array(0, '')), 'first', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_FIRST_ELEMENT, false, array('first' => AMatchArray::EMPTY_ARRAY_FIRST_ELEMENT) ),
				array( array('some' => array(0, array(0, array(0, 1)))), 'some', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_SOME_ELEMENT, false, array('some' => AMatchArray::EMPTY_ARRAY_SOME_ELEMENT)),
			);
		}

		public function testUserErrorsComments()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_DONT_STOP_MATCHING)
				->bad_key(null, null, 'Wow!')
				->title(array(1), 'in_array', 'Oops!')
				->subject_id(200, 'AMatchString::length', 'Length!')
				->data('', '!array', '!Array')
				->empty_key(true, '==')
				->empty_key(true, '', 'Bad!') // Стандартная ошибка должна замениться пользовательской
				->empty_key2(23, '', 'Bad!')
				->empty_key2(23, '==') // Пользовательская ошибка должна замениться стандартной
			;
			$this->assertFalse($result->stopMatch());
			$this->assertEquals(array(
				'bad_key' => 'Wow!',
				'title' => 'Oops!',
				'subject_id' => 'Length!',
				'data' => '!Array',
				'empty_key' => 'Bad!',
				'empty_key2' => AMatch::KEY_CONDITION_NOT_VALID,
			),
			$result->matchComments());
		}
	}