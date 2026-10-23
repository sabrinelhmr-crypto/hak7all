<?php

namespace App\DataFixtures;

use App\Entity\City;
use App\Entity\Trade;
use App\Entity\User;
use App\Entity\WorkerProfile;
use App\Util\Phone;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $cityNames = ['Mahdia', 'Sfax', 'Monastir', 'Sousse'];
        $tradeNames = ['Plombier', 'Électricien', 'Climatisation', 'Serrurier', 'Peintre', 'Menuisier'];

        $cities = [];
        foreach ($cityNames as $name) {
            $city = (new City())->setName($name);
            $manager->persist($city);
            $cities[$name] = $city;
        }

        $trades = [];
        foreach ($tradeNames as $name) {
            $trade = (new Trade())->setName($name);
            $manager->persist($trade);
            $trades[$name] = $trade;
        }

        $workerData = [
            ['name' => 'Mohamed Trabelsi', 'email' => 'mohamed.trabelsi@example.tn', 'phone' => '98123456', 'city' => 'Sousse', 'trade' => 'Plombier', 'rate' => 40],
            ['name' => 'Amine Ben Salah', 'email' => 'amine.bensalah@example.tn', 'phone' => '98234567', 'city' => 'Monastir', 'trade' => 'Électricien', 'rate' => 35],
            ['name' => 'Hassen Gharbi', 'email' => 'hassen.gharbi@example.tn', 'phone' => '98345678', 'city' => 'Mahdia', 'trade' => 'Climatisation', 'rate' => 60],
            ['name' => 'Karim Jaziri', 'email' => 'karim.jaziri@example.tn', 'phone' => '98456789', 'city' => 'Sfax', 'trade' => 'Serrurier', 'rate' => 30],
            ['name' => 'Sami Chaabane', 'email' => 'sami.chaabane@example.tn', 'phone' => '98567890', 'city' => 'Sousse', 'trade' => 'Peintre', 'rate' => 25],
            ['name' => 'Tarek Bouazizi', 'email' => 'tarek.bouazizi@example.tn', 'phone' => '98678901', 'city' => 'Monastir', 'trade' => 'Menuisier', 'rate' => 45],
        ];

        foreach ($workerData as $data) {
            $user = (new User())
                ->setEmail($data['email'])
                ->setFullName($data['name'])
                ->setPhone(Phone::normalize($data['phone']))
                ->setCity($cities[$data['city']])
                ->setRole(User::ROLE_WORKER)
                ->setPassword($this->passwordHasher->hashPassword(new User(), 'Motdepasse123!'))
                ->markEmailVerified();

            $manager->persist($user);

            $profile = (new WorkerProfile())
                ->setUser($user)
                ->setTrade($trades[$data['trade']])
                ->setRateTnd($data['rate'])
                ->setIsAvailable(true)
                ->setPhonePublic(true);

            $manager->persist($profile);
            $user->setWorkerProfile($profile);
        }

        $client = (new User())
            ->setEmail('client.demo@example.tn')
            ->setFullName('Client Démo')
            ->setPhone('98765432')
            ->setCity($cities['Sousse'])
            ->setRole(User::ROLE_CLIENT)
            ->setPassword($this->passwordHasher->hashPassword(new User(), 'Motdepasse123!'))
            ->markEmailVerified();

        $manager->persist($client);

        $manager->flush();
    }
}
