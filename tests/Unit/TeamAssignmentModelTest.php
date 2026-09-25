<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Department;
use App\Entity\Team;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class TeamAssignmentModelTest extends TestCase
{
    public function testUserCanBelongToDepartmentAndTeam(): void
    {
        $department = new Department();
        $department->setCode('LOANS');
        $department->setName('Loans');

        $team = new Team();
        $team->setCode('LOAN_INQUIRY');
        $team->setName('Loan Inquiry Team');
        $team->setDepartment($department);

        $user = new User();
        $user->setDepartment($department);
        $user->addTeam($team);

        $this->assertTrue($user->belongsToDepartment($department));
        $this->assertTrue($user->belongsToTeam($team));
    }

    public function testUserOutsideDepartmentCannotUseDepartmentTeam(): void
    {
        $financeDepartment = new Department();
        $financeDepartment->setCode('FINANCE');
        $financeDepartment->setName('Finance');

        $loanDepartment = new Department();
        $loanDepartment->setCode('LOANS');
        $loanDepartment->setName('Loans');

        $team = new Team();
        $team->setCode('LOAN_INQUIRY');
        $team->setName('Loan Inquiry Team');
        $team->setDepartment($loanDepartment);

        $user = new User();
        $user->setDepartment($financeDepartment);

        $this->assertFalse($user->belongsToDepartment($loanDepartment));
        $this->assertFalse($user->belongsToTeam($team));
    }
}
