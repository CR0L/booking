<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/registration', name: 'app_registration', methods: "POST")]
    public function registration(Request $request, UserPasswordHasherInterface $hasher, UserRepository $userRepository): Response
    {
	    $user = new User();
	    $email = $request->get('email');
		if (!str_ends_with($email, "unipa.it"))
			return new JsonResponse(['error'=>'email error'], Response::HTTP_BAD_REQUEST);
	    $user->setEmail($email);
		$password = $hasher->hashPassword($user, $request->get('password'));
		$user->setPassword($password);

	    try {
		    $userRepository->add($user);
	    } catch (OptimisticLockException|ORMException $e) {
			throw new BadRequestHttpException();
		    return new JsonResponse(['error'=>'Unable to create user'], Response::HTTP_BAD_REQUEST);
	    }

	    return $this->json([
            'message' => 'User successfully registered'
        ]);
    }
}
