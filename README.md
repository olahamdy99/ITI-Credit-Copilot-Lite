# ITI Credit Copilot Lite

ITI Staff Technical Assessment — Finance  
**Credit Copilot Lite: A Grounded RAG Assistant for Personal Loan Underwriting**

Credit Copilot Lite is a Laravel-based application that ingests synthetic lending policy documents, answers policy questions with citations, evaluates personal loan applications using deterministic calculations and rules, generates grounded credit memos, and requires human Credit Officer approval before an offer can be issued.

> This project uses synthetic data only and does not represent real banking policy or financial advice.

---

## Core Principles

The implementation follows four main rules:

1. **Grounded, never guessing**
   - Policy answers are based only on retrieved policy evidence.
   - Answers include citations.
   - If the available documents do not contain enough evidence, the system refuses to answer.

2. **Numbers are deterministic**
   - The LLM does not calculate installments, DBR, limits, or eligibility values.
   - Financial calculations are implemented in normal PHP code and covered by unit tests.

3. **Fair by design**
   - Protected attributes such as gender, religion, marital status, and nationality are removed before LLM or underwriting rule processing.

4. **Human approval is mandatory**
   - Assessments remain pending until a Credit Officer approves or rejects them.
   - An offer cannot be marked as issued before approval.

---

## Tech Stack

- Laravel 10
- PHP 8.3
- PostgreSQL 16
- pgvector
- Docker Compose
- Laravel Sanctum
- Gemini API
- L5-Swagger / OpenAPI
- Blade
- PHPUnit
- Laravel Pint
- GitHub Actions

---

## Architecture

The application separates business logic from infrastructure concerns.

Main areas include:

```text
app/
├── Contracts/
├── Domain/
├── DTOs/
├── Exceptions/
├── Infrastructure/
├── Models/
├── Services/
│   ├── ApplicationProcessing/
│   ├── Approval/
│   ├── CreditMemo/
│   ├── Embeddings/
│   ├── Policy/
│   ├── PolicyIngestion/
│   ├── PolicyQA/
│   ├── PolicyRetrieval/
│   └── Underwriting/
└── Http/
```

Examples of this separation:

- deterministic calculations and rules do not depend on the LLM
- the LLM is accessed through an interface
- pgvector retrieval is implemented in the infrastructure layer
- controllers delegate business logic to services

More detailed architecture decisions will be documented in `DESIGN.md`.

---

## Policy Ingestion

The system supports the provided synthetic policy corpus, including:

- PDF
- Markdown
- CSV

The ingestion pipeline is separated into stages such as:

```text
extract
  ↓
chunk
  ↓
embed
  ↓
index
```

Policy chunks store metadata including:

- source document
- section / clause
- page when available
- policy edition
- effective dates

Re-ingestion is designed to avoid creating duplicate policy chunks.

---

## Policy Retrieval and Q&A

Policy questions use semantic retrieval through PostgreSQL + pgvector.

The Q&A flow is:

```text
Question
   ↓
Select applicable policy edition
   ↓
Retrieve policy chunks
   ↓
Build policy evidence
   ↓
LLM generates grounded answer
   ↓
Validate citations
   ↓
Return answer + citations
```

The LLM is explicitly instructed to answer only from the supplied policy evidence.

If the evidence is insufficient, the user receives:

```text
The documents do not contain enough information.
```

---

## Policy Edition Selection

The correct policy edition is selected using the application date.

Policy editions are selected by application code, not by the LLM.

This is important because some lending limits differ between policy editions.

---

## Application Processing

The provided synthetic loan application packs can be processed by the system.

The application pipeline includes:

```text
Load application
   ↓
Validate required data
   ↓
Remove protected attributes
   ↓
Select policy edition
   ↓
Extract financial information
   ↓
Verify extracted evidence
   ↓
Retrieve policy rules
   ↓
Run deterministic calculations
   ↓
Run underwriting rules
   ↓
Generate recommendation
```

Application documents are treated as **untrusted data** and are not added to the trusted policy knowledge base.

---

## Protected Attributes

The underwriting workflow removes protected attributes before they can influence LLM or rule processing.

Protected attributes include:

- gender
- religion
- marital status
- nationality

A dedicated fairness test still needs to be finalized before submission to demonstrate that changing only protected attributes does not change the underwriting result.

---

## Deterministic Loan Calculations

Financial values are calculated by application code.

The LLM never performs these calculations.

Implemented calculations include:

- monthly installment
- Debt Burden Ratio (DBR)
- maximum eligible amount
- age at maturity
- applicable interest rate

The calculation engine is unit tested against worked examples and edge cases.

---

## Underwriting Rules

The rules engine evaluates conditions including:

- Debt Burden Ratio
- minimum monthly income
- employment duration
- bureau score
- age at maturity
- requested tenor
- requested loan amount

Possible rule outcomes include:

```text
PASS
FAIL
REFER
```

A low bureau score results in a human referral rather than an automatic decline.

---

## Credit Memo

The system generates a grounded credit memo using:

- application data
- deterministic calculations
- underwriting rule results
- policy citations

Important safety rule:

> Every numeric value in the final credit memo is inserted by application code.

The LLM is not allowed to invent or calculate numeric values.

Generated numbers not supplied by application code cause credit memo generation to fail.

---

## Human Approval Workflow

The supported lifecycle is:

```text
pending_approval
        |
        +----> approved ----> issued
        |
        +----> rejected
```

Approval records include:

- assessment
- approving user
- decision
- comment
- decision timestamp
- authority limit

A Credit Officer cannot approve an amount above their configured authority limit.

This validation is enforced server-side.

Example:

```text
Recommended amount: 300,000 EGP
Credit Officer authority: 250,000 EGP

Result:
Approval rejected by the server.
```

---

## Authentication and RBAC

Authentication is implemented using Laravel Sanctum.

The system currently supports two roles.

### Loan Officer

Can:

- login
- view applications
- run an underwriting assessment
- generate a credit memo
- ask policy questions

### Credit Officer

Can perform Loan Officer actions and also:

- approve assessments
- reject assessments
- issue approved assessments

Permissions are enforced on the server through middleware and service-level validation.

They are not implemented only by hiding UI buttons.

---

## REST API

The application exposes REST API endpoints.

Main endpoints currently include:

```text
POST /api/login
GET  /api/user
POST /api/logout

POST /api/applications/{application}/assess

POST /api/assessments/{assessment}/memo

POST /api/assessments/{assessment}/approve
POST /api/assessments/{assessment}/reject
POST /api/assessments/{assessment}/issue
```

---

## OpenAPI / Swagger

OpenAPI documentation is generated using L5-Swagger.

After starting the application and generating the documentation:

```bash
sudo docker exec -it credit_copilot_app \
php artisan l5-swagger:generate
```

Swagger UI is available at:

```text
http://localhost:8001/api/documentation
```

The documentation currently includes:

- authentication
- assessments
- credit memo generation
- approval
- rejection
- issuing

---

## Minimal Web UI

A minimal Blade interface is included because the assessment focuses on functionality rather than frontend styling.

Implemented screens currently include:

- login
- loan application list
- underwriting assessment
- rule results
- policy citations
- credit memo generation
- Credit Officer approval / rejection
- issue approved assessment

Policy Q&A UI is currently being completed.

The ingestion UI is still pending.

---

## Local Development Setup

### Prerequisites

You only need:

- Docker
- Docker Compose
- Git
- a Gemini API key

---

### 1. Clone the repository

```bash
git clone <REPOSITORY_URL>
cd ITI-Credit-Copilot-Lite
```

---

### 2. Create the environment file

```bash
cp .env.example .env
```

Configure your Gemini API key:

```env
GEMINI_API_KEY=
GEMINI_EMBEDDING_MODEL=gemini-embedding-2
GEMINI_CHAT_MODEL=gemini-3.5-flash-lite
```

The project uses PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=credit_copilot
DB_USERNAME=credit_user
DB_PASSWORD=
```

---

### 3. Start Docker

```bash
sudo docker-compose up -d
```

---

### 4. Install PHP dependencies

```bash
sudo docker exec -it credit_copilot_app \
composer install
```

---

### 5. Generate Laravel application key

```bash
sudo docker exec -it credit_copilot_app \
php artisan key:generate
```

---

### 6. Run migrations

```bash
sudo docker exec -it credit_copilot_app \
php artisan migrate
```

---

### 7. Generate Swagger documentation

```bash
sudo docker exec -it credit_copilot_app \
php artisan l5-swagger:generate
```

---

### Application URL

```text
http://localhost:8001
```

Swagger:

```text
http://localhost:8001/api/documentation
```

---

## Demo Accounts

The current development environment uses two main roles:

```text
Loan Officer
Credit Officer
```

Demo-user seeding still needs to be finalized before submission so that a fresh clone can reproduce the same accounts automatically.

No real user credentials are committed to this repository.

---

## Testing

Run the full test suite:

```bash
sudo docker exec -it credit_copilot_app \
php artisan test
```

Run code-style validation:

```bash
sudo docker exec -it credit_copilot_app \
./vendor/bin/pint --test
```

Previously verified backend test suite:

```text
43 tests passed
89 assertions
```

The test count may increase as the remaining UI, fairness, integration, and evaluation tests are completed.

Existing automated coverage includes:

- loan calculations
- DBR edge cases
- maximum eligible amount
- age at maturity
- underwriting rules
- assessment APIs
- API authentication
- RBAC
- Credit Officer authority limits
- approval
- rejection
- issue lifecycle
- grounded credit memo values
- rejection of LLM-generated numbers

---

## CI

GitHub Actions runs:

- Laravel Pint
- automated tests

on Pull Requests.

The project should only be submitted with CI green on `main`.

---

## Security

Security controls currently include:

- environment-based secrets
- `.env` excluded from Git
- server-side role validation
- server-side approval limits
- policy/application trust separation
- protected attribute removal
- grounded LLM prompts
- citation validation
- deterministic numeric calculations
- synthetic data only

Before final submission, the following security checks will also be completed:

- full Git history secret scan
- final phone-number / national-ID masking verification
- upload type and size validation if document upload is exposed through the UI

No API keys or production credentials should exist in the repository or Git history.

---

## What Is Sent to the LLM

The LLM receives only the information required for the relevant operation.

### Policy Q&A

The LLM receives:

- the user question
- retrieved trusted policy evidence
- citation identifiers

It is instructed not to follow instructions contained inside retrieved document text.

### Application Processing

Application documents are treated as untrusted input.

Protected attributes are removed before underwriting processing.

### Credit Memo

The LLM receives rule outcomes and placeholders.

Numeric values are inserted into the final memo by application code rather than generated by the LLM.

---

## Evaluation

The assessment requires a dedicated evaluation set containing at least 15 cases.

The final evaluation harness will cover:

- at least 3 out-of-corpus questions
- at least 2 policy-edition comparison cases
- at least 3 deterministic calculation cases
- at least 2 prompt-injection cases
- at least 1 prompt injection hidden inside an applicant document

The harness must report:

```text
Retrieval hit-rate
Refusal correctness
Calculation exactness
```

Actual results, including failures, will be recorded before final submission.

**Status:** evaluation harness still pending.

---

## Observability and Audit Logging

Assessment records already include a unique run identifier.

The final logging requirement must include:

- request ID
- pipeline steps executed
- retrieved policy chunk IDs
- selected policy edition
- protected fields removed
- LLM token usage

**Status:** full FR-9 logging is not yet complete.

---

## Current Requirement Status

| Requirement | Status |
|---|---|
| Policy ingestion | Implemented |
| Semantic retrieval | Implemented |
| Grounded citations | Implemented |
| Insufficient-evidence refusal | Implemented |
| Policy edition selection | Implemented |
| Application processing | Implemented |
| Deterministic calculations | Implemented |
| Underwriting rules | Implemented |
| Credit memo | Implemented |
| Human approval | Implemented |
| Authority limit enforcement | Implemented |
| Authentication | Implemented |
| Role-based access | Implemented |
| REST API | Implemented |
| OpenAPI / Swagger | Implemented |
| Minimal assessment UI | Implemented |
| Policy Q&A UI | In progress |
| Ingestion UI | Pending |
| 15-case evaluation harness | Pending |
| Fairness automated test | Pending final verification |
| Ingestion + retrieval integration test | Pending |
| Full audit / token logging | Pending |
| Secret-history scan | Pending |
| Final demo video | Pending |

---

## Git Workflow

Development is performed using feature branches and Pull Requests.

Pull Requests describe:

```text
What
Why
Testing
```

GitHub Actions verify formatting and tests before merge.

---

## Environment and Secrets

Secrets must never be committed.

The following files are intentionally excluded from Git:

```text
.env
.env.testing
```

The repository contains only:

```text
.env.example
```

with empty secret values.

---

## AI Usage

AI tools are allowed for this assessment.

AI assistance has been used during development for:

- understanding requirements
- discussing architecture
- reviewing implementation ideas
- troubleshooting Laravel / Docker issues
- generating test ideas
- improving documentation

All generated suggestions are reviewed and verified against:

- the project requirements
- application behavior
- automated tests
- manual testing

A more detailed AI usage log will be included before final submission.

---

## Known Remaining Work

Before final submission:

1. Complete Policy Q&A UI.
2. Add minimal policy ingestion UI.
3. Implement the 15-case evaluation harness.
4. Add / verify fairness testing.
5. Add ingestion + retrieval integration testing.
6. Complete request and assessment audit logging.
7. Verify phone and national-ID masking before LLM calls.
8. Run a full Git history secret scan.
9. Finalize reproducible demo users / seed data.
10. Complete `DESIGN.md`.
11. Update this README with final test and evaluation results.
12. Clone the repository from scratch and verify setup instructions.
13. Record and verify the final demo video.

---

## Submission Checklist

Before submitting:

```text
[ ] Repository is public
[ ] CI is green on main
[ ] Fresh clone works using README instructions
[ ] No secrets exist in Git history
[ ] All synthetic documents are included
[ ] Evaluation harness is complete
[ ] Evaluation results are documented
[ ] DESIGN.md is complete
[ ] README matches actual implementation
[ ] Demo link works in a private browser
```

---

## License

A project license should be included before final submission.

---

## Disclaimer

This project is a technical assessment built entirely with synthetic data.

It is not intended for real lending decisions and does not contain real banking policy or financial advice.
