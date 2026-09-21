# 👥 Employee Roles & Responsibilities Guide

## Overview
When creating employees in the admin panel, you're assigning them specific roles that determine what actions they can perform in the ticketing system.

---

## 📊 Quick Reference

### **STAFF** ✓
**WHO THEY ARE:** Support agents answering member calls

**WHAT THEY DO:**
- ✓ **CREATE** new tickets (primary responsibility)
- ✓ **SEARCH** for members in database
- ✓ **RESOLVE** tickets (change status, add responses)
- ✓ Update ticket notes and customer communications
- ✓ See their assigned tickets
- ✓ See their department's full ticket queue

**EXAMPLE WORKFLOW:**
```
Phone Rings → Grace (Staff) answers → Member says "My card isn't working"
→ Grace searches member database by member number
→ Grace creates new ticket: "Member Services" department, "Card Issues" category
→ Ticket automatically assigned to Member Services team
→ Grace or another staff member resolves it → Ticket closed
```

**HOW TO ASSIGN:**
- Create account with role: **STAFF**
- Assign to a **Department** (Customer Service, Loans, Compliance, etc.)
- Email: staff member's work email
- Department determines which tickets they see

---

### **SUPERVISOR** 👔
**WHO THEY ARE:** Team leads overseeing staff and ticket queues

**WHAT THEY DO:**
- 👁️ See **entire department** ticket queue (not just assigned)
- 👔 **Reassign** tickets between staff members
- 🚀 Handle **escalations** from staff
- 📊 Monitor **SLA compliance** (due times)
- ⚠️ Alert staff if workload is too high
- 🔍 Review staff performance
- ✓ Also can create/resolve tickets (has staff permissions too)

**EXAMPLE WORKFLOW:**
```
Samuel (Supervisor) logs in → See Customer Service queue
→ Notice 5 tickets waiting, 2 about to breach SLA
→ Reassign some tickets from Grace to Peter (who's less busy)
→ Watch for alerts about overdue tickets
→ Escalate complex issue to Admin if needed
```

**HOW TO ASSIGN:**
- Create account with role: **SUPERVISOR**
- Assign to a **Department** (they oversee that team)
- Can be supervisor for multiple departments if needed
- Email: supervisor's work email

---

### **ADMINISTRATOR** 🔧
**WHO THEY ARE:** System managers configuring the platform

**WHAT THEY DO:**
- 🔧 **Create/edit/delete** staff accounts
- 📋 Manage **departments and categories**
- ⏱️ Set **SLA rules** (response times)
- 🔑 Manage **permissions and roles**
- 📊 View **audit logs** (who did what)
- ⚙️ System configuration
- 🚨 Receive critical system alerts

**EXAMPLE WORKFLOW:**
```
Admin (admin@mudi-sacco.local) needs to add a new department
→ Goes to Admin → Departments → Create new department
→ Sets up SLA rules: Response 1 hour, Resolution 24 hours
→ Creates new staff account assigned to this department
→ New staff can now create/resolve tickets there
```

**HOW TO ASSIGN:**
- Create account with role: **ADMINISTRATOR**
- Department is optional (not critical)
- Email: admin's work email
- Only select this role for trusted system managers

---

## 🎯 Common Scenarios

### **Scenario 1: Hire New Support Agent**
```
Steps:
1. Go to Admin → Staff Management
2. Click "Create employee"
3. Enter: Full name, work email
4. Select Role: STAFF
5. Select Department: Customer Service (or relevant department)
6. Send setup link to employee
7. Employee sets password
8. Employee can now answer calls and create tickets!
```

### **Scenario 2: Promote Staff to Supervisor**
```
Steps:
1. Go to Admin → Staff Management
2. Find the person (Grace Wanjiku)
3. Click Edit
4. Change Role: SUPERVISOR
5. Keep Department: Customer Service
6. Save changes
7. Grace now sees all Customer Service tickets and can reassign them
```

### **Scenario 3: Add Second Department Supervisor**
```
Current: Samuel Karanja supervises Customer Service
Goal: Add supervisor for Loans department (no supervisor currently)

Steps:
1. Identify who should supervise Loans (e.g., Peter Otieno or someone new)
2. Go to Admin → Staff Management
3. Create new account or edit existing
4. Set Role: SUPERVISOR
5. Set Department: Loans
6. Save changes
7. Now Loans department has oversight!
```

---

## 🚨 Important: Missing Supervisors

**CRITICAL:** Two departments currently have NO supervisors:
- ❌ **Compliance** (Amina Hassan - Staff only)
- ❌ **Finance** (Joel Chaula Ndege - Staff only)

**CONSEQUENCE:** 
- Tickets can't be reassigned
- No one monitors their SLA compliance
- Escalations go directly to Admin

**FIX:**
```
Option A: Assign external supervisors
  → Create new accounts with SUPERVISOR role
  → Assign to Compliance and Finance

Option B: Promote existing staff
  → Edit Amina Hassan or Joel Chaula Ndege
  → Change role from STAFF to SUPERVISOR
  → Keep department the same
  
Option C: Temporary - Admin oversees both
  → Admin monitors these tickets manually
  → Assign permanent supervisors later
```

---

## 📱 Department Assignments

| Department | Supervisor | Staff | Status |
|-----------|-----------|-------|--------|
| Customer Service | Samuel Karanja | Grace Wanjiku | ✅ Complete |
| Loans | Samuel Karanja* | Peter Otieno | ⚠️ Needs dedicated supervisor |
| Member Services | None | - | ❌ Empty |
| Compliance | None | Amina Hassan | ❌ Needs supervisor |
| Finance | None | Joel Chaula Ndege | ❌ Needs supervisor |
| ICT | Jabulani Mayenda | Lusungu Muyawa | ✅ Complete |
| Savings | None | - | ❌ Empty |

*Samuel oversees both Customer Service + Loans (split responsibility)

---

## ✅ Step-by-Step: Create New Staff

### **1. Go to Staff Management**
```
Admin Dashboard → Staff Management
```

### **2. Click "Create Employee"**
Form appears with fields:
- Full name
- Work email
- Role (Staff / Supervisor / Admin)
- Department
- Account active (checkbox)

### **3. Fill Out Information**

**Example:**
```
Full name: John Kimani
Work email: john.kimani@sacco.co.ke
Role: STAFF
Department: Customer Service
Account active: ✓ checked
```

### **4. Submit Form**
System shows setup link (copy and send securely to employee)

### **5. Employee Receives Setup Link**
Employee clicks link → Sets password → Can now log in

### **6. Employee Can Now**
✓ Log in to dashboard
✓ Create tickets by searching members
✓ Resolve assigned tickets
✓ See department queue (if staff, limited view; if supervisor, full view)

---

## 🔐 Security Notes

**When creating staff:**
- ✓ Use their **official work email**
- ✓ Send setup link via **secure channel** (encrypted email, message app)
- ✓ NEVER paste link in group chats or shared documents
- ✓ Link expires in **24 hours** (employee must set password quickly)

**When managing accounts:**
- ✓ Deactivate instead of delete (keeps audit trail)
- ✓ Reset password if employee forgets (generates new setup link)
- ✓ ADMIN should have strong password
- ✓ Review staff list regularly for inactive accounts

---

## 📞 Ticket Creation Summary

**WHO CREATES TICKETS:**
1. ✓ STAFF members (primary way - via system when member calls)
2. ✓ SUPERVISORS can also create if needed
3. ✓ ADMIN can create if needed

**HOW TICKETS ARE CREATED:**
```
Phone Call In
  ↓
Staff member (or supervisor) answers
  ↓
Staff searches member database
  ↓
Staff creates ticket in system
  ↓
System assigns to correct department based on category
  ↓
Department staff/supervisor handles it
```

**NOT SELF-SERVICE:**
- ❌ Members do NOT create their own tickets
- ❌ Members do NOT access the system
- ❌ System is STAFF-ONLY for ticket creation
- ❌ Members communicate via phone/email ONLY

---

## 📊 Current Staff Count

```
Total Employees: 8

Admin: 1
  └─ admin@mudi-sacco.local

Supervisors: 2
  ├─ Samuel Karanja (Customer Service + Loans)
  └─ Jabulani Mayenda (ICT)

Staff: 5
  ├─ Grace Wanjiku (Customer Service)
  ├─ Peter Otieno (Loans)
  ├─ Amina Hassan (Compliance) - NEEDS SUPERVISOR
  ├─ Joel Chaula Ndege (Finance) - NEEDS SUPERVISOR
  └─ Lusungu Muyawa (ICT)
```

---

## 🎓 Next Steps

1. **Review this guide** with team
2. **Assign supervisors** to Compliance and Finance departments
3. **Train staff** on ticket creation workflow
4. **Set up members** (import CSV list)
5. **Test workflow** with sample tickets
6. **Configure alerts** (email/SMS for notifications)

---

## 💡 Tips

- **Role affects visibility:** Staff see only assigned tickets; Supervisors see entire department
- **Department assignment is key:** Determines which queue tickets appear in
- **Supervisors don't replace staff:** They oversee; staff still does the work
- **Admin can do everything:** But focus on system setup, not daily ticket handling
- **Deactivate old accounts:** Don't delete; keeps audit trail intact

---

**Questions?** Check [SYSTEM_ANALYSIS_SUMMARY.md](SYSTEM_ANALYSIS_SUMMARY.md) for complete system overview.
