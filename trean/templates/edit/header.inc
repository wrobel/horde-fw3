<script type="text/javascript">
function cancelEdit()
{
<?php if (!Util::getFormData('popup')) { ?>
    document.bookmark_edit_form.actionID.value = 'cancel';
    return !document.bookmark_edit_form.submit();
<?php } else { ?>
    return !window.close();
<?php } ?>
}
</script>

<form name="bookmark_edit_form" action="edit.php" method="post">
<?php echo Util::formInput() ?>
<input type="hidden" name="actionID" value="save" />
<input type="hidden" name="f" value="<?php echo htmlspecialchars($folderId) ?>" />
<input type="hidden" name="popup" value="<?php echo (int)Util::getFormData('popup') ?>" />
