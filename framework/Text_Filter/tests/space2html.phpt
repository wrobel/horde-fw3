--TEST--
Text_Filter_space2html tests
--FILE--
<?php

require dirname(__FILE__) . '/../Filter.php';

$spaces = array('x x', 'x  x', 'x   x', 'x	x', 'x		x');
foreach ($spaces as $space) {
    echo Text_Filter::filter($space, 'space2html', array('encode_all' => false));
    echo "\n";
    echo Text_Filter::filter($space, 'space2html', array('encode_all' => true));
    echo "\n";
}

?>
--EXPECT--
x x
x&nbsp;x
x&nbsp; x
x&nbsp;&nbsp;x
x&nbsp; &nbsp;x
x&nbsp;&nbsp;&nbsp;x
x&nbsp; &nbsp; &nbsp; &nbsp; x
x&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;x
x&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; x
x&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;x
