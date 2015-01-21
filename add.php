<?php require 'functions.php';

if ( isset( $_POST['url'] ) )   // Check if the form was submitted
	add_link( $_POST['url'] );
else                            // Return home otherwise
	header( "Location: ./" );
