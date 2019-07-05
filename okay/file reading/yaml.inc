<?php

namespace fixture {

}

namespace ok {

    EXPECT("reading non-existant file");

    // note warning suppression
    $data = @yaml_parse_file('non-existant.yml');
    
    assert( $data === false );
}

 