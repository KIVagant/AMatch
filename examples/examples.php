<?
/**
 * Пример использования AMatch
 * @package AMatch
 * @author KIVagant
 * @see AMatch
 * @link http://habrahabr.ru/post/149114/
 */
namespace KIVagant\AMatch;

require_once(__DIR__ . '/../src/AMatch.php');
require_once(__DIR__ . '/../src/AMatchStatus.php');
require_once(__DIR__ . '/../src/AMatchString.php');
require_once(__DIR__ . '/../src/AMatchArray.php');

$params = array(
    'doc_id' => 133,
    'subject_id' => '64',
    'parent_id' => 32,
    'title' => 'New document',
    'data' => array(
        'flag' => 'experiment',
        'from_topic' => false,
        'checker' => 'true',
    ),
);

$params_bad = array(
    'doc_id' => -4,
    'subject_id' => null,
    'parent_id' => 30,
    'author_name' => 'Admin',
    'data' => array(
        'flag' => 'booom',
        'from_topic' => array(),
        'old_property' => true,
    ),
    'wtf_param' => 'exploit',
);
// Try good and bad structures
// $params = $params_bad;

// AMatch with flags:

define('DEBUG_MODE', true);
$flags = AMatch::FLAG_DONT_STOP_MATCHING | AMatch::FLAG_STRICT_STRUCTURE;
if (DEBUG_MODE) {
    $flags |= AMatch::FLAG_SHOW_GOOD_COMMENTS;
}
$match = AMatch::runMatch($params, $flags)
    ->doc_id(0, '<') // Левое значение меньше (ожидаемое меньше актуального)
    ->subject_id(0, '!=') // Не равен нулю
    ->subject_id('', '!array') // Не массив
    ->author_name(AMatch::OPTIONAL, 'string') // Необязательный или текст
    ->author_name('Guest') // Гость сайта
    ->parent_id(AMatch::OPTIONAL, 'int') // Необязательный или int
    ->parent_id(0, '<') // Левое значение меньше
    ->parent_id(array(32, 33), 'in_left_array') // Значение содержится в указанном слева массиве
    ->data('', 'array') // массив
    ->data('', '!empty') // не пустой
    ->data('old_property', '!key_exists') // не должно быть ключа
    ->data('experiment', 'in_array') // внутри массива есть значение 'experiment'
    ->data('experiment', 'in_array', 'My personal error text!') // Замена стандартной ошибки собственной на любом условии, кроме опциональных
    ->title() // существует
;


//
// AMatch and callback function:
//

function checkDocumentData($data)
{
    $result = AMatch::runMatch($data)
        ->flag('experiment') // Равно указанному
        ->flag(9, 'AMatchString::minLength') // Минимальная длина
        ->flag(11, 'AMatchString::maxLength') // Максимальная длина
        ->from_topic(specialValidation(), true) // Принять условие, если вызываемая пользовательская функция отработала с true
        ->from_topic(false) // Равно false
        ->checker('', 'smartbool') // Принадлежит типу "умное булево"
        ->link_id(AMatch::OPTIONAL, 'int') // Необязательный или int
    ;

    return array($result->stopMatch(), $result->matchComments(), $result->matchCommentsConditions());
}
function specialValidation()
{
    return 1 < 2; // Некие особые внешние условия, от которых что-то зависит (например, наличие записи в базе)
}

$match->data('checkDocumentData', 'callback'); // проверить содержимое через пользовательскую функцию
$result = $match->stopMatch();
if (!$result) {
    die(
        var_export($match->matchComments(), true)
        . PHP_EOL
        . var_export($match->matchCommentsConditions(), true)
    ); // для наглядности умрём
}
echo PHP_EOL . 'Victory!' . PHP_EOL;

if (DEBUG_MODE) {
    $comments = $match->matchComments();
    $comments_explanation = $match->matchCommentsConditions();
    echo PHP_EOL; var_export($comments);
    echo PHP_EOL; var_export($comments_explanation);
}

/*
VS:

// Простые проверки
if (!isset($params['doc_id'])) {
throw new Exception('Expected document id');
}
if (!isset($params['subject_id'])) {
throw new Exception('Expected subject id');
}
if ($params['doc_id'] <= 0) {
throw new Exception('Incorrect document id');
}
if ($params['subject_id'] == 0) { // Отрицательные можно
throw new Exception('Incorrect document id');
}
if (isset($params['parent_id']) && $params['parent_id'] <= 0) { // Если существует — можно положительные
throw new Exception('Incorrect parent id');
}
if (isset($params['data']) && (!is_array($params['data']) || empty($params['data']))) {
throw new Exception('Incorrect document data');
}
//...if
//...if-else
	//...if-or-if-else-if
//...the brain is burning
*/