# Design Document — Doctor Share UI

## Overview

The Doctor Share UI module adds the complete administrative frontend for the doctor revenue-sharing system in the HMS. The backend (DoctorShareService, DoctorShareRule, DoctorShareItem, DoctorShareAllocation) is already fully implemented and hooked into BillController. This design covers the new database table, model, controller, routes, Blade views, sidebar integration, and permission seeder needed to expose that backend through the UI.

The module has four functional areas:

- **Share Rule Management** — CRUD for the three-level rule hierarchy
- **Share Item Ledger** — read-only view of earned share liabilities with allocation drill-down
- **Settlement Batches** — run and review settlement batches stored in doctor_share_settlements`n- **Share Reports** — filterable summary and detail reports with a dedicated print route

All views extend dmin.layout, use the existing Tailwind design tokens (medical-blue, medical-light, medical-green), and follow the card/table/badge/flash patterns established in esources/views/admin/bills/ and esources/views/admin/doctors/.
