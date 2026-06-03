Tenant schema bootstrap migrations belong here.

This folder is intentionally separate from `database/migrations` so central
registry tables and platform billing tables are not executed inside tenant
schemas by accident.
