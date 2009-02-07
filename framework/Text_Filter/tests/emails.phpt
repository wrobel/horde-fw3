--TEST--
Text_Filter_email tests
--FILE--
<?php

require dirname(__FILE__) . '/../Filter.php';

$emails = <<<EOT
Inline address jan@horde.org test.
Inline protocol mailto: jan@horde.org test with whitespace.
Inline Outlook [mailto:jan@horde.org] test.
Inline angle brackets &lt;jan@horde.org&gt; test.
Inline angle brackets with mailto &lt;mailto:jan@horde.org&gt; test.
Inline with parameters jan@horde.org?subject=A%20subject&body=The%20message%20body test.
Inline protocol with parameters mailto:jan@horde.org?subject=A%20subject&body=The%20message%20body test.
jan@horde.org in front test.
At end test of jan@horde.org
Don't link http://jan@www.horde.org/ test.
Real world example: mailto:pmx-auto-approve%2b27f0e770e2d85bf9bd8fea61f9dedbff@example.com?subject=Release%20message%20from%20quarantine&body=%5b%23ptn6Pw-1%5d
EOT;

echo Text_Filter::filter($emails, 'emails', array('always_mailto' => true, 'class' => 'pagelink'));

?>
--EXPECT--
Inline address <a class="pagelink" href="mailto:jan@horde.org" title="New Message to jan@horde.org">jan@horde.org</a> test.
Inline protocol mailto: <a class="pagelink" href="mailto:jan@horde.org" title="New Message to jan@horde.org">jan@horde.org</a> test with whitespace.
Inline Outlook [mailto:<a class="pagelink" href="mailto:jan@horde.org" title="New Message to jan@horde.org">jan@horde.org</a>] test.
Inline angle brackets &lt;<a class="pagelink" href="mailto:jan@horde.org" title="New Message to jan@horde.org">jan@horde.org</a>&gt; test.
Inline angle brackets with mailto &lt;mailto:<a class="pagelink" href="mailto:jan@horde.org" title="New Message to jan@horde.org">jan@horde.org</a>&gt; test.
Inline with parameters <a class="pagelink" href="mailto:jan@horde.org?subject=A%20subject&body=The%20message%20body" title="New Message to jan@horde.org">jan@horde.org?subject=A%20subject&body=The%20message%20body</a> test.
Inline protocol with parameters mailto:<a class="pagelink" href="mailto:jan@horde.org?subject=A%20subject&body=The%20message%20body" title="New Message to jan@horde.org">jan@horde.org?subject=A%20subject&body=The%20message%20body</a> test.
<a class="pagelink" href="mailto:jan@horde.org" title="New Message to jan@horde.org">jan@horde.org</a> in front test.
At end test of <a class="pagelink" href="mailto:jan@horde.org" title="New Message to jan@horde.org">jan@horde.org</a>
Don't link http://jan@www.horde.org/ test.
Real world example: mailto:<a class="pagelink" href="mailto:pmx-auto-approve%2b27f0e770e2d85bf9bd8fea61f9dedbff@example.com?subject=Release%20message%20from%20quarantine&body=%5b%23ptn6Pw-1%5d" title="New Message to pmx-auto-approve%2b27f0e770e2d85bf9bd8fea61f9dedbff@example.com">pmx-auto-approve%2b27f0e770e2d85bf9bd8fea61f9dedbff@example.com?subject=Release%20message%20from%20quarantine&body=%5b%23ptn6Pw-1%5d</a>
