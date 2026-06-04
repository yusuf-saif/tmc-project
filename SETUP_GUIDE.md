# TMC Project Setup Guide
## How to use Claude Code and OpenCode with this project

---

## What files do what

```
your-repo/
├── CLAUDE.md              ← Claude Code reads this AUTOMATICALLY every session
├── AGENTS.md              ← OpenCode reads this AUTOMATICALLY every session
├── docs/
│   ├── PRD.md             ← Feature requirements
│   ├── TRD.md             ← Database schema + routes + services
│   ├── DESIGN_SYSTEM.md   ← CSS tokens + components
│   ├── DESIGN_GUIDE.md    ← Screen-by-screen UI specs
│   └── BUILD_PHASES.md    ← Phase prompts
├── .claude/
│   ├── settings.json      ← Permissions + model config
│   └── commands/          ← Custom slash commands for Claude Code
│       ├── phase.md       → /phase 1  (loads Phase 1 context)
│       ├── design.md      → /design "Home Dashboard"
│       ├── check.md       → /check   (runs tests + verifies rules)
│       ├── schema.md      → /schema users (looks up table schema)
│       └── brand.md       → /brand resources/views/  (checks brand rules)
└── .opencode/
    └── commands/          ← Custom slash commands for OpenCode
        ├── phase.md       → /user:phase 1
        └── check.md       → /user:check
```

---

## Claude Code setup

### Install
```bash
npm install -g @anthropic-ai/claude-code
```

### First session in this repo
```bash
cd your-repo
claude
```

Type these commands in order:
```
/init          ← generates or updates CLAUDE.md from your code
/memory        ← refine what Claude remembers about the project
/mcp           ← set up MCP servers (optional, see MCP section below)
```

### Starting a build session
```bash
claude
```
Claude Code automatically reads `CLAUDE.md`. Then use slash commands:
```
/phase 1       ← loads Phase 1 context from docs/BUILD_PHASES.md
/check         ← runs tests and verifies rules after each phase
/design "Home Dashboard"   ← loads screen spec before building a view
/schema events             ← looks up events table schema from TRD.md
/brand resources/views/    ← checks for brand violations in a folder
```

### Useful built-in commands
```
/plan          ← switches to plan-only mode before a big change
               (Claude explains what it will do, asks before doing it)
/compact       ← summarises the conversation to free up context
               (use when session gets long, e.g. mid-phase)
/clear         ← resets conversation (keep if you restart a phase)
/model         ← switch model mid-session
/effort        ← adjust reasoning level (high for complex phases)
```

---

## OpenCode setup

### Install
```bash
curl -fsSL https://opencode.ai/install | bash
```

### First session
```bash
cd your-repo
opencode
```

Run `/init` inside OpenCode — it reads your project and creates/updates `AGENTS.md`.

### Starting a build session
```bash
cd your-repo
opencode
```

OpenCode automatically reads `AGENTS.md`. Use slash commands:
```
/user:phase 1   ← loads Phase 1 (reads docs/BUILD_PHASES.md + TRD + DESIGN_GUIDE)
/user:check     ← runs tests + verifies rules
```

### Useful built-in commands
```
/compact        ← summarise conversation (OpenCode does this automatically near limits)
Tab key         ← switch to Plan mode (proposes a plan, no edits until you approve)
```

---

## MCP Servers (optional but useful)

MCP servers extend what Claude Code can do. These are the most relevant for this project.

### 1. Memory MCP — persistent knowledge across sessions
Lets Claude Code remember things between sessions without relying only on CLAUDE.md.

```bash
# In your Claude Code session:
/mcp
# Choose "Add server" and paste:
```

Add to `.mcp.json` in project root:
```json
{
  "mcpServers": {
    "memory": {
      "command": "npx",
      "args": [
        "-y",
        "@modelcontextprotocol/server-memory",
        "--memory-path",
        "./.claude/memory.json"
      ]
    }
  }
}
```

Useful for: remembering which phase you are on, design decisions, open questions.

---

### 2. GitHub MCP — read issues and PRs
Useful if you track features or bugs on GitHub.

```json
{
  "mcpServers": {
    "github": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-github"],
      "env": {
        "GITHUB_PERSONAL_ACCESS_TOKEN": "your-token-here"
      }
    }
  }
}
```

Useful for: "Fix the bug in issue #12" or "What PRs are open?"

---

### 3. Filesystem MCP — explicit file access
Gives Claude Code explicit permission to read/write specific directories.

```json
{
  "mcpServers": {
    "filesystem": {
      "command": "npx",
      "args": [
        "-y",
        "@modelcontextprotocol/server-filesystem",
        "/path/to/your/repo"
      ]
    }
  }
}
```

---

## How to use the docs during a session

### At the start of each phase
1. Open Claude Code or OpenCode
2. Type `/phase 2` (or whichever phase)
3. Claude reads the phase prompt + TRD + DESIGN_GUIDE automatically
4. Confirm it lists the right screens and migrations before saying "start"

### When building a screen
1. Type `/design "Journal Screen"` before writing any Blade/Livewire code
2. Claude will output the exact layout, colours, and typography for that screen
3. Then proceed — it already has the brand context loaded

### When you finish a phase
1. Type `/check`
2. Claude runs `php artisan test`, checks routes, verifies journal privacy
3. If all pass: `git commit -m "Phase 2 complete"`
4. Update the "Current phase" line in `CLAUDE.md`

---

## Updating CLAUDE.md as you build

After each phase completes, update this line in `CLAUDE.md`:
```
## Current phase
Phase: 2 — Home Dashboard
Status: Complete ✓
```

And add any important decisions made during the phase:
```
## Decisions made
- Using Tailwind prose plugin for resource article body rendering
- Event reminder job uses 23h delay (not 24h) to account for queue lag
- Logo in nav uses height: 36px not 38px due to mobile spacing
```

This keeps future sessions aware of decisions without re-reading all the code.

---

## What NOT to do

| Don't | Do instead |
|-------|-----------|
| Start a new session without CLAUDE.md in the root | Make sure CLAUDE.md is committed to the repo |
| Paste the full BUILD_PHASES.md into every session | Use `/phase X` command — it loads what's needed |
| Skip `/check` after a phase | Always run `/check` — it catches broken routes and journal privacy |
| Trust UI-only permission checking | Always verify Policy files exist in `app/Policies/` |
| Let Claude Code write code without reading the design guide | Always use `/design` before building a new view |

---

## Recommended Claude Code plan type by phase

| Phase | Mode | Why |
|-------|------|-----|
| 0 — Setup | Normal | Mostly commands, low risk |
| 1 — Auth | `/plan` first | Auth flows need careful planning |
| 2 — Home | Normal | Straightforward views |
| 3 — Events | `/plan` first | RSVP queue logic |
| 4 — Journal | **`/plan` always** | Privacy is critical — plan before touching |
| 5 — Souq | Normal | Standard CRUD + service |
| 6 — Profile | Normal | Visual, low risk |
| 7 — Admin | `/plan` first | Role management is sensitive |
| 8 — PWA | `/plan` first | Service worker + push is complex |
| 9 — QA | Normal | Checklist execution |
