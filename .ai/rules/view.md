---
paths:
  - 'app/View/**'
---

# View

## PHP View Component Contracts
PHP view components expose explicit, typed data needed by their templates. Required props such as company and companies must be passed deliberately rather than assumed to be globally available.
Keep business queries and decisions outside view components; controllers or the appropriate application layer prepare the data.
