<?php

namespace App\DataFixtures;

use App\Entity\Advice;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Création d'une trantaine de conseils
        for ($i = 0; $i < 50; $i++) {
            $advice = new Advice();
            $advice->setMonth(rand(1,12));
            $advice->setText('Conseil numéro : ' . $i);
            $manager->persist($advice);
        }
        $manager->flush();
    }
}
