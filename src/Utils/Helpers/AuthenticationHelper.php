<?php

namespace App\Utils\Helpers;

use App\Entity\AuthenticationAttempts;
use App\Entity\Users;
use DateInterval;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Exception;
use Symfony\Component\HttpFoundation\Request;

class AuthenticationHelper
{

    public const AUTH_ATTEMPTS = 5;
    public const LOCK_EMAIL_MINUTES = 30;

    /**
     * @throws Exception
     */
    public static function checkAccess(Request $request, EntityManager $em): bool
    {
        return false;
    }

    public static function checkAttempts(string $email, EntityManager $em): array
    {

        $authenticationAttempt = $em->getRepository(AuthenticationAttempts::class)->findOneBy(['email' => $email]);
        if (!$authenticationAttempt) {
            return ['locked' => false];
        }

        if ($authenticationAttempt->getAttempts() >= self::AUTH_ATTEMPTS) {
            $lastAttemptDate = $authenticationAttempt->getLastAttemptDate();
            if ($lastAttemptDate) {
                $penalizationDate = $lastAttemptDate->add(new DateInterval('PT' . self::LOCK_EMAIL_MINUTES . 'M'));
                if ((new DateTime()) <= $penalizationDate) {
                    $difference = $penalizationDate->diff(new DateTime());

                    $minutes = $difference->days * 24 * 60;
                    $minutes += $difference->h * 60;
                    $minutes += $difference->i;
                    if ($difference->s) {
                        $minutes++;
                    }

                    return ['locked' => true, 'minutes' => $minutes];
                }
            }

            self::clearAttempts($email, $em);
        }

        return ['locked' => false];
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws Exception
     */
    public static function wrongAttempt(string $email, EntityManager $em): bool
    {

        $authenticationAttempt = $em->getRepository(AuthenticationAttempts::class)->findOneBy(['email' => $email]);
        if (!$authenticationAttempt) {
            $authenticationAttempt = new AuthenticationAttempts();
            $authenticationAttempt->setEmail($email);
        } else {
            $lastAttemptDate = $authenticationAttempt->getLastAttemptDate();
            if ($lastAttemptDate) {
                $penalizationDate = $lastAttemptDate->add(new DateInterval('PT' . self::LOCK_EMAIL_MINUTES . 'M'));
                if ((new DateTime()) > $penalizationDate) {
                    $authenticationAttempt->setAttempts(0);
                }
            }
        }

        $authenticationAttempt->setAttempts($authenticationAttempt->getAttempts() + 1);
        $authenticationAttempt->setLastAttemptDate(new DateTime());

        $em->persist($authenticationAttempt);
        $em->flush();

        return true;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public static function clearAttempts(string $email, EntityManager $em): bool
    {

        $authenticationAttempt = $em->getRepository(AuthenticationAttempts::class)->findOneBy(['email' => $email]);
        if ($authenticationAttempt) {
            $authenticationAttempt->setAttempts(0);

            $em->persist($authenticationAttempt);
            $em->flush();
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public static function getTokenLifeTime(Users $user, int $normalLifeTime = 3600, int $rememberMeLifeTime = 604800): DateTime
    {

        $timezone = new DateTimeZone('UTC');

        $normalDate = (new DateTime('now', $timezone))->add(new DateInterval('PT' . $normalLifeTime . 'S'));
        $authenticationDate = new DateTime($user->getAuthenticationDate()?->format('Y-m-d H:i:s'), $timezone);

        // check if user is in remember me life time period, and return max remember me lifetime period
        if ($user->isRememberMe()) {
            $rememberMeDate = $authenticationDate->add(new DateInterval('PT' . $rememberMeLifeTime . 'S'));
            if ($rememberMeDate > $normalDate) {
                return $rememberMeDate;
            }
        }

        return $normalDate;
    }

    public static function generateUniqueString(array $params = []): string
    {
        return md5(implode('_', $params) . '_' . time() . '_' . uniqid((string)mt_rand(), true));
    }

}
