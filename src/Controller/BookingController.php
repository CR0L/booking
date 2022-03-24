<?php

namespace App\Controller;

use App\Entity\Classroom;
use App\Entity\ClassroomReservation;
use App\Entity\LectureReservation;
use App\Entity\User;
use App\Repository\ClassroomReservationRepository;
use DateInterval;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;

class BookingController extends AbstractController
{
	public function __construct(
		private Security $security,
		private ClassroomReservationRepository $classroomReservationRepository,
		private SerializerInterface $serializer,
		private EntityManagerInterface $em
	) {}

	#[Route('/booking')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/BookingController.php',
        ]);
    }

	#[Route('/classroomReservations/{classroomReservation}/book')]
	#[IsGranted('ROLE_USER')]
	public function lectureReservation(ClassroomReservation $classroomReservation): Response {
		/** @var User $user */
		$user = $this->security->getUser();
		if ($classroomReservation->getLectureReservations()->count() >= $classroomReservation->getMaxStudents()) {
			return new JsonResponse(['error'=>'Maximum number of students reached.'], Response::HTTP_BAD_REQUEST);
		}
		if ($classroomReservation->getLectureReservations()->exists(function($key, LectureReservation $lectureReservation) use ($user) {
			return $lectureReservation->getReservedBy()->getId() === $this->user->getId();
		})) {
			return new JsonResponse(['error'=>"You've already booked this lecture."], Response::HTTP_BAD_REQUEST);
		}
		$lectureReservation = new LectureReservation();
		$lectureReservation->setReservedBy($user);
		$classroomReservation->addLectureReservation($lectureReservation);
		$this->em->persist($lectureReservation);
		$this->em->flush();
		return new JsonResponse(['message'=>"Booked successfully"]);
	}

	#[Route('/classroomReservations')]
	public function classroomReservations(): Response {
		/** @var User $user */
		$user = $this->security->getUser();
		$classroomReservations = new ArrayCollection($this->classroomReservationRepository->findAll());
		$classroomReservations = $classroomReservations->filter(function(ClassroomReservation $classroomReservation) {
			return $classroomReservation->getReservedBy() !== null && $classroomReservation->getLectureReservations()->count() < $classroomReservation->getMaxStudents();
		});
		return $this->json($classroomReservations,200, [], ["groups" => ["classroomReservation"]]);
	}

	#[Route('/classroomReservations/{classroomReservation}/new', methods: 'POST')]

	public function newClassroomReservation(Request $request, ClassroomReservation $classroomReservation): Response {
		if ($classroomReservation->getReservedBy() !== null)
			return new JsonResponse(['error'=>"Already booked."], Response::HTTP_BAD_REQUEST);
		$post = json_decode($request->getContent(), true);
		$start = new DateTime($post['start']);
		$end = new DateTime($post['end']);
		$maxStudents = $post['end'];
		if (!is_numeric($maxStudents))
			return new JsonResponse(['error'=>"maxStudents must be a number"], Response::HTTP_BAD_REQUEST);
		$classroomReservation->setMaxStudents($maxStudents);

		if($start === false || $end === false)
			return new JsonResponse(['error'=>"Wrong datetime format."], Response::HTTP_BAD_REQUEST);

		if ($end > $classroomReservation->getEnd() || $start < $classroomReservation->getStart()) {
			return new JsonResponse(['error'=>"Wrong datetime interval."], Response::HTTP_BAD_REQUEST);
		}

		/** @var User $user */
		$user = $this->security->getUser();
		$classroomReservation->setReservedBy($user);

		if ($classroomReservation->getStart() < $start) {
			$classroomReservationBefore = new ClassroomReservation();
			$classroomReservationBefore->setClassroom($classroomReservation->getClassroom());
			$classroomReservationBefore->setStart($classroomReservation->getStart());
			$classroomReservationBefore->setEnd($start);
			$classroomReservation->setStart($start);
			$this->em->persist($classroomReservationBefore);
		}
		if ($classroomReservation->getEnd() > $end) {
			$classroomReservationAfter = new ClassroomReservation();
			$classroomReservationAfter->setClassroom($classroomReservation->getClassroom());
			$classroomReservationAfter->setStart($end);
			$classroomReservationAfter->setEnd($classroomReservation->getEnd());
			$classroomReservation->setEnd($end);
			$this->em->persist($classroomReservationAfter);
		}
		$this->em->flush();
		$classroomReservations = $this->classroomReservationRepository->findBy(['classroom'=>$classroomReservation->getClassroom()->getId()]);
		return $this->json($classroomReservations,200, [], ["groups" => ["classroomReservation"]]);
	}

	#[Route('/classroomReservations/{classroomReservation}', methods: 'GET')]
	public function classroomReservation(Request $request, ClassroomReservation $classroomReservation): Response {
		return $this->json($classroomReservation,200, [], ["groups" => ["classroomReservation"]]);
	}

	#[Route('/classroomReservations/{classroomReservation}', methods: 'DELETE')]
	public function deleteClassroomReservation(ClassroomReservation $classroomReservation): Response {
		if ($classroomReservation->getReservedBy() === null)
			return new JsonResponse(['error'=>"Cannot delete this"], Response::HTTP_BAD_REQUEST);
		$classroomReservation->setReservedBy(null);
		$classroomReservation->setMaxStudents(null);
		$before = $this->classroomReservationRepository->findOneBy(['end' => $classroomReservation->getStart()]);
		if ($before->getReservedBy() === null) {
			$classroomReservation->setStart($before->getStart());
			$this->em->remove($before);
		}
		$after = $this->classroomReservationRepository->findOneBy(['start' => $classroomReservation->getEnd()]);
		if ($after->getReservedBy() === null) {
			$classroomReservation->setEnd($after->getEnd());
			$this->em->remove($after);
		}
		$this->em->flush();
		return $this->json($classroomReservation,200, [], ["groups" => ["classroomReservation"]]);
	}

	#[Route('/classrooms/new/{label}')]
	public function newClassroom(string $label): Response {
		$classroom = new Classroom();
		$classroom->setLabel($label);
		$classroomReservation = new ClassroomReservation();
		$classroomReservation->setClassroom($classroom);
		$classroomReservation->setStart(new DateTime());
		$classroomReservation->setEnd((new DateTime())->add(new DateInterval('P1000Y')));
		$this->em->persist($classroom);
		$this->em->persist($classroomReservation);
		$this->em->flush();
		return $this->json([
			'message' => "Classroom '{$label}' was created successfully."
		]);
	}


}
