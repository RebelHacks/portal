# Contributing

Thank you for contributing to RebelHacks.

This guide defines the development workflow and conventions used by the project.

## Development Workflow

1. Create or find the GitHub issue for your work.
2. Create a branch from `main`.
3. Make and test your changes.
4. Commit your changes.
5. Create a Pull Request into `main`.
6. A team lead will review the Pull Request and may request changes.
7. Once approved, the Pull Request will be merged.
8. Delete the branch after it has been merged.

Keep each branch focused on a single issue or related set of changes.

## Naming Conventions

Use these types when naming issues, branches, commits, and Pull Requests:

- `feat` for new functionality
- `fix` for bug fixes
- `refactor` for changes to existing code without changing its intended behavior
- `docs` for documentation
- `test` for tests
- `style` for formatting or styling changes
- `chore` for maintenance and supporting work

### Issues

Issue titles should follow:

```text
type: short-description
```

Examples:

```text
feat: add team creation
fix: prevent duplicate team invitations
docs: add contribution workflow
```

### Branches

Branch names should follow:

```text
type/issue-number-short-description
```

Examples:

```text
feat/42-team-creation
fix/57-login-redirect
docs/40-contribution-workflow
```

Use the GitHub issue number associated with the work. Keep branch names short and descriptive.

### Commits

We loosely follow the [Conventional Commits](https://www.conventionalcommits.org/) format:

```text
type: short-description
```

A scope is not required.

Examples:

```text
feat: add team creation page
fix: prevent duplicate team invitations
refactor: simplify authentication logic
docs: update setup instructions
test: add registration tests
style: fix dashboard spacing
chore: update dependencies
```

Commit messages should describe what changed. Strict compliance with the full Conventional Commits specification is not required.

### Pull Requests

Pull Request titles should follow:

```text
type: short-description
```

Examples:

```text
feat: add team creation
fix: prevent duplicate team invitations
docs: add contribution workflow
```

The issue number does not need to be included in the title. Link the related issue in the Pull Request.

## Coding Standards

Follow the standard naming conventions and practices of the language or framework you are working with.

Keep logic straightforward and easy to follow. Avoid unnecessary complexity or abstraction when a simpler solution works.

Use clear structure and naming so the code explains itself when possible. Add comments or documentation when needed to explain why code exists or why an approach was taken. Avoid comments that only restate what the code does.

## Pull Requests

Before creating a Pull Request:

- Make sure your changes work as expected.
- Keep the Pull Request focused on its related issue.
- Explain important implementation decisions, tradeoffs, or changes from the proposed solution.
- Link the related issue.

If the Pull Request completes an issue, use a GitHub closing keyword:

```text
Closes #42
```

A team lead will review the Pull Request and may request changes before merging.

Delete the branch after the Pull Request has been successfully merged.

## Use of AI

AI tools may be used to assist with development.

You are responsible for the code you submit. Understand your changes and be prepared to explain how the code works and why it was implemented that way.

Review and test AI-generated code before submitting it.
