# Frontend: Next.js 16 dashboard

App Router, React 19, TypeScript, Tailwind CSS 4. See the [root README](../README.md) and the [setup guide](../documentation/setup-guide.md).

```bash
npm install
cp .env.example .env.local   # NEXT_PUBLIC_API_URL=http://localhost:8000/api
npm run dev                  # http://localhost:3000
npm run lint && npm run typecheck && npm run build
```

> The npm scripts call `node node_modules/...` directly instead of the `.bin` shims. This keeps them working on Windows when the project path contains `&` (see the setup guide).

## Layout

| Path | Contents |
|---|---|
| `src/app/login` | Login page |
| `src/app/(app)/layout.tsx` | Auth guard and top bar for signed-in pages |
| `src/app/(app)/tasks` | Task list (filters, search, sort, pagination) |
| `src/app/(app)/tasks/[id]` | Task detail: attachments (drag-and-drop upload with progress) and comments |
| `src/components/ui` | Reusable UI: Button, Modal/ConfirmDialog, Field inputs, Badges, Pagination, states |
| `src/components/tasks` | Task feature components |
| `src/components/providers` | Auth and toast context providers |
| `src/lib/api.ts` | Central API client (Bearer token, 401 → /login, XHR upload progress, blob download) |
| `public/` | Static assets |

Tests are planned, not implemented. See [architecture.md](../documentation/architecture.md#testing).
