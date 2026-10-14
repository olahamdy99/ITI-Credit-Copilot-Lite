# AI Usage Log

## Overview

AI tools were used as assistants during the implementation of **Credit Copilot Lite**. They were mainly used to discuss architecture, review implementation ideas, explain unfamiliar concepts, suggest debugging steps, draft tests and documentation, and review command-line workflows.

AI-generated suggestions were not treated as authoritative. I reviewed the code, ran the application, inspected the database, executed tests and evaluation cases, and changed or rejected AI suggestions when they did not match the task requirements or the actual system behavior.

## AI Tools Used

- ChatGPT
- AI-assisted IDE tooling during development

## What I Asked AI Tools to Help With

I used AI assistance for:

- understanding and breaking down the finance assessment requirements
- discussing the Laravel project structure
- understanding RAG, embeddings, pgvector, and policy retrieval
- reviewing the separation between domain logic and infrastructure
- discussing deterministic loan calculations and underwriting rules
- reviewing policy-edition selection
- suggesting Laravel commands, services, DTOs, exceptions, and tests
- debugging Docker, PostgreSQL, migrations, and test-environment issues
- reviewing API authentication, RBAC, and approval flows
- suggesting CLI workflows
- designing the evaluation harness
- investigating retrieval/refusal failures
- drafting README, DESIGN, and evaluation documentation
- reviewing the final demo setup and fresh-database workflow

## What I Implemented and Verified Myself

I personally performed and verified the project implementation, including:

- setting up Laravel 10, PHP 8.3, PostgreSQL 16, Docker, and pgvector
- creating and running database migrations
- importing the provided synthetic policy corpus
- implementing and testing policy ingestion and chunking
- generating and storing embeddings
- implementing semantic retrieval and policy-edition filtering
- implementing the application processing pipeline
- validating and sanitizing applicant documents
- removing protected attributes before underwriting
- masking national IDs, phone numbers, and applicant names
- implementing deterministic installment, DBR, maximum eligible amount, and age-at-maturity calculations
- implementing underwriting rules
- implementing credit memo generation and validation
- implementing authentication and RBAC
- implementing approval, rejection, issue, and authority-limit enforcement
- creating CLI commands for the workflow
- creating and running the evaluation harness
- inspecting retrieved chunks and similarity scores
- running Laravel Pint and automated tests
- manually testing API and CLI workflows
- creating demo users and a reproducible `demo:setup` command
- verifying the project against a fresh testing database

# Cases Where AI Was Wrong or Incomplete

## Case 1 — The AI Initially Mixed the Wrong Assessment Scope Into the Finance Task

### What the AI got wrong

Early in the work, AI guidance mixed requirements from a different ITI assessment that involved a multi-agent architecture and additional agent-oriented deliverables.

Those requirements were not part of the **Credit Copilot Lite finance task**.

### How I found out

I went back to the actual finance task document and reviewed its requirements directly.

The finance task requires a grounded RAG underwriting pipeline with deterministic calculations, policy-edition selection, protected-attribute removal, evaluation, and human approval. A multi-agent implementation is only an optional stretch goal, not a core requirement.

### What I changed

I stopped following the unrelated multi-agent scope and based the implementation on the finance brief itself.

The final design therefore uses a fixed Laravel pipeline with:

- deterministic PHP calculations
- policy edition selected in code
- LLM usage behind an interface
- applicant documents treated as untrusted input
- human approval before issue

### Lesson

AI context can mix requirements from similar or previous tasks. I therefore treated the provided task document as the source of truth.

## Case 2 — The Initial Retrieval Threshold Produced a False Positive

### What the AI got wrong

The initial retrieval implementation used / accepted a semantic similarity threshold of `0.73`.

This appeared reasonable during development, but it had not yet been validated against enough out-of-corpus questions.

### How I found out

I created and ran the evaluation harness.

The out-of-corpus question:

`What is the policy for cryptocurrency-backed loans?`

should have been refused because the provided documents do not contain such a policy.

Instead, retrieval returned an unrelated generic policy-purpose clause with similarity `0.7386`.

The baseline evaluation result was:

- Retrieval hit-rate: 100%
- Refusal correctness: 66.67%
- Calculation exactness: 100%
- Prompt injection protection: 100%

### What I changed

I inspected the retrieved chunk and increased the minimum similarity threshold from `0.73` to `0.74`.

I then reran the complete evaluation harness.

The result became:

- Retrieval hit-rate: 100%
- Refusal correctness: 100%
- Calculation exactness: 100%
- Prompt injection protection: 100%

### Lesson

A retrieval threshold should not be accepted because it “looks reasonable.” It must be tested against both supported and unsupported questions.

## Case 3 — The Test Environment Was Not Properly Isolated From the Development Database

### What the AI missed

During the testing setup, the test database configuration was initially incomplete. The PHPUnit configuration did not correctly point database tests to the dedicated testing database.

As a result, tests ran against the development database.

### How I found out

I inspected the database configuration and noticed that the expected test database was not actually being used. The issue became especially clear after development data was affected by the test run.

### What I changed

I corrected the testing environment so the project uses the dedicated `credit_copilot_test` database.

I then verified the environment explicitly and later used that database to test a completely fresh migration and demo setup.

### Lesson

An AI-generated or AI-reviewed test setup is not enough. Database isolation must be verified by checking the actual active database before destructive test operations.

## Case 4 — A Migration Was Marked as Executed Even Though the Expected Column Was Missing

### What the AI-assisted debugging did not solve

A migration for `pricing_segment` appeared in Laravel's migration history as already run, but the actual column was missing from the database.

A normal rollback attempt did not resolve the inconsistency.

### How I found out

I compared Laravel's migration status with the real database schema. The two did not match.

### What I changed

I manually removed the inconsistent migration record from the migrations table and reran the migration.

After rerunning it, the `pricing_segment` column was created correctly.

### Lesson

When framework migration state and the actual database schema disagree, I need to inspect both rather than assuming the migration table is correct.

## Case 5 — I Decided Not to Continue Building the Web UI

### What was incomplete in the AI direction

AI-assisted implementation started moving toward a Blade web UI.

After reviewing the task again, I confirmed that the interface requirement allows a minimal web UI **or CLI**.

### How I found out

I reread FR-7 in the assessment document and compared it with the work still remaining. The task explicitly prioritizes core correctness and even recommends cutting UI first if time runs out.

### What I changed

I removed the unfinished UI work and completed the workflow through Artisan CLI commands instead.

The CLI supports:

- policy ingestion
- policy questions
- application assessment
- memo generation
- approve
- reject
- issue

### Lesson

AI may suggest technically valid work that is not the best use of time. I still need to prioritize according to the actual assessment criteria.

# How I Validated AI Suggestions

I validated AI suggestions using:

- Laravel automated tests
- Laravel Pint
- manual CLI tests
- manual API tests
- database queries
- migration inspection
- retrieved-chunk inspection
- similarity-score inspection
- deterministic expected calculation results
- evaluation cases
- fresh testing database setup
- end-to-end `demo:setup` verification

A concrete fresh database verification produced:

- Policy editions: 2
- Policy documents: 7
- Policy chunks: 79
- Chunks without embeddings: 0
- Demo users: 3

# Final Approach to AI Assistance

I used AI as a development assistant rather than as a source of truth.

My process was:

AI suggestion → review against the task → implement/adapt → run code and tests → inspect actual output → keep, correct, or reject the suggestion.

The most useful AI assistance was in accelerating exploration, explaining unfamiliar concepts, and proposing implementation options.

The most important part of my own work was verifying those suggestions against the task document, actual database state, deterministic calculations, tests, and evaluation results.
