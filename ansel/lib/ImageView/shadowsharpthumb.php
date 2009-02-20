<?php
/**
 * ImageView to create the shadowsharpthumb view (sharp corners, shadowed)
 *
 * $Horde: ansel/lib/ImageView/shadowsharpthumb.php,v 1.6 2008/01/16 01:30:58 mrubinsk Exp $
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_ImageView_shadowsharpthumb extends Ansel_ImageView {

    var $need = array('drop_shadow');

    function _create()
    {
        $this->_image->_image->resize(min($GLOBALS['conf']['thumbnail']['width'], $this->_dimensions['width']),
                                      min($GLOBALS['conf']['thumbnail']['height'], $this->_dimensions['height']),
                                      true);

        /* Don't bother with these effects for a stack image
         * (which will have a negative gallery_id). */
        if ($this->_image->gallery > 0) {
            if (is_null($this->_style)) {
                $gal = $GLOBALS['ansel_storage']->getGallery($this->_image->gallery);
                $styleDef = $gal->getStyle();
            } else {
                $styleDef = Ansel::getStyleDefinition($this->_style);
            }

            $res = $this->_image->_image->addEffect(
                'border', array('bordercolor' => '#333'));

            $res = $this->_image->_image->addEffect('drop_shadow',
                                                    array('background' => $styleDef['background'],
                                                          'padding' => 5,
                                                          'distance' => '8',
                                                          'fade' => 2));

            if (is_a($res, 'PEAR_Error')) {
                Horde::logMessage($res, __FILE__, __LINE__, PEAR_LOG_ERR);
            }

            return $this->_image->_image->applyEffects();
        }
    }

}