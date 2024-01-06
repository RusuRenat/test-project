<?php

namespace App\Utils\Module\Users;


use App\Entity\Languages;
use App\Entity\Users;
use App\Entity\UsersProfiles;
use App\Utils\Constants\Status;
use App\Utils\Constants\Users\UsersRoles;
use App\Utils\Helpers\StringHelper;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class UsersManager
{

    public function __construct(private readonly ObjectManager $em, private readonly Request $request, private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    /**
     * @throws Exception
     */
    final public function createUser(): Users
    {

        $user = new Users();
        $user->setUsername($this->request->get('email'));
        $user->setEmail($this->request->get('email'));
        $user->setSalt(StringHelper::generateRandomString(random_int(30, 40)));
        $user->setResetPasswordToken(StringHelper::generateRandomString(random_int(30, 40)));
        $user->setPassword($this->passwordHasher->hashPassword($user, $this->request->get('password') ?? StringHelper::generateRandomString()));
        $user->setRoles([$this->request->get('roles') ?? UsersRoles::USER]);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    final public function createUserProfile(Users $user): UsersProfiles
    {

        $userProfile = new UsersProfiles();
        $userProfile->setUsers($user);

        $userProfile->setFullName($this->request->get('fullName'));
        $userProfile->setPhoneNumber($this->request->get('phoneNumber'));
        $userProfile->setStatus(Status::ACTIVE);

        $this->em->persist($userProfile);
        $this->em->flush();

        return $userProfile;
    }

    /**
     * @throws Exception
     */
    final public function create(): UsersProfiles
    {
        return $this->update();
    }

    final public function update(): UsersProfiles
    {
        $userProfile = $this->em->getRepository(UsersProfiles::class)->findOneBy(['users' => $this->entity]);
        if (!$userProfile){
            $userProfile = new UsersProfiles();
            $userProfile->setUsers($this->entity);
        }

        $userProfile->setFullName($this->request->get('fullName'));
        $userProfile->setPhoneNumber($this->request->get('phoneNumber'));

        $this->em->persist($userProfile);
        $this->em->flush();

        return $userProfile;
    }

}