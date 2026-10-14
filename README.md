# ITI Credit Copilot Lite

**ITI Staff Technical Assessment — Finance**  
**Credit Copilot Lite: A Grounded RAG Assistant for Personal Loan Underwriting**

Credit Copilot Lite is a Laravel-based application that ingests synthetic lending policy documents, answers policy questions with citations, evaluates personal-loan applications using deterministic calculations and rules, generates grounded credit memos, and requires human Credit Officer approval before an offer can be issued.

> This project uses synthetic data only and does not represent real banking policy or financial advice.

---

## Core Principles

The implementation follows four main rules:

1. **Grounded, never guessing**
   - Policy answers are based only on retrieved policy evidence.
   - Answers include citations.
   - If the documents do not contain enough evidence, the system returns:

   ```text
   The documents do not contain enough information.
   ```

2. **Numbers are deterministic**
   - The LLM does not calculate installments, DBR, limits, or eligibility values.
   - Financial calculations are implemented in PHP and covered by tests.

3. **Fair by design**
   - Protected attributes such as gender, religion, marital status, and nationality are removed before LLM extraction and underwriting-rule processing.

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
- PHPUnit
- Laravel Pint
- GitHub Actions

---

## Architecture

The application separates domain/business logic from infrastructure concerns.

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

Key design rules:

- deterministic calculations and rules do not depend on the LLM
- the LLM is accessed through an interface
- pgvector retrieval is isolated from domain calculations
- policy-edition selection is performed by application code
- applicant documents are treated as untrusted input

See [`docs/DESIGN.md`](docs/DESIGN.md) for the detailed system design.

---

## Policy Ingestion

The system supports the supplied synthetic policy corpus in:

- PDF
- Markdown
- CSV

The ingestion pipeline is:

```text
extract
  ↓
chunk
  ↓
embed
  ↓
index
```

Each policy chunk stores metadata such as:

- source document
- section / clause
- page when available
- policy edition
- effective dates

Re-ingestion is idempotent: existing chunks for a policy document are replaced before new chunks are created, preventing duplicates.

---

## Policy Retrieval and Q&A

Policy questions use semantic retrieval through PostgreSQL + pgvector.

```text
Question
   ↓
Select applicable policy edition
   ↓
Retrieve policy chunks
   ↓
Apply similarity threshold
   ↓
Build bounded policy evidence
   ↓
LLM generates grounded answer
   ↓
Validate citations
   ↓
Return answer + citations
```

The current minimum similarity threshold is:

```text
0.74
```

The threshold was changed from `0.73` after evaluation revealed a false-positive retrieval for an out-of-corpus cryptocurrency question.

Detailed results are documented in [`docs/EVALUATION.md`](docs/EVALUATION.md).

---

## Policy Edition Selection

The correct policy edition is selected in code from the application date.

The LLM does **not** choose the policy edition.

This is important because rules differ by edition. For example:

```text
2024 maximum DBR: 50%
2025 maximum DBR: 45%
```

---

## Application Processing

Application documents are treated as **untrusted data** and are never added to the trusted policy knowledge base.

The processing flow is:

```text
Application PDF
   ↓
Extract raw text
   ↓
Integrity validation
   ↓
Remove protected attributes
   ↓
Mask sensitive values
   ↓
LLM structured extraction
   ↓
Verify evidence quotes
   ↓
Select policy edition
   ↓
Persist structured application data
```

If integrity validation fails or extracted evidence cannot be verified, the system fails safely rather than guessing.

---

## Protected and Sensitive Data

Protected attributes removed before underwriting processing include:

- gender
- religion
- marital status
- nationality

Sensitive values masked before LLM processing include:

- national ID
- phone number
- applicant name

Example replacements:

```text
[NATIONAL_ID_REDACTED]
[PHONE_REDACTED]
[APPLICANT_NAME_REDACTED]
```

---

## Deterministic Loan Calculations

The LLM never performs lending calculations.

Implemented calculations include:

- monthly installment
- Debt Burden Ratio (DBR)
- maximum eligible amount
- age at maturity
- applicable interest rate

The calculation engine is implemented in normal PHP code and tested independently from the LLM.

---

## Underwriting Rules

The rules engine evaluates conditions including:

- DBR
- minimum monthly income
- employment duration
- bureau score
- age at maturity
- requested tenor
- requested loan amount

Possible outcomes include:

```text
PASS
FAIL
REFER
```

Decision precedence is:

```text
FAIL > REFER > ELIGIBLE
```

A low bureau score is treated as a referral for human review rather than an automatic decline.

---

## Credit Memo

The credit memo is generated from:

- verified application data
- deterministic calculations
- underwriting rule results
- policy citations

Important safety rule:

> Numeric values in the final credit memo are supplied by application code rather than calculated by the LLM.

If the LLM introduces an unexpected number, memo generation is rejected.

---

## Human Approval Workflow

The lifecycle is:

```text
pending_approval
        |
        +----> approved ----> issued
        |
        +----> rejected
```

Approval records store:

- assessment
- approving user
- decision
- comment
- decision timestamp
- authority limit

Authority limits are enforced server-side.

Example:

```text
Recommended amount: 300,000 EGP
Credit Officer authority: 250,000 EGP

Result:
Approval rejected by the server.
```

---

## Authentication and RBAC

Authentication uses Laravel Sanctum.

### Loan Officer

Can:

- authenticate
- ask policy questions
- process applications
- run assessments
- generate credit memos

### Credit Officer

Can perform Loan Officer actions and also:

- approve assessments
- reject assessments
- issue approved assessments

Permissions are enforced server-side through middleware and service-level checks.

---

## REST API

Main API endpoints include:

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

Generate the OpenAPI documentation:

```bash
sudo docker exec -it credit_copilot_app \
php artisan l5-swagger:generate
```

Swagger UI:

```text
http://localhost:8001/api/documentation
```

---

# Quick Start

## Prerequisites

You need:

- Docker
- Docker Compose
- Git
- a free Gemini API key

No local PHP, PostgreSQL, or Composer installation is required when using Docker.

---

## 1. Clone the Repository

```bash
git clone <REPOSITORY_URL>
cd ITI-Credit-Copilot-Lite
```

---

## 2. Create the Environment File

```bash
cp .env.example .env
```

Update the values required for your environment.

The main project-specific variables are:

```env
APP_NAME="ITI Credit Copilot Lite"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8001

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=credit_copilot
DB_USERNAME=credit_user
DB_PASSWORD=

GEMINI_API_KEY=
GEMINI_EMBEDDING_MODEL=gemini-embedding-2
GEMINI_CHAT_MODEL=gemini-3.5-flash-lite

DEMO_USER_PASSWORD=demo-password
```

The complete `.env.example` in the repository is the source of truth for all supported environment variables.

Do not commit `.env` or `.env.testing`.

---

## 3. Get a Free Gemini API Key

1. Open Google AI Studio.
2. Sign in with a Google account.
3. Open the API key section.
4. Create a Gemini API key.
5. Copy the key into:

```env
GEMINI_API_KEY=your_key_here
```

Only use a development/test key. Never commit it to Git.

---

## 4. Start Docker

```bash
sudo docker-compose up -d
```

---

## 5. Install Dependencies

```bash
sudo docker exec -it credit_copilot_app composer install
```

---

## 6. Generate the Laravel Application Key

```bash
sudo docker exec -it credit_copilot_app php artisan key:generate
```

---

## 7. Prepare the Complete Demo Environment

Run one command:

```bash
sudo docker exec -it credit_copilot_app php artisan demo:setup
```

`demo:setup` performs:

```text
migrations
→ demo-user seeding
→ policy corpus import
→ policy ingestion
→ policy embedding
```

A verified fresh setup creates:

```text
Policy editions: 2
Policy documents: 7
Policy chunks: 79
Chunks without embeddings: 0
Demo users: 3
```

---

## 8. Generate Swagger Documentation

```bash
sudo docker exec -it credit_copilot_app \
php artisan l5-swagger:generate
```

Swagger UI:

```text
http://localhost:8001/api/documentation
```

---


# Demo Accounts

The demo setup creates the following synthetic accounts.

### Loan Officer

```text
Email: loan.officer@delta.test
Password: demo-password
Role: loan_officer
Authority limit: 0 EGP
```

### Credit Officer

```text
Email: credit.officer@delta.test
Password: demo-password
Role: credit_officer
Authority limit: 250,000 EGP
```

### Senior Credit Officer

```text
Email: senior.credit@delta.test
Password: demo-password
Role: credit_officer
Authority limit: 500,000 EGP
```

The password can be changed through:

```env
DEMO_USER_PASSWORD=
```

These are synthetic demo credentials only.

---

# CLI Workflow

The project uses an Artisan CLI as the minimal interaction surface required by the assessment.

Main commands include:

```text
demo:import-corpus
demo:setup

policy:ingest
policy:embed
policy:search
policy:ask

application:process
application:assess
application:memo

assessment:approve
assessment:reject
assessment:issue

evaluation:run
```

Examples:

```bash
sudo docker exec -it credit_copilot_app php artisan policy:ingest
```

```bash
sudo docker exec -it credit_copilot_app php artisan policy:embed
```

```bash
sudo docker exec -it credit_copilot_app \
php artisan policy:ask "What is the maximum DBR?" 2025-04-03
```

Use `php artisan help <command>` for the exact arguments/options of any command.

---

# Demo Video

[Watch demo](https://www.awesomescreenshot.com/video/57454668?key=2b5f890c5b18e53cfd2831233389ed5c)


# Testing

Run the automated test suite:

```bash
sudo docker exec -it credit_copilot_app php artisan test
```

Run code-style validation:

```bash
sudo docker exec -it credit_copilot_app ./vendor/bin/pint --test
```

Pipeline/unit tests should not require an internet connection or API key where fake LLM implementations are used.

---

# Evaluation

Run the evaluation harness:

```bash
sudo docker exec -it credit_copilot_app php artisan evaluation:run
```

The current harness contains 15 cases covering:

- policy retrieval
- out-of-corpus refusal
- policy-edition-sensitive retrieval
- deterministic calculations
- prompt injection
- prompt injection hidden inside an applicant document

## Baseline

With similarity threshold `0.73`:

```text
Retrieval hit-rate:          100%
Refusal correctness:          66.67%
Calculation exactness:       100%
Prompt injection protection: 100%
```

The cryptocurrency-backed-loan question incorrectly retrieved an unrelated generic policy clause with similarity `0.7386`.

## After Improvement

The threshold was increased to `0.74`.

Current result:

```text
Retrieval hit-rate:          100%
Refusal correctness:         100%
Calculation exactness:       100%
Prompt injection protection: 100%
```

See [`docs/EVALUATION.md`](docs/EVALUATION.md) for the detailed cases and failure analysis.

---

# What Is Sent to the LLM

## Policy Q&A

The LLM receives:

- the user's policy question
- retrieved trusted policy evidence
- evidence/citation identifiers

The model is instructed to answer only from that evidence.

## Application Extraction

The LLM receives sanitized application text after application code:

- removes gender
- removes religion
- removes marital status
- removes nationality
- masks national ID
- masks phone number
- masks applicant name

Application text is explicitly treated as untrusted data.

The LLM returns structured fields plus supporting quotes, and application code verifies that the cited evidence exists in the sanitized document.

## Credit Memo

The LLM receives:

- verified application facts
- rule outcomes
- policy citations
- controlled placeholders

Financial calculations are performed by PHP code, not by the LLM.

---

# 5-Minute Demo Path

Use this sequence for a quick review of the core capabilities.

1. **Start the environment**

   ```bash
   sudo docker-compose up -d
   ```

2. **Prepare all demo data**

   ```bash
   sudo docker exec -it credit_copilot_app php artisan demo:setup
   ```

3. **Show a supported policy question with citations**

   ```bash
   sudo docker exec -it credit_copilot_app \
   php artisan policy:ask "What is the maximum DBR?" 2025-04-03
   ```

4. **Show a correct out-of-corpus refusal**

   Ask about a policy that is not present in the supplied documents, such as cryptocurrency-backed loans.

   Expected behavior:

   ```text
   The documents do not contain enough information.
   ```

5. **Show a policy-edition difference**

   Ask the DBR question using a 2024 date and then a 2025 date.

   Expected difference:

   ```text
   2024: 50%
   2025: 45%
   ```

6. **Process and assess a synthetic application**

   Use the application-processing and assessment CLI commands for one of the provided application packs.

7. **Show deterministic calculations**

   Demonstrate that installment, DBR, maximum eligible amount, and rule results come from application code.

8. **Show prompt-injection protection**

   Process the malicious application pack containing an embedded instruction.

   Expected behavior: the suspicious document is blocked / referred for human review rather than following the embedded instruction.

9. **Show authority-limit enforcement**

   Attempt to approve a 300,000 EGP recommendation using:

   ```text
   credit.officer@delta.test
   ```

   with a 250,000 EGP authority limit.

   Expected behavior: the server rejects the approval.

10. **Approve using the higher-authority Credit Officer**

    Use:

    ```text
    senior.credit@delta.test
    ```

    then issue the approved assessment.

---

# Security

Implemented controls include:

- synthetic data only
- environment-based secrets
- `.env` excluded from Git
- applicant documents treated as untrusted
- prompt-injection detection
- protected-attribute removal
- national-ID masking
- phone-number masking
- applicant-name masking
- evidence-quote verification
- deterministic financial calculations
- server-side RBAC
- server-side authority-limit enforcement
- policy/application trust separation
- citation validation
- LLM-output validation

Before final submission, a Git-history secret scan should be run.

---

# Known Limitations / Remaining Work

The following items are intentionally documented rather than presented as complete:

- full FR-9 request/step/token logging is not fully implemented
- fairness test still needs final verification if not already completed
- ingestion + retrieval integration test should be confirmed before submission
- final secret-history scan must be run before making the submission final
- no polished web UI; the required minimal interaction surface is provided through the CLI

---

# Documentation

Project documentation:

```text
docs/DESIGN.md
docs/EVALUATION.md
docs/AI-USAGE-LOG.md
```

- `DESIGN.md` explains architecture, trust boundaries, provider abstraction, chunking, calculations, approval flow, and known limitations.
- `EVALUATION.md` records the real evaluation cases, baseline failure, and improved results.
- `AI-USAGE-LOG.md` documents how AI tools were used, what was implemented and verified manually, and cases where AI guidance was wrong or incomplete.

---

# AI Usage

AI tools were used as development assistants for:

- understanding requirements
- discussing architecture
- troubleshooting
- reviewing implementation ideas
- suggesting tests
- drafting documentation

AI output was reviewed against:

- the assessment brief
- actual application behavior
- deterministic calculations
- automated tests
- database state
- evaluation results

See [`docs/AI-USAGE-LOG.md`](docs/AI-USAGE-LOG.md).

---

# CI

GitHub Actions runs formatting/lint checks and automated tests on Pull Requests.

The project should be submitted only with CI green on `main`.

---

# Submission Checklist

Before submission:

```text
[ ] Repository is public
[ ] CI is green on main
[ ] Fresh clone works using this README
[ ] No secrets exist in Git history
[ ] Synthetic documents are included
[ ] Evaluation harness passes
[ ] Evaluation results are documented
[ ] docs/DESIGN.md is complete
[ ] docs/EVALUATION.md is complete
[ ] docs/AI-USAGE-LOG.md is complete
[ ] Demo video is 3–5 minutes
[ ] Demo video link works in a private browser window
```

---

# Disclaimer

This project is a technical assessment built entirely with synthetic data.

It is not intended for real lending decisions and does not contain real banking policy or financial advice.
