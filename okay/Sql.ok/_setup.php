<?php

// Per Given

global $READER, $PERSISTENCE;

// Fresh fixture copy per "Given"
$environment = $READER->choose('seeded')->which('snapshots')->copyTo($READER->choose('seeded'));
 
$PERSISTENCE = new Persistence\Sql( $environment->pdo() );

