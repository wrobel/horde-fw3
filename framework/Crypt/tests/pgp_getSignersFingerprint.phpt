--TEST--
Horde_Crypt_pgp::getSignersFingerprint()
--SKIPIF--
<?php require 'pgp_skipif.inc'; ?>
--FILE--
<?php

require 'pgp.inc';
echo $pgp->getSignersFingerprint(file_get_contents(dirname(__FILE__) . '/fixtures/pgp_signed.txt'));

?>
--EXPECT--
BADEABD7