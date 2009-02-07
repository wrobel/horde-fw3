<?php
/**
 * Image border decorator for the Horde_Image package.
 *
 * $Horde: framework/Image/Image/Effect/im/border.php,v 1.2.2.1 2007/12/20 13:49:11 jan Exp $
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Image
 */
class Horde_Image_Effect_im_border extends Horde_Image_Effect {

    /**
     * Valid parameters for border effects:
     *
     *   bordercolor     - Border color. Defaults to black.
     *   borderwidth     - Border thickness, defaults to 1 pixel.
     *   roundwidth      - Width of the corner rounding. Defaults to none.
     *
     * @var array
     */
    var $_params = array('bordercolor' => 'black',
                         'borderwidth' => 1);

    /**
     * Draw the border.
     *
     * This draws the configured border to the provided image. Beware,
     * that every pixel inside the border clipping will be overwritten
     * with the background color.
     */
    function apply()
    {
        if (!is_null($this->_image->_imagick)) {
             $this->_image->_imagick->borderImage(
                $this->_params['bordercolor'],
                $this->_params['borderwidth'],
                $this->_params['borderwidth']);
        } else {
            $this->_image->_postSrcOperations[] = sprintf(
                "-bordercolor \"%s\" -border %s",
                $this->_params['bordercolor'],
                $this->_params['borderwidth']);
        }
        return true;
    }

}
