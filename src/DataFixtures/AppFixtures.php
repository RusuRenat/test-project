<?php

namespace App\DataFixtures;

use App\Entity\Books;
use App\Utils\Constants\Status;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $batchSize = 100;

        for ($i = 1; $i <= 1000000; $i++) {
            $entity = new Books();
            $entity->setStatus(Status::ACTIVE);
            $entity->setTitle($faker->title);
            $entity->setDescription($faker->text);
            $entity->setAuthor($faker->name);
            $entity->setPrice($faker->randomFloat());

            $manager->persist($entity);

            if ($i % $batchSize === 0) {
                $manager->flush();
                $manager->clear();
            }
        }

        $manager->flush();
        $manager->clear();
    }
}
