<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\Admin\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/users')]
final class UserAdminController extends AbstractController
{
    #[Route('', name: 'admin_users_index')]
    public function index(Request $request, UserRepository $repo): Response
    {
        $filters = [
            'q' => (string) $request->query->get('q', ''),
            'role' => (string) $request->query->get('role', ''),
            'status' => (string) $request->query->get('status', ''),
        ];

        // QueryBuilder (search like ton JS)
        $qb = $repo->createQueryBuilder('u');

        if ($filters['q'] !== '') {
            $qb->andWhere('LOWER(u.fullName) LIKE :q OR LOWER(u.email) LIKE :q')
               ->setParameter('q', '%'.mb_strtolower($filters['q']).'%');
        }

        if ($filters['status'] !== '') {
            $qb->andWhere('u.status = :st')->setParameter('st', $filters['status']);
        }

        if ($filters['role'] !== '') {
            // roles est JSON => on fait un LIKE simple (suffisant pour projet)
            $qb->andWhere('u.roles LIKE :r')->setParameter('r', '%"'.$filters['role'].'"%');
        }

        $users = $qb->orderBy('u.id', 'DESC')->getQuery()->getResult();

        // stats
        $total = count($users); // pour l’instant stats sur la liste filtrée
        $active = count(array_filter($users, fn(User $u) => $u->getStatus() === 'ACTIVE'));
        $disabled = $total - $active;

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
            'filters' => $filters,
            'stats' => ['total' => $total, 'active' => $active, 'disabled' => $disabled],
        ]);
    }

    #[Route('/new', name: 'admin_users_new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $user = new User();
        $user->setStatus('ACTIVE');
        $user->setRoles(['ROLE_PATIENT']);

        $form = $this->createForm(UserType::class, $user, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $role = (string) $form->get('role')->getData();
            $user->setRoles([$role]);

            $plain = (string) $form->get('plainPassword')->getData();
            $user->setPassword($hasher->hashPassword($user, $plain));

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Utilisateur ajouté.');
            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/user/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_users_edit')]
    public function edit(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => true,
        ]);

        // pré-sélectionner role dropdown
        $roles = $user->getRoles();
        $form->get('role')->setData($roles[0] ?? 'ROLE_PATIENT');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $role = (string) $form->get('role')->getData();
            $user->setRoles([$role]);

            $plain = (string) $form->get('plainPassword')->getData();
            if ($plain !== '') {
                $user->setPassword($hasher->hashPassword($user, $plain));
            }

            $em->flush();
            $this->addFlash('success', 'Utilisateur modifié.');
            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/{id}/toggle', name: 'admin_users_toggle', methods: ['POST'])]
    public function toggle(User $user, EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('toggle_user_'.$user->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $user->setStatus($user->getStatus() === 'ACTIVE' ? 'DISABLED' : 'ACTIVE');
        $em->flush();

        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/{id}', name: 'admin_users_delete', methods: ['POST'])]
    public function delete(User $user, EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete_user_'.$user->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Utilisateur supprimé.');
        return $this->redirectToRoute('admin_users_index');
    }
    #[Route('/new-modal', name: 'admin_users_new_modal')]
public function newModal(
    Request $request,
    EntityManagerInterface $em,
    UserPasswordHasherInterface $hasher
): Response {
    $user = new User();
    $user->setStatus('ACTIVE');
    $user->setRoles(['ROLE_PATIENT']);

    $form = $this->createForm(UserType::class, $user, ['is_edit' => false]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $role = (string) $form->get('role')->getData();
        $user->setRoles([$role]);

        $plain = (string) $form->get('plainPassword')->getData();
        $user->setPassword($hasher->hashPassword($user, $plain));

        $em->persist($user);
        $em->flush();

        return $this->json(['ok' => true]);
    }

    return $this->render('admin/user/_modal_form.html.twig', [
        'form' => $form->createView(),
    ]);
}
}
