<?php
	include "../WC.php";

	$a=new App();

	$a->route("/r/(.*?)", function($app, $u){
		printf("hi %s", $u);
	});

	$b=new App();
	$b->route("/", function($app){ $app->template("tpl_inner.php"); });

	$a->mount("/b", $b);

	$a->run();