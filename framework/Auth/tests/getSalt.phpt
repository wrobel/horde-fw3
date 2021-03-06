--TEST--
Auth::getSalt() test
--FILE--
<?php

require dirname(__FILE__) . '/../Auth.php';
require dirname(__FILE__) . '/credentials.php';

for ($i = 0; $i < count($passwords); $i++) {
    echo $encryptions[$i] . ':' . Auth::getSalt($encryptions[$i], $passwords[$i], 'foobar') . "\n";
}

?>
--EXPECT--
plain:
msad:
sha:
crypt:8e
crypt-des:45
crypt-md5:$1$537a3a0e$
crypt-blowfish:*0OayF9ttbxIs
md5-base64:
ssha:czfodunn
ssha:�(K�
ssha:��>5
smd5:izlrqwhv
smd5:irvc
smd5:0� 
smd5:X�W
aprmd5:11CBbKXP
md5-hex:
