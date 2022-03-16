<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
	name: 'app:create-user',
	description: 'Creates a new user.',
	aliases: ['app:add-user'],
	hidden: false
)]
class CreateUserCommand extends Command {
	private UserRepository $userRepository;
	private UserPasswordHasherInterface $hasher;

	public function __construct(UserRepository $userRepository, UserPasswordHasherInterface $hasher) {
		$this->userRepository = $userRepository;
		$this->hasher = $hasher;

		parent::__construct();
	}

	protected function configure() {
		$this
			->addArgument('email', InputArgument::REQUIRED, 'Email')
			->addArgument('password', InputArgument::REQUIRED, 'Password');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$user = new User();
		$user->setEmail($input->getArgument('email'));
		$user->setPassword($this->hasher->hashPassword($user, $input->getArgument('password')));
		try {
			$this->userRepository->add($user);
		} catch (OptimisticLockException|ORMException $e) {
			$output->writeln('User successfully generated!');
			return Command::FAILURE;
		}
		$output->writeln('User successfully generated!');
		return Command::SUCCESS;
	}
}