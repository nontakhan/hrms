# HRMS2 Setup Guide

## 1. Prepare Environment

- Place the project under the web root, for example `C:\xampp\htdocs\hrms2`
- Configure the correct `base_url` in `config/app.php`
- Configure database connection in `config/database.php`

## 2. Create Database

1. Create a MySQL database
2. Import `risk_management_schema.sql`
3. Optionally import `risk_management_seed.sql` for demo or initial setup

## 3. Configure Initial Data

Set up these items in order after first login:

1. Public report password
2. Fiscal years and active fiscal year
3. Teams
4. Departments
5. Users
6. Team categories
7. Visibility rules for department heads if required

## 4. Verify Workflow

- Submit a test public report
- Receive and assign the report from admin
- Process it from team lead
- Forward to department head if needed
- Return to team and then admin
- Verify severity history, route log, workflow history, and audit detail

## 5. Director Read-Only Access

- Log in with a director account
- Open `director/dashboard.php`
- Confirm dashboard, report list, and report detail are visible
- Confirm no edit or assignment actions appear

## 6. Production Practice

- Remove demo data before real use
- Review permissions for each real account
- Back up the database regularly
- Review workflow history and audit logs during early rollout
