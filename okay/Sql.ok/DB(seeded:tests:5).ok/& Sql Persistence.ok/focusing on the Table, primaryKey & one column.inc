<?php

namespace fixture {

    global $PERSISTENCE;

    $db = $PERSISTENCE->withTable('tests', 'id', 'name');
    
    require_once __DIR__ . "/TestClass.php";

}

namespace ok {

    EXPECT("fetchAll()");

    _('to retrieve 5 items into instances of Actor\StdClass');

    $set = $db->fetchAll();

    assert(5 == count($set), count($set));

    assert('Actor\StdClass' == get_class($set[0]));

    _('but only have the two properties');

    assert(2 == count(array_keys($set[0]->toArray())), count(array_keys((array) $set[0])));
}

namespace ok {

    EXPECT("fetchAll('ok\TestClass')");

    _('to retrieve 5 items into instances of ok\TestClass');

    $set = $db->fetchAll('ok\TestClass');

    assert(5 == count($set), count($set));

    assert('ok\TestClass' == get_class($set[0]));
}

namespace ok {

    EXPECT("fetchAll('ok\TestClass', 'name')");

    _('to retrieve 5 items into instances of ok\TestClass');

    $set = $db->fetchAll('ok\TestClass', 'name');

    assert(5 == count($set), count($set));

    assert('ok\TestClass' == get_class($set[0]));

    _('but only have the one property');

    assert(1 == count(array_keys($set[0]->toArray())), count(array_keys((array) $set[0])));
}

