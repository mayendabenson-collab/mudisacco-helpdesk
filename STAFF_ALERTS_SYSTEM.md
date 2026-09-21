# MUDI SACCO Ticketing System - Staff & Alert Overview

## 📊 Current Staff Structure (From Database)

### **ADMINISTRATORS** (System Managers)
| Email | Name | Role | Department | Can Do |
|-------|------|------|-----------|--------|
| admin@mudi-sacco.local | Mudi SACCO Administrator | ADMIN | None | Everything - Manage system |

### **SUPERVISORS** (Department Heads)
| Email | Name | Department | Can Do |
|-------|------|-----------|--------|
| supervisor@mudi-sacco.local | Samuel Karanja | Customer Service | Oversee dept, escalate, manage SLA |
| mayendabenson@gmail.com | Jabulani Mayenda | ICT | Oversee dept, escalate, manage SLA |

### **STAFF** (Support Team - Ticket Handlers)
| Email | Name | Department | Can Do |
|-------|------|-----------|--------|
| support@mudi-sacco.local | Grace Wanjiku | Customer Service | Create & resolve tickets |
| loans@mudi-sacco.local | Peter Otieno | Loans | Create & resolve tickets |
| compliance@mudi-sacco.local | Amina Hassan | Compliance | Create & resolve tickets |
| mayendajabulani@gmail.com | Joel Chaula Ndege | Finance | Create & resolve tickets |
| muyawa@gmail.com | Lusungu Muyawa | ICT | Create & resolve tickets |

---

## 🎯 Ticket Creation & Resolution Flow

### **WHO CREATES TICKETS?**
✅ **STAFF** - When member calls
✅ **SUPERVISORS** - Can also create on behalf
✅ **ADMINS** - Can create anything

❌ **MEMBERS** - Cannot create (call-center model)

### **WHO RESOLVES TICKETS?**
✅ **STAFF** - Resolves tickets in their department
✅ **SUPERVISORS** - Can resolve + reassign
✅ **ADMINS** - Can resolve any ticket

### **TICKET CREATION PROCESS**

```
MEMBER CALLS HELPLINE
    ↓
STAFF (Grace, Peter, Amina, Joel, Lusungu) ANSWERS
    ↓
STAFF LOGS IN → Tickets → Create Ticket
    ↓
STAFF FILLS FORM:
  ├─ Member Number (search in database)
  ├─ Issue Category (auto-routes to department)
  ├─ Subject & Description
  ├─ Priority
  └─ Channel (Phone/Email/SMS)
    ↓
TICKET AUTO-ROUTES TO DEPARTMENT:
  ├─ Customer Service Issues → Grace (Supervisor: Samuel)
  ├─ Loans Issues → Peter (Supervisor: Samuel via Customer Service supervisor)
  ├─ Compliance Issues → Amina (No supervisor - needs setup)
  ├─ Finance Issues → Joel (No supervisor - needs setup)
  └─ ICT Issues → Lusungu (Supervisor: Jabulani)
    ↓
DEPARTMENT STAFF SEES TICKET IN QUEUE
    ↓
STAFF HANDLES TICKET:
  └─ Updates status: Open → In Progress → Resolved
    ↓
STAFF RESOLVES & NOTIFIES MEMBER
    ↓
TICKET CLOSED ✓
```

---

## 🔔 Alert & Notification System

### **Current Alert Channels Available:**
1. 📱 **IN_APP** - In-application notifications
2. 📧 **EMAIL** - Email notifications
3. 📞 **SMS** - SMS text messages
4. 💬 **WHATSAPP** - WhatsApp messages

### **When Should Staff Be Alerted?**

#### **For STAFF (Ticket Handlers):**
| Event | Alert? | Channel | Urgency |
|-------|--------|---------|---------|
| New ticket assigned to you | ✅ YES | IN_APP + EMAIL | MEDIUM |
| Ticket escalated to you | ✅ YES | IN_APP + EMAIL + SMS | HIGH |
| Ticket SLA about to expire | ✅ YES | IN_APP + SMS | URGENT |
| Ticket SLA BREACHED | ✅ YES | IN_APP + SMS + EMAIL | CRITICAL |
| Member replied to ticket | ✅ YES | IN_APP + EMAIL | MEDIUM |
| Ticket reassigned from you | ✅ YES | IN_APP | LOW |

#### **For SUPERVISORS (Department Heads):**
| Event | Alert? | Channel | Urgency |
|-------|--------|---------|---------|
| Critical ticket in department | ✅ YES | IN_APP + SMS | CRITICAL |
| High priority ticket | ✅ YES | IN_APP + EMAIL | HIGH |
| Staff member overloaded (5+ pending) | ✅ YES | IN_APP | MEDIUM |
| SLA breached in department | ✅ YES | IN_APP + EMAIL + SMS | URGENT |
| Escalation needed | ✅ YES | IN_APP + SMS | HIGH |

#### **For ADMINS:**
| Event | Alert? | Channel | Urgency |
|-------|--------|---------|---------|
| System errors | ✅ YES | EMAIL | CRITICAL |
| Multiple SLA breaches | ✅ YES | EMAIL + SMS | URGENT |
| Staff member inactive | ✅ YES | IN_APP | LOW |

---

## 📧 Alert Examples

### **Example 1: New Ticket Alert (Staff)**
```
TO: support@mudi-sacco.local (Grace Wanjiku)
FROM: MUDI SACCO Support System

SUBJECT: New Ticket Assigned - TKT-2026-001
PRIORITY: Customer Service - Failed Withdrawal

BODY:
A new ticket has been assigned to you:

Ticket #: TKT-2026-001
Member: John Doe (MUDI-001)
Issue: Failed withdrawal 5000 KES
Priority: HIGH
Status: OPEN
Created: 2026-09-16 10:30 AM

ACTION REQUIRED: Investigate and resolve
SLA Time: 2 hours remaining

[OPEN TICKET] button
```

### **Example 2: SLA Breach Alert (Staff + Supervisor)**
```
TO: support@mudi-sacco.local, supervisor@mudi-sacco.local
FROM: MUDI SACCO Support System

SUBJECT: ⚠️ CRITICAL - SLA BREACHED - TKT-2026-001

BODY:
SLA TIME EXCEEDED!

Ticket #: TKT-2026-001
Member: John Doe
Time Overdue: 45 minutes
Original SLA: 2 hours
Current Status: IN_PROGRESS

REQUIRED ACTION:
- Resolve urgently OR
- Escalate to supervisor

[ESCALATE] [RESOLVE]
```

### **Example 3: Workload Alert (Supervisor)**
```
TO: supervisor@mudi-sacco.local (Samuel Karanja)
FROM: MUDI SACCO Support System

SUBJECT: Staff Workload Alert

BODY:
Grace Wanjiku has 7 pending tickets (High workload)

Pending Tickets:
- 2 CRITICAL priority
- 3 HIGH priority
- 2 MEDIUM priority

RECOMMENDATION:
Consider reassigning some tickets to other staff

[VIEW QUEUE] button
```

---

## 💡 Smart Alert Logic

### **Priority Rules:**
1. **CRITICAL** (SLA BREACHED) → IN_APP + SMS + EMAIL immediately
2. **URGENT** (SLA expiring soon) → IN_APP + SMS within 15 mins
3. **HIGH** (Important) → IN_APP + EMAIL within 1 hour
4. **MEDIUM** (Normal) → IN_APP only
5. **LOW** (Info) → IN_APP only

### **Escalation Rules:**
- If ticket SLA expires → Auto-notify supervisor
- If ticket unresolved for 4+ hours → Escalate to supervisor
- If supervisor doesn't handle → Escalate to admin

### **Staff Workload Rules:**
- Alert supervisor if staff has 5+ pending tickets
- Alert supervisor if any ticket is overdue
- Alert admin if entire department is backed up

---

## 🚨 Alert Configuration (To Be Set Up)

### **Current State:**
- ✅ Notification system created (database + models)
- ✅ Alert channels defined (Email, SMS, WhatsApp, In-App)
- ⏳ **NOT YET IMPLEMENTED:**
  - Actual email sending
  - SMS sending (Twilio/Africa's Talking integration)
  - WhatsApp messaging
  - Real-time push notifications
  - Alert rule engine

### **What's Needed:**
1. **Email Service** - Configure SMTP for alerts
2. **SMS Provider** - Africa's Talking or Twilio
3. **Alert Rules Engine** - Trigger based on ticket events
4. **Real-time Notifications** - WebSocket or polling for in-app alerts
5. **Notification Preferences** - Let staff choose which channels to use

---

## 📱 Recommended Alert Setup

### **Priority Mapping:**

**CRITICAL** (SLA BREACHED):
```
CHANNELS: SMS + EMAIL + IN_APP (pop-up alert)
DELAY: 0 seconds (immediate)
SOUND: Alert tone
RECIPIENTS: Staff + Supervisor + Admin
```

**URGENT** (SLA expiring in <30 mins):
```
CHANNELS: SMS + EMAIL + IN_APP
DELAY: 15 seconds
RECIPIENTS: Staff + Supervisor
```

**HIGH** (Important ticket):
```
CHANNELS: EMAIL + IN_APP
DELAY: 5 minutes
RECIPIENTS: Staff + Supervisor (if overdue)
```

**MEDIUM** (New ticket):
```
CHANNELS: IN_APP only
DELAY: 1 minute
RECIPIENTS: Assigned staff
```

---

## 🎯 Department Alert Structure

### **Customer Service (Grace Wanjiku, Supervisor: Samuel Karanja)**
- General inquiries, complaints
- If overdue: Grace notified → Samuel notified (if still pending)

### **Loans (Peter Otieno, Supervisor: Samuel Karanja)**
- Loan applications, repayments
- If overdue: Peter notified → Samuel notified (if still pending)

### **Compliance (Amina Hassan, Supervisor: NONE)**
- Fraud, security, account issues
- ⚠️ **NEEDS SUPERVISOR ASSIGNED**
- Alert goes to: Amina → Admin (no supervisor)

### **Finance (Joel Chaula Ndege, Supervisor: NONE)**
- Deposits, shares, corrections
- ⚠️ **NEEDS SUPERVISOR ASSIGNED**
- Alert goes to: Joel → Admin (no supervisor)

### **ICT (Lusungu Muyawa, Supervisor: Jabulani Mayenda)**
- Portal access, technical issues
- If overdue: Lusungu notified → Jabulani notified

---

## ⚙️ Implementation Roadmap

### **Phase 1: Foundation (Done)**
- ✅ Staff structure set up
- ✅ Ticket creation/resolution workflow
- ✅ Notification database schema

### **Phase 2: Alerts (In Progress)**
- ⏳ Email sending (SMTP configuration)
- ⏳ Alert rule engine
- ⏳ In-app notifications

### **Phase 3: Advanced Messaging**
- ⏳ SMS sending (Africa's Talking)
- ⏳ WhatsApp integration
- ⏳ Real-time push notifications

### **Phase 4: Analytics**
- ⏳ Staff performance dashboard
- ⏳ SLA compliance reporting
- ⏳ Queue health monitoring

---

## 📝 Next Actions

### **Immediate (This Week):**
1. Set up missing supervisors:
   - Assign supervisor for Compliance (Amina)
   - Assign supervisor for Finance (Joel)

2. Configure email alerts:
   - Set MAILER_DSN in .env for real email sending

3. Test ticket workflow:
   - Create test members
   - Create test tickets
   - Verify routing to departments

### **Short Term (Next Week):**
1. Implement SMS alerts
2. Test all alert channels
3. Set alert preferences per staff member

### **Medium Term (This Month):**
1. Add WhatsApp integration
2. Real-time notifications
3. Performance dashboard

---

## 🔗 Quick Links

- **Create Ticket:** `/tickets/new`
- **View Tickets:** `/tickets`
- **Admin Panel:** `/admin/staff`
- **Members:** `/admin/members`
- **Dashboard:** `/dashboard`

---

## 📞 Contact for Setup Help

- **Supervisors needing assignment:** Admin to configure
- **Alert configuration:** Check `.env` file for MAILER_DSN
- **SMS setup:** Need Africa's Talking or Twilio credentials
