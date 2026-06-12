# Architecture Enforcement with Deptrac

Deptrac is a static analysis tool that validates import boundaries between
layers and modules. It runs against the compiled PHP source — no runtime
required — and is fast enough to run on every push in CI.

---

## Running Locally

Install the dev dependencies first (one-time):

```bash
composer install
```

Then run the analyser:

```bash
composer deptrac
```

This executes `vendor/bin/deptrac analyse --config-file=deptrac.yaml` from the
project root and exits with a non-zero code if any violation is found.

To see a verbose per-file breakdown add `--report-uncovered`:

```bash
vendor/bin/deptrac analyse --config-file=deptrac.yaml --report-uncovered
```

To generate a dependency graph (requires graphviz):

```bash
vendor/bin/deptrac analyse --config-file=deptrac.yaml --formatter=graphviz-image --output=docs/architecture/dependency-graph.png
```

---

## Layer Map

| Layer | Paths | Purpose |
|---|---|---|
| `Controllers` | `app/Http/Controllers/` | HTTP entry points — Admin and API |
| `Middleware` | `app/Http/Middleware/` | Request/response pipeline |
| `DomainBlog` | `app/Domain/Blog/` | Article, category, tag content domain |
| `DomainHadith` | `app/Domain/Hadith/` | Hadith collections, narrators, grading |
| `DomainQuran` | `app/Domain/Quran/` | Surahs, verses, reciters, tafseer |
| `DomainSahaba` | `app/Domain/Sahaba/` | Companions of the Prophet |
| `DomainSeerah` | `app/Domain/Seerah/` | Prophetic biography events |
| `DomainGenealogy` | `app/Domain/Genealogy/` | Family tree / lineage data |
| `Shared` | `app/Domain/Shared/`, `app/Support/` | Contracts, DTOs, value objects, utilities used across modules |
| `Infrastructure` | `app/Repositories/`, `app/Services/` | Top-level repositories and services (legacy location) |
| `Models` | `app/Models/` | Top-level Eloquent models (Article, User, Tag, Media, etc.) |
| `Bootstrap` | `app/Providers/`, `app/Console/` | Service providers and Artisan commands — wire the full graph |

---

## Allowed Dependencies

```
Controllers    --> Shared, all Domain layers, Infrastructure
Middleware     --> Shared
DomainBlog     --> Shared
DomainHadith   --> Shared
DomainQuran    --> Shared
DomainSahaba   --> Shared
DomainSeerah   --> Shared
DomainGenealogy--> Shared
Shared         --> (nothing inside app/)
Infrastructure --> Shared, Models
Models         --> Shared
Bootstrap      --> everything (wires the container)
```

The key invariants are:

1. **No cross-domain imports.** `DomainHadith` cannot `use` a class from
   `DomainQuran`. Cross-domain communication must go through a contract defined
   in `Domain/Shared/Contracts/`.

2. **Controllers do not touch Models directly.** A controller may only depend
   on a domain service, a domain repository interface, or a class from the
   `Shared` layer. This keeps HTTP concerns out of persistence logic.

3. **Shared is a leaf node.** Nothing in `app/Domain/Shared/` or `app/Support/`
   may import from any domain module. Violations here indicate that shared code
   has grown a hidden coupling to a specific module.

4. **Services depend on interfaces, not concretions.** Within each domain, a
   Service class should type-hint the Repository interface defined under
   `Domain/X/Repositories/` or `Domain/Shared/Contracts/`, never the concrete
   Eloquent implementation.

---

## Adding a New Domain Module

1. Create the directory: `app/Domain/YourModule/`

2. Add a layer entry to `deptrac.yaml`:

   ```yaml
   - name: DomainYourModule
     collectors:
       - type: directory
         value: app/Domain/YourModule/
   ```

3. Add the layer to the `ruleset` section, allowing only `Shared`:

   ```yaml
   DomainYourModule:
     - Shared
   ```

4. Add `DomainYourModule` to the `Controllers` and `Bootstrap` allowed lists so
   controllers and service providers can reference it:

   ```yaml
   Controllers:
     - ...
     - DomainYourModule

   Bootstrap:
     - ...
     - DomainYourModule
   ```

5. Run `composer deptrac` locally to verify the config is valid before pushing.

---

## Handling Legitimate Violations

Occasionally a violation is intentional — for example, a shared query service
that must reach across two domain models during a transitional refactor. Deptrac
supports inline suppressions:

```php
// @deptrac-ignore-next-line DomainHadith
use App\Domain\Hadith\Models\Hadith;
```

Use these sparingly. Each suppression should have a comment explaining why it is
acceptable and what the migration plan is to remove it.

---

## CI Integration

Add the following step to your GitHub Actions workflow (or equivalent):

```yaml
- name: Check architecture boundaries
  run: composer deptrac
```

Place it after `composer install` and before running tests. A boundary violation
should block a merge in the same way a failing test does.
