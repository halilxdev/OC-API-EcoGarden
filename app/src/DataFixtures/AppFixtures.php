<?php

namespace App\DataFixtures;

use App\Entity\Advice;
use App\Entity\Month;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Création des mois
        $monthArray = [
            ["numeric_value"=> 1,"name"=> "Janvier"],
            ["numeric_value"=> 2,"name"=> "Février"],
            ["numeric_value"=> 3,"name"=> "Mars"],
            ["numeric_value"=> 4,"name"=> "Avril"],
            ["numeric_value"=> 5,"name"=> "Mai"],
            ["numeric_value"=> 6,"name"=> "Juin"],
            ["numeric_value"=> 7,"name"=> "Juillet"],
            ["numeric_value"=> 8,"name"=> "Août"],
            ["numeric_value"=> 9,"name"=> "Septembre"],
            ["numeric_value"=> 10,"name"=> "Octobre"],
            ["numeric_value"=> 11,"name"=> "Novembre"],
            ["numeric_value"=> 12,"name"=> "Décembre"]
        ];
        $months = [];
        foreach($monthArray as $m) {
            $month = new Month();
            $month->setNumericValue($m['numeric_value']);
            $month->setName($m['name']);
            $manager->persist($month);
            $months[] = $month;
        }

        // Création d'un user sans privilèges particuliers
        $user = new User();
        $user->setEmail("user@ecogarden.com");
        $user->setRoles(["ROLE_USER"]);
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "password"));
        $user->setZipCode(57100);
        $manager->persist($user);
        
        // Création d'un user admin
        $userAdmin = new User();
        $userAdmin->setEmail("admin@ecogarden.com");
        $userAdmin->setRoles(["ROLE_ADMIN"]);
        $userAdmin->setPassword($this->userPasswordHasher->hashPassword($userAdmin, "password"));
        $userAdmin->setZipCode(57450);
        $manager->persist($userAdmin);

        // Création d'une trentaine de conseils
        for ($i = 0; $i < 100; $i++) {
            $advice = new Advice();
            $advice->setText('Conseil numéro : ' . $i);

            $numberOfMonths = rand(1, 3);
            $selectedMonths = array_rand($months, $numberOfMonths);

            if (!is_array($selectedMonths)) {
                $selectedMonths = [$selectedMonths];
            }
            foreach ($selectedMonths as $monthIndex) {
                $advice->addMonth($months[$monthIndex]);
            }

            $manager->persist($advice);
        }
        $manager->flush();
    }
}
