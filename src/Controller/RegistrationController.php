<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class RegistrationController extends AbstractController
{
	public function __construct(
		private VerifyEmailHelperInterface $verifyEmailHelper,
		private UserPasswordHasherInterface $hasher,
		private UserRepository $userRepository,
		private MailerInterface $mailer,
		private EntityManagerInterface $em) {}

	#[Route('/registration', name: 'app_registration', methods: "POST")]
    public function registration(Request $request): Response
    {
		$post = json_decode($request->getContent(), true);
	    $email = $post['email'];
	    $user = $this->userRepository->findOneBy(['email' => $email]);
		if ($user !== null)
			return new JsonResponse(['error'=>'User with this email already exist.'], Response::HTTP_BAD_REQUEST);
	    $user = new User();
		if (!str_ends_with($email, "unipa.it"))
			return new JsonResponse(['error'=>'email error'], Response::HTTP_BAD_REQUEST);
	    $user->setEmail($email);
		$password = $this->hasher->hashPassword($user, $post['password']);
		$user->setPassword($password);
		$user->setConfirmed(false);

	    try {
		    $this->userRepository->add($user);
	    } catch (OptimisticLockException|ORMException $e) {
		    return new JsonResponse(['error'=>'Unable to create user'], Response::HTTP_BAD_REQUEST);
	    }

	    $signatureComponents = $this->verifyEmailHelper->generateSignature(
		    'registration_confirmation_route',
		    $user->getId(),
		    $user->getEmail(),
		    ['id' => $user->getId()] // add the user's id as an extra query param
	    );

	    /*$email = (new Email())
		    ->from('ololo@ololo.com')
		    ->to($email)
		    ->subject('Please confirm your email')
		    ->html('<p>Please confirm your email address by clicking the following link:</p><br><a href=""></a>');

	    $this->mailer->send($email);*/

	    return $this->json(['message' => 'User successfully registered']);
    }

	#[Route('/verify', name: 'registration_confirmation_route')]
	public function verifyUserEmail(Request $request): Response
	{
		$id = $request->get('id'); // retrieve the user id from the url

        // Verify the user id exists and is not null
        if (null === $id) {
	        return new JsonResponse(['error'=>"User id must be set"], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->find($id);

        // Ensure the user exists in persistence
		if (null === $user) {
			return new JsonResponse(['error'=>"User doesn't exist"], Response::HTTP_BAD_REQUEST);
		}
		// Do not get the User's Id or Email Address from the Request object
		try {
			$this->verifyEmailHelper->validateEmailConfirmation($request->getUri(), $user->getId(), $user->getEmail());
		} catch (VerifyEmailExceptionInterface $e) {
			//$this->addFlash('verify_email_error', $e->getReason());

			//return $this->redirectToRoute('app_registration');
			return new JsonResponse(['error'=>"Error during verification."], Response::HTTP_BAD_REQUEST);
		}

		// Mark your user as verified. e.g. switch a User::verified property to true
		$user->setConfirmed(true);
		$this->em->flush();

		//$this->addFlash('success', 'Your e-mail address has been verified.');

		//return $this->redirectToRoute('app_home');
		return $this->json(['message' => 'Confirmed!']);
	}

	#[Route('/profile', methods: "GET")]
	#[IsGranted('ROLE_USER')]
	public function getUserEndpoint(Request $request): Response
	{
		return $this->json($this->getUser(),200, [], ["groups" => ["user"]]);
	}
}
