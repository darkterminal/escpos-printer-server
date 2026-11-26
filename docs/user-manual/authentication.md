---
title: Authentication
description: Optional shared token via EPS_AUTH_TOKEN; loopback by default, remote requires token.
section: Security
eyebrow: Security
---

## Shared token

The WebSocket print job and the HTTP `GET` / `POST /api/config` endpoints are protected by an optional shared token.

- If `EPS_AUTH_TOKEN` is unset, only loopback clients (`127.0.0.1` and `::1`) are accepted.
- To serve remote clients, set `EPS_AUTH_TOKEN` in the service environment, restart the server, and send the same value as `auth_token` in every WebSocket payload or as the `X-EPS-Token` header on `/api/config` requests.

The token is sent only in the WebSocket `auth_token` field or the `X-EPS-Token` header and is not persisted in `printer-settings.json`.

This is one shared token for every client, not per-user authentication; treat it as a secret.

## Next steps

* 👉 [WebSocket protocol](websocket-protocol.md)
* 👉 [Validation](validation.md)
