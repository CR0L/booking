<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{

	/**
	 * @inheritDoc
	 */
	public function checkPreAuth(UserInterface $user)
	{
		if ($user instanceof User && !$user->getConfirmed()) {
			$ex = new CustomUserMessageAccountStatusException('Email address is not confirmed.');
			$ex->setUser($user);
			throw $ex;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function checkPostAuth(UserInterface $user)
	{

	}
}