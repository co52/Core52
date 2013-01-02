<PHP= fastview_inc('_inc/head'); PHP>

<h2><?= $plural; ?></h2>
<a href="<?= $url; ?>/add">Add <?= $singular; ?></a>

<PHP if ($records) { PHP>
<table>
	<thead>
		<tr>
<? 			foreach($field_names as $field) { ?>
			<th><?= $form->field_name($field); ?></th>
<? 			} ?>
		</tr>
	</thead>
	
	<tbody>
		<PHP foreach ($records as $record) { PHP> 
		<tr>
<? 	
	foreach($field_names as $field) { $i++; 
		if ($i == 1) { 
?> 
			<td><a href="<?= $url; ?>/view:<PHP= e($record->pk()); PHP>"><PHP= e($record-><?= $field; ?>); PHP></a></td>
<? 		} else { ?>
			<td><PHP= e($record-><?= $field; ?>); PHP></td>
<?
 		} 
 	} 
?>
			<td>
				<a href="<?= $url; ?>/edit:<PHP= e($record->pk()); PHP>">Edit</a>
				<a href="<?= $url; ?>/delete:<PHP= e($record->pk()); PHP>:<PHP= e($record->checksum()); PHP>">Delete</a>
			</td>

		</tr>
		<PHP } PHP> 
	</tbody>

</table>
<PHP } else { PHP>
	<p>There are no <?= $plural; ?>.</p>
<PHP } PHP>

<PHP= fastview_inc('_inc/foot'); PHP>