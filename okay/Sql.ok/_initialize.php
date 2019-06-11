<?php // On INIT Request

require( __DIR__ . "/_ok.php");

global $READER;

// recreate all of the fixture snapshots
foreach ($READER->choices() as $choice) {
    $READER->choose($choice)->clobber(true); // delete fixture
    $READER->choose($choice)->which('snapshots')->create(true); // delete and re-migrate i.e. re-create fixture
}
 