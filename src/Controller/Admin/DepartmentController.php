<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Department;
use App\Security\SystemRole;
use App\Service\Audit\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/departments')]
class DepartmentController extends AbstractController
{
    #[Route('', name: 'admin_department_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(SystemRole::ADMIN->value);

        $departments = $entityManager->createQueryBuilder()
            ->select('d', 'COUNT(u.id) AS HIDDEN staffCount', 'COUNT(c.id) AS HIDDEN catCount')
            ->from(Department::class, 'd')
            ->leftJoin('d.staffMembers', 'u')
            ->leftJoin('d.categories', 'c')
            ->groupBy('d.id')
            ->orderBy('d.active', 'DESC')
            ->addOrderBy('d.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/departments/index.html.twig', [
            'departments' => $departments,
        ]);
    }

    #[Route('/new', name: 'admin_department_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, AuditLogger $auditLogger): Response
    {
        $this->denyAccessUnlessGranted(SystemRole::ADMIN->value);
        $department = new Department();
        $errors = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('dept_create', (string) $request->request->get('_token'))) {
                $errors[] = 'Your session expired. Please submit the form again.';
            }

            $errors = array_merge($errors, $this->applyInput($department, $request, $entityManager));

            if ($errors === []) {
                $entityManager->persist($department);
                $auditLogger->record($this->requireAdmin(), 'department.created', 'department', $department->getId(), [
                    'code' => $department->getCode(),
                    'name' => $department->getName(),
                ], $request->getClientIp());
                $entityManager->flush();
                $this->addFlash('success', 'Department "' . $department->getName() . '" created.');

                return $this->redirectToRoute('admin_department_index');
            }
        }

        return $this->render('admin/departments/form.html.twig', [
            'department' => $department,
            'errors'     => $errors,
            'mode'       => 'new',
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_department_edit', methods: ['GET', 'POST'])]
    public function edit(Department $department, Request $request, EntityManagerInterface $entityManager, AuditLogger $auditLogger): Response
    {
        $this->denyAccessUnlessGranted(SystemRole::ADMIN->value);
        $errors = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('dept_edit_' . $department->getId()->toRfc4122(), (string) $request->request->get('_token'))) {
                $errors[] = 'Your session expired. Please submit the form again.';
            }

            $errors = array_merge($errors, $this->applyInput($department, $request, $entityManager));

            if ($errors === []) {
                $auditLogger->record($this->requireAdmin(), 'department.updated', 'department', $department->getId(), [
                    'code'   => $department->getCode(),
                    'name'   => $department->getName(),
                    'active' => $department->isActive(),
                ], $request->getClientIp());
                $entityManager->flush();
                $this->addFlash('success', 'Department updated.');

                return $this->redirectToRoute('admin_department_index');
            }
        }

        return $this->render('admin/departments/form.html.twig', [
            'department' => $department,
            'errors'     => $errors,
            'mode'       => 'edit',
        ]);
    }

    /** @return list<string> */
    private function applyInput(Department $department, Request $request, EntityManagerInterface $entityManager): array
    {
        $errors = [];
        $name = trim((string) $request->request->get('name'));
        $code = strtoupper(trim((string) $request->request->get('code')));

        if (mb_strlen($name) < 2) {
            $errors[] = 'Department name is required.';
        }

        if (!preg_match('/^[A-Z0-9_]{2,40}$/', $code)) {
            $errors[] = 'Code must be 2–40 uppercase letters, digits, or underscores.';
        }

        // Uniqueness check (allow same entity on edit)
        $existing = $entityManager->getRepository(Department::class)->findOneBy(['code' => $code]);
        if ($existing instanceof Department && !$existing->getId()->equals($department->getId())) {
            $errors[] = 'A department with that code already exists.';
        }

        if ($errors !== []) {
            return $errors;
        }

        $department->setName($name);
        $department->setCode($code);
        $department->setDescription(trim((string) $request->request->get('description')) ?: null);

        if ($request->request->has('active') !== null) {
            $department->setActive((bool) $request->request->get('active', false));
        }

        return [];
    }

    private function requireAdmin(): \App\Entity\User
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException('Administrator access required.');
        }

        return $user;
    }
}
