# Documentation

This directory collects supporting documentation for the **Car Rental Management System**.

For installation and an overview, start with the [main README](../README.md).

## Diagrams

- [Entity Relationship Diagram (ERD)](assets/erd.png)

## Audits and requirements

- [Implementation audit (December 2025)](audits/AUDIT_REPORT.md)
- [Requirements audit](audits/REQUIREMENTS_AUDIT.md)
- [Requirements compliance notes](audits/PROJECT_REQUIREMENTS_COMPLIANCE.md)

## Maintenance notes

- [CSS fixes summary](maintenance/CSS_FIXES_SUMMARY.md)
- [Image inventory and troubleshooting notes](maintenance/MISSING_IMAGES.md)

> **Documentation status:** Some audit and troubleshooting files are historical snapshots and may describe issues that have since changed. Check the current source code and assets before treating any old report as a current test result.

## Hosting

- [Free PHP & MySQL hosting and deployment guide](HOSTING.md)

## Database

The SQL schema and sample records are available at [database/schema_and_seed.sql](../database/schema_and_seed.sql).

## Structure policy

Runtime directories (`admin/`, `php/`, `includes/`, `css/`, `js/`, `html/`, `img/`, and `uploads/`) stay at their existing locations to preserve application-relative URLs and PHP includes. The `docs/` and `database/` folders contain non-runtime project resources.
