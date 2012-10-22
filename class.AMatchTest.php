<?php
	require_once('class.AMatch.php');
	require_once('class.AMatchStatus.php');
	require_once('class.AMatchString.php');
	require_once('class.AMatchArray.php');

	/**
	 * Пример собственного маппинга ошибок
	 */
	class MyStatusMapping extends AMatchStatus
	{
		const MY_STATUS = 'my_error_code_comment';
		const MY_OTHER_STATUS = 12345;
		protected function _fillComments()
		{
			parent::_fillComments();
			$this->_result_comments[self::KEY_NOT_EXISTS] = self::MY_STATUS;
			$this->_result_comments[self::KEY_TYPE_NOT_VALID] = self::MY_OTHER_STATUS;
		}
	}

	/**
	 * PHPUnit Test Class for AMatch
	 * @package AMatch
	 * @author KIVagant <KIVagant@gmail.com>
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
			$str = 'bad input data';
			$result = AMatch::runMatch($str)->doc_id('', 'fukaka');
			$this->assertFalse($result->stopMatch());
			$this->assertEquals(array(
			'runMatch' => AMatchStatus::MATCHING_DATA_NOT_ARRAY
			), $result->matchResults());
		}

		public function testBadCondition()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params)->doc_id('', 'fukaka');
			$this->assertFalse($result->stopMatch());
			$this->assertEquals(array(
				'doc_id' => AMatchStatus::CONDITION_IS_UNKNOWN
			), $result->matchResults());
		}

		public function testBadKeyNotExists()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params)->bad_key();
			$this->assertFalse($result->stopMatch());
			$this->assertEquals(array(
				'bad_key' => AMatchStatus::KEY_NOT_EXISTS
			),
				$result->matchResults());
		}

		public function testKeyExists()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)->doc_id();
			$this->assertTrue($result->stopMatch());
			$this->assertEquals(array(
				'doc_id' => AMatchStatus::KEY_EXISTS
			), $result->matchResults());
		}

		public function testLastKeyNotExists()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)->doc_id()->parent_id()->bad_key()
				;
			$expected_ar = array(
				'doc_id' => AMatchStatus::KEY_EXISTS,
				'parent_id' => AMatchStatus::KEY_EXISTS,
				'bad_key' => AMatchStatus::KEY_NOT_EXISTS
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());
		}

		public function testValidAndNotValidCondition()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)->doc_id(133)->subject_id(20);
			$expected_ar = array(
				'doc_id' => AMatchStatus::KEY_CONDITION_VALID,
				'subject_id' => AMatchStatus::KEY_CONDITION_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)->data(AMatchTest::$actual_params['data']);
			$expected_ar = array(
				'data' => AMatchStatus::KEY_CONDITION_VALID,
			);
			$this->assertTrue($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)->data(array(
				1,
				2
			))
				;
			$expected_ar = array(
				'data' => AMatchStatus::KEY_CONDITION_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());
		}

		public function testFullyValidation()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)->doc_id(133, '===')->subject_id(64,
				'===');
			$expected_ar = array(
				'doc_id' => AMatchStatus::KEY_VALID_FULLY,
				'subject_id' => AMatchStatus::KEY_CONDITION_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)->data(AMatchTest::$actual_params['data'], '===')
				;
			$expected_ar = array(
				'data' => AMatchStatus::KEY_VALID_FULLY,
			);
			$this->assertTrue($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());
		}

		public function testLargerAndSmaller()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)
			->doc_id(120, '<')
			->subject_id(80, '>');
			$expected_ar = array(
				'doc_id' => AMatchStatus::KEY_CONDITION_VALID,
				'subject_id' => AMatchStatus::KEY_CONDITION_VALID,
			);
			$this->assertTrue($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());
		}

		public function testSmallerOrEqual()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)
				->doc_id(133, '=')
				->subject_id(64, '<=')
				->parent_id(32, '>=');
			$expected_ar = array(
				'doc_id' => AMatchStatus::KEY_CONDITION_VALID,
				'subject_id' => AMatchStatus::KEY_CONDITION_VALID,
				'parent_id' => AMatchStatus::KEY_CONDITION_VALID,
			);
			$this->assertTrue($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());
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
				'doc_id' => AMatchStatus::KEY_TYPE_VALID,
				'subject_id' => AMatchStatus::KEY_TYPE_VALID,
				'parent_id' => AMatchStatus::KEY_TYPE_VALID,
				'title' => AMatchStatus::KEY_TYPE_VALID,
				'data' => AMatchStatus::KEY_TYPE_VALID,
				'empty_key' => AMatchStatus::KEY_TYPE_VALID,
				'empty_key2' => AMatchStatus::KEY_TYPE_VALID,
				'longlong' => AMatchStatus::KEY_TYPE_VALID,
			);
			$this->assertTrue($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());
		}

		/**
		 * Проверка типа данных "float"
		 * @param mixed $float Проверяемое значение
		 * @param bool $expected_result Ожидаемый результат
		 * @dataProvider _floatDataProvider
		 */
		public function testFloatType($float, $expected_result)
		{
			$validate_ar = array('float' => $float);
			$result = AMatch::runMatch($validate_ar, AMatch::FLAG_SHOW_GOOD_COMMENTS)
				->float('', 'float');

			if ($expected_result) {
				$expected_ar = array(
					'float' => AMatchStatus::KEY_TYPE_VALID,
				);
				$this->assertTrue($result->stopMatch());
			} else {
				$expected_ar = array(
				'float' => AMatchStatus::KEY_TYPE_NOT_VALID,
				);
				$this->assertFalse($result->stopMatch());
			}
			$this->assertEquals($expected_ar, $result->matchResults());
		}

		public static function _floatDataProvider()
		{
			return array(
				array(1, true),
				array(-1, true),
				array(1.0, true),
				array(-1.0, true),
				array('1', true),
				array('-1', true),
				array('1.0', true),
				array('-1.0', true),
				array('2.1', true),
				array('0', true),
				array(0, true),
				array(' 0 ', true),
				array(' 0.1 ', true),
				array(' -0.0 ', true),
				array(-0.0, true),
				array(3., true),
				array('-3.', true),
				array('.27', true),
				array(.27, true),
				array('-0', true),
				array('+4', true),
				array('1e2', true),
				array('+1353.0316547', true),
				array('13213.032468e-13465', true),
				array('-8E+3', true),
				array('-1354.98879e+37436', true),
				array('-1.2343E+14', true),

				//
				array(false, false),
				array(true, false),
				array('', false),
				array('-', false),
				array('.a', false),
				array('-1.a', false),
				array('.a', false),
				array('.', false),
				array('-.', false),
				array('1+', false),
				array('1.3+', false),
				array('a1', false),
				array('e.e', false),
				array('-e-4', false),
				array('e2', false),
				array('8e', false),
				array('3,25', false),
				array('1.1.1', false),
				array('1111.11.1111E1', false),
			);
		}
		public function testTypesNotValid()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params)->doc_id(false, 'string');
			$expected_ar = array(
				'doc_id' => AMatchStatus::KEY_TYPE_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)->title(false, 'array');
			$expected_ar = array(
				'title' => AMatchStatus::KEY_TYPE_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)->title(false, 'longint');
			$expected_ar = array(
				'title' => AMatchStatus::KEY_TYPE_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)->data(false, 'object');
			$expected_ar = array(
				'data' => AMatchStatus::KEY_TYPE_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)->parent_id(false, 'smartbool');
			$expected_ar = array(
				'parent_id' => AMatchStatus::KEY_TYPE_NOT_VALID,
			);

			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());
		}

		public function testInArrayAndKeyExists()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)->data('data3', 'in_array');
			$expected_ar = array(
				'data' => AMatchStatus::KEY_CONDITION_VALID,
			);
			$this->assertTrue($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)->data('key2', 'key_exists');
			$expected_ar = array(
				'data' => AMatchStatus::KEY_CONDITION_VALID,
			);
			$this->assertTrue($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());
			
			//
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)->doc_id(array(132, 133, 134), 'in_expected_array');
			$expected_ar = array(
				'doc_id' => AMatchStatus::KEY_CONDITION_VALID,
			);
			$this->assertTrue($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());
		}

		public function testNotInArrayAndKeyNotExists()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params)->data('data16', 'in_array');
			$expected_ar = array(
				'data' => AMatchStatus::KEY_CONDITION_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)->data('key7', 'key_exists');
			$expected_ar = array(
				'data' => AMatchStatus::KEY_CONDITION_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());
			
			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)->doc_id(array(10, 11, 1000), 'in_expected_array');
			$expected_ar = array(
				'doc_id' => AMatchStatus::KEY_CONDITION_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());
		}
		public function testActualAndExpectedNotIsArrays()
		{
			$result = AMatch::runMatch(AMatchTest::$actual_params)->doc_id(false, 'in_array');
			$expected_ar = array(
				'doc_id' => AMatchStatus::ACTUAL_NOT_IS_ARRAY,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)->doc_id(false, 'in_expected_array');
			$expected_ar = array(
				'doc_id' => AMatchStatus::EXPECTED_NOT_IS_ARRAY,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());
		}
		public function testInstanceOf()
		{
			$actual_obj = new AMatch(array());
			$result = AMatch::runMatch(array('my_obj' => $actual_obj), AMatch::FLAG_SHOW_GOOD_COMMENTS)
				->my_obj('AMatch', 'instanceof');
			$expected_ar = array(
				'my_obj' => AMatchStatus::KEY_CONDITION_VALID,
			);
			$this->assertTrue($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());
			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)->doc_id('AMatch', 'instanceof');
			$expected_ar = array(
				'doc_id' => AMatchStatus::KEY_CONDITION_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());
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
				'doc_id' => AMatchStatus::KEY_CONDITION_VALID,
				'subject_id' => AMatchStatus::KEY_CONDITION_VALID,
				'parent_id' => AMatchStatus::KEY_TYPE_VALID,
				'empty_key' => AMatchStatus::KEY_VALID_FULLY,
				'data' => AMatchStatus::KEY_CONDITION_VALID,
				'title' => AMatchStatus::KEY_TYPE_VALID,
			);
			$this->assertTrue($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_SHOW_GOOD_COMMENTS)
			->doc_id(133, '!>')
			->empty_key('', '!is_float')
			->data('data2', '!in_array')
			;
			$expected_ar = array(
				'doc_id' => AMatchStatus::KEY_CONDITION_VALID,
				'empty_key' => AMatchStatus::KEY_TYPE_VALID,
				'data' => AMatchStatus::KEY_CONDITION_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)
			->doc_id(133, '!<=')
			;
			$expected_ar = array(
				'doc_id' => AMatchStatus::KEY_CONDITION_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)
			->data('key3', '!key_exists')
			;
			$expected_ar = array(
				'data' => AMatchStatus::KEY_CONDITION_NOT_VALID,
			);
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());
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
				'doc_id' => AMatchStatus::KEY_CONDITION_VALID,
				'empty_key' => AMatchStatus::KEY_CONDITION_VALID,
			);

			$this->assertTrue($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());

			// ложные утверждения:
			$result = AMatch::runMatch(AMatchTest::$actual_params)
			->parent_id(AMatch::CURRENT, false)
			;

			$expected_ar = array(
				'parent_id' => AMatchStatus::KEY_CONDITION_NOT_VALID,
			);

			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)
			->empty_key(AMatch::CURRENT, true)
			;

			$expected_ar = array(
			'empty_key' => AMatchStatus::KEY_CONDITION_NOT_VALID,
			);

			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)
			->empty_key(0, true)
			;

			$expected_ar = array(
			'empty_key' => AMatchStatus::KEY_CONDITION_NOT_VALID,
			);

			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());

			//
			$result = AMatch::runMatch(AMatchTest::$actual_params)
			->empty_key(1, false)
			;

			$expected_ar = array(
			'empty_key' => AMatchStatus::KEY_CONDITION_NOT_VALID,
			);

			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());
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
			'subject_id' => AMatchStatus::KEY_CONDITION_VALID,
			'empty_key' => AMatchStatus::KEY_CONDITION_VALID,
			'bad_key' => AMatchStatus::KEY_NOT_EXISTS_OPTIONAL,
			'stopMatch' => AMatchStatus::UNKNOWN_PARAMETERS_LIST,
			AMatch::_UNKNOWN_PARAMETERS_LIST => 'doc_id,parent_id,title,empty_key2,longlong,data',
			);

			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());

			$result = AMatch::runMatch(array('a' => 1, 'b' => null), AMatch::FLAG_STRICT_STRUCTURE | AMatch::FLAG_SHOW_GOOD_COMMENTS)
			->a(true)
			->b(false)
			;
			
			$expected_ar = array(
			'a' => AMatchStatus::KEY_CONDITION_VALID,
			'b' => AMatchStatus::KEY_CONDITION_VALID,
			'stopMatch' => AMatchStatus::ALL_PARAMETERS_CHECKED,
			);
			$this->assertTrue($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());
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
			->parent_id('', 'is_array') // true Инт тоже считается float
			->something(AMatch::OPTIONAL) // Необязательный параметр в конце условий (ошибка перебивания результата на true)
			;
			
			$expected_ar = array(
			'bad_key' => AMatchStatus::KEY_NOT_EXISTS_OPTIONAL,
			'missed_key' => AMatchStatus::KEY_NOT_EXISTS,
			'empty_key' => AMatchStatus::KEY_CONDITION_NOT_VALID,
			'data' => AMatchStatus::KEY_CONDITION_NOT_VALID,
			'parent_id' => AMatchStatus::KEY_TYPE_NOT_VALID,
			'something' => AMatchStatus::KEY_NOT_EXISTS_OPTIONAL,
			'stopMatch' => AMatchStatus::UNKNOWN_PARAMETERS_LIST,
			AMatch::_UNKNOWN_PARAMETERS_LIST => 'doc_id,subject_id,title,empty_key2,longlong',
			);

			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());

			$expected_conditions_ar = array(
				'bad_key' => array(AMatch::OPTIONAL),
				'missed_key' => array('', 'is_array'),
				'empty_key' => array(true),
				'data' => array('key15', 'key_exists'),
				'parent_id' => array('is_array', 'is_array'),
				'something' => array(AMatch::OPTIONAL),
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
		public function _callbackMethodWithPersonalStatusCodes($sub_ar, $param_name)
		{
			$flags = AMatch::FLAG_SHOW_GOOD_COMMENTS | AMatch::FLAG_DONT_STOP_MATCHING;
			$result = AMatch::runMatch($sub_ar, $flags)->key1()->key2()->key15(AMatch::OPTIONAL);

			return array($result->stopMatch(), $result->matchResults(), $result->matchCommentsConditions());
		}

		public function testCallbackBad()
		{
			$flags = AMatch::FLAG_SHOW_GOOD_COMMENTS | AMatch::FLAG_DONT_STOP_MATCHING;
			$result = AMatch::runMatch(AMatchTest::$actual_params, $flags)
			->subject_id('', 'AMatchString->min::max')
			->title('AMatchString->max::min', 'callback')
			->longlong('AMatchString->max->min', 'callback')
			;

			$expected_ar = array(
				'subject_id' => AMatchStatus::CALLBACK_NOT_CALLABLE,
				'title' => AMatchStatus::CALLBACK_NOT_CALLABLE,
				'longlong' => AMatchStatus::CALLBACK_NOT_CALLABLE,
			);

			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());
		}

		public function testCallbackSimple()
		{
			$flags = AMatch::FLAG_SHOW_GOOD_COMMENTS | AMatch::FLAG_DONT_STOP_MATCHING;
			$result = AMatch::runMatch(AMatchTest::$actual_params, $flags)
			->data(array($this, '_callbackMethod'), 'callback') // true
			->data('', array($this, '_callbackMethod')) // true
			->subject_id(array($this, '_callbackMethod'), 'callback'); // false

			$expected_ar = array(
			'data' => AMatchStatus::KEY_CONDITION_VALID,
			'subject_id' => AMatchStatus::KEY_CONDITION_NOT_VALID,
			);

			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());
		}
	
		public function testCallbackWithStatusCodes()
		{
			$flags = AMatch::FLAG_SHOW_GOOD_COMMENTS | AMatch::FLAG_DONT_STOP_MATCHING;

			// Каллбек с возвращаемыми комментариями
			$result = AMatch::runMatch(AMatchTest::$actual_params, $flags)
			->data(array($this, '_callbackMethodWithPersonalStatusCodes'), 'callback') // true
			->subject_id(array($this, '_callbackMethodWithPersonalStatusCodes'), 'callback'); // false

			$expected_ar = array(
				'data' => array(
						'key1' => AMatchStatus::KEY_EXISTS,
						'key2' => AMatchStatus::KEY_EXISTS,
						'key15' => AMatchStatus::KEY_NOT_EXISTS_OPTIONAL,
				),
				'subject_id' => array(
						'runMatch' => AMatchStatus::MATCHING_DATA_NOT_ARRAY,
				)
			);
			
			$this->assertFalse($result->stopMatch());
			$this->assertEquals($expected_ar, $result->matchResults());

			$expected_conditions_ar = array(
				'data' => array(
						'key1' => array(),
						'key2' => array(),
						'key15' => array(AMatch::OPTIONAL)
				),
				'subject_id' => array(
						'runMatch' => array(),
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
		 * @param array $expected_statuses Статусы ошибок
		 */
		public function testCallbackPlugins($array, $param_name, $callback, $expected_value, $expected_result, $expected_statuses = array())
		{
			$result = AMatch::runMatch($array)->$param_name($expected_value, $callback);
			if ($expected_result) {
				$this->assertTrue($result->stopMatch());
			} else {
				$this->assertFalse($result->stopMatch());
				$this->assertEquals($expected_statuses, $result->matchResults());
			}
		}

		public static function _pluginsDataProvider()
		{
			return array(
				array(self::$actual_params, 'title', 'AMatchString->minLength', 15, true), // Можно вызывать через ->
				array(self::$actual_params, 'title', 'AMatchString::maxLength', 15, true),
				array(self::$actual_params, 'title', 'AMatchString::length', 15, true),
				array(self::$actual_params, 'title', 'AMatchString::minLength', 16, false, array('title' => AMatchStatus::STRING_TOO_SHORT)),
				array(self::$actual_params, 'title', 'AMatchString::maxLength', 14, false, array('title' => AMatchStatus::STRING_TOO_LONG)),
				array(self::$actual_params, 'title', 'AMatchString::length', 16, false, array('title' => AMatchStatus::STRING_TOO_SHORT)),
				array(self::$actual_params, 'title', 'AMatchString::length', 14, false, array('title' => AMatchStatus::STRING_TOO_LONG)),
				array(self::$actual_params, 'title', array('AMatchString', 'length'), 14, false, array('title' => AMatchStatus::STRING_TOO_LONG)),
			
				//
				array( array('classic' => array()), 'classic', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_CLASSIC, true),
				array( array('first' => array()), 'first', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_FIRST_ELEMENT, true),
				array( array('first' => array(array())), 'first', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_FIRST_ELEMENT, true),
				array( array('first' => array(array(array(array())))), 'first', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_FIRST_ELEMENT, true),
				array( array('first' => array('')), 'first', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_FIRST_ELEMENT, true),
				array( array('first' => array(0)), 'first', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_FIRST_ELEMENT, true),
				array( array('some' => array()), 'some', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_SOME_ELEMENT, true),
				array( array('some' => array(0, array(0, array(0)))), 'some', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_SOME_ELEMENT, true),
				array( array('integers' => array(0, '1234', '-213', -432, '-12314924312341324132412341234123')), 'integers', 'AMatchArray::onlyIntegerValues', '', true),
				array( array('integers' => array(0, 'a', 'b', -432 => 0, '-12314924312341324132412341234123' => 1)), 'integers', 'AMatchArray::onlyIntegerKeys', '', true),
				array( array('user' => 'some.user_check@i.ua'), 'user', 'AMatchString::isEmail', '', true),
				array( array('int' => '-12342451235345124351234124'), 'int', 'AMatchString::pregMatch', '/^-?\d+$/', true),

				//
				array( array('classic' => array(array())), 'classic', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_CLASSIC, false, array('classic' => AMatchStatus::EMPTY_ARRAY_CLASSIC)),
				array( array('classic' => array('')), 'classic', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_CLASSIC, false, array('classic' => AMatchStatus::EMPTY_ARRAY_CLASSIC)),
				array( array('first' => array(array(), array())), 'first', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_FIRST_ELEMENT, false, array('first' => AMatchStatus::EMPTY_ARRAY_FIRST_ELEMENT)),
				array( array('first' => array(array(array(array(), array())))), 'first', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_FIRST_ELEMENT, false, array('first' => AMatchStatus::EMPTY_ARRAY_FIRST_ELEMENT)),
				array( array('first' => array(0, '')), 'first', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_FIRST_ELEMENT, false, array('first' => AMatchStatus::EMPTY_ARRAY_FIRST_ELEMENT) ),
				array( array('some' => array(0, array(0, array(0, 1)))), 'some', 'AMatchArray::isEmpty', AMatchArray::FLAG_EMPTY_SOME_ELEMENT, false, array('some' => AMatchStatus::EMPTY_ARRAY_SOME_ELEMENT)),
				array( array('integers' => array(0, '1234', '-213', -432, '- 12314924312341324132412341234123')), 'integers', 'AMatchArray::onlyIntegerValues', '', false, array('integers' => AMatchStatus::ARRAY_OF_INTS_REQUIRED)),
				array( array('integers' => array('0.12')), 'integers', 'AMatchArray::onlyIntegerValues', '', false, array('integers' => AMatchStatus::ARRAY_OF_INTS_REQUIRED)),
				array( array('integers' => array(0.33)), 'integers', 'AMatchArray::onlyIntegerValues', '', false, array('integers' => AMatchStatus::ARRAY_OF_INTS_REQUIRED)),
				array( array('integers' => array('a' => 1, 2)), 'integers', 'AMatchArray::onlyIntegerKeys', '', false, array('integers' => AMatchStatus::ARRAY_OF_INTS_KEYS_REQUIRED)),
				array( array('user' => 'aaa@.com'), 'user', 'AMatchString::isEmail', '', false, array('user' => AMatchStatus::STRING_IS_NOT_EMAIL)),
				array( array('int' => '1.123'), 'int', 'AMatchString::pregMatch', '/^-?\d+$/', false, array('int' => AMatchStatus::REGEXP_FAILURE)),
				array( array('badstr' => new stdClass()), 'badstr', 'AMatchString::length', 10, false, array('badstr' => AMatchStatus::ACTUAL_IS_NOT_STRING)),
				array( array('badstr' => new stdClass()), 'badstr', 'AMatchString::minLength', 10, false, array('badstr' => AMatchStatus::ACTUAL_IS_NOT_STRING)),
				array( array('badstr' => new stdClass()), 'badstr', 'AMatchString::maxLength', 10, false, array('badstr' => AMatchStatus::ACTUAL_IS_NOT_STRING)),
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
				'empty_key2' => AMatchStatus::KEY_CONDITION_NOT_VALID,
			),
			$result->matchResults());
		}

		public function testMyStatusCommentsBadClass()
		{
			$my_mapping_object = new stdClass(); // Некорректный объект
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_DONT_STOP_MATCHING, $my_mapping_object)
			->unknown_key()
			->title('', 'int')
			;
			
			$this->assertFalse($result->stopMatch());
			$this->assertEquals(array(
			'runMatch' => AMatchStatus::BAD_STATUSES_CLASS,
			),
			$result->matchResults());
		}

		/**
		 * Тест собственного маппинга ошибок
		 */
		public function testMyStatusComments()
		{
			$my_mapping_object = new MyStatusMapping(); // Собственный объект со статусами ошибок
			$result = AMatch::runMatch(AMatchTest::$actual_params, AMatch::FLAG_DONT_STOP_MATCHING, $my_mapping_object)
				->unknown_key()
				->title('', 'int')
			;

			$this->assertFalse($result->stopMatch());
			$this->assertEquals(array(
				'unknown_key' => AMatchStatus::KEY_NOT_EXISTS,
				'title' => AMatchStatus::KEY_TYPE_NOT_VALID,
			),
			$result->matchResults());

			// В matchComments будут собственные ошибки
			$this->assertEquals(array(
				'unknown_key' => MyStatusMapping::MY_STATUS,
				'title' => MyStatusMapping::MY_OTHER_STATUS,
			),
			$result->matchComments());
		}
	}