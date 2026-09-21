<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\Department;
use App\Enum\TicketPriority;
use App\Security\SystemRole;
use App\Service\Audit\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/admin/categories')]
class CategoryController extends AbstractController
{
    #[Route('', name: 'admin_category_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(SystemRole::ADMIN->value);

        $categories = $entityManager->createQueryBuilder()
            ->select('c', 'd')
            ->from(Category::class, 'c')
            ->innerJoin('c.department', 'd')
            ->orderBy('c.active', 'DESC')
            ->addOrderBy('d.name', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/categories/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/new', name: 'admin_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, AuditLogger $auditLogger): Response
    {
        $this->denyAccessUnlessGranted(SystemRole::ADMIN->value);
        $category = new Category();
        $errors = [];

        $departments = $entityManager->getRepository(Department::class)->findBy(['active' => true], ['name' => 'ASC']);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('category_create', (string) $request->request->get('_token'))) {
                $errors[] = 'Your session expired. Please submit the form again.';
            }

            $errors = array_merge($errors, $this->applyInput($category, $request, $entityManager));

            if ($errors === []) {
                $entityManager->persist($category);
                $auditLogger->record($this->requireAdmin(), 'category.created', 'category', $category->getId(), [
                    'code' => $category->getCode(),
                    'name' => $category->getName(),
                    'department' => $category->getDepartment()->getName(),
                ], $request->getClientIp());
                $entityManager->flush();

                $this->addFlash('success', sprintf('Category "%s" has been created.', $category->getName()));

                return $this->redirectToRoute('admin_category_index');
            }
        }

        return $this->render('admin/categories/form.html.twig', [
            'mode' => 'new',
            'category' => $category,
            'departments' => $departments,
            'errors' => $errors,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_category_edit', methods: ['GET', 'POST'])]
    public function edit(string $id, Request $request, EntityManagerInterface $entityManager, AuditLogger $auditLogger): Response
    {
        $this->denyAccessUnlessGranted(SystemRole::ADMIN->value);

        try {
            $uuid = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException('Invalid category ID.');
        }

        $category = $entityManager->getRepository(Category::class)->find($uuid);
        if ($category === null) {
            throw $this->createNotFoundException('Category not found.');
        }

        $departments = $entityManager->getRepository(Department::class)->findBy([], ['name' => 'ASC']);
        $errors = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('category_edit', (string) $request->request->get('_token'))) {
                $errors[] = 'Your session expired. Please submit the form again.';
            }

            $errors = array_merge($errors, $this->applyInput($category, $request, $entityManager));

            if ($errors === []) {
                $auditLogger->record($this->requireAdmin(), 'category.updated', 'category', $category->getId(), [
                    'code' => $category->getCode(),
                    'name' => $category->getName(),
                    'active' => $category->isActive(),
                ], $request->getClientIp());
                $entityManager->flush();

                $this->addFlash('success', sprintf('Category "%s" has been updated.', $category->getName()));

                return $this->redirectToRoute('admin_category_index');
            }
        }

        return $this->render('admin/categories/form.html.twig', [
            'mode' => 'edit',
            'category' => $category,
            'departments' => $departments,
            'errors' => $errors,
        ]);
    }

    /** @return list<string> */
    private function applyInput(Category $category, Request $request, EntityManagerInterface $entityManager): array
    {
        $errors = [];

        $name = trim((string) $request->request->get('name'));
        $code = strtoupper(trim((string) $request->request->get('code')));
        $departmentId = (string) $request->request->get('department_id');
        $priorityRaw = (string) $request->request->get('default_priority');
        $description = trim((string) $request->request->get('description'));
        $active = (bool) $request->request->get('active', false);

        if ($name === '') {
            $errors[] = 'Category name is required.';
        } elseif (mb_strlen($name) > 120) {
            $errors[] = 'Category name cannot exceed 120 characters.';
        }

        if ($code === '') {
            $errors[] = 'Category code is required.';
        } elseif (!preg_match('/^[A-Z0-9_-]{2,50}$/', $code)) {
            $errors[] = 'Code must be 2-50 characters and contain only letters, numbers, hyphens, and underscores.';
        } else {
            // Check code uniqueness
            $qb = $entityManager->createQueryBuilder()
                ->select('COUNT(c.id)')
                ->from(Category::class, 'c')
                ->where('c.code = :code')
                ->setParameter('code', $code);

            if ($category->getId() !== null) {
                $qb->andWhere('c.id != :id')->setParameter('id', $category->getId());
            }

            if ((int) $qb->getQuery()->getSingleScalarResult() > 0) {
                $errors[] = sprintf('The code "%s" is already taken by another category.', $code);
            }
        }

        try {
            $deptUuid = Uuid::fromString($departmentId);
            $department = $entityManager->getRepository(Department::class)->find($deptUuid);
            if ($department === null) {
                $errors[] = 'Please select a valid department.';
            }
        } catch (\InvalidArgumentException) {
            $errors[] = 'Please select a valid department.';
        }

        $priority = TicketPriority::tryFrom($priorityRaw) ?? TicketPriority::MEDIUM;

        if ($errors === []) {
            $category->setName($name);
            $category->setCode($code);
            $category->setDepartment($department);
            $category->setDefaultPriority($priority);
            $category->setDescription($description !== '' ? $description : null);
            $category->setActive($active);
        }

        return $errors;
    }

    private function requireAdmin(): \App\Entity\User
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
