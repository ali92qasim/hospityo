# Hospital Management System - User Manual

## 📖 Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [User Roles & Permissions](#user-roles--permissions)
4. [Dashboard Overview](#dashboard-overview)
5. [Patient Management](#patient-management)
6. [Doctor Management](#doctor-management)
7. [Department Management](#department-management)
8. [Appointment Management](#appointment-management)
9. [Visit Management (OPD)](#visit-management-opd)
10. [IPD Management](#ipd-management)
11. [Pharmacy Management](#pharmacy-management)
12. [Diagnostics (Lab & Radiology)](#diagnostics-lab--radiology)
13. [Billing & Payments](#billing--payments)
14. [Reports](#reports)
15. [Access Control](#access-control)
16. [Settings](#settings)
17. [Common Workflows](#common-workflows)
18. [Troubleshooting](#troubleshooting)
19. [FAQ](#faq)

---

## 1. Introduction

### About Hospityo

Hospityo is a comprehensive Hospital Management System designed to streamline hospital operations, from patient registration to billing and reporting. The system provides role-based access control, ensuring that each staff member has access only to the features they need.

### Key Features

- **Patient Management**: Complete patient records with history tracking
- **Appointment Scheduling**: Calendar-based appointment system
- **OPD Management**: Outpatient department workflow with triage, consultation, and prescriptions
- **IPD Management**: Inpatient department with ward and bed management
- **Pharmacy**: Medicine inventory, prescriptions, and stock management
- **Laboratory**: Investigation orders, sample tracking, and results
- **Billing**: Comprehensive billing with multiple payment methods
- **Reports**: 13 detailed reports for analytics and decision-making
- **Access Control**: Role-based permissions for security

### System Requirements

- Modern web browser (Chrome, Firefox, Safari, Edge)
- Internet connection
- Screen resolution: 1366x768 or higher (recommended)

---

## 2. Getting Started

### First Login

1. Open your web browser and navigate to your hospital's system URL
2. Enter your email and password
3. Click "Login"

**Default Admin Credentials** (Change immediately after first login):
```
Email: admin@hospityo.com
Password: password
```

### Changing Your Password

1. Click on your profile icon in the top-right corner
2. Select "Profile"
3. Click "Update Password"
4. Enter your current password and new password
5. Click "Save Changes"

### Navigation

The system uses a sidebar navigation menu on the left side:
- **Dashboard**: Overview and quick stats
- **Patients**: Patient records
- **Departments**: Hospital departments
- **Doctors**: Doctor profiles
- **Visits**: OPD visits
- **Appointments**: Appointment calendar
- **IPD Management**: Wards and beds
- **Pharmacy**: Medicine and inventory
- **Diagnostics**: Lab tests and results
- **Billing**: Bills and payments
- **Reports**: Analytics and reports
- **Access Control**: Users, roles, and permissions

---

## 3. User Roles & Permissions

### Available Roles

#### Super Admin
- Full system access
- Can manage all modules
- Can create and manage users
- Can configure system settings

#### Hospital Administrator
- Manages hospital operations
- Access to all modules except system configuration
- Can manage staff and departments
- Can view all reports

#### Doctor
- View assigned patients
- Manage consultations and prescriptions
- Order investigations
- View patient history

#### Nurse
- Register patients
- Record vital signs
- Triage patients
- Assist with patient care

#### Receptionist
- Register patients
- Schedule appointments
- Manage patient check-ins
- Basic billing operations

#### Pharmacist
- Manage medicine inventory
- Dispense prescriptions
- Track stock levels
- Create purchase orders

#### Lab Technician
- Receive investigation orders
- Collect samples
- Enter test results
- Generate reports

#### Billing Clerk
- Create and manage bills
- Process payments
- Generate invoices
- Track outstanding payments

---

## 4. Dashboard Overview

The dashboard provides a quick overview of hospital operations:

### Key Metrics (varies by role)
- Total patients registered
- Today's appointments
- Active visits
- Pending bills
- Low stock medicines
- Pending lab results

### Quick Actions
- Register new patient
- Create appointment
- Start new visit
- View pending tasks

---

## 5. Patient Management

### Registering a New Patient

1. Click "Patients" in the sidebar
2. Click "Add New Patient" button
3. Fill in patient information:
   - **Personal Information**: Name, date of birth, gender
   - **Contact Information**: Phone, email, address
   - **Emergency Contact**: Name and phone number
   - **Medical Information**: Blood group, allergies
4. Click "Save Patient"

**Note**: A unique Patient Number is automatically generated.

### Searching for Patients

Use the search bar to find patients by:
- Patient number
- Name
- Phone number
- Email

### Viewing Patient Details

1. Click on a patient from the list
2. View tabs:
   - **Overview**: Basic information
   - **Visits**: Visit history
   - **Prescriptions**: Medication history
   - **Lab Results**: Investigation results
   - **Bills**: Billing history

### Editing Patient Information

1. Open patient details
2. Click "Edit" button
3. Update information
4. Click "Save Changes"

### Patient History

View complete patient history including:
- Previous visits and diagnoses
- Prescribed medications
- Lab test results
- Admission records
- Billing information

---

## 6. Doctor Management

### Adding a New Doctor

1. Navigate to "Doctors" in the sidebar
2. Click "Add New Doctor"
3. Fill in doctor information:
   - **Personal Details**: Name, qualification, specialization
   - **Professional Details**: PMDC number, experience
   - **Department**: Assign to department
   - **Contact**: Phone, email
   - **Schedule**: Working days and hours
4. Click "Save Doctor"

### Linking Doctor to User Account

To allow a doctor to login:
1. Go to "Access Control" > "Users"
2. Create a new user with doctor's email
3. Assign "Doctor" role
4. Link user to doctor profile in doctor edit page

### Managing Doctor Schedules

1. Open doctor details
2. Click "Edit Schedule"
3. Set available days and time slots
4. Click "Save Schedule"

---

## 7. Department Management

### Creating a Department

1. Click "Departments" in sidebar
2. Click "Add New Department"
3. Enter:
   - Department name
   - Department code
   - Description
   - Status (Active/Inactive)
4. Click "Save Department"

### Assigning Doctors to Departments

1. Open doctor profile
2. Select department from dropdown
3. Save changes

---

## 8. Appointment Management

### Creating an Appointment

1. Click "Appointments" in sidebar
2. Click "Create Appointment"
3. Select or search for patient
4. Choose doctor
5. Select date and time
6. Add appointment reason/notes
7. Click "Save Appointment"

### Calendar View

- View appointments in calendar format
- Color-coded by status:
  - **Blue**: Scheduled
  - **Green**: Completed
  - **Red**: Cancelled
  - **Yellow**: No-show

### Managing Appointments

**Reschedule**:
1. Click on appointment
2. Click "Edit"
3. Change date/time
4. Save changes

**Cancel**:
1. Click on appointment
2. Click "Cancel"
3. Confirm cancellation

**Mark as Completed**:
1. Click on appointment
2. Click "Complete"

---

## 9. Visit Management (OPD)

### Starting a New Visit

1. Click "Visits" in sidebar
2. Click "New Visit"
3. Select patient (or register new)
4. Choose visit type (OPD/Emergency)
5. Click "Start Visit"

### Visit Workflow

The visit follows these stages:

#### 1. Triage (Nurse)
- Record chief complaint
- Assign priority level (Normal/Urgent/Emergency)
- Record initial observations

#### 2. Vital Signs (Nurse)
- Blood Pressure
- Temperature
- Pulse Rate
- Respiratory Rate
- Weight
- Height
- Oxygen Saturation

#### 3. Doctor Assignment
- Assign available doctor
- Doctor receives notification

#### 4. Consultation (Doctor)
- Review patient history
- Record presenting complaints
- Perform examination (GPE)
- Add provisional diagnosis
- Record allergies
- Create treatment plan

#### 5. Prescription (Doctor)
- Add medicines with instructions
- Specify dosage and duration
- Select from predefined instructions

#### 6. Investigation Orders (Doctor)
- Order lab tests
- Order radiology tests
- Add special instructions

#### 7. Billing
- Generate bill for services
- Process payment
- Print receipt

#### 8. Complete Visit
- Mark visit as complete
- Print prescription
- Schedule follow-up if needed

### Printing Prescription

1. Open visit details
2. Click "Print Prescription"
3. Prescription includes:
   - Patient information
   - Vital signs
   - Diagnosis
   - Medicines with instructions
   - Investigation orders
   - Doctor's signature
   - Next visit date

---

## 10. IPD Management

### Ward Management

#### Creating a Ward

1. Navigate to "IPD Management" > "Wards"
2. Click "Add New Ward"
3. Enter:
   - Ward name
   - Ward type (General/ICU/Private)
   - Total beds
   - Description
4. Click "Save Ward"

### Bed Management

#### Adding Beds

1. Go to "IPD Management" > "Beds"
2. Click "Add New Bed"
3. Select ward
4. Enter bed number
5. Set bed type
6. Click "Save Bed"

#### Bed Status
- **Available**: Ready for patient
- **Occupied**: Patient admitted
- **Maintenance**: Under repair
- **Reserved**: Booked for patient

### Patient Admission

1. Open patient visit
2. Click "Admit Patient"
3. Select ward and bed
4. Enter admission details:
   - Admission date/time
   - Reason for admission
   - Attending doctor
5. Click "Admit"

### Patient Discharge

1. Open admitted patient
2. Click "Discharge"
3. Enter:
   - Discharge date/time
   - Discharge summary
   - Follow-up instructions
4. Generate final bill
5. Click "Discharge Patient"

---

## 11. Pharmacy Management

### Medicine Categories

#### Creating Categories

1. Go to "Pharmacy" > "Categories"
2. Click "Add Category"
3. Enter:
   - Category name
   - Category code
   - Description
4. Click "Save"

Examples: Antibiotics, Analgesics, Cardiovascular, etc.

### Medicine Brands

#### Adding Brands

1. Go to "Pharmacy" > "Brands"
2. Click "Add Brand"
3. Enter brand name and description
4. Click "Save"

### Medicine Management

#### Adding a New Medicine

1. Navigate to "Pharmacy" > "Medicines"
2. Click "Add Medicine"
3. Fill in details:
   - **Medicine Name** (Required)
   - **SKU**: Auto-generated or manual
   - **Generic Name**
   - **Brand**: Select from list
   - **Category**: Select from list
   - **Dosage Form**: Tablet, Capsule, Syrup, etc.
   - **Strength**: e.g., 500mg, 10ml
   - **Units**: Base, Purchase, Dispensing
   - **Reorder Level**: Minimum stock alert
   - **Manage Stock**: Enable/disable inventory tracking
4. Click "Save Medicine"

**SKU (Stock Keeping Unit)**:
- Auto-generated based on medicine name, strength, dosage, and brand
- Can be manually entered
- Must be unique
- Helps in inventory tracking

### Prescription Instructions

#### Managing Instructions

1. Go to "Pharmacy" > "Instructions"
2. Click "Add Instruction"
3. Enter instruction text (e.g., "Take 1 tablet twice daily after meals")
4. Select category (Frequency, Timing, etc.)
5. Click "Save"

### Inventory Management

#### Stock In (Receiving Stock)

1. Go to "Pharmacy" > "Inventory"
2. Click "Stock In"
3. Select medicine
4. Enter:
   - Quantity
   - Unit cost
   - Supplier
   - Batch number
   - Expiry date
   - Reference number (Invoice/PO)
5. Click "Add Stock"

#### Stock Out (Dispensing/Wastage)

1. Go to "Pharmacy" > "Inventory"
2. Click "Stock Out"
3. Select medicine
4. Enter quantity and reason
5. Click "Process"

#### Low Stock Alert

- View medicines below reorder level
- System highlights critical stock
- Quick link to add stock

#### Expiring Stock

- View medicines expiring within 3 months
- Color-coded by urgency:
  - **Red**: Expired or expiring within 30 days
  - **Yellow**: Expiring within 60 days
  - **Green**: Expiring within 90 days

### Supplier Management

#### Adding Suppliers

1. Go to "Pharmacy" > "Suppliers"
2. Click "Add Supplier"
3. Enter:
   - Supplier name
   - Contact person
   - Phone and email
   - Address
   - Payment terms
4. Click "Save"

### Purchase Orders

#### Creating a Purchase Order

1. Go to "Pharmacy" > "Purchase Orders"
2. Click "Create Purchase Order"
3. Select supplier
4. Add medicines:
   - Select medicine
   - Enter quantity
   - Enter unit price
5. Review total amount
6. Add notes if needed
7. Click "Create Order"

#### Purchase Order Workflow

1. **Pending**: Order created
2. **Approved**: Order approved by manager
3. **Received**: Stock received and added to inventory
4. **Cancelled**: Order cancelled

---

## 12. Diagnostics (Lab & Radiology)

### Investigation Management

#### Adding Investigations

1. Go to "Diagnostics" > "Investigations"
2. Click "Add Investigation"
3. Enter:
   - Investigation name (e.g., "Complete Blood Count (CBC)")
   - Investigation code
   - Type: Laboratory or Radiology
   - Category
   - Price
   - Normal range (for lab tests)
4. Click "Save"

### Investigation Orders

#### Viewing Orders

1. Go to "Diagnostics" > "Investigation Orders"
2. Filter by:
   - Status (Pending/Sample Collected/Completed)
   - Date range
   - Investigation type

#### Sample Collection

1. Open investigation order
2. Click "Collect Sample"
3. Enter:
   - Collection date/time
   - Collected by
   - Sample type
4. Click "Save"

### Lab Results

#### Entering Results

1. Go to "Diagnostics" > "Investigation Results"
2. Click on pending result
3. Enter test values
4. Add remarks if needed
5. Click "Save Result"

#### Verifying Results

1. Open completed result
2. Review values
3. Click "Verify"
4. Result is marked as verified

#### Printing Reports

1. Open investigation result
2. Click "Print Report"
3. Report includes:
   - Patient information
   - Test details
   - Results with normal ranges
   - Lab technician signature
   - Verification status

---

## 13. Billing & Payments

### Services Management

#### Adding Services

1. Go to "Billing" > "Services"
2. Click "Add Service"
3. Enter:
   - Service name
   - Service code
   - Category (Consultation, Procedure, etc.)
   - Price
   - Description
4. Click "Save"

### Creating a Bill

1. Go to "Billing" > "Bills"
2. Click "Create Bill"
3. Select patient
4. Add items:
   - Services
   - Medicines
   - Investigation charges
5. Review total
6. Click "Generate Bill"

### Processing Payments

1. Open bill
2. Click "Add Payment"
3. Enter:
   - Payment amount
   - Payment method (Cash/Card/Insurance)
   - Reference number
4. Click "Process Payment"

### Payment Status

- **Unpaid**: No payment received
- **Partial**: Partial payment made
- **Paid**: Fully paid

### Printing Bills

1. Open bill
2. Click "Print Bill"
3. Invoice includes:
   - Hospital information
   - Patient details
   - Itemized charges
   - Payment details
   - Balance due

---

## 14. Reports

The system provides 13 comprehensive reports:

### 1. Daily Cash Register

**Purpose**: Track daily cash collection

**Filters**:
- Date range
- Payment method

**Shows**:
- Total collection
- Payment method breakdown
- Cashier-wise collection
- Hourly trends

### 2. Patient Visit Report

**Purpose**: OPD attendance statistics

**Filters**:
- Date range
- Department
- Doctor

**Shows**:
- Total visits
- New vs. returning patients
- Doctor-wise breakdown
- Visit type distribution

### 3. Revenue Report

**Purpose**: Financial performance analysis

**Filters**:
- Date range
- Service category

**Shows**:
- Total revenue
- Service-wise revenue
- Doctor-wise revenue
- Daily trends

### 4. Outstanding Bills Report

**Purpose**: Track unpaid bills

**Filters**:
- Date range
- Payment status
- Aging period

**Shows**:
- Total outstanding
- Patient-wise breakdown
- Aging analysis
- Collection efficiency

### 5. Lab Test Report

**Purpose**: Investigation statistics

**Filters**:
- Date range
- Test type
- Status

**Shows**:
- Total tests ordered
- Test-wise breakdown
- Turnaround time
- Pending tests

### 6. Medicine Sales Report

**Purpose**: Prescription and dispensing statistics

**Filters**:
- Date range
- Medicine
- Category

**Shows**:
- Total prescriptions
- Medicine-wise sales
- Category breakdown
- Doctor-wise prescriptions

### 7. Inventory Status Report

**Purpose**: Current stock levels

**Filters**:
- Category
- Stock status

**Shows**:
- Total medicines
- Stock levels
- Low stock items
- Out of stock items
- Category-wise health

### 8. Expiry Report

**Purpose**: Track expired and expiring medicines

**Filters**:
- Expiry period (30/60/90 days)
- Category

**Shows**:
- Expired items
- Expiring soon
- Batch details
- Quantity affected

### 9. Doctor Performance Report

**Purpose**: Individual doctor metrics

**Filters**:
- Date range
- Doctor

**Shows**:
- Total patients seen
- Revenue generated
- Average consultation time
- Patient satisfaction

### 10. Appointment Statistics

**Purpose**: Appointment trends

**Filters**:
- Date range
- Doctor
- Status

**Shows**:
- Total appointments
- Completion rate
- No-show rate
- Time slot analysis

### 11. IPD Report

**Purpose**: Inpatient statistics

**Filters**:
- Date range
- Ward

**Shows**:
- Total admissions
- Bed occupancy rate
- Average length of stay
- Discharge summary

### 12. Department Performance

**Purpose**: Department-wise metrics

**Filters**:
- Date range
- Department

**Shows**:
- Patient volume
- Revenue contribution
- Resource utilization
- Efficiency scores

### 13. Patient Demographics

**Purpose**: Patient population analysis

**Filters**:
- Date range

**Shows**:
- Age distribution
- Gender distribution
- Geographic distribution
- Visit patterns

### Printing Reports

All reports include a "Print" button that generates a printer-friendly version.

---

## 15. Access Control

### User Management

#### Creating a New User

1. Go to "Access Control" > "Users"
2. Click "Add User"
3. Enter:
   - Name
   - Email
   - Password
   - Assign role
4. Click "Create User"

#### Editing Users

1. Open user details
2. Click "Edit"
3. Update information
4. Change role if needed
5. Click "Save"

#### Deactivating Users

1. Open user details
2. Click "Deactivate"
3. Confirm action

### Role Management

#### Creating Custom Roles

1. Go to "Access Control" > "Roles"
2. Click "Create Role"
3. Enter role name
4. Select permissions
5. Click "Save Role"

### Permission Management

Permissions are organized by module:
- **Patients**: View, create, edit, delete
- **Doctors**: View, create, edit, delete
- **Visits**: View, create, edit, delete
- **Appointments**: View, create, edit, delete
- **Bills**: View, create, edit, delete
- **Services**: View, create, edit, delete
- **Departments**: View, create, edit, delete
- **Roles**: View, create, edit, delete
- **Permissions**: View, create, edit, delete
- **Users**: View, create, edit, delete

---

## 16. Settings

### Hospital Settings

1. Click "Settings" in sidebar
2. Update:
   - Hospital name
   - Hospital logo
   - Contact information
   - Address
   - Email and phone
3. Click "Save Settings"

### Profile Settings

1. Click profile icon
2. Select "Profile"
3. Update:
   - Name
   - Email
   - Password
4. Click "Save"

---

## 17. Common Workflows

### Workflow 1: New Patient Registration to Billing

1. **Register Patient** (Receptionist)
   - Add patient details
   - Generate patient number

2. **Create Appointment** (Receptionist)
   - Schedule with doctor
   - Set date and time

3. **Start Visit** (Receptionist/Nurse)
   - Check-in patient
   - Create visit record

4. **Triage** (Nurse)
   - Record chief complaint
   - Assign priority

5. **Record Vitals** (Nurse)
   - Measure vital signs
   - Update visit

6. **Consultation** (Doctor)
   - Examine patient
   - Add diagnosis
   - Create prescription
   - Order investigations

7. **Dispense Medicine** (Pharmacist)
   - Review prescription
   - Dispense medicines
   - Update inventory

8. **Perform Tests** (Lab Technician)
   - Collect samples
   - Perform tests
   - Enter results

9. **Generate Bill** (Billing Clerk)
   - Add all charges
   - Calculate total
   - Generate invoice

10. **Process Payment** (Billing Clerk)
    - Receive payment
    - Print receipt
    - Complete visit

### Workflow 2: Emergency Patient Admission

1. **Register Patient** (Emergency Staff)
   - Quick registration
   - Mark as emergency

2. **Triage** (Nurse)
   - Assess severity
   - Assign emergency priority

3. **Initial Treatment** (Doctor)
   - Stabilize patient
   - Order urgent tests

4. **Admit Patient** (Nurse)
   - Select ward and bed
   - Complete admission

5. **Ongoing Care** (Medical Team)
   - Monitor patient
   - Update treatment
   - Record progress

6. **Discharge** (Doctor)
   - Discharge summary
   - Follow-up instructions
   - Final billing

### Workflow 3: Medicine Stock Management

1. **Check Stock Levels** (Pharmacist)
   - Review inventory report
   - Identify low stock

2. **Create Purchase Order** (Pharmacist)
   - Select supplier
   - Add medicines
   - Submit for approval

3. **Approve Order** (Manager)
   - Review order
   - Approve purchase

4. **Receive Stock** (Pharmacist)
   - Verify delivery
   - Mark as received
   - Stock automatically updated

5. **Monitor Expiry** (Pharmacist)
   - Check expiry report
   - Remove expired items
   - Plan stock rotation

---

## 18. Troubleshooting

### Common Issues

#### Cannot Login

**Problem**: Invalid credentials error

**Solution**:
1. Verify email and password
2. Check Caps Lock is off
3. Contact administrator to reset password

#### Page Not Loading

**Problem**: Blank or error page

**Solution**:
1. Refresh the page (F5)
2. Clear browser cache
3. Try different browser
4. Check internet connection

#### Cannot Save Data

**Problem**: Form submission fails

**Solution**:
1. Check all required fields are filled
2. Verify data format (dates, numbers)
3. Check for error messages
4. Try again after a few seconds

#### Print Not Working

**Problem**: Print button doesn't work

**Solution**:
1. Check browser pop-up blocker
2. Allow pop-ups for the site
3. Try different browser
4. Check printer connection

#### Report Not Generating

**Problem**: Report shows no data

**Solution**:
1. Verify date range is correct
2. Check filters are not too restrictive
3. Ensure data exists for selected period
4. Try broader date range

---

## 19. FAQ

### General Questions

**Q: How do I change my password?**
A: Click profile icon > Profile > Update Password

**Q: Can I access the system from mobile?**
A: Yes, the system is responsive and works on tablets and phones.

**Q: How do I get help?**
A: Contact your system administrator or refer to this manual.

### Patient Management

**Q: Can I merge duplicate patient records?**
A: Contact your administrator to merge records.

**Q: How do I view patient history?**
A: Open patient details and click "History" tab.

**Q: Can patients have multiple visits on the same day?**
A: Yes, each visit is tracked separately.

### Appointments

**Q: Can I book recurring appointments?**
A: Currently, each appointment must be booked individually.

**Q: What happens if a patient doesn't show up?**
A: Mark the appointment as "No-show" in the system.

**Q: Can I see doctor availability?**
A: Yes, the calendar shows available time slots.

### Pharmacy

**Q: What if medicine SKU is not unique?**
A: System will show error. Use a different SKU or let system auto-generate.

**Q: How do I handle medicine returns?**
A: Use Stock Out with reason "Return" or "Expired".

**Q: Can I track medicine batches?**
A: Yes, enter batch number during stock-in.

### Billing

**Q: Can I apply discounts?**
A: Yes, discounts can be applied at bill level.

**Q: How do I handle insurance claims?**
A: Select "Insurance" as payment method and enter details.

**Q: Can I split payments?**
A: Yes, add multiple payments until bill is fully paid.

### Reports

**Q: Can I export reports to Excel?**
A: Use browser print function and select "Save as PDF".

**Q: How often are reports updated?**
A: Reports show real-time data.

**Q: Can I schedule automatic reports?**
A: Contact administrator for scheduled report setup.

---

## Support & Contact

For technical support or questions:
- Contact your system administrator
- Refer to the Installation Guide for technical issues
- Check the Developer Guide for customization

---

**Version**: 1.0.0  
**Last Updated**: March 2026  
**Copyright**: © 2026 Hospityo. All rights reserved.

---

## Quick Reference Card

### Keyboard Shortcuts
- `Ctrl + S`: Save form (where applicable)
- `Esc`: Close modal/dialog
- `Ctrl + P`: Print current page

### Status Color Codes
- **Green**: Active/Completed/Paid
- **Yellow**: Pending/Warning
- **Red**: Cancelled/Urgent/Overdue
- **Blue**: Scheduled/In Progress
- **Gray**: Inactive/Disabled

### Common Icons
- 🏥 Hospital/Ward
- 👤 Patient
- 👨‍⚕️ Doctor
- 📅 Appointment
- 💊 Medicine
- 🔬 Lab Test
- 💰 Billing
- 📊 Reports
- ⚙️ Settings

---

**End of User Manual**
