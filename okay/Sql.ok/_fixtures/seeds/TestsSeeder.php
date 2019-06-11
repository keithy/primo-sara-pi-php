<?php

use Phinx\Seed\AbstractSeed;

class TestsSeeder extends AbstractSeed {

    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     */

    public function run() {
        $faker = Faker\Factory::create();

        $data = [];
        for ($i = 0; $i < 5; $i++) {
            $data[] = [                
                'name' => $faker->userName,
                'email' => $faker->email,
            ];
        }

        $this->table('tests')->insert($data)->save();
    }

}
