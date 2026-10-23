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
            ['name' => 'Nizar Khelifi', 'email' => 'nizar.khelifi@example.tn', 'phone' => '97123456', 'city' => 'Sfax', 'trade' => 'Plombier', 'rate' => 45],
            ['name' => 'Wael Mansour', 'email' => 'wael.mansour@example.tn', 'phone' => '96123456', 'city' => 'Monastir', 'trade' => 'Plombier', 'rate' => 38],
            ['name' => 'Amine Ben Salah', 'email' => 'amine.bensalah@example.tn', 'phone' => '98234567', 'city' => 'Monastir', 'trade' => 'Électricien', 'rate' => 35],
            ['name' => 'Bilel Hammami', 'email' => 'bilel.hammami@example.tn', 'phone' => '97234567', 'city' => 'Sousse', 'trade' => 'Électricien', 'rate' => 40],
            ['name' => 'Yassine Ferchichi', 'email' => 'yassine.ferchichi@example.tn', 'phone' => '96234567', 'city' => 'Mahdia', 'trade' => 'Électricien', 'rate' => 32],
            ['name' => 'Hassen Gharbi', 'email' => 'hassen.gharbi@example.tn', 'phone' => '98345678', 'city' => 'Mahdia', 'trade' => 'Climatisation', 'rate' => 60],
            ['name' => 'Sofiene Dridi', 'email' => 'sofiene.dridi@example.tn', 'phone' => '97345678', 'city' => 'Sousse', 'trade' => 'Climatisation', 'rate' => 55],
            ['name' => 'Mehdi Kacem', 'email' => 'mehdi.kacem@example.tn', 'phone' => '96345678', 'city' => 'Sfax', 'trade' => 'Climatisation', 'rate' => 65],
            ['name' => 'Karim Jaziri', 'email' => 'karim.jaziri@example.tn', 'phone' => '98456789', 'city' => 'Sfax', 'trade' => 'Serrurier', 'rate' => 30],
            ['name' => 'Anis Belhaj', 'email' => 'anis.belhaj@example.tn', 'phone' => '97456789', 'city' => 'Sousse', 'trade' => 'Serrurier', 'rate' => 35],
            ['name' => 'Ridha Saidi', 'email' => 'ridha.saidi@example.tn', 'phone' => '96456789', 'city' => 'Monastir', 'trade' => 'Serrurier', 'rate' => 28],
            ['name' => 'Sami Chaabane', 'email' => 'sami.chaabane@example.tn', 'phone' => '98567890', 'city' => 'Sousse', 'trade' => 'Peintre', 'rate' => 25],
            ['name' => 'Fethi Oueslati', 'email' => 'fethi.oueslati@example.tn', 'phone' => '97567890', 'city' => 'Mahdia', 'trade' => 'Peintre', 'rate' => 30],
            ['name' => 'Chokri Ben Amor', 'email' => 'chokri.benamor@example.tn', 'phone' => '96567890', 'city' => 'Sfax', 'trade' => 'Peintre', 'rate' => 27],
            ['name' => 'Tarek Bouazizi', 'email' => 'tarek.bouazizi@example.tn', 'phone' => '98678901', 'city' => 'Monastir', 'trade' => 'Menuisier', 'rate' => 45],
            ['name' => 'Lotfi Mejri', 'email' => 'lotfi.mejri@example.tn', 'phone' => '97678901', 'city' => 'Sousse', 'trade' => 'Menuisier', 'rate' => 50],
            ['name' => 'Hatem Bouzid', 'email' => 'hatem.bouzid@example.tn', 'phone' => '96678901', 'city' => 'Mahdia', 'trade' => 'Menuisier', 'rate' => 42],
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
