# MUDI SACCO Ticketing System - Call Center Model

## 📞 How It Actually Works

### **The Workflow:**

```
1. MEMBER CALLS HELPLINE
   ├─ Staff answers phone
   ├─ Gets member details (member number)
   └─ Enters member into system

2. STAFF CREATES TICKET (On behalf of member)
   ├─ Searches for member in database
   ├─ Fills in: Issue subject, description
   ├─ Selects category (IT, Loans, Compliance, Finance, etc.)
   └─ Selects priority level

3. TICKET AUTO-ROUTES TO DEPARTMENT
   ├─ Category determines department
   └─ Department staff see in their queue

4. DEPARTMENT STAFF HANDLES TICKET
   ├─ Sees all tickets in their department
   ├─ Updates status (Open → In Progress → Resolved)
   ├─ Adds notes/comments
   └─ Resolves issue

5. STAFF NOTIFIES MEMBER
   ├─ Calls member (phone)
   ├─ Sends email
   ├─ Sends SMS
   └─ Tells them: "Your ticket #XYZ is resolved"

6. TICKET CLOSED
   └─ Member satisfied, case complete
```

---

## 👥 User Roles Explained

### **MEMBERS** - Incoming Call Queue
- **Can:** View their own tickets (read-only)
- **Cannot:** Create tickets, resolve issues, or contact others
- **Access:** Via phone helpline (staff creates tickets FOR them)

### **STAFF** - Front-line Support
- **Account:** `support@mudi-sacco.local` (Customer Service)
- **Account:** `loans@mudi-sacco.local` (Loans dept)
- **Account:** `compliance@mudi-sacco.local` (Compliance dept)
- **Can:** 
  - ✅ Create tickets for members
  - ✅ See all tickets in their department
  - ✅ Resolve tickets assigned to them
  - ✅ Update ticket status
  - ✅ Add messages/notes
- **Cannot:** Access admin panel, import members

### **SUPERVISORS** - Department Managers
- **Account:** `supervisor@mudi-sacco.local`
- **Can:** 
  - ✅ Everything staff can do
  - ✅ See ALL department tickets
  - ✅ Reassign tickets to staff
  - ✅ Escalate urgent issues
  - ✅ View department reports
  - ✅ Monitor SLA compliance
- **Cannot:** Access admin panel

### **ADMINISTRATORS** - System Managers
- **Account:** `admin@mudi-sacco.local`
- **Can:**
  - ✅ Import members (bulk CSV upload)
  - ✅ Manage staff accounts
  - ✅ Configure departments & categories
  - ✅ Set SLA rules & escalation policies
  - ✅ See all tickets across all departments
  - ✅ View audit logs

---

## 🏢 Department Structure

Your SACCO has multiple departments, each handling specific issue types:

| Department | Staff | Issues They Handle |
|------------|-------|-------------------|
| **Customer Service** | Grace Wanjiku | General inquiries, complaints, profile updates |
| **Loans** | Peter Otieno | Loan applications, repayments, statements |
| **Compliance** | Amina Hassan | Fraud, security, account takeover |
| **Finance** | (to be added) | Missing deposits, shares, corrections |
| **Member Services** | (to be added) | Account details, contact changes |
| **Savings** | (to be added) | Withdrawals, account issues |
| **ICT** | (to be added) | Portal access, technical issues |

Each department has its own **queue of tickets** to handle.

---

## 🎯 Step-by-Step: Handle a Member Call

### **Example: Member calls about failed withdrawal**

```
STEP 1: MEMBER CALLS
Member: "Hi, I tried to withdraw 5000 but it failed"
Staff: "Let me help. What's your member number?"
Member: "MUDI-001"

STEP 2: STAFF CREATES TICKET
Staff logs in as: support@mudi-sacco.local
Goes to: Tickets → Create Ticket
Searches: "MUDI-001" (finds member)
Selects Category: "Failed Withdrawal" (routes to Savings dept)
Enters:
  Subject: "Failed withdrawal 5000 KES"
  Description: "Member attempted withdrawal on mobile app, 
                funds not received"
  Priority: HIGH (member needs money)
  
STEP 3: TICKET CREATED
System:
  ✓ Ticket #TKT-2026-001 created
  ✓ Auto-routed to Savings department
  ✓ Status: OPEN
  ✓ Assigned to: Savings department queue

STEP 4: SAVINGS TEAM HANDLES
Savings Staff sees ticket in their list
Investigates: Checks member's account, transaction logs
Updates Status: In Progress
Adds Note: "Found duplicate charge, processing refund"
Updates Status: Resolved
Adds Note: "Refunded 5000 KES to account"

STEP 5: STAFF NOTIFIES MEMBER
Original staff member who took the call:
Calls member: "Your ticket #TKT-2026-001 is resolved. 
              We've refunded 5000 KES to your account. 
              It should appear within 24 hours."

STEP 6: TICKET CLOSED
Status: CLOSED
Case Complete ✓
```

---

## 🚀 Quick Start Guide for Staff

### **1. Log In**
- Email: `support@mudi-sacco.local`
- Password: `MudiDemo123!`

### **2. Create Ticket (When member calls)**

**Path:** Tickets → Create Ticket

**Fill in:**
1. **Search Member** - Enter member number (e.g., "MUDI-001")
   - ⚠️ Member must exist in system
   - If not found → Admin needs to import them
2. **Category** - Select the issue type
   - Examples: "General Enquiry", "Loan Issue", "Failed Withdrawal"
3. **Subject** - Brief issue title (5+ chars)
4. **Description** - Detailed issue (10+ chars)
5. **Priority** - Low/Medium/High/Urgent
6. **Channel** - How member contacted you (Phone/Email/SMS)
7. **Branch** - Member's branch (optional)

**Click:** Create Ticket

### **3. Resolve Ticket**

**Path:** Tickets → View Ticket → Manage

**Actions:**
- **Update Status:**
  - OPEN → IN_PROGRESS (when you start work)
  - IN_PROGRESS → RESOLVED (when issue is fixed)
  - RESOLVED → CLOSED (after member confirms)

- **Add Messages/Notes:**
  - Internal notes (staff only)
  - Customer visible messages

- **Escalate:**
  - Send to supervisor if too complex
  - Move to another department if routed wrong

### **4. Notify Member**

**Outside the system (for now):**
- 📞 **Call** member with update
- 📧 **Email** ticket link (if they have email)
- 💬 **SMS** status update

---

## 📊 Dashboard Views

### **For Staff:**
- Your assigned tickets
- Department ticket queue
- Open issues needing action

### **For Supervisor:**
- Entire department workload
- Staff performance
- SLA compliance
- Escalated issues

### **For Admin:**
- All tickets across all departments
- System health
- Member database
- Staff management

---

## ⚠️ Important: Before Staff Can Create Tickets

**MEMBERS MUST BE IN THE SYSTEM FIRST!**

### Import Members:
1. Go to: Admin Dashboard → Members → Bulk Import
2. Upload CSV file with members
3. Format:
   ```
   member_number,display_name,primary_phone,email,status,branch_code
   MUDI-001,John Doe,+254700000001,john@example.com,active,BLANTYRE_MAIN
   MUDI-002,Jane Smith,+254700000002,jane@example.com,active,LILONGWE_KANENGO
   ```
4. Click Import

Now staff can create tickets for these members!

---

## 🔄 Ticket Status Flow

```
OPEN
  ↓ (Staff starts work)
IN_PROGRESS
  ↓ (Issue resolved)
RESOLVED
  ↓ (Member confirms)
CLOSED
  ↓ (If issue not resolved)
REOPENED (back to OPEN)
```

---

## 📞 Demo Accounts for Testing

| Role | Email | Password |
|------|-------|----------|
| **Staff (Customer Service)** | support@mudi-sacco.local | MudiDemo123! |
| **Staff (Loans)** | loans@mudi-sacco.local | MudiDemo123! |
| **Staff (Compliance)** | compliance@mudi-sacco.local | MudiDemo123! |
| **Supervisor** | supervisor@mudi-sacco.local | MudiDemo123! |
| **Admin** | admin@mudi-sacco.local | MudiDemo123! |
| **Member** | member@mudi-sacco.local | MudiDemo123! |

---

## 🧪 Test Scenario

### **Step 1: Admin Imports Members**
1. Log in as admin
2. Go to Members → Bulk Import
3. Upload CSV with 10 test members

### **Step 2: Staff Creates Ticket**
1. Log in as support@mudi-sacco.local
2. Tickets → Create Ticket
3. Search: "MUDI-001" (first member)
4. Fill in: Subject, Description, Category
5. Click Create

### **Step 3: Supervisor Views Queue**
1. Log in as supervisor@mudi-sacco.local
2. See ticket in department queue
3. Assign to self or other staff

### **Step 4: Staff Resolves**
1. Log in as support@mudi-sacco.local
2. Click ticket → Update Status to RESOLVED
3. Add note: "Issue resolved, member notified"

### **Step 5: Close Ticket**
1. Click ticket → Update Status to CLOSED
2. Ticket complete!

---

## ❓ FAQs

**Q: Can members create their own tickets?**
A: No. This is a call-center model. Members call, staff creates tickets.

**Q: Why is staff ticket creation limited to their department?**
A: To ensure proper routing. Finance staff shouldn't create Loans tickets.

**Q: What if a ticket is assigned to the wrong department?**
A: Supervisors can reassign/escalate to the correct department.

**Q: Can members see other members' tickets?**
A: No. Each member only sees their own tickets.

**Q: How do members get notified?**
A: Staff calls/emails/SMS them outside the system (feature can be automated later).

**Q: What if a member's ticket is pending for too long?**
A: SLA rules trigger escalation alerts to supervisors.

---

## 🎯 Next Steps

1. ✅ Import 100+ members (Member Database)
2. ✅ Test creating tickets as staff
3. ✅ Configure departments and categories
4. ✅ Set SLA rules for escalations
5. ⏳ Add notification system (Email/SMS alerts)
6. ⏳ Add customer portal (Members can check ticket status)
7. ⏳ Add reporting & analytics dashboard
