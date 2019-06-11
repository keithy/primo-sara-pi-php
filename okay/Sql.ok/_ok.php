<?php // Per Suite

# _ok.php turns a directory of *.inc scripts into a test suite.
# doubles as the one-time setup script

global $OKAY_SUITE;
$OKAY_SUITE = __DIR__;

# first time read the library 
if (!defined('__OKAY__')) {
    require_once(__DIR__ . '/../../vendor/okay/okay/_okay.php');
    return;
}

# second time
# Initialisation code - one-time setup for this directory
require_once( __DIR__ . "/../../vendor/autoload.php");

global $READER;

$READER = new Primo\Phinx\ConfigReader( __DIR__. '/_fixtures/phinx.php');

