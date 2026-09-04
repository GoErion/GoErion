---
paths:
  - 'app/**/Product/**'
---

# App Product

## Product Domain Boundaries
Keep categories, units, brands, products, prices, barcodes, and supplier relationships within the Product catalog. ProductPrice stays separate and unique for its approved product/type/currency combination; ProductSupplier keeps its approved composite identity and has no public UUID unless explicitly changed.
