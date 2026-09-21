# MUDI SACCO Ticketing System - Visual Workflow

## 🏢 Organizational Structure

```
ADMINISTRATOR
    admin@mudi-sacco.local
    ├── Manages everything
    └── Can see all departments
        
    CUSTOMER SERVICE DEPARTMENT                 LOANS DEPARTMENT
    Supervisor: Samuel Karanja                  Supervisor: Samuel Karanja
    ├─ Grace Wanjiku (Staff)                    ├─ Peter Otieno (Staff)
    └─ support@mudi-sacco.local                 └─ loans@mudi-sacco.local
    
    COMPLIANCE DEPARTMENT                       FINANCE DEPARTMENT
    ⚠️ Supervisor: NOT ASSIGNED                 ⚠️ Supervisor: NOT ASSIGNED
    ├─ Amina Hassan (Staff)                     ├─ Joel Chaula Ndege (Staff)
    └─ compliance@mudi-sacco.local              └─ mayendajabulani@gmail.com
    
    ICT DEPARTMENT
    Supervisor: Jabulani Mayenda
    ├─ Lusungu Muyawa (Staff)
    └─ muyawa@gmail.com
```

---

## 📞 Ticket Lifecycle

### **Call Comes In**
```
┌─────────────────┐
│  MEMBER CALLS   │
│   HELPLINE      │
│ "My card was    │
│  blocked!"      │
└────────┬────────┘
         │
         ▼
┌─────────────────────────────────────────────┐
│ STAFF ANSWERS PHONE (Any available staff)   │
│ Examples:                                   │
│ • Grace (Customer Service)                  │
│ • Peter (Loans)                             │
│ • Amina (Compliance)                        │
│ • Joel (Finance)                            │
│ • Lusungu (ICT)                             │
└────────┬────────────────────────────────────┘
         │
         ▼
    ┌─────────────────────────┐
    │ STAFF SEARCHES FOR      │
    │ MEMBER IN DATABASE      │
    │ "What's your member #?" │
    │ "MUDI-001"              │
    └────────┬────────────────┘
             │
             ▼
    ┌─────────────────────────────────┐
    │ STAFF CREATES TICKET            │
    │ Via Dashboard → Tickets → New   │
    │                                 │
    │ Fields:                         │
    │ • Member: MUDI-001              │
    │ • Issue Category: Card blocked  │
    │ • Subject: Account card blocked │
    │ • Description: Details here     │
    │ • Priority: HIGH                │
    │ • Channel: Phone                │
    └────────┬────────────────────────┘
             │
             ▼
    ┌────────────────────────────────┐
    │ SYSTEM CREATES TICKET          │
    │ Ticket #: TKT-2026-001         │
    │ Status: OPEN                   │
    │ Assigned to: Compliance Dept   │
    │ (Based on issue category)      │
    └────────┬───────────────────────┘
             │
             ▼
    ┌─────────────────────────────────┐
    │ COMPLIANCE TEAM SEES TICKET IN  │
    │ THEIR QUEUE                     │
    │                                 │
    │ Amina Hassan (Staff)            │
    │ [Can see ticket, handle it]     │
    └────────┬────────────────────────┘
             │
             ▼
    ┌────────────────────────────────┐
    │ AMINA HANDLES TICKET           │
    │                                │
    │ 1. Updates: OPEN → IN PROGRESS │
    │ 2. Investigates issue          │
    │ 3. Fixes card block            │
    │ 4. Adds note: "Issue resolved" │
    │ 5. Updates: → RESOLVED         │
    └────────┬───────────────────────┘
             │
             ▼
    ┌─────────────────────────────────┐
    │ STAFF NOTIFIES MEMBER           │
    │ (Outside system)                │
    │                                 │
    │ Amina CALLS member:             │
    │ "Your ticket is resolved.       │
    │  Card is unblocked now"         │
    └────────┬────────────────────────┘
             │
             ▼
    ┌────────────────────────────┐
    │ TICKET CLOSED              │
    │ Status: CLOSED             │
    │ Time to resolve: 15 mins   │
    │ ✓ Member satisfied         │
    └────────────────────────────┘
```

---

## 🔔 Alert Notification System

### **Alert Flow**

```
TICKET EVENT OCCURS
(New, Escalated, SLA at risk, etc.)
         │
         ▼
┌─────────────────────────────┐
│ SYSTEM CHECKS ALERT RULES   │
│ • Priority level?           │
│ • Who should be notified?   │
│ • Which channels?           │
└────────┬────────────────────┘
         │
         ▼
    ┌────────────────────────────────────────────┐
    │ ALERT PRIORITY DETERMINATION               │
    │                                            │
    │ CRITICAL (SLA BREACHED):                   │
    │ → SMS + EMAIL + IN-APP (immediate)         │
    │                                            │
    │ URGENT (SLA warning):                      │
    │ → SMS + EMAIL + IN-APP (15 min delay)      │
    │                                            │
    │ HIGH (Important):                          │
    │ → EMAIL + IN-APP (1 hour delay)            │
    │                                            │
    │ MEDIUM (Normal):                           │
    │ → IN-APP only (1 min delay)                │
    └────────┬─────────────────────────────────┘
             │
             ▼
┌──────────────────────────────────────────────┐
│ NOTIFICATIONS SENT VIA CHANNELS              │
│                                              │
│ 📱 IN-APP (Browser/Dashboard)                │
│    └─ Real-time notification banner          │
│                                              │
│ 📧 EMAIL                                     │
│    └─ Sent to staff@mudi-sacco.local        │
│                                              │
│ 📞 SMS                                       │
│    └─ Sent to registered phone number       │
│                                              │
│ 💬 WhatsApp (Future)                         │
│    └─ Direct WhatsApp message                │
└──────────────────────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────┐
│ STAFF SEES NOTIFICATION              │
│                                      │
│ Example:                             │
│ ⚠️ URGENT - SLA Expiring Soon        │
│ TKT-2026-001 - Card Blocked          │
│ Time Remaining: 15 minutes           │
│ [OPEN TICKET] [ESCALATE]             │
└──────────────────────────────────────┘
```

---

## 📊 Ticket Status Progression

```
                    TICKET STATES
                    
    ┌─────────────────────────────────────┐
    │            OPEN                     │
    │ (New ticket, waiting for staff)     │
    └──────────────┬──────────────────────┘
                   │
                   ▼ (Staff starts work)
    ┌─────────────────────────────────────┐
    │         IN_PROGRESS                 │
    │ (Staff actively working on issue)   │
    └──────────────┬──────────────────────┘
                   │
        ┌──────────┴──────────┐
        │                     │
        ▼                     ▼
    ┌─────────────┐    ┌─────────────────┐
    │  RESOLVED   │    │   WAITING       │
    │  (Issue     │    │ (Waiting for    │
    │   fixed)    │    │  member reply)  │
    └──────┬──────┘    └────────┬────────┘
           │                    │
           └────────┬───────────┘
                    │
                    ▼ (Member confirms)
    ┌─────────────────────────────────────┐
    │          CLOSED                     │
    │ (Issue resolved, ticket complete)   │
    └─────────────────────────────────────┘
    
    OR
    
    ┌──────────────────────────────────────────┐
    │ REOPENED (If issue not actually fixed)   │
    │ → Goes back to OPEN state                │
    └──────────────────────────────────────────┘
```

---

## 👥 Who Can Do What

```
┌─────────────────────────────────────────────────────────┐
│            CREATE TICKET                                │
├─────────────────────────────────────────────────────────┤
│ ✅ STAFF      (for members calling helpline)            │
│ ✅ SUPERVISOR (can create + manage department)          │
│ ✅ ADMIN      (can create anything)                     │
│ ❌ MEMBER     (cannot - call-center model)              │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│            RESOLVE/CLOSE TICKET                         │
├─────────────────────────────────────────────────────────┤
│ ✅ STAFF      (their assigned tickets only)             │
│ ✅ SUPERVISOR (any ticket in department)                │
│ ✅ ADMIN      (any ticket in system)                    │
│ ❌ MEMBER     (cannot resolve)                          │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│            ESCALATE/REASSIGN                            │
├─────────────────────────────────────────────────────────┤
│ ❌ STAFF      (cannot escalate own tickets)             │
│ ✅ SUPERVISOR (can reassign within department)          │
│ ✅ ADMIN      (can reassign/escalate anything)          │
│ ❌ MEMBER     (cannot)                                  │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│            SEE TICKETS                                  │
├─────────────────────────────────────────────────────────┤
│ STAFF:       Own assigned + Department queue            │
│ SUPERVISOR:  All department tickets                     │
│ ADMIN:       All tickets in system                      │
│ MEMBER:      Only own tickets (if any)                  │
└─────────────────────────────────────────────────────────┘
```

---

## ⏰ SLA (Service Level Agreement) Flow

```
TICKET CREATED
Time: 10:00 AM
Status: OPEN
└─ SLA Timer Starts (Based on Priority)
   
   PRIORITY: HIGH
   Response Time: 2 hours
   Resolution Time: 8 hours
   
┌──────────────────────────────────────┐
│  10:00 AM - Ticket Created           │
│  10:30 AM - Staff sees alert         │
│            "New ticket waiting"      │
└──────────────────────────────────────┘
           │
           ▼ (Within 2 hours)
┌──────────────────────────────────────┐
│  11:00 AM - Staff starts work        │
│            Status: IN_PROGRESS       │
│  Alert Sent: "On track"              │
└──────────────────────────────────────┘
           │
           ▼ (Within 8 hours)
┌──────────────────────────────────────┐
│  6:00 PM - Issue resolved            │
│           Status: RESOLVED           │
│  Time Taken: 8 hours ✓ (Within SLA)  │
│  Alert: "SLA Met"                    │
└──────────────────────────────────────┘

IF SLA IS BREACHED:

┌──────────────────────────────────────┐
│  6:30 PM - Still not resolved!       │
│  Time Overdue: 30 minutes            │
│  ⚠️ ALERT SENT:                      │
│  - TO: Staff member (SMS + EMAIL)    │
│  - CC: Supervisor (SMS + EMAIL)      │
│  - CC: Admin (EMAIL)                 │
│  Urgency: CRITICAL 🔴               │
└──────────────────────────────────────┘
```

---

## 📈 Department Performance

```
CUSTOMER SERVICE
Supervisor: Samuel Karanja
Staff: Grace Wanjiku
│
├─ Total Tickets This Month: 45
├─ Open: 3
├─ In Progress: 5
├─ Resolved: 37
├─ Average Resolution Time: 2.5 hours
├─ SLA Compliance: 94% ✓
└─ Escalations: 2

LOANS
Supervisor: Samuel Karanja (cross-dept)
Staff: Peter Otieno
│
├─ Total Tickets This Month: 23
├─ Open: 1
├─ In Progress: 2
├─ Resolved: 20
├─ Average Resolution Time: 4 hours
├─ SLA Compliance: 87%
└─ Escalations: 1

COMPLIANCE
⚠️ Supervisor: NOT ASSIGNED
Staff: Amina Hassan
│
├─ Total Tickets This Month: 12
├─ Open: 2
├─ In Progress: 1
├─ Resolved: 9
├─ Average Resolution Time: 3 hours
├─ SLA Compliance: 75% ⚠️
└─ Escalations: 3

FINANCE
⚠️ Supervisor: NOT ASSIGNED
Staff: Joel Chaula Ndege
│
├─ Total Tickets This Month: 8
├─ Open: 1
├─ In Progress: 1
├─ Resolved: 6
├─ Average Resolution Time: 3.5 hours
├─ SLA Compliance: 80%
└─ Escalations: 1

ICT
Supervisor: Jabulani Mayenda
Staff: Lusungu Muyawa
│
├─ Total Tickets This Month: 18
├─ Open: 2
├─ In Progress: 3
├─ Resolved: 13
├─ Average Resolution Time: 1.5 hours
├─ SLA Compliance: 98% ✓ (BEST)
└─ Escalations: 0
```

---

## ⚠️ Issues to Fix

```
1. MISSING SUPERVISORS
   ├─ Compliance Department: Needs supervisor
   │  (Currently alerts go to: Amina → Admin)
   └─ Finance Department: Needs supervisor
      (Currently alerts go to: Joel → Admin)

2. CROSS-DEPARTMENT SUPERVISION
   └─ Samuel Karanja supervises both:
      • Customer Service ✓
      • Loans (should be separate supervisor)

3. ALERT CONFIGURATION
   ├─ Email not configured (MAILER_DSN)
   ├─ SMS not configured (Africa's Talking)
   └─ WhatsApp not configured

4. NOTIFICATION DELIVERY
   └─ Currently notifications stored in DB
      but NOT actively sent to staff
```

---

## ✅ Quick Checklist

```
STAFF READY TO USE SYSTEM:
├─ ✅ Grace Wanjiku (Customer Service) - READY
├─ ✅ Peter Otieno (Loans) - READY
├─ ⚠️ Amina Hassan (Compliance) - READY (no supervisor)
├─ ⚠️ Joel Chaula Ndege (Finance) - READY (no supervisor)
├─ ✅ Lusungu Muyawa (ICT) - READY
├─ ✅ Samuel Karanja (Supervisor) - READY
├─ ✅ Jabulani Mayenda (Supervisor) - READY
└─ ✅ Admin - READY

NEXT STEPS:
├─ [ ] Import members (100+ records)
├─ [ ] Test creating tickets
├─ [ ] Assign supervisors to Compliance/Finance
├─ [ ] Configure email alerts
├─ [ ] Configure SMS alerts
└─ [ ] Train staff on system
```
