# HRMS2 Deployment Checklist

## Before Deploy

- Confirm PHP extensions are available: `pdo_mysql`, `mbstring`, `json`, `openssl`, `fileinfo`
- Review `config/app.php` and `config/database.php`
- Ensure write permission exists for `storage/uploads` and `storage/logs`
- Import `risk_management_schema.sql`
- Import `risk_management_seed.sql` only for demo or first-time setup
- Replace demo users and passwords before go-live
- Set the central public-report password in the admin settings page
- Create real fiscal years, teams, departments, users, categories, and visibility rules

## Functional Validation

- Test public report access and submission
- Test admin receive and assign flow
- Test multi-team assignment for one report
- Test team lead review flow
- Test department head return flow
- Test admin close flow
- Test director read-only dashboard, report list, and report detail
- Test workflow history, workflow audit detail, and CSV export

## Security Checks

- Change all default passwords
- Use HTTPS in production
- Restrict database access to trusted hosts
- Verify file upload validation
- Verify CSRF validation on protected POST actions
- Review audit logs after test activity

## Go-Live

- Back up the database before first import or update
- Deploy code
- Apply schema updates
- Set production config values
- Run smoke tests with admin and public reporter flow
- Announce go-live only after role-based tests pass

## Post Go-Live

- Monitor audit logs and application behavior during the first week
- Back up the database regularly
- Review fiscal year and running number settings before each new period
