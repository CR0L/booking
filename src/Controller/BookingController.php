<?php

namespace App\Controller;

use App\Entity\Classroom;
use App\Entity\ClassroomReservation;
use App\Entity\LectureReservation;
use App\Entity\User;
use App\Repository\ClassroomRepository;
use App\Repository\ClassroomReservationRepository;
use DateInterval;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
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
		private ClassroomRepository $classroomRepository,
		private EntityManagerInterface $em
	) {}

	#[Route('/classrooms', methods: 'GET')]
	public function classrooms(): Response {
		$classrooms = $this->classroomRepository->findAll();
		return $this->json($classrooms,200, [], ["groups" => ["classroom"]]);
	}

	#[Route('/classrooms', methods: 'POST')]
	public function newClassroom(Request $request): Response {
		$post = json_decode($request->getContent(), true);
		$label = $post['label'];

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

	#[Route('/classroomReservations/{classroomReservation}/book', methods: 'GET')]
	#[IsGranted('ROLE_USER')]
	public function lectureReservation(ClassroomReservation $classroomReservation): Response {
		/** @var User $user */
		$user = $this->security->getUser();
		if ($classroomReservation->getLectureReservations()->count() >= $classroomReservation->getMaxStudents()) {
			return new JsonResponse(['error'=>'Maximum number of students reached.'], Response::HTTP_BAD_REQUEST);
		}
		if ($classroomReservation->getClassroom()->getReservations()->exists(function($key, ClassroomReservation $classroomReservation2) use ($user, $classroomReservation) {
			if (
				$classroomReservation->getStart()->format('Y-m-d') == $classroomReservation2->getStart()->format('Y-m-d') &&
				$classroomReservation2->getLectureReservations()->exists(function($key, LectureReservation $lectureReservation) use ($user) {
					return $lectureReservation->getReservedBy()->getId() === $user->getId();
				})
			)
				return true;
			return false;
		}))
			return new JsonResponse(['error'=>"You've already booked this lecture this date."], Response::HTTP_BAD_REQUEST);
		$lectureReservation = new LectureReservation();
		$lectureReservation->setReservedBy($user);
		$classroomReservation->addLectureReservation($lectureReservation);
		$this->em->persist($lectureReservation);
		$this->em->flush();
		return new JsonResponse(['message'=>"Booked successfully"]);
	}

	#[Route('/classroomReservations', methods: 'GET')]
	public function classroomReservations(): Response {
		/** @var User $user */
		$user = $this->security->getUser();
		$classroomReservations = new ArrayCollection($this->classroomReservationRepository->findAll());
		$classroomReservations = $classroomReservations->filter(function(ClassroomReservation $classroomReservation) {
			return $classroomReservation->getReservedBy() !== null && $classroomReservation->getLectureReservations()->count() < $classroomReservation->getMaxStudents();
		});
		return $this->json($classroomReservations,200, [], ["groups" => ["classroomReservation"]]);
	}

	#[Route('/classroomReservations', methods: 'POST')]
	//#[IsGranted('ROLE_USER')]
	public function newClassroomReservation(Request $request): Response {
		$post = json_decode($request->getContent(), true);
		$start = new DateTime($post['start']);
		$end = new DateTime($post['end']);
		$maxStudents = $post['maxStudents'];
		$classroom = $this->classroomRepository->find($post['classroom']);

		if ($classroom === null)
			return new JsonResponse(['error'=>"Classroom doesn't exist."], Response::HTTP_BAD_REQUEST);
		if (!is_numeric($maxStudents))
			return new JsonResponse(['error'=>"maxStudents must be a number"], Response::HTTP_BAD_REQUEST);

		if($start === false || $end === false)
			return new JsonResponse(['error'=>"Wrong datetime format."], Response::HTTP_BAD_REQUEST);

		$creteria = Criteria::create()
			->where(Criteria::expr()->eq('classroom', $classroom))
			->andWhere(Criteria::expr()->lt('start', $end))
			->andWhere(Criteria::expr()->gt('end', $start));
		$classroomReservations = $this->classroomReservationRepository->matching($creteria);

		if ($classroomReservations->count() === 0)
			return new JsonResponse(['error'=>"No lectures found."], Response::HTTP_BAD_REQUEST);
		elseif ($classroomReservations->count() > 1)
			return new JsonResponse(['error'=>"More then one lectures found."], Response::HTTP_BAD_REQUEST);

		/** @var ClassroomReservation $classroomReservation */
		$classroomReservation = $classroomReservations->first();

		if ($classroomReservation->getReservedBy() !== null)
			return new JsonResponse(['error'=>"Already booked."], Response::HTTP_BAD_REQUEST);
		$classroomReservation->setMaxStudents($maxStudents);
		if ($end > $classroomReservation->getEnd() || $start < $classroomReservation->getStart()) {
			return new JsonResponse([
				'error'=>"Wrong datetime interval.",
				's1'=>$classroomReservation->getStart()->getTimestamp(),
				's2'=>$start->getTimestamp(),
				'e1'=>$classroomReservation->getEnd(),
				'e2'=>$end,
			], Response::HTTP_BAD_REQUEST);
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
}
