<PHP= fastview_inc('_inc/head'); PHP>

<h2><?= $singular; ?></h2>

<form action="<?= $url; ?>/<PHP= $url; PHP>" method="POST" <? if (in_array('upload', $form->types)) { ?>enctype="multipart/form-data"<? } ?>>
	<PHP= $form_obj->triggerfield(); PHP>

<? 
	foreach($field_names as $field) { 
?>

	<label>
<?	if ($form->types[$field] == 'checkbox') { ?>
		<PHP= implode($fields['<?= $form->field_name($field); ?>']); PHP> <?= $form->field_name($field); ?>
<? 	} else { ?>
		<?= $form->field_name($field); ?>

		<PHP= $fields['<?= $form->field_name($field); ?>']; PHP><? 	} ?>

	</label>

	<PHP= $errors['<?= $field; ?>']; PHP>

<? if($form->types[$field] == 'upload') { ?>
	<PHP if ($model-><?= $field; ?>) { PHP>
<? 		if (stripos($field, 'image') !== FALSE) { ?>
		<img src="/static/uploads/<PHP= $model-><?= $field; ?>; PHP>" />
<? 		} else { ?>
		<a href="/static/uploads/<PHP= $model-><?= $field; ?>; PHP>"><PHP= $model-><?= $field; ?>; PHP></a>
<?		} ?> 
	<PHP } PHP>
<?	} ?>
<? } ?>

	<div class="form-actions">
		<input type="submit" value="Save" class="btn btn-large btn-primary" />
	</div>

</form>

<PHP= fastview_inc('_inc/foot'); PHP>