AMatch
======

Validate associative arrays

Habrahabr examlpes:

- Part 1: http://habrahabr.ru/post/149114/
- Part 2: http://habrahabr.ru/post/150039/

See examples in folder 'examples'.

This is short demo for usage:

<?

$params_bad = array(
    'doc_id' => -4,
    'subject_id' => null,
    'parent_id' => 30,
    'data' => array(
        'flag' => 'booom',
        'from_topic' => array(),
        'old_property' => true,
    ),
    'wtf_param' => 'exploit',
);
$match = AMatch::runMatch($params_bad)
    ->doc_id(0, '<') // Left value is smaller then array value
    ->subject_id(0, '!=') // Array value != zero
    ->subject_id('', '!float') // Array value is not float
    ->author_name(AMatch::OPTIONAL, 'string') // String type or not defined
    ->author_name('Guest') // Array value equal 'Guest'
    ->parent_id(AMatch::OPTIONAL, 'int') // Int type or not defined
    ->parent_id(0, '<') // Left value is smaller than array value
    ->parent_id(array(32, 33), 'in_left_array') // Array value exists in this array
    ->data('', 'array') // Type of array value is 'array'
    ->data('', '!empty') // Array value not empty
    ->data('old_property', '!key_exists') // Array does not have this key
    ->data('experiment', 'in_array') // In array value must be another array with value 'experiment'
    ->title() // This key must exist
    ;
if ($match->stopMatch()) {
	echo 'Victory!';
} else {
	var_export($match->matchComments());
}

?>