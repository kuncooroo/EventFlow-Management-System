---
paths:
  - 'tests/Feature/Security/**'
---

# Security

## Security hardening contract suite location
tests/Feature/Security/ holds the cross-cutting hardening contracts (tenant isolation, token entropy, auth/rate limiting, production protection). docs/SECURITY_CHECKLIST.md is the release checklist + public/protected route inventory + rate-limit policy — update it whenever an endpoint, limiter, cookie setting, or token format changes.
