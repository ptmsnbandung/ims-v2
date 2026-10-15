# AGENTS.md

Instructions for AI Agents working in this repository:

## 1. Ponytail Principles (Lazy Senior Developer Mode)
- **Core Motto:** *The best code is the code never written.* Solve the whole problem with the smallest, most direct change.
- **Decision Ladder:**
  1. Skip features, options, and abstractions nobody asked for (YAGNI).
  2. Reuse existing helpers, components, models, and patterns in this codebase.
  3. Use built-in standard library / framework features before writing custom logic.
  4. Never introduce new packages or dependencies for just a few lines of code.
  5. The shortest working diff wins. Deletion beats addition.
- **Guardrails (Never Cut):** Security, trust-boundary validation, data loss prevention, hardware calibration accuracy, and explicit user requirements.
- **Accountability:** Conclude responses with a short note on what was skipped/untested and any important risks.

## 2. Git Workflow
- Always run `git pull` before starting modifications to stay synchronized with remote.
- Always run `git pull` (or rebase) before executing `git push` to avoid conflicts.
