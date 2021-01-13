<?php include(__DIR__ . '/partial.header.php') ?>
<body>
	<div class="form">
		<? if ( empty($types) ) exit ?>

		<div class="projects--title">
			<h1> Select a project type to add </h1>
		</div>	

	<!-- Navigatie naar project setup pagina'' -->

		<div class="projects--select">
			<? foreach ( $types as $type ) { ?>
				<button class="projects--button"><a href="/setup?type=<?= $type ?>"> Set up a <?= $type ?> project. </a></button>
			<? } ?>
		</div>

		<!-- Navigatie naar overzicht -->
		<div class="overview">
			<button class="overview--button"><a href="/list"> View running containers </a></button>
		</div>
	</div>	

</body>
<?