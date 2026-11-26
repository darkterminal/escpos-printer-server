---
title: Validation
description: Strict payload validation and single timestamped responses per print job.
section: Reference
eyebrow: Reference
---

## Validation

Every message is validated before it reaches the printer engine.

- Required: `from`, `printer_name`, `printer_settings`.
- Required for `posclient` and `testprinter`: `receipt_data`.
- Required within `receipt_data`: `app_name`, `full_name`, `transaction`, `transaction.date_transaction`, `transaction.transaction`, `transaction.total_transaction`, `cart`.
- Unknown keys are preserved so payload extensions remain compatible.

Invalid payloads produce a single timestamped error response:

```text
[2026/11/26 15:00:00][ERROR] Invalid payload: from must be a non-empty string
```

A valid job produces exactly one timestamped success response:

```text
[2026/11/26 15:00:00][SUCCESS] Receipt printed successfully
```

## Next steps

* 👉 [Overview](index.md)
* 👉 [Getting started](installation.md)
