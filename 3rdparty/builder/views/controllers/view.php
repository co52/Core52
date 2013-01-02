<?= fastview_inc('_inc/head'); ?>

	<h1><?= $controller; ?></h1>
	<h2>Functions</h2>

 <table class="table table-bordered" id="functions">
	<tbody>
	<? foreach ($controller->functions() as $function) { ?>

	<tr><td>
		<h3 style="line-height: 12px; margin-bottom: .3em">
			<?= $function['name']; ?> 
			<span class="badge" style="vertical-align: top;"><?= $function['comment']['author']; ?></span>
		</h3>

		<p><?= $function['comment']['description']; ?></p>
		<code style="color: black; padding: 8px; margin: 8px 0; display: block;">function <?= $function['name']; ?> 
			(<? foreach ($function['arguments'] as $i => $argument) { $param = $function['comment']['params'][$i]; ?>
				<span class="argument" rel="tooltip" data-original-title="<?= $param[2]; ?>"><?= $argument; ?> 
					<span class="label"><?= $param[1]; ?></span></span><? if ($i + 1 != count($function['arguments'])) { ?>,<? } ?>

			<? } ?>)
	</td></tr>

	<?/*<tr><td colspan="2"><pre><code><?= $function['comment']['raw']; ?></code></pre></td></tr>
	<tr>
		<td class="name"><?= $function['name']; ?></td>
		<td class="tools">
			<?= implode(', ', (array) $function['arguments']); ?>
		</td>
	</tr> */?>
	<? } ?>
</tbody>
</table>


<?= fastview_inc('_inc/foot'); ?>