<?php
/**
 * $Horde: framework/Rdo/examples/Horde/Rdo/User.php,v 1.1.2.1 2008-05-15 23:23:14 chuck Exp $
 *
 * @package Horde_Rdo
 */

@include './conf.php';
if (empty($conf['sql'])) {
    die('No sql configuration found.');
}

require_once 'Horde/Loader.php';

/**
 */
class User extends Horde_Rdo_Base {
}

/**
 */
class UserMapper extends Horde_Rdo_Mapper {

    public function getAdapter()
    {
        $adapter = isset($GLOBALS['conf']['sql']['adapter']) ? $GLOBALS['conf']['sql']['adapter'] : 'pdo';
        return Horde_Rdo_Adapter::factory($adapter, $GLOBALS['conf']['sql']);
    }

}

$um = new UserMapper();

// Count all users.
$userCount = $um->count();
echo "# users: $userCount\n";

// Get the number of new users in May 2005
//$userCount = $um->count('created > \'2005-05-01\' AND created <= \'2005-05-31\'');
//echo "# new: $userCount\n";

// Check if id 1 exists.
$exists = $um->exists(1);
echo "exists: " . ($exists ? 'yes' : 'no') . "\n";

// Look for Alice
$userTwo = $um->find(Horde_Rdo::FIND_FIRST, array('name' => 'Alice'));
if ($userTwo) {
    echo "Found Alice: id $userTwo->id\n";
} else {
    echo "No Alice found, creating:\n";
    $userOne = $um->create(array('name' => 'Alice', 'phone' => '212-555-6565'));
    $userOneId = $userOne->id;
    echo "Created new user with id: $userOneId\n";
}

// Change the name of the user and save.
if ($userTwo) {
    $userTwo->name = 'Bob';
    $result = $userTwo->save();
    var_dump($result);
}

// List all users.
echo "Looking for all:\n";
foreach ($um->find(Horde_Rdo::FIND_ALL) as $userOb) {
    echo "  (" . $userOb->id . ") " . $userOb->name . "\n";
}

// Fetch id 2.
//$user = $um->find(2);
// Try to delete it.
//$result = $user->delete();
//var_dump($result);

/*
// $user->billingAddresses is an Iterator.
foreach ($user->billingAddresses as $billingAddress) {
    echo $billingAddress->zipCode . "\n";
}

if ($user->favorite) {
    echo $user->favorite->name . "\n";
} else {
    $user->favorite = new User(array('name' => 'Charles'));
}
*/
