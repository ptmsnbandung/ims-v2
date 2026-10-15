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

## 2. Anti-Slop Principles (Clean Code & Zero AI Bloat)
- **No Wrapper/Abstraction Bloat:** Direct calls beat multi-layer abstraction. Never invent new DTOs, wrapper services, or unnecessary helpers when standard platform methods suffice.
- **Ban Conversational Comments:** Delete comments that merely restate syntax or obvious logic. Only keep high-value comments explaining domain rationale, strange business constraints, or hardware quirks.
- **No Fake Fallbacks:** Never swallow exceptions with empty `catch` blocks or inject fake default data to bypass real errors. Fail fast and handle errors at trust boundaries.
- **High Signal-to-Noise Ratio:** Prefer deep modules, flat logic, and direct data flows. Clean up dead code and unused variables.

## 3. Taste-Skill Principles (UI Frontend Craftsmanship)
- **Reject Generic AI Slop UI:** Never use raw browser primaries (`#0000ff`, `#00cc00`), generic purple gradients, or uninspired cluttered cards.
- **Cohesive Art Direction:** Telemetry/NOC modules must feel like a modern datacenter console (deep obsidian/slate, glowing emerald/cyan neon accents, delicate grid lines). Admin/Customer modules must feel crisp, clean, and spacious.
- **Typographic Rigor:** Always use `font-mono` for metrics, IPs, SNs, and timestamps. Avoid glitchy vertical rotated text.
- **Layout Integrity:** Guarantee strict HTML tag closure, maintain balanced aspect ratios for charts (~2.5:1 to ~3:1), and structure metric cards into clean, responsive grids.

## 4. Git Workflow
- Always run `git pull` before starting modifications to stay synchronized with remote.
- Always run `git pull` (or rebase) before executing `git push` to avoid conflicts.

