<?php foreach ($result as $face_id => $face_name):?>
    <div id="face<?php echo $face_id ?>">
    <img src="<?php echo $autocreate ? $faces->getFaceUrl($image_id, $face_id) : Util::addParameter(Horde::applicationUrl('faces/img.php'), 'face', $face_id); ?>" alt="<?php echo $face_id ?>" style="padding-bottom: 5px; padding-left: 5px; width: 50px; height: 50px;" />
    <input type="text" name="facename<?php echo $face_id ?>" id="facename<?php echo $face_id ?>" size="15" value="<?php echo $face_name ?>" />
    <a href="javascript:void(0)" onclick="setFaceName(<?php echo $image_id ?>, <?php echo $face_id ?>)" title="<?php echo _("Save face name") ?>">
      <img src="<?php echo $imgdir ?>/save.png" /></a>
    <a href="javascript:void(0)" onclick="deleteFace(<?php echo $image_id ?>, <?php echo $face_id ?>)" title="<?php echo _("Delete face") ?>">
      <img src="<?php echo $imgdir ?>/delete.png" /></a>
    <a href="<?php echo Util::addParameter($customurl, array('face' => $face_id, 'image' => $image_id, 'url' => (!empty($url) ? $url : '')), null, false) ?>" title="<?php echo _("Edit face region") ?>">
      <img src="<?php echo $imgdir ?>/edit.png" /></a>
    </div>
<?php endforeach;?>