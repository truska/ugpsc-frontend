# Workflow Rules

## Frontend Development

- Make frontend code changes only in this repo: `/var/www/dev-mst.witecanvas.com/web`
- Commit and push frontend changes from this repo
- Treat this repo as the source of truth for this site's frontend code

## Protected Codebases

- Do not make code changes here to codebases managed from other sites, including `dev-wc.witecanvas.com`
- Apply the same rule to other sites as they are added: only edit code in that site's own dev repo

## Live Environment

- Treat live repos as read-only for code by default
- Live is for debugging, inspection, data checks, and deploy actions such as `git pull`
- Do not make live code edits unless explicitly requested for a one-off exception

## Git Safety

- Add only this dev repo to Git `safe.directory`
- Do not add live repos to `safe.directory` unless there is a specific reason

Recommended command for this repo:

```bash
git config --global --add safe.directory /var/www/dev-mst.witecanvas.com/web
```

## Deployment Principle

- Develop in dev
- Commit and push from dev
- Deploy to live by pulling the committed change
- Avoid "quick fixes" made directly in live code, because they create drift from Git
