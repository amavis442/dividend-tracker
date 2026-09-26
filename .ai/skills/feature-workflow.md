# Feature workflow

Use when creating a new feature: always start with a GitLab issue and a dedicated branch via `glab`.

## 1. Create a GitLab issue

```bash
glab issue create \
  --title "feat: short feature description" \
  --description "$(cat <<EOF
## What

Technical summary of what needs to be done.

## Why

Why is this needed? Link to ticket/conversation.

## Acceptance criteria

- [ ] criterion 1
- [ ] criterion 2
EOF
)" \
  --label "feature"
```

Note the issue number from the output (e.g. `#42`).

## 2. Pull latest main

```bash
git switch main
git pull origin main
```

## 3. Create a feature branch

Naming: `{issue-number}-short-description` — use the same slug as the issue.

```bash
git switch -c 42-short-description
```

Branch is based on latest `main`.

## 4. Implement the feature

Follow the principles in `.ai/rules`:

- SOLID, DRY, KISS, Clean Architecture
- PHP 8.x strict mode (typed properties, return types, attributes)
- Symfony 8.1 conventions (AbstractController, action injection, #[Route])
- All documentation and commits in English

Commit as you go:

```bash
git add -A
git commit -m "feat(scope): description of what this commit does"
git push origin 42-short-description
```

Commit style: `type(scope): description` — types: feat, fix, refactor, chore, docs, test.

## 5. Open a Merge Request via glab

```bash
glab mr create \
  --title "feat: short description" \
  --description "Closes #42" \
  --fill
```

## 6. Use glab for follow-up

```bash
glab mr view      # check MR status
glab mr list      # list open MRs
glab mr approve   # approve an MR
glab issue list   # list open issues
glab issue view #42   # view issue details
glab ci list      # check CI pipeline status
glab ci view      # view CI job logs
```

## 7. Wait for CI / review

After creating the MR, the task is ready. Do not wait in Hermes — the user picks it up later.