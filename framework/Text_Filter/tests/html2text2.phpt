--TEST--
Text_Filter_html2text lists test
--FILE--
<?php

require dirname(__FILE__) . '/../Filter.php';
$html = <<<EOT
<ul>
  <li>This is a short line.</li>
  <li>This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line.</li>
  <li>And again a short line.</li>
</ul>
EOT;
echo Text_Filter::filter($html, 'html2text', array('width' => 50));
echo Text_Filter::filter($html, 'html2text', array('wrap' => false));

?>
--EXPECT--
  * This is a short line.
  * This is a long line. This is a long line.
This is a long line. This is a long line. This is
a long line. This is a long line. This is a long
line. This is a long line. This is a long line.
This is a long line. This is a long line. This is
a long line.
  * And again a short line.



  * This is a short line.
  * This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line.
  * And again a short line.
