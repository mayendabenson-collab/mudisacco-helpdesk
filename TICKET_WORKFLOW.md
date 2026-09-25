# MUDI SACCO Ticket System Workflow

## 🎯 Who Can Do What

### **MEMBERS** (Portal Access - `member@mudi-sacco.local`)
- ✅ **Create tickets** - Report their own issues (self-serve portal)
- ✅ **View their own tickets** - Only tickets they created
- ✅ **Reply to tickets** - Add comments/messages to their tickets
- ❌ Cannot see staff tickets or other members' tickets

### **STAFF** (Support Team - `support@mudi-sacco.local`, `loans@mudi-sacco.local`, etc.)
- ✅ **Create tickets** - Create tickets FOR members (on their behalf)
- ✅ **See department tickets** - All tickets in their assigned department
- ✅ **See assigned tickets** - Any ticket assigned to them
- ✅ **Handle/Resolve tickets** - Update status, add messages
- ✅ **Search members** - Look up members to create tickets for
- ❌ Cannot see tickets from other departments (unless assigned to them)

### **SUPERVISORS** (Department Managers - `supervisor@mudi-sacco.local`)
- ✅ All staff permissions PLUS:
- ✅ **Escalate tickets** - Move tickets between staff
- ✅ **See all department tickets** - Entire department workload
- ✅ **Create reports** - Department performance analytics

### **ADMINISTRATORS** (System Admin - `admin@mudi-sacco.local`)
- ✅ **See ALL tickets** - From any department
- ✅ **Manage tickets** - Any ticket action
- ✅ **Import members** - Bulk upload members
- ✅ **Configure system** - Departments, categories, SLA rules, staff

---

## 📋 Ticket Creation Scenarios

### **Scenario 1: Member Self-Service (Member Portal)**
1. Member logs in as `member@mudi-sacco.local`
2. Goes to Dashboard → Create Ticket
3. Fills in: Subject, Description, Category, Priority
4. Ticket is assigned to the appropriate department
5. Staff in that department sees it and can handle it

### **Scenario 2: Staff Creates For Member**
1. Staff logs in as `support@mudi-sacco.local` (or any staff)
2. Goes to Tickets → Create Ticket
3. **Searches for existing member** (enter member number or name)
4. If member not found: **Ticket CANNOT be created** ← This is the problem!
5. Fills in: Subject, Description, Category, Priority
6. Staff's own department gets the ticket automatically
7. Other staff in that department can see & handle it

---

## 🔴 **WHY YOU CAN'T CREATE TICKETS AS STAFF**

### The Issue:
```
Staff (support@mudi-sacco.local) tries to create ticket
  ↓
System asks: "Which member is this ticket for?"
  ↓
Staff searches: No members found! (You haven't imported any)
  ↓
❌ CANNOT CREATE TICKET
```

### The Solution:
**You MUST have members in the system FIRST**

---

## ✅ How to Fix It Now

### **Option 1: Import Members Quickly (Recommended)**

1. Go to Admin Dashboard (as admin@mudi-sacco.local)
2. Click: **Members → Bulk Import**
3. Create CSV file with this format:
   ```
   member_number,display_name,primary_phone,email,status,branch_code
   MUDI-001,John Doe,+254700000001,john@example.com,active,BLANTYRE_MAIN
   MUDI-002,Jane Smith,+254700000002,jane@example.com,active,LILONGWE_KANENGO
   MUDI-003,Bob Johnson,+254700000003,bob@example.com,active,MZUZU
   ```
4. Upload the CSV
5. Now staff can create tickets!

### **Option 2: Use CLI (For Large Imports)**
```bash
php bin/console app:import-members members.csv
```

### **Option 3: Add Single Member**
1. Go to Admin → Members → Add Member
2. Fill in: Member Number, Name, Phone, Email, Branch
3. Click Create

---

## 📊 Complete Ticket Workflow

```
MEMBER CREATES TICKET (Portal)
   ↓
   Ticket Status: OPEN
   ↓
   [Assigned to Department automatically based on Category]
   ↓
STAFF SEES TICKET (In their department)
   ↓
STAFF HANDLES TICKET:
   - Update Status (Open → In Progress → Resolved → Closed)
   - Add Messages/Comments
   - Assign to specific staff member
   - Escalate to Supervisor if needed
   ↓
SUPERVISOR REVIEWS (If escalated)
   ↓
TICKET RESOLVED
   ↓
MEMBER GETS NOTIFIED
   ↓
Ticket can be CLOSED
```

---

## 🔍 How Tickets Are Visible

| User Type | Can See | Cannot See |
|-----------|---------|-----------|
| **Member** | Only their own tickets | Tickets from other members or staff |
| **Staff** | Tickets assigned to them + Department tickets | Tickets from other departments |
| **Supervisor** | All their department's tickets + Assigned to them | Tickets from other departments |
| **Admin** | ALL tickets in the system | Nothing hidden |

---

## 🧪 Test This Now

### Step 1: Import Test Members
1. Go to **Admin Dashboard**
2. **Members → Bulk Import**
3. Use the template file provided
4. Import 5-10 test members

### Step 2: Create Ticket as Staff
1. Log in as `support@mudi-sacco.local` / `MudiDemo123!`
2. Go to **Tickets → Create Ticket**
3. Search for one of the members you just imported
4. Create a test ticket
5. See it appear in the ticket list

### Step 3: See Ticket as Supervisor
1. Log in as `supervisor@mudi-sacco.local` / `MudiDemo123!`
2. Go to **Tickets**
3. You should see the ticket (because supervisor can see ALL department tickets)

---

## ❓ Common Issues

### "No members found" when creating ticket
→ **Import members first** (see solution above)

### Staff can't see tickets in their department
→ Check if staff has the right **department assigned** in Admin → Staff settings

### Can only see tickets assigned to me
→ You're viewing "My Tickets" filter. Remove filter to see all department tickets

### Member can't see tickets when logged in
→ Member must create the ticket first (or have a ticket created FOR them by staff)

---

## 📞 Quick Reference: Demo Accounts

| Role | Email | Password | Purpose |
|------|-------|----------|---------|
| Member | member@mudi-sacco.local | MudiDemo123! | Create self-service tickets |
| Staff | support@mudi-sacco.local | MudiDemo123! | Handle customer service tickets |
| Staff | loans@mudi-sacco.local | MudiDemo123! | Handle loan tickets |
| Supervisor | supervisor@mudi-sacco.local | MudiDemo123! | Oversee department |
| Admin | admin@mudi-sacco.local | MudiDemo123! | Manage everything |

---

## 🚀 Next Steps

1. **Import members** (100+ records)
2. **Test creating tickets** as different staff
3. **Monitor tickets** through dashboard
4. **Set up SLA rules** (Admin → SLA)
5. **Create notifications** (for escalations)
