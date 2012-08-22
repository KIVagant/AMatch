<?
/**
 * Пример использования AMatch
 * @package AMatch
 * @author KIVagant
 * @see AMatch
 * @link http://habrahabr.ru/post/150039/
 */

$params = array(
	'subject_id' => '64',
	'parent_id' => -32,
	'delimeter' => '-4.645E+32',
	'title' => 'New document',
	'links' => array(13, '-16', 24),
	'email' => 'someuser@mail.dom',
);
$params_bad = array(
	'subject_id' => '64.43',
	'parent_id' => array(),
	'delimeter' => '-4.x6E.32',
	'title' => new stdClass(),
	'links' => array(0, array(0, array(0)), 0),
	'email' => 'someuser!@mail.dom',
);
function result(AMatch $match)
{
	echo PHP_EOL; echo $match->stopMatch() ? 'Dance!' : 'Cry!' ;
	echo PHP_EOL; var_export($match->matchResults());
	echo PHP_EOL; var_export($match->matchComments());
	echo PHP_EOL; var_export($match->matchCommentsConditions());
}

require_once('../class.AMatch.php');
require_once('../class.AMatchString.php');
require_once('../class.AMatchArray.php');

//$flags = AMatch::FLAG_DONT_STOP_MATCHING | AMatch::FLAG_SHOW_GOOD_COMMENTS;//| AMatch::FLAG_STRICT_STRUCTURE

echo PHP_EOL . PHP_EOL . 'EXAMPLE 1: bad and good policeman' . PHP_EOL;

$match = AMatch::runMatch($params, AMatch::FLAG_SHOW_GOOD_COMMENTS)->delimeter('', 'float'); // Существует с указанным типом
result($match);
$match = AMatch::runMatch($params_bad, AMatch::FLAG_SHOW_GOOD_COMMENTS)->delimeter('', 'float');
result($match);

echo PHP_EOL . PHP_EOL . 'EXAMPLE 2: mapping' . PHP_EOL;
function mapping(AMatch $match)
{
	// Карта ошибок
	$errors_mapping = array(
			AMatchStatus::KEY_TYPE_NOT_VALID => 'invalid_type',
			AMatchStatus::KEY_CONDITION_NOT_VALID => 'invalid_data',
			AMatchStatus::KEY_NOT_EXISTS => 'required',
		);
	$results = $match->matchResults(); // Результат в кодах
	$comments = $match->matchComments(); // Комментарий к результату
	$comments_conditions = $match->matchCommentsConditions(); // Расшифровка результата
	$output = array();
	foreach ($results as $param => $result) {
		$error = array_key_exists($result, $errors_mapping)
			? $errors_mapping[$result] : 'other_errors'; // Ошибка, не имеющая аналогов в карте
		$comment = $param . ': ' . $comments[$param];
		if (isset($comments_conditions[$param]) && !empty($comments_conditions[$param][0])) {
			$comment .= ' (' . $comments_conditions[$param][0] . ')'; // Дополнительная информация
		}
		$output[$error][] = $comment;
	}
	var_export($output);
}
class AMatchRussian extends AMatchStatus
{
	protected function _fillComments()
	{
		parent::_fillComments(); // Если не вызвать родительский метод, то отсутствующие строки будут отданы в виде кодов
		$this->_result_comments[self::KEY_NOT_EXISTS] = 'Искал, вот честно. Не нашел';
		$this->_result_comments[self::KEY_CONDITION_NOT_VALID] = 'Попробуйте поиграть шрифтами';
		$this->_result_comments[self::CONDITION_IS_UNKNOWN] = 'Нипаняятна';
	}
}
$match = AMatch::runMatch($params_bad, AMatch::FLAG_SHOW_GOOD_COMMENTS | AMatch::FLAG_DONT_STOP_MATCHING, new AMatchRussian())
	->title('', 'string', 'Incorrect document title. Please, read FAQ.') // Существует с типом string
	->parent_id('', 'int') // Существует с типом string
	->ineedkey() // Ключ должен существовать
	->subject_id(1, '>') // "1" больше имеющегося значения
	->delimeter('', 'blabla') // Ошибка в условии
	;
mapping($match);

//
echo PHP_EOL . PHP_EOL . 'EXAMPLE 3: plugins' . PHP_EOL;
function matchCallbacks($params)
{
	$match = AMatch::runMatch($params, AMatch::FLAG_DONT_STOP_MATCHING)
	->parent_id('/^-?\d+$/', 'AMatchString::pregMatch') // проверка значения регулярным выражением
	->title(12, 'AMatchString::length') // длина строки равна
	->email('([\w-]+@([\w-]+\.)+[\w-]+)', 'AMatchString::isEmail') // проверка email собственной регуляркой (игнорируя встроенный алгоритм)
	->links(AMatchArray::FLAG_EMPTY_SOME_ELEMENT, 'AMatchArray::isNotEmpty') // проверка на пустоту по алгоритму: хотя бы один элемен массива или его вложенных массивов должен быть не-пустым
	;
	result($match);
}
matchCallbacks($params);
matchCallbacks($params_bad);