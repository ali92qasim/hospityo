<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        Page::firstOrCreate(
            ['slug' => 'terms-and-conditions'],
            [
                'title' => 'Terms & Conditions',
                'is_active' => true,
                'content' => $this->getTermsContent(),
            ]
        );

        Page::firstOrCreate(
            ['slug' => 'privacy-policy'],
            [
                'title' => 'Privacy Policy',
                'is_active' => true,
                'content' => $this->getPrivacyContent(),
            ]
        );

        Page::firstOrCreate(
            ['slug' => 'refund-policy'],
            [
                'title' => 'Refund Policy',
                'is_active' => true,
                'content' => $this->getRefundContent(),
            ]
        );

        Page::firstOrCreate(
            ['slug' => 'service-policy'],
            [
                'title' => 'Service Policy',
                'is_active' => true,
                'content' => $this->getServiceContent(),
            ]
        );
    }

    private function getTermsContent(): string
    {
        return <<<'HTML'
<p>Welcome to Hospityo. These Terms &amp; Conditions ("Terms") govern your access to and use of the Hospityo cloud-based hospital management platform ("Service"), operated by Hospityo ("we", "us", or "our"). By registering for or using the Service, you agree to be bound by these Terms.</p>

<h2>1. Definitions</h2>
<ul>
    <li><strong>"Tenant"</strong> refers to any hospital, clinic, or medical practice that registers for and uses the Service.</li>
    <li><strong>"User"</strong> refers to any individual authorised by a Tenant to access the Service, including administrators, doctors, nurses, receptionists, and lab technicians.</li>
    <li><strong>"Patient Data"</strong> refers to any personal health information, medical records, or identifiable patient details entered into the Service.</li>
    <li><strong>"Subscription"</strong> refers to the plan selected by the Tenant (Starter, Professional, or Enterprise).</li>
</ul>

<h2>2. Account Registration &amp; Tenant Provisioning</h2>
<p>2.1. To use the Service, you must register a Tenant account by providing accurate and complete information including your hospital name, administrator details, and contact information.</p>
<p>2.2. Upon registration, a dedicated subdomain and isolated database will be provisioned for your Tenant. You are responsible for all activity that occurs under your Tenant account.</p>
<p>2.3. You must keep your login credentials confidential. Notify us immediately if you suspect unauthorised access to your account.</p>

<h2>3. Acceptable Use</h2>
<p>3.1. The Service is intended solely for lawful healthcare management purposes including patient registration, appointment scheduling, billing, pharmacy management, laboratory operations, and clinical documentation.</p>
<p>3.2. You agree not to:</p>
<ul>
    <li>Use the Service for any unlawful purpose or in violation of any applicable healthcare regulations.</li>
    <li>Attempt to access another Tenant's data or systems.</li>
    <li>Upload malicious code, viruses, or any harmful content.</li>
    <li>Reverse-engineer, decompile, or attempt to extract the source code of the Service.</li>
    <li>Share your account credentials with unauthorised individuals.</li>
    <li>Use the Service to store data unrelated to healthcare operations.</li>
</ul>

<h2>4. Patient Data &amp; Privacy</h2>
<p>4.1. You retain full ownership of all Patient Data entered into the Service. We do not claim any ownership rights over your data.</p>
<p>4.2. We act as a data processor on your behalf. You remain the data controller and are responsible for obtaining appropriate patient consent for data collection and processing.</p>
<p>4.3. Patient Data is stored in your Tenant's isolated database. We implement industry-standard security measures including 256-bit SSL encryption, access controls, and regular backups to protect your data.</p>
<p>4.4. We will not access, share, sell, or disclose Patient Data to any third party except as required by law or with your explicit written consent.</p>
<p>4.5. You are responsible for ensuring your use of the Service complies with all applicable data protection and healthcare privacy laws in your jurisdiction.</p>

<h2>5. Subscriptions &amp; Payments</h2>
<p>5.1. The Service is offered under tiered subscription plans. Features and limits vary by plan as described on our pricing page.</p>
<p>5.2. Subscription fees are billed in advance on a monthly or annual basis as selected during registration. All fees are quoted in Pakistani Rupees (PKR) unless otherwise stated.</p>
<p>5.3. Payments are processed through PayFast Pakistan. By subscribing, you agree to PayFast's terms of service.</p>
<p>5.4. We reserve the right to modify pricing with 30 days' advance notice. Existing subscriptions will be honoured until the end of the current billing cycle.</p>
<p>5.5. Failure to pay may result in suspension of your Tenant account. Data will be retained for 90 days after suspension, after which it may be permanently deleted.</p>

<h2>6. Service Availability &amp; Support</h2>
<p>6.1. We strive to maintain 99.9% uptime for the Service. However, we do not guarantee uninterrupted access and shall not be liable for any downtime caused by maintenance, updates, or circumstances beyond our control.</p>
<p>6.2. Scheduled maintenance will be communicated in advance whenever possible.</p>
<p>6.3. Support is provided via email for Starter plans and priority support channels for Professional and Enterprise plans.</p>

<h2>7. Data Backup &amp; Recovery</h2>
<p>7.1. We perform regular automated backups of all Tenant databases. However, you are encouraged to maintain your own backup procedures for critical data.</p>
<p>7.2. In the event of data loss due to system failure, we will make reasonable efforts to restore data from the most recent backup.</p>

<h2>8. Intellectual Property</h2>
<p>8.1. The Service, including its design, code, features, and documentation, is the intellectual property of Hospityo and is protected by applicable copyright and trademark laws.</p>
<p>8.2. Your Subscription grants you a limited, non-exclusive, non-transferable licence to use the Service for the duration of your active subscription.</p>

<h2>9. Limitation of Liability</h2>
<p>9.1. The Service is provided "as is" without warranties of any kind, express or implied, including but not limited to warranties of merchantability, fitness for a particular purpose, or non-infringement.</p>
<p>9.2. To the maximum extent permitted by law, Hospityo shall not be liable for any indirect, incidental, special, consequential, or punitive damages arising from your use of the Service.</p>
<p>9.3. Our total liability for any claim arising from the Service shall not exceed the amount paid by you in the twelve (12) months preceding the claim.</p>
<p>9.4. The Service is a management tool and does not provide medical advice. Clinical decisions remain the sole responsibility of qualified healthcare professionals.</p>

<h2>10. Termination</h2>
<p>10.1. You may cancel your Subscription at any time. Cancellation takes effect at the end of the current billing cycle.</p>
<p>10.2. We may suspend or terminate your account if you violate these Terms, fail to pay subscription fees, or engage in activity that threatens the security or integrity of the Service.</p>
<p>10.3. Upon termination, you may request an export of your data within 30 days. After this period, your data may be permanently deleted.</p>

<h2>11. Modifications to Terms</h2>
<p>11.1. We reserve the right to update these Terms at any time. Material changes will be communicated via email or through the Service dashboard.</p>
<p>11.2. Continued use of the Service after changes are posted constitutes acceptance of the revised Terms.</p>

<h2>12. Governing Law</h2>
<p>12.1. These Terms shall be governed by and construed in accordance with the laws of Pakistan.</p>
<p>12.2. Any disputes arising from these Terms shall be resolved through arbitration in Lahore, Pakistan, in accordance with applicable arbitration rules.</p>

<h2>13. Contact Information</h2>
<p>For questions or concerns regarding these Terms, please contact us at:</p>
<ul>
    <li>Email: <a href="mailto:legal@hospityo.com">legal@hospityo.com</a></li>
    <li>Website: <a href="https://hospityo.com">hospityo.com</a></li>
</ul>

<p><em>These Terms are effective as of April 10, 2026.</em></p>
HTML;
    }

    private function getPrivacyContent(): string
    {
        return <<<'HTML'
<p>This Privacy Policy describes how Hospityo ("we", "us", or "our") collects, uses, stores, and protects information when you use our cloud-based hospital management platform ("Service"). We are committed to safeguarding the privacy of all users and the patients whose data is managed through our platform.</p>

<h2>1. Information We Collect</h2>
<h3>1.1 Tenant Registration Data</h3>
<p>When a hospital, clinic, or medical practice ("Tenant") registers for the Service, we collect:</p>
<ul>
    <li>Hospital/clinic name and address</li>
    <li>Administrator name, email address, and phone number</li>
    <li>Subdomain preference</li>
    <li>Selected subscription plan</li>
</ul>

<h3>1.2 User Account Data</h3>
<p>For each user created within a Tenant account (doctors, nurses, receptionists, lab technicians, etc.), we store:</p>
<ul>
    <li>Full name and contact details</li>
    <li>Role and permissions within the system</li>
    <li>Login credentials (passwords are hashed and never stored in plain text)</li>
</ul>

<h3>1.3 Patient Health Data</h3>
<p>Tenants enter patient information into the Service as part of their healthcare operations. This may include:</p>
<ul>
    <li>Patient demographics (name, age, gender, contact information, CNIC)</li>
    <li>Medical history, diagnoses, and treatment records</li>
    <li>Prescription and medication details</li>
    <li>Laboratory test orders and results</li>
    <li>Vital signs and clinical observations</li>
    <li>Billing and payment records</li>
    <li>Appointment and visit history</li>
</ul>

<h3>1.4 Technical &amp; Usage Data</h3>
<p>We automatically collect certain technical information including:</p>
<ul>
    <li>IP address and browser type</li>
    <li>Pages visited and features used within the Service</li>
    <li>Session duration and login timestamps</li>
    <li>Device and operating system information</li>
</ul>

<h2>2. How We Use Your Information</h2>
<p>We use the collected information for the following purposes:</p>
<ul>
    <li>Provisioning and maintaining your Tenant account and isolated database</li>
    <li>Authenticating users and enforcing role-based access controls</li>
    <li>Processing subscription payments through PayFast Pakistan</li>
    <li>Providing customer support and responding to enquiries</li>
    <li>Monitoring system performance, security, and uptime</li>
    <li>Generating aggregated, anonymised analytics to improve the Service (no individual patient data is used)</li>
    <li>Communicating service updates, maintenance schedules, and billing notifications</li>
</ul>

<h2>3. Data Isolation &amp; Multi-Tenancy</h2>
<p>3.1. Each Tenant is provisioned with a completely isolated database. Patient data from one Tenant is never accessible to another Tenant.</p>
<p>3.2. Tenant databases are identified by unique subdomains and database credentials. Cross-tenant data access is architecturally prevented at the application and database level.</p>
<p>3.3. The central (landlord) database stores only Tenant registration details, subscription information, and super-admin accounts. It does not contain any patient health data.</p>

<h2>4. Data Storage &amp; Security</h2>
<p>We implement comprehensive security measures to protect your data:</p>
<ul>
    <li>256-bit SSL/TLS encryption for all data in transit</li>
    <li>Encrypted database connections between application and database servers</li>
    <li>Role-based access control with granular permissions</li>
    <li>Hashed password storage using industry-standard algorithms</li>
    <li>Regular automated backups of all Tenant databases</li>
    <li>Audit logging of critical actions (login, data modifications, permission changes)</li>
    <li>Session management with cross-tenant session protection</li>
    <li>CSRF protection on all form submissions</li>
</ul>

<h2>5. Data Sharing &amp; Third Parties</h2>
<p>5.1. We do not sell, rent, or trade any personal or patient data to third parties.</p>
<p>5.2. We may share limited data with the following service providers who assist in operating the Service:</p>
<ul>
    <li>PayFast Pakistan — for processing subscription payments (only billing-related data, never patient data)</li>
    <li>Hosting providers — for infrastructure and server management (data remains encrypted)</li>
</ul>
<p>5.3. We may disclose information if required by law, court order, or government regulation, or to protect the rights, safety, or property of Hospityo, our users, or the public.</p>

<h2>6. Data Retention</h2>
<p>6.1. Active Tenant data is retained for the duration of the subscription.</p>
<p>6.2. Upon subscription cancellation, Tenant data is retained for 90 days to allow for data export or account reactivation.</p>
<p>6.3. After the 90-day retention period, all Tenant data including the isolated database may be permanently and irreversibly deleted.</p>
<p>6.4. Audit logs and technical logs are retained for up to 12 months for security and compliance purposes.</p>

<h2>7. Your Rights</h2>
<p>As a Tenant administrator, you have the right to:</p>
<ul>
    <li>Access and review all data stored within your Tenant account</li>
    <li>Correct or update inaccurate information</li>
    <li>Export your data in standard formats</li>
    <li>Request deletion of your Tenant account and all associated data</li>
    <li>Manage user access and permissions within your organisation</li>
</ul>
<p>To exercise any of these rights, contact us at <a href="mailto:privacy@hospityo.com">privacy@hospityo.com</a>.</p>

<h2>8. Patient Rights</h2>
<p>8.1. Patients whose data is stored in the Service should direct any data access, correction, or deletion requests to the Tenant (hospital/clinic) that manages their records.</p>
<p>8.2. Tenants are responsible for complying with patient data rights under applicable healthcare and data protection laws in their jurisdiction.</p>

<h2>9. Cookies &amp; Tracking</h2>
<p>9.1. The Service uses essential cookies for session management and authentication. These are strictly necessary for the Service to function.</p>
<p>9.2. We do not use third-party advertising cookies or tracking pixels within the Service.</p>
<p>9.3. The landing page may use analytics cookies to understand visitor behaviour. These do not collect any patient or healthcare data.</p>

<h2>10. Children's Privacy</h2>
<p>The Service is designed for use by healthcare professionals and administrators. While patient records may include minors, the Service itself is not directed at children under 18. Only authorised healthcare staff should access the platform.</p>

<h2>11. International Data</h2>
<p>The Service is primarily operated from servers located in Pakistan. If you access the Service from outside Pakistan, your data may be transferred to and processed in Pakistan. By using the Service, you consent to this transfer.</p>

<h2>12. Changes to This Policy</h2>
<p>12.1. We may update this Privacy Policy from time to time. Material changes will be communicated via email to Tenant administrators or through a notice within the Service.</p>
<p>12.2. The "Last updated" date at the top of this page indicates when the policy was last revised.</p>

<h2>13. Contact Us</h2>
<p>For privacy-related questions, concerns, or data requests, please contact:</p>
<ul>
    <li>Email: <a href="mailto:privacy@hospityo.com">privacy@hospityo.com</a></li>
    <li>Website: <a href="https://hospityo.com">hospityo.com</a></li>
</ul>

<p><em>This Privacy Policy is effective as of April 10, 2026.</em></p>
HTML;
    }

    private function getRefundContent(): string
    {
        return <<<'HTML'
<p>This Refund Policy outlines the terms under which Hospityo ("we", "us", or "our") processes refunds for subscriptions to our cloud-based hospital management platform ("Service"). By subscribing to the Service, you agree to the refund terms described below.</p>

<h2>1. Free Trial &amp; Starter Plan</h2>
<p>1.1. The Starter plan is offered free of charge. Since no payment is collected, no refund applies.</p>
<p>1.2. If a free trial period is offered for paid plans, no charge is made during the trial. You will only be billed once the trial ends and you have not cancelled.</p>

<h2>2. Monthly Subscriptions</h2>
<p>2.1. Monthly subscription fees are billed in advance at the beginning of each billing cycle.</p>
<p>2.2. <strong>Monthly payments are non-refundable.</strong> Once a monthly billing cycle has begun, the fee for that cycle cannot be refunded, whether in full or in part.</p>
<p>2.3. You may cancel your monthly subscription at any time. Upon cancellation:</p>
<ul>
    <li>Your access to paid features will continue until the end of the current billing cycle.</li>
    <li>No further charges will be made after the current cycle ends.</li>
    <li>No refund will be issued for the remaining days in the current cycle.</li>
</ul>
<p>2.4. Downgrading from a higher-tier monthly plan to a lower-tier plan takes effect at the start of the next billing cycle. The difference is not refunded for the current cycle.</p>

<h2>3. Annual Subscriptions</h2>
<p>3.1. Annual subscription fees are billed in advance as a single payment for the full year.</p>
<p>3.2. <strong>14-Day Cooling-Off Period:</strong> If you cancel an annual subscription within 14 days of the initial purchase or renewal date, you are eligible for a full refund minus any applicable payment processing fees.</p>
<p>3.3. <strong>After the 14-day period:</strong> Annual subscriptions are non-refundable. If you cancel after 14 days:</p>
<ul>
    <li>Your access to paid features will continue until the end of the annual billing period.</li>
    <li>No pro-rated refund will be issued for the unused portion of the year.</li>
    <li>The subscription will not auto-renew at the end of the period.</li>
</ul>
<p>3.4. <strong>Exception — Service Discontinuation:</strong> If we discontinue the Service entirely or remove a core feature that was a primary reason for your subscription, you may request a pro-rated refund for the unused months remaining on your annual plan.</p>

<h2>4. Plan Upgrades</h2>
<p>4.1. When upgrading from a lower-tier plan to a higher-tier plan mid-cycle, you will be charged the pro-rated difference for the remainder of the current billing period.</p>
<p>4.2. Upgrade charges are non-refundable once applied.</p>

<h2>5. Plan Downgrades</h2>
<p>5.1. Downgrading to a lower-tier plan takes effect at the start of the next billing cycle.</p>
<p>5.2. No refund or credit is issued for the price difference during the current cycle.</p>
<p>5.3. Upon downgrade, features exclusive to the higher-tier plan will become unavailable at the start of the next cycle.</p>

<h2>6. Payment Failures &amp; Involuntary Cancellation</h2>
<p>6.1. If a scheduled payment fails, we will attempt to process the payment up to three times over a 10-day period.</p>
<p>6.2. If all payment attempts fail, your account may be suspended. Suspended accounts can be reactivated by updating payment information and clearing the outstanding balance.</p>
<p>6.3. No refund applies to accounts suspended due to payment failure.</p>

<h2>7. Service Outages &amp; Credits</h2>
<p>7.1. We strive for 99.9% uptime. In the event of a prolonged, unscheduled service outage exceeding 24 consecutive hours, affected Tenants may request a service credit.</p>
<p>7.2. Service credits are applied to future billing cycles and are not issued as monetary refunds.</p>
<p>7.3. Outages caused by scheduled maintenance (communicated in advance), force majeure events, or issues with third-party services are not eligible for credits.</p>

<h2>8. Duplicate Payments</h2>
<p>8.1. If you are charged more than once for the same billing period due to a system error, the duplicate amount will be refunded in full within 7-10 business days.</p>
<p>8.2. Please contact us at <a href="mailto:billing@hospityo.com">billing@hospityo.com</a> with your payment receipt to report duplicate charges.</p>

<h2>9. How to Request a Refund</h2>
<p>To request a refund (where eligible), please:</p>
<ol>
    <li>Email <a href="mailto:billing@hospityo.com">billing@hospityo.com</a> with the subject line "Refund Request".</li>
    <li>Include your hospital name, registered email address, subscription plan, and the reason for your request.</li>
    <li>Attach a copy of the payment receipt or transaction reference number.</li>
</ol>
<p>We will review your request and respond within 5 business days. Approved refunds are processed through the original payment method (PayFast Pakistan) and may take 7-14 business days to appear in your account.</p>

<h2>10. Non-Refundable Circumstances</h2>
<p>Refunds will not be issued in the following cases:</p>
<ul>
    <li>Failure to use the Service during an active subscription period.</li>
    <li>Dissatisfaction with features that were clearly described on the pricing page at the time of purchase.</li>
    <li>Account suspension or termination due to violation of our Terms &amp; Conditions.</li>
    <li>Requests made after the applicable refund window has expired.</li>
    <li>Changes in your organisation's requirements or staffing that reduce your need for the Service.</li>
</ul>

<h2>11. Currency &amp; Processing Fees</h2>
<p>11.1. All refunds are processed in Pakistani Rupees (PKR), the same currency as the original transaction.</p>
<p>11.2. Payment gateway processing fees charged by PayFast Pakistan are non-refundable and may be deducted from the refund amount where applicable.</p>

<h2>12. Changes to This Policy</h2>
<p>We reserve the right to modify this Refund Policy at any time. Changes will be communicated via email to active subscribers and posted on this page. The updated policy applies to all subscriptions initiated or renewed after the effective date of the change.</p>

<h2>13. Contact Us</h2>
<p>For billing enquiries or refund requests:</p>
<ul>
    <li>Email: <a href="mailto:billing@hospityo.com">billing@hospityo.com</a></li>
    <li>Website: <a href="https://hospityo.com">hospityo.com</a></li>
</ul>

<p><em>This Refund Policy is effective as of April 10, 2026.</em></p>
HTML;
    }

    private function getServiceContent(): string
    {
        return <<<'HTML'
<p>This Service Policy describes the scope, standards, and commitments governing the delivery of the Hospityo cloud-based hospital management platform ("Service"). It defines what you can expect from us and what we expect from you as a Tenant.</p>

<h2>1. Service Description</h2>
<p>Hospityo is a multi-tenant, cloud-based hospital management system that provides the following core capabilities:</p>
<ul>
    <li>Patient registration and electronic medical records</li>
    <li>OPD, IPD, and Emergency visit workflows</li>
    <li>Appointment scheduling with calendar management</li>
    <li>Doctor consultation, prescription, and clinical documentation</li>
    <li>Laboratory information system (test ordering, sample collection, result entry)</li>
    <li>Pharmacy and inventory management (stock tracking, purchase orders, dispensing)</li>
    <li>Billing, invoicing, and payment tracking</li>
    <li>Ward and bed management for inpatient care</li>
    <li>Role-based access control and user management</li>
    <li>Reports and analytics dashboards</li>
</ul>
<p>Feature availability varies by subscription plan (Starter, Professional, Enterprise). Refer to our pricing page for plan-specific details.</p>

<h2>2. Service Availability &amp; Uptime</h2>
<p>2.1. We target 99.9% monthly uptime for the Service, measured as the percentage of total minutes in a calendar month during which the Service is operational and accessible.</p>
<p>2.2. Uptime excludes the following:</p>
<ul>
    <li>Scheduled maintenance windows (communicated at least 24 hours in advance)</li>
    <li>Emergency security patches (communicated as soon as practicable)</li>
    <li>Outages caused by factors outside our control (internet service providers, DNS, force majeure)</li>
    <li>Issues arising from Tenant-side configurations, browsers, or network conditions</li>
</ul>
<p>2.3. Scheduled maintenance is typically performed during off-peak hours (midnight to 6:00 AM PKT) to minimise disruption.</p>

<h2>3. Tenant Provisioning</h2>
<p>3.1. Upon registration, each Tenant receives:</p>
<ul>
    <li>A dedicated subdomain (e.g., <code>your-hospital.hospityo.com</code>)</li>
    <li>An isolated database with no data shared across Tenants</li>
    <li>Pre-configured roles (Admin, Doctor, Nurse, Receptionist, Lab Technician, Pharmacist)</li>
    <li>Default system settings and seed data for immediate use</li>
</ul>
<p>3.2. Provisioning is automated and typically completes within 60 seconds. In rare cases of high demand, provisioning may take up to 5 minutes.</p>
<p>3.3. If provisioning fails, the system will retry automatically. Persistent failures are escalated to our engineering team and resolved within 24 hours.</p>

<h2>4. Data Management</h2>
<p>4.1. All Tenant data is stored in isolated databases. We do not co-mingle patient or operational data between Tenants.</p>
<p>4.2. Automated daily backups are performed for all Tenant databases. Backups are retained for 30 days.</p>
<p>4.3. In the event of accidental data loss or corruption, we will restore from the most recent available backup upon request. Restoration requests should be submitted to <a href="mailto:support@hospityo.com">support@hospityo.com</a>.</p>
<p>4.4. Tenants may request a full data export at any time. Exports are provided in standard formats (CSV, JSON) within 5 business days of the request.</p>

<h2>5. Security Standards</h2>
<p>We maintain the following security measures across the Service:</p>
<ul>
    <li>256-bit SSL/TLS encryption for all data in transit</li>
    <li>Encrypted database connections</li>
    <li>Bcrypt password hashing — passwords are never stored in plain text</li>
    <li>CSRF protection on all form submissions</li>
    <li>Cross-tenant session protection to prevent session hijacking</li>
    <li>Role-based access control with granular permission management</li>
    <li>Audit logging of critical operations (logins, data changes, permission modifications)</li>
    <li>Regular security updates and dependency patching</li>
</ul>

<h2>6. Support Services</h2>
<h3>6.1. Support Channels</h3>
<table style="width:100%; border-collapse:collapse; margin:12px 0;">
    <thead>
        <tr style="border-bottom:2px solid #d1d5db;">
            <th style="text-align:left; padding:8px;">Plan</th>
            <th style="text-align:left; padding:8px;">Channel</th>
            <th style="text-align:left; padding:8px;">Response Time</th>
        </tr>
    </thead>
    <tbody>
        <tr style="border-bottom:1px solid #e5e7eb;">
            <td style="padding:8px;">Starter</td>
            <td style="padding:8px;">Email</td>
            <td style="padding:8px;">Within 48 hours</td>
        </tr>
        <tr style="border-bottom:1px solid #e5e7eb;">
            <td style="padding:8px;">Professional</td>
            <td style="padding:8px;">Email + Priority Queue</td>
            <td style="padding:8px;">Within 12 hours</td>
        </tr>
        <tr style="border-bottom:1px solid #e5e7eb;">
            <td style="padding:8px;">Enterprise</td>
            <td style="padding:8px;">Email + Phone + Dedicated Manager</td>
            <td style="padding:8px;">Within 4 hours</td>
        </tr>
    </tbody>
</table>

<h3>6.2. Support Scope</h3>
<p>Our support team assists with:</p>
<ul>
    <li>Account setup and configuration questions</li>
    <li>Bug reports and technical issues within the Service</li>
    <li>Guidance on feature usage and best practices</li>
    <li>Billing and subscription enquiries</li>
    <li>Data export and migration assistance</li>
</ul>
<p>Support does not cover:</p>
<ul>
    <li>Custom software development or feature requests (these are evaluated for the product roadmap)</li>
    <li>Third-party integrations not officially supported by Hospityo</li>
    <li>Training for clinical or medical procedures</li>
    <li>Issues caused by Tenant-side hardware, network, or browser configurations</li>
</ul>

<h2>7. Updates &amp; New Features</h2>
<p>7.1. The Service is continuously improved. Updates, bug fixes, and new features are deployed regularly without requiring any action from Tenants.</p>
<p>7.2. Major feature releases and interface changes will be communicated via email and/or in-app notifications at least 7 days in advance.</p>
<p>7.3. We do not guarantee the addition of specific features. Feature requests are welcome and considered for the product roadmap.</p>

<h2>8. Service Limitations</h2>
<p>8.1. The Service is a management and administrative tool. It does not provide medical advice, clinical decision support, or diagnostic recommendations.</p>
<p>8.2. All clinical decisions, diagnoses, and treatment plans remain the sole responsibility of qualified healthcare professionals using the Service.</p>
<p>8.3. The Service is designed for use within Pakistan. While accessible globally, localisation (language, currency, regulatory compliance) is optimised for Pakistani healthcare facilities.</p>
<p>8.4. Usage limits (number of users, patients, or storage) may apply based on your subscription plan.</p>

<h2>9. Account Suspension &amp; Termination</h2>
<p>9.1. We may suspend a Tenant account if:</p>
<ul>
    <li>Subscription payment is overdue by more than 10 days</li>
    <li>The Tenant violates our Terms &amp; Conditions or Acceptable Use Policy</li>
    <li>We detect activity that threatens the security or stability of the Service</li>
</ul>
<p>9.2. Suspended accounts retain all data but lose access to the Service. Access is restored upon resolution of the suspension cause.</p>
<p>9.3. Terminated accounts have 30 days to request a data export before permanent deletion.</p>

<h2>10. Disaster Recovery</h2>
<p>10.1. Our infrastructure includes redundant systems and automated failover to minimise the impact of hardware or software failures.</p>
<p>10.2. In the event of a major incident, our recovery time objective (RTO) is 4 hours and our recovery point objective (RPO) is 24 hours (i.e., data loss is limited to the most recent 24-hour backup).</p>
<p>10.3. Incident status updates are communicated via email to affected Tenant administrators.</p>

<h2>11. Modifications to This Policy</h2>
<p>We may update this Service Policy to reflect changes in our infrastructure, support offerings, or operational practices. Material changes will be communicated via email to active Tenant administrators. Continued use of the Service after changes are posted constitutes acceptance.</p>

<h2>12. Contact Us</h2>
<p>For questions about this Service Policy or to report a service issue:</p>
<ul>
    <li>Email: <a href="mailto:support@hospityo.com">support@hospityo.com</a></li>
    <li>Website: <a href="https://hospityo.com">hospityo.com</a></li>
</ul>

<p><em>This Service Policy is effective as of April 10, 2026.</em></p>
HTML;
    }
}
