<?php
/**
 * Ansel_XRequest:: class for wrapping Ajax requests made by various Ansel pages..
 * Based on the Imple class from Imp.
 *
 * $Horde: ansel/lib/XRequest.php,v 1.7.2.1 2009/01/06 15:22:28 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_XRequest {

    /**
     * Any needed parameters for the concrete classes.
     *
     * @var array
     */
    var $_params = array();

    /**
     * Any javascript variables that should be sent to the page
     * as JSON data.
     */
    var $_jsVars = array();

    /**
     * Return a concrete Ansel_Request instance based on $type.
     *
     * @param string $type  The concrete class to return.
     * @param unknown_type $params
     */
    function factory($type, $params = array())
    {
        $classname = basename($type);
        if (!$classname) {
            return false;
        }
        $class = 'Ansel_XRequest_' . $classname;
        if (!class_exists($class)) {
            include dirname(__FILE__) . '/XRequest/' . $classname . '.php';
            if (!class_exists($class)) {
                return false;
            }
        }

        return new $class($params);
    }

    /**
     * Constructor
     *
     * @param array $params  Any parameters needed by the class.
     * @return Ansel_Request
     */
    function Ansel_XRequest($params)
    {
        $this->_params = $params;
    }

    /**
     * Attach to a javascript event.
     */
    function attach()
    {
        Horde::addScriptFile('prototype.js', 'horde', true);
        $this->_attach();
    }

    /**
     * Ouputs JSON variable data that this class' javascript might need.
     *
     * @param array $js  Optional array of javascript code to include in
     *                   addition to the json output.
     */
    function _outputJS($js = array())
    {
        require_once 'Horde/Serialize.php';

        echo "\n" . '<script type="text/javascript">' . "\n";

        if (count($this->_jsVars)) {
            echo '    //<![CDATA[' . "\n";

            foreach ($this->_jsVars as $key => $value) {
                $json = Horde_Serialize::serialize($value, SERIALIZE_JSON,
                                                   NLS::getCharset());
                echo $key . ' = ' . $json . ';' . "\n";
            }
            echo '    //]]>' . "\n";
        }
        if (count($js)) {
            $jsout = implode("\n", $js);
            echo $jsout;
        }
        echo "\n" . '</script>';
    }

    /**
     * Perform the requested action
     *
     * @param array  $args  Any arguments needed to handle the request.
     */
    function handle($args)
    {
    }

}
