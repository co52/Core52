<\x3f

/**
 * <?= ucfirst($plain_name); ?> Controller
 */
class <?= $class_name; ?> extends <?= $controller_type; ?> {

	<? if ($form_fields) { ?>
	protected $_fields = <?= $form_fields; ?>;
	<? } ?>

	public function _default() {
		
		print "<?= ucfirst($plain_name); ?>";
	}

}