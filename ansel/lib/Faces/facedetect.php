<?php
/**
 * Face_detect implementation
 *
 * $Horde: ansel/lib/Faces/facedetect.php,v 1.1.2.2 2008/10/30 17:32:00 mrubinsk Exp $
 *
 * @author  Duck <duck@obala.net>
 * @package Ansel
 */
class Ansel_Faces_facedetect extends Ansel_Faces {

    /**
     * Where the face defintions are stored
     */
    var $_defs = '';

    /**
     * Create instance
     */
    function Ansel_Faces_facedetect($params)
    {
        $this->_defs = $params['defs'];
    }

    /**
     * Get faces
     *
     * @param string $file Picture filename
     */
    function _getFaces($file)
    {
        $result = Util::loadExtension('facedetect');
        if (!$result) {
            return PEAR::raiseError(_("You do not have the facedetect extension enabled in PHP"));
        }
        return face_detect($file, $this->_defs);
    }

    /**
     * Check if a face in is inside anoter face
     *
     * @param array $face  Face we are cheking
     * @param array $faces Existing faces
     *
     * @param int Face ID containg passed face
     */
    function _isInFace($face, $faces)
    {
        foreach ($faces as $id => $rect) {
            if ($face['x'] > $rect['x'] && $face['x'] + $face['w'] < $face['x'] + $rect['w']
                && $face['y'] > $rect['y'] && $face['y'] + $face['h'] < $face['y'] + $rect['h']) {
                return $id;
            }
        }

        return false;
    }

    function _getParamsArray($face_id, $image, $rect)
    {
        $params = array($face_id,
                $image->id,
                $image->gallery,
                $rect['x'],
                $rect['y'],
                $rect['x'] + $rect['w'],
                $rect['y'] + $rect['h']);
       return $params;
    }

    function _createView($face_id, $image, $rect)
    {
        return $this->createView($face_id,
                                $image,
                                $rect['x'],
                                $rect['y'],
                                $rect['x'] + $rect['w'],
                                $rect['y'] + $rect['h']);
    }
}
