<?php

namespace fixture {

    global $PERSISTENCE;
}

namespace ok {

    EXPECT("model created with query-role added later");

    $db = $PERSISTENCE->withTable('tests', 'id');

    $test = new \Actor\StdClass;
    $test->id = 1;

    $db->useAsQuery($test);

    _("to be able to query Tests - by id");

    $set = $test->fetch();

    assert(1 == count($set));
}

namespace ok {

    EXPECT("model created with query-role added initially");

    $db = $PERSISTENCE->withTable('tests', 'id');

    $test = $db->useAsQuery(new \Actor\StdClass);

    $test->id = 1;

    _("to be able to query Tests - by id");

    $set = $test->fetch();

    assert(1 == count($set));
}

namespace ok {

    EXPECT("empty model with query-role provided by the context");

    $db = $PERSISTENCE->withTable('tests', 'id');

    $test = $db->newQuery();

    $test->id = 1;

    _("to be able to query Tests - by id");

    $set = $test->fetch();

    assert(1 == count($set));
}

namespace ok {

    EXPECT("model created with query-role - fetch ids");

    $db = $PERSISTENCE->withTable('tests', 'id');

    $test = new \Actor\StdClass;
    
    $db->useAsQuery($test);

    _("to be able to query Tests - by id");

    $set = $test->fetchIds([1,2,3]);
 
    assert(3 == count($set));
}

namespace ok {

    EXPECT("using model with query-role to delete a row by id");

    $db = $PERSISTENCE->withTable('tests', 'id');

    $test = $db->newQuery();
    $test->id = 1;

    _("to return true -> success");

    $result = $test->delete();

    assert(true == $result);

    _("and a query to return empty");

    $set = $test->fetch();

    assert(0 == count($set));
}

namespace ok {

    EXPECT("to be able to fetch all Tests");

    $db = $PERSISTENCE->withTable('tests', 'id');

    $test = new \Actor\StdClass;

    $set = $db->useAsQuery($test)->fetchAll();

    assert(4 == count($set));
}
