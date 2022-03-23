<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/registration', name: 'app_registration', methods: "POST")]
    public function registration(Request $request, UserPasswordHasherInterface $hasher, UserRepository $userRepository, MailerInterface $mailer): Response
    {
		$post = json_decode($request->getContent(), true);
	    $email = $post['email'];
	    $user = $userRepository->findOneBy(['email' => $email]);
		if ($user !== null)
			return new JsonResponse(['error'=>'User with this email already exist.'], Response::HTTP_BAD_REQUEST);
	    $user = new User();
		if (!str_ends_with($email, "unipa.it"))
			return new JsonResponse(['error'=>'email error'], Response::HTTP_BAD_REQUEST);
	    $user->setEmail($email);
		$password = $hasher->hashPassword($user, $post['password']);
		$user->setPassword($password);

	    try {
		    $userRepository->add($user);
	    } catch (OptimisticLockException|ORMException $e) {
		    return new JsonResponse(['error'=>'Unable to create user'], Response::HTTP_BAD_REQUEST);
	    }

	    $email = (new Email())
		    ->from('ololo@ololo.com')
		    ->to($email)
		    ->subject('Please confirm your email')
		    ->html('<p>Please confirm your email address by clicking the following link:</p><br><a href=""></a>');

	    $mailer->send($email);

	    return $this->json([
            'message' => 'User successfully registered'
        ]);
    }
}
