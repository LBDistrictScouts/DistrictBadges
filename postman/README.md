# District Badges – Postman

> Part of the [District Badges](../README.md) system. See also: [Backend](../backend/README.md) · [Webstore](../webstore/README.md) · [Design](../design/README.md)

This directory contains Postman collections and globals for exploring and testing the District Badges REST API.

## Contents

| Path | Description |
|------|-------------|
| [`collections/Scout Shop Collection.postman_collection.json`](collections/Scout%20Shop%20Collection.postman_collection.json) | Postman collection covering the Scout Shop API endpoints |
| [`globals/workspace.postman_globals.json`](globals/workspace.postman_globals.json) | Postman global variables for the workspace |

## Collection Structure

The **Scout Shop** collection is organised into the following folders:

| Folder | Description |
|--------|-------------|
| Badge Information | Endpoints for retrieving badge catalogue data |
| Category Information | Endpoints for retrieving badge category data |

## Importing into Postman

### Import the collection

1. Open Postman.
2. Click **Import** (top left).
3. Select `collections/Scout Shop Collection.postman_collection.json`.
4. Click **Import**.

### Import the globals

1. In Postman, go to **Environments** (left sidebar) and click **Globals**.
2. Click the **…** menu and choose **Import**.
3. Select `globals/workspace.postman_globals.json`.

## Usage

Set the `baseUrl` variable in Globals (or create an Environment) to point at your running backend instance before sending requests:

| Variable | Example value |
|----------|---------------|
| `baseUrl` | `http://localhost:8765` |

See the [backend README](../backend/README.md#getting-started) for instructions on starting the CakePHP development server.
