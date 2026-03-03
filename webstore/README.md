# District Badges – Webstore

> Part of the [District Badges](../README.md) system. See also: [Backend](../backend/README.md) · [Design](../design/README.md) · [Postman](../postman/README.md)

The webstore is the customer-facing front end for the District Badges system. It is built with [React 19](https://react.dev), [TypeScript](https://www.typescriptlang.org) and [Vite](https://vite.dev), and uses [React Bootstrap](https://react-bootstrap.github.io) for UI components.

Scout group administrators use the webstore to browse available badges, place orders and view their account history.

## Requirements

| Dependency | Version |
|------------|---------|
| [Node.js](https://nodejs.org) | LTS (≥ 20) |
| [Yarn](https://yarnpkg.com)   | ≥ 1.x      |

> npm can be used in place of Yarn if preferred.

## Getting Started

### 1. Install dependencies

```bash
yarn install
```

### 2. Start the development server

```bash
yarn dev
```

The application will be available at [http://localhost:5173](http://localhost:5173) (or the next available port) with Hot Module Replacement enabled.

### 3. Build for production

```bash
yarn build
```

The compiled output is written to the `dist/` directory and is ready to be served as a static site or deployed to a CDN / web server.

### 4. Preview the production build locally

```bash
yarn preview
```

## Project Structure

```
webstore/
├── public/          # Static assets copied verbatim to dist/
├── src/
│   ├── assets/      # Images and other imported assets
│   ├── App.tsx      # Root application component
│   ├── App.css      # Root-level styles
│   ├── index.css    # Global CSS (resets, variables)
│   └── main.tsx     # Application entry point
├── index.html       # HTML template
├── vite.config.ts   # Vite build configuration
├── tsconfig.json    # TypeScript project references
├── tsconfig.app.json    # TypeScript config for application code
├── tsconfig.node.json   # TypeScript config for Node/Vite tooling
├── eslint.config.js     # ESLint flat config
└── package.json     # Dependencies and scripts
```

## Key Technologies

| Library | Purpose |
|---------|---------|
| [React 19](https://react.dev) | UI component framework |
| [React Router 7](https://reactrouter.com) | Client-side routing |
| [Bootstrap 5](https://getbootstrap.com) | CSS design system |
| [React Bootstrap](https://react-bootstrap.github.io) | Bootstrap components as React elements |
| [Vite 8](https://vite.dev) | Dev server and production bundler |
| [TypeScript 5](https://www.typescriptlang.org) | Static typing |

## Linting

Run ESLint across the project:

```bash
yarn lint
```

The project uses the TypeScript-aware ESLint flat config defined in `eslint.config.js`.

## Connecting to the Backend

During development the webstore communicates with the [backend](../backend/README.md) CakePHP API. Configure the backend base URL by creating a `.env.local` file in this directory:

```bash
VITE_API_BASE_URL=http://localhost:8765
```

> Vite only exposes variables prefixed with `VITE_` to client-side code.

## Available Scripts

| Command | Description |
|---------|-------------|
| `yarn dev` | Start the Vite development server with HMR |
| `yarn build` | Type-check and build for production |
| `yarn preview` | Serve the production build locally |
| `yarn lint` | Run ESLint |
