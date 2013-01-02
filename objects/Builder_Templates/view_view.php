<\x3f= fastview_inc('_inc/head'); \x3f>

<h2><\x3f= e($model); \x3f></h2>
<a href="<?= $url; ?>/edit:<\x3f= $model->pk(); \x3f>">Edit</a>

<dl>
	<? foreach($field_names as $field) { ?>
		<dt><?= $form->field_name($field); ?></dt>
		<dd><\x3f= e($model-><?= $field; ?>); \x3f></dd>
	<? } ?>
</dl>

<\x3f= fastview_inc('_inc/foot'); \x3f>