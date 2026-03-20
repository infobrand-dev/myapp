# SAAS_PRODUCT_MODEL.md - SaaS Product Model And Rollout

## Mission
- The target product is a complete and configurable business web app.
- One codebase serves multiple tenants.
- What each tenant gets is determined by:
  - subscription plan
  - enabled modules
  - tenant industry profile
  - quotas and feature entitlements

## Scope hierarchy
- `tenant`
  - the SaaS customer account
  - billing, subscription, plan, module entitlement, and overall ownership live here
- `company`
  - an internal business entity owned by a tenant
  - used when one tenant operates more than one legal entity or business unit
- `branch`
  - an outlet, store, warehouse group, office, or operational location under a company
  - restaurant outlets are a branch-level concept
  - optional for tenants that do not operate outlet-level segmentation

## What is not a company
- `Contacts.company` is an external business/contact relationship.
- It may represent a customer company, supplier company, or employer of a person.
- It must not be used as the internal multi-company boundary of the tenant.

## Recommended operating model
- `tenant`
  - owns users
  - owns subscription
  - owns module entitlement
  - owns industry profile
- `company`
  - belongs to tenant
  - owns branch tree
  - becomes the accounting and reporting boundary
- `branch`
  - belongs to company
  - becomes the operational outlet/location boundary for POS, stock, cashier shift, and local sales activity

## Example: restaurant
- `tenant`
  - one restaurant group or customer account in the SaaS platform
- `company`
  - `PT Rasa Nusantara`
- `branch`
  - `Kemang`
  - `BSD`
  - `Surabaya`

In that model:
- financial statements can be per company
- operational sales can be per branch
- branch summaries can roll up to company
- company summaries can roll up to tenant

## Recommended data scoping
- every tenant-owned record should have `tenant_id`
- records that belong to an internal business entity should also have `company_id`
- records that belong to an outlet or location should also have `branch_id`
- `branch_id` should usually be nullable unless the workflow is explicitly outlet-bound

## Typical module scope
- `finance`
  - `tenant_id`
  - `company_id`
  - sometimes `branch_id`
- `sales`
  - `tenant_id`
  - `company_id`
  - often `branch_id`
- `purchases`
  - `tenant_id`
  - `company_id`
  - sometimes `branch_id`
- `payments`
  - `tenant_id`
  - `company_id`
  - often `branch_id`
- `inventory`
  - `tenant_id`
  - `company_id`
  - usually `branch_id` or location ownership tied to a branch
- `pos`
  - `tenant_id`
  - `company_id`
  - `branch_id`
- `reports`
  - must support branch, company, and tenant aggregation where relevant

## Industry profile
- A tenant should have an industry profile such as:
  - `restaurant`
  - `retail`
  - `distribution`
  - `services`
  - `manufacturing`
- Industry profile should not hardcode one workflow.
- It should influence:
  - default module bundle
  - recommended settings
  - onboarding defaults
  - enabled navigation or starter data
- Industry profile is a product configuration layer, not a tenant isolation layer.

## Module entitlement
- A tenant does not automatically get every module forever.
- A plan should decide which module families or premium features are available.
- Module entitlement should support:
  - module access by plan
  - module access by industry preset
  - manual override per tenant if needed

## Plan model
- A plan should contain:
  - identity
  - feature flags
  - quotas
  - optional industry compatibility rules
  - optional default module bundle

## Example plan entitlements
- `Starter`
  - 1 company
  - 3 users
  - 100 products
  - limited modules
- `Growth`
  - multi-company enabled
  - higher user and product quotas
  - WhatsApp and Email Marketing enabled
- `Scale`
  - larger quotas
  - premium reporting
  - more operational features

## Quota examples
- `max_companies`
- `max_branches`
- `max_users`
- `max_products`
- `max_contacts`
- `max_whatsapp_instances`
- `max_email_campaigns`

## Feature flag examples
- `multi_company`
- `multi_branch`
- `email_marketing`
- `whatsapp_api`
- `advanced_reports`
- `inventory`
- `finance`
- `pos`

## Enforcement rules
- Hiding menu items is not enough.
- Enforcement must exist at:
  - route and controller level
  - job and queue level
  - API and webhook level
  - creation/update actions
  - import/sync paths

## Current foundation already in code
- tenant runtime context exists
- company runtime context exists
- branch runtime context exists
- branch runtime context is optional and must not auto-select the first branch
- tenant auth and tenant-scoped permission teams exist
- plan tables exist:
  - `subscription_plans`
  - `tenant_subscriptions`
- internal company table exists:
  - `companies`
- internal branch table exists:
  - `branches`
- central plan service exists:
  - `App\Support\TenantPlanManager`
- initial quota enforcement already exists for:
  - user creation
  - product creation
- company-aware rollout has started in:
  - `finance`
  - `pos cash sessions`
- branch-aware rollout has started in:
  - `pos cash sessions`
  - `finance transactions`
  - `sales`
  - `payments`
- branch-aware rollout is still partial and many modules should remain company-level until a branch boundary is operationally justified

## What is not finished yet
- `companies` is not yet the active operational scope across most modules
- most business tables are not yet fully `company_id + branch_id` aware
- industry profile and module bundle logic are not yet wired
- billing/provider sync is not yet wired
- plan UI and tenant admin UI are not yet built

## Recommended rollout order
1. Finalize product boundaries
   - tenant
   - company
   - branch
   - industry profile
   - module entitlement
2. Add tenant provisioning service
   - create tenant
   - create owner user
   - attach default plan
   - create first company
3. Add company context
   - current company in session
   - company-aware authorization
4. Make core modules company-aware
   - finance
   - sales
   - purchases
   - payments
   - inventory
   - pos
5. Expand branch model only where needed
   - especially for outlet-heavy industries like restaurant and retail
   - keep branch nullable for company-level flows
6. Add industry preset and module bundle logic
7. Add plan management UI and billing integration

## Immediate next step
- Start with `company` as the next active scope under tenant.
- Do not start with branch first.
- The first module batch to convert should be:
  - `finance`
  - `sales`
  - `purchases`
  - `payments`
  - `inventory`
  - `pos`

Reason:
- those modules define the real operational and reporting boundaries
- once they are company-aware, plan limits like `max_companies` become meaningful
- branch can then be layered under company without breaking the main design
