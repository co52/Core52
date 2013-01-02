<?= fastview_inc('_inc/head'); ?>

<table class="table table-bordered" id="projects">
	<thead>
		<tr>
			<th>Name</th>
			<th>Revision</th>
		</tr>
	</thead>
	<tbody>
	<? foreach ($projects as $project) { ?>
	<tr>
		<td class="name"><a href="/projects/view:<?= $project['name']; ?>"><?= $project['name']; ?></a></td>
		<td class="revision">
			<? if ($project['revision']) { ?>
				<span class="badge" rel="tooltip" data-original-title="checking status"><?= $project['revision']; ?></span>
			<? } ?>
		</td>
	</tr>
	<? } ?>
</tbody>
</table>
<?= fastview_inc('_inc/foot'); ?>
