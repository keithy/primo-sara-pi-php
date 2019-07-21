<?php

namespace fixture {

    global $PERSISTENCE;

    $db = $PERSISTENCE->withTableEtc('Actors\StdClass', 'tests', 'id');
}

namespace ok {

    EXPECT("model created with query-role added later");

    $test = new \Actors\StdClass;
    $test->id = 1;

    $db->useAsQuery($test);

    _("to be able to query Tests - by id");

    $set = $test->dbFetch();

    assert(1 == count($set), count($set));
}

namespace ok {

    EXPECT("model created with query-role added initially");

    $test = $db->useAsQuery(new \Actors\StdClass);

    $test->id = 1;

    _("to be able to query Tests - by id");

    $set = $test->dbFetch();

    assert(1 == count($set));
}

namespace ok {

    EXPECT("model created with newQuery()");

    $test = $db->newQuery();

    _("to create query model of class provided by the context");

    assert('Actors\StdClass' == get_class($test));

    _("to be able to query Tests - by id");

    $test->id = 1;
    $set = $test->dbFetch();

    assert(1 == count($set));
}

namespace ok {

    EXPECT("model created with query-role - fetch ids");

    $test = new \Actors\StdClass;

    $db->useAsQuery($test);

    _("to be able to query Tests - by id");

    $set = $test->dbFetchIds([1, 2, 3]);

    assert(3 == count($set));
}

namespace ok {

    EXPECT("using model with query-role to delete a row by id");

    $test = $db->newQuery();
    $test->id = 1;

    _("to return true -> success");

    $result = $test->dbDelete();

    assert(true == $result);

    _("and a query to return empty");

    $set = $test->dbFetch();

    assert(0 == count($set));
}

namespace ok {

    EXPECT("to be able to fetch all Tests");

    $test = new \Actors\StdClass;

    $set = $db->useAsQuery($test)->dbFetchAll();

    assert(4 == count($set));
}
