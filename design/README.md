# District Badges – Design

> Part of the [District Badges](../README.md) system. See also: [Backend](../backend/README.md) · [Webstore](../webstore/README.md) · [Postman](../postman/README.md)

This directory contains the visual design assets for the District Badges system.

## File

| File | Description |
|------|-------------|
| `District Badges.bsdesign` | [Bootstrap Studio](https://bootstrapstudio.io) project file containing the UI designs for the webstore |

## Opening the Design

1. Install [Bootstrap Studio](https://bootstrapstudio.io) (desktop application, available for macOS, Windows and Linux).
2. Open Bootstrap Studio.
3. Go to **File → Open** and select `District Badges.bsdesign`.

## Design Scope

The Bootstrap Studio file contains the page layouts and component designs for the webstore front end, including:

- Badge catalogue / browse pages
- Order flow screens
- Account and group management views

## Exporting to the Webstore

Bootstrap Studio can export HTML, CSS and JavaScript that can be used as a reference when implementing React components in the [webstore](../webstore/README.md).

To export:

1. Open the project in Bootstrap Studio.
2. Go to **File → Export** and choose your export location.
3. Use the exported files as a visual reference when building or updating components in `webstore/src/`.

## Design System

The designs use [Bootstrap 5](https://getbootstrap.com/docs/5.3/) as the base design system, consistent with the Bootstrap 5 dependency used in the webstore.
