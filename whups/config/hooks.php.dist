<?php
/**
 * Whups Hooks configuration file.
 *
 * THE HOOKS PROVIDED IN THIS FILE ARE EXAMPLES ONLY.  DO NOT ENABLE THEM
 * BLINDLY IF YOU DO NOT KNOW WHAT YOU ARE DOING.  YOU HAVE TO CUSTOMIZE THEM
 * TO MATCH YOUR SPECIFIC NEEDS AND SYSTEM ENVIRONMENT.
 *
 * For more information please see the horde/config/hooks.php.dist file.
 *
 * $Horde: whups/config/hooks.php.dist,v 1.7 2008/06/24 17:38:13 jan Exp $
 */

// This is an example hook that customizes the grouping of ticket fields. It
// splits all fields into two groups, one for custom attribute fields and one
// for the regular fields. Additionally, it moves the 'queue' field at the top
// of the regular fields list.

// if (!function_exists('_whups_hook_group_fields')) {
//     function _whups_hook_group_fields($type, $fields)
//     {
//         $common_fields = $attributes = array();
//         foreach ($fields as $field) {
//             if (substr($field, 0, 10) == 'attribute_') {
//                 $attributes[] = $field;
//             } elseif ($field == 'queue') {
//                 array_unshift($common_fields, $field);
//             } else {
//                 $common_fields[] = $field;
//             }
//         }
//         return array('Common Fields' => $common_fields,
//                      'Attributes' => $attributes);
//     }
// }

// This is an example hook that intercepts ticket changes. If a comment has
// been added to a closed ticket, it will re-open the ticket, setting the
// state to "assigned". You might want to use numeric ids for the 'to' item in
// a real life hook.

// if (!function_exists('_whups_hook_ticket_update')) {
//     function _whups_hook_ticket_update($ticket, $changes)
//     {
//         /* We only want to change the ticket state if it is closed, a comment
//          * has been added, and the state hasn't been changed already. */
//         if (!empty($changes['comment']) &&
//             empty($changes['state']) &&
//             $ticket->get('state_category') == 'resolved') {
//             /* Pick the first state from the state category 'assigned'. */
//             $states = $GLOBALS['whups_driver']->getStates($ticket->get('type'),
//                                                           'assigned');
//             /* These three item have to exist in a change set. */
//             $changes['state'] = array(
//                 'to' => key($states),
//                 'from' => $ticket->get('state'),
//                 'from_name' => $ticket->get('state_name'));
//         }
// 
//         return $changes;
//     }
// }
