<?php
/**
 * Image effect for adding a drop shadow.
 *
 * $Horde: framework/Image/Image/Effect/im/drop_shadow.php,v 1.11.2.1 2007/12/20 13:49:11 jan Exp $
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @since   Horde 3.2
 * @package Horde_Image
 */
class Horde_Image_Effect_im_drop_shadow extends Horde_Image_Effect {

    /**
     * Valid parameters: Most are currently ignored for the im version
     * of this effect.
     *
     * @TODO
     *
     * @var array
     */
    var $_params = array('distance' => 5, // This is used as the x and y offset
                         'width' => 2,
                         'hexcolor' => '000000',
                         'angle' => 215,
                         'fade' => 3, // Sigma value
                         'padding' => 0,
                         'background' => 'none');

    /**
     * Apply the effect.
     *
     * @return mixed true | PEAR_Error
     */
    function apply()
    {
        if (!is_null($this->_image->_imagick)) {
            // $shadow is_a ImagickProxy object
            $shadow = $this->_image->_imagick->cloneIM();
            $shadow->setImageBackgroundColor('black');
            $shadow->shadowImage(80, $this->_params['fade'],
                                 $this->_params['distance'],
                                 $this->_params['distance']);
            if ($this->_params['padding']) {
                $shadow->borderImage($this->_params['background'],
                                     $this->_params['padding'],
                                     $this->_params['padding']);
            }
            $shadow->compositeImage($this->_image->_imagick,
                                    constant('Imagick::COMPOSITE_OVER'),
                                    0, 0);
            $this->_image->_imagick->clear();
            $this->_image->_imagick->addImage($shadow);
            $shadow->destroy();
        } else {
            $this->_image->_postSrcOperations[] = '\( +clone -background black -shadow 80x' . $this->_params['fade'] . '+' . $this->_params['distance'] . '+' . $this->_params['distance'] . ' \) +swap -background none -flatten +repage -bordercolor ' . $this->_params['background'] . ' -border ' . $this->_params['padding'] ;
        }
        $this->_image->_width = 0;
        $this->_image->_height = 0;

        return true;
    }

}
