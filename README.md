# DUT Recruitment

Minimal orchestration plugin for the new DUT Jobs recruitment flow.

## Current scope

- Dependency checks for WP Job Manager plugins
- Health endpoint at `/wp-json/dut/v1/health`
- Create application endpoint at `/wp-json/dut/v1/applications`
- Current user applications endpoint at `/wp-json/dut/v1/my-applications`
- Recruiter job applications endpoint at `/wp-json/dut/v1/jobs/{jobId}/applications`
- Recruiter status update endpoint at `/wp-json/dut/v1/applications/{id}/status`
- Theme integration guide at `THEME_INTEGRATION.md`

## Deploy

1. Zip the `dut-recruitment` plugin folder.
2. Upload it from `WP Admin > Plugins > Add New > Upload Plugin`.
3. Activate the plugin.
4. Test `/wp-json/dut/v1/health`.

## Required plugins

- WP Job Manager
- WP Job Manager - Applications
- WP Job Manager - Resume Manager
