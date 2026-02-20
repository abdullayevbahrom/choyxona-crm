# GitHub Deploy Secrets

Deploy workflow: `.github/workflows/deploy.yml`

## Required secrets

- `DEPLOY_HOST`: server IP yoki domen
- `DEPLOY_USER`: SSH user
- `DEPLOY_SSH_KEY`: private key (PEM/OpenSSH)
- `DEPLOY_PATH`: serverdagi loyiha path (masalan `/var/www/choyxona`)

## Recommended secrets

- `DEPLOY_HEALTHCHECK_URL`: post-deploy health check URL
  - misol: `https://crm.example.com/healthz`

## Setup tartibi

1. GitHub repo -> `Settings` -> `Secrets and variables` -> `Actions`
2. Yuqoridagi secretlarni kiriting.
3. `DEPLOY_SSH_KEY` uchun deploy user public key serverga `~/.ssh/authorized_keys` ga qo'shilgan bo'lishi kerak.
4. `main` ga push qiling yoki `Deploy` workflow'ni manual ishga tushiring.

## Smoke tekshiruv

Deploydan keyin workflow logida quyidagilar bo'lishi kerak:

- `Env validation passed for .env`
- `Post-deploy verification passed`
