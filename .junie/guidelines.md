# Junie Guidelines — WebSocket Chat (Symfony 8 + PHP 8 + Twig)

## Project Context
This is a **small experimental project** (POC / sandbox).

**Stack**
- Backend: PHP 8 + Symfony 8
- Frontend: Twig + minimal JavaScript
- Realtime: WebSocket
- Tests: **none (by design)**

The goal is **clarity, simplicity, and correctness**, not enterprise-level architecture.

---

## Absolute Rules (Non-Negotiable)

### 1. No Git Commands
You must **never** suggest or run any Git command:
- ❌ git status
- ❌ git add
- ❌ git commit
- ❌ git push
- ❌ git checkout
- ❌ Any Git-related operation

---

### 2. No Dependencies Without Explicit Approval
- You must **not add Composer or JS dependencies automatically**
- If a dependency seems useful:
    - Explain **why**
    - Explain **what problem it solves**
    - Wait for explicit confirmation

---

### 3. No Tests
- Do **not** create:
    - unit tests
    - integration tests
    - e2e tests
- As a consequence:
    - code must be **very readable**
    - runtime validation is mandatory
    - manual testing steps must always be provided

---

### 4. No Large Refactors Without Explicit Request
- Only change what is strictly necessary
- No “cleanup” or “while I’m here” refactors

---

### 5. No Magic / No Over-Engineering
- No premature abstractions
- No generic frameworks inside the project
- No complex patterns just for elegance

---

## Quality Priorities (Strict Order)
1. **It works**
2. **Readable**
3. **Simple**
4. **Maintainable**
5. **Optimized** (only if a real problem exists)

---

## Target Architecture (Simple & Clean)

### Backend (Symfony)

Recommended structure:
```
src/
 ├─ Controller/
 ├─ WebSocket/
 ├─ Service/
 ├─ Dto/
 ├─ Entity/
 ├─ Exception/
```

Rules:
- Controllers contain **no business logic**
- Controllers orchestrate validation and service calls
- Business rules live in Services

---

### Frontend (Twig + JavaScript)
- templates/: Twig templates only
- assets/: JS / CSS
- JavaScript must stay minimal and readable

---

## Clean Code Rules
- Small functions (< 30 lines)
- Explicit naming
- No useless comments
- Explicit error handling

---

## WebSocket Rules

### Message Contract
Every message must contain:
- type (string)
- payload (object)

Example:
```json
{ "type": "chat.message.send", "payload": { "text": "hello" } }
```

Rules:
- Never trust client input
- Validate payload size
- Invalid messages must not crash the server

---

## Security
- Twig auto-escape must stay enabled
- Never inject user content via innerHTML
- Secrets only in .env.local

---

## Logging
- Use Psr\Log\LoggerInterface
- Log connections and errors only

---

## PHP Rules
- declare(strict_types=1)
- Typed arguments and returns
- readonly when possible
- Domain-specific exceptions

---

## Required Output For Every Task
1. Files to modify
2. Full code blocks
3. Commands to run (no Git)
4. Manual testing steps

---

## Manual Test Plan (Mandatory)
- Open two browser tabs
- Connect both to WebSocket
- Send a message
- Verify real-time reception
- Test invalid payloads
