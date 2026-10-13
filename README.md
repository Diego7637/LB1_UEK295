# API

Simple PHP API built with Slim.

## Setup

1. Put the project in your XAMPP `htdocs` folder or run it with a local PHP server.
2. Install dependencies:
   ```bash
   composer install
   ```
3. Start the app.

## Base URL

The API runs under:

```text
/api/v1
```

Examples:
- `POST /api/v1/authenticate`
- `GET /api/v1/products`
- `GET /api/v1/categories`

## Notes

- `/api/v1/authenticate` is public.
- Other routes need a valid Bearer token.
- Responses are JSON.
