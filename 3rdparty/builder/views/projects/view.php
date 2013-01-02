<?= fastview_inc('_inc/head'); ?>

<h1><?= strtoupper($project); ?></h1>

<div class="row">
 	<div class="span4">
 		<h2>Controllers  (<?= count($project->controllers());?>)</h2>

		 <table class="table table-bordered" id="controllers">
			<tbody>
			<? foreach ($project->controllers() as $controller) { ?>
			<tr>
				<td class="name"><a href="/controllers/view:<?= $project; ?>:<?= $controller; ?>"><?= $controller; ?></a></td>

			</tr>
			<? } ?>
		</tbody>
		</table>
 		<p><a href="#" class="btn btn-primary">New Controller</a></p>

	</div>

	<div class="span4">
		<h2>Models (<?= count($project->models());?>)</h2>
		<table class="table table-bordered" id="controllers">
			<tbody>
				<? foreach ($project->models() as $model) { ?>
				<tr>
					<td class="name"><a href="/models/view:<?= $project; ?>:<?= $model; ?>"><?= $model; ?></a></td>
				</tr>
				<? } ?>
			</tbody>
		</table>

		<p><a href="#" class="btn btn-primary">New Model</a></p>

	</div>
   <!-- <div class="span4"><h2>Views</h2><a href="#" class="btn btn-primary">New View</a></div> -->
</div>



<?= fastview_inc('_inc/foot'); ?>
