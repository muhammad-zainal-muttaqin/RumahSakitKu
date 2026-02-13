# Contributing Guide

Terima kasih atas minat Anda untuk berkontribusi pada SIMRS RumahSakitKu!

Dokumen ini provides guidelines dan information untuk kontributor. Project ini open-source di bawah AGPL-3.0 license.

---

## Table of Contents

1. [Code of Conduct](#code-of-conduct)
2. [Getting Started](#getting-started)
3. [Development Environment](#development-environment)
4. [Workflow](#workflow)
5. [Coding Standards](#coding-standards)
6. [Testing](#testing)
7. [Pull Request Process](#pull-request-process)
8. [Code Review](#code-review)
9. [Issue Reporting](#issue-reporting)
10. [Documentation](#documentation)
11. [Security Disclosures](#security-disclosures)

---

## Code of Conduct

Kami berkomitmen untuk lingkungan yang ramah, inklusif, dan bebas harassment.

- Berpihak dalam setiap cara
- Bersikap profesional dan respectful
- Menerima konstruktif feedback dengan baik
- Fokus pada apa yang paling untuk community
- Menunjukkan empati kepada community members

Kita semua adalah contributors di project ini. Mari kita buat experience excellent untuk semua.

---

## Getting Started

### Who Can Contribute?

Everyone! We accept contributions dari:
- Bug fixes
- New features
- Documentation improvements
- Test additions
- Performance optimizations
- Security improvements
- Translation/localization

### Before You Start

1. **Check existing issues**:
   - Search [GitHub Issues](https://github.com/muhammad-zainal-muttaqin/RumahSakitKu/issues) untuk existing reports
   - Comment `I want to work on this` untuk claim an issue
   - Create new issue untuk bug atau feature request

2. **Read relevant documentation**:
   - [README.md](./README.md) - Project overview
   - [ROADMAP.md](./ROADMAP.md) - Future plans
   - [CHANGELOG.md](./CHANGELOG.md) - Recent changes
   - [DEVELOPMENT.md](./docs/DEVELOPMENT.md) - Development guidelines

3. **Fork repository**:
   - Fork project ke your GitHub account
   - Clone your fork locally:
     ```bash
     git clone https://github.com/YOUR-USERNAME/RumahSakitKu.git
     cd RumahSakitKu
     ```
   - Add upstream remote:
     ```bash
     git remote add upstream https://github.com/muhammad-zainal-muttaqin/RumahSakitKu.git
     ```

---

## Development Environment

### Prerequisites

- **PHP** >= 8.2 dengan extensions:
  - BCMath, Ctype, cURL, DOM, Fileinfo, JSON, Mbstring, OpenSSL, PCRE, PDO, PDO_MySQL, Tokenizer, XML, Zip, Redis, SQLite3
- **Composer** >= 2.0
- **Node.js** >= 18.x dengan npm >= 9.x
- **MySQL** >= 8.0 atau **MariaDB** >= 10.6
- **Git**
- **Docker** & **Docker Compose** (disarankan)

### Quick Setup (Docker)

```bash
# Start services
./sail.bat up -d

# Install dependencies
./sail.bat composer install
./sail.bat npm install

# Generate key
./sail.bat artisan key:generate

# Run migrations
./sail.bat artisan migrate --seed

# Build assets
./sail.bat npm run build
```

Access: `http://localhost:8000/admin`

### Manual Setup

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Configure database di .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=rumahsakitu_simrs
# DB_USERNAME=root
# DB_PASSWORD=your_password

# Create database
mysql -u root -p -e "CREATE DATABASE rumahsakitu_simrs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations & seeders
php artisan migrate
php artisan db:seed

# Storage link
php artisan storage:link

# Build assets
npm run dev  # atau npm run build untuk production

# Start development server
php artisan serve
```

---

## Workflow

We follow **Git Flow** dengan branch strategy:

```
main              → Production releases (stable)
develop           → Development integration (pre-release)
feature/*         → New features (from develop, merge ke develop)
bugfix/*          → Bug fixes (from develop, merge ke develop)
hotfix/*          → Critical production fixes (from main, merge ke main & develop)
release/*         → Release preparation (from develop, merge ke main & develop)
```

### 1. Create Feature Branch

```bash
# Start dari develop
git checkout develop
git pull origin develop

# Create feature branch dengan naming convention
git checkout -b feature/SHORT-DESCRIPTION

# Example:
git checkout -b feature/add-mfa-authentication
# atau dengan ticket number
git checkout -b feature/SIM-123-patient-search-enhancement
```

### 2. Make Changes

- Write code dengan follows [Coding Standards](#coding-standards)
- Add tests untuk new functionality
- Update documentation jika perlu
- Commit small, logical changes (not entire feature in one commit)

### 3. Commit Guidelines

Kami menggunakan [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, missing semicolons, etc.)
- `refactor`: Code refactoring (no functional change)
- `test`: Adding or updating tests
- `chore`: Build process, auxiliary tools, etc.
- `ci`: CI/CD configuration changes
- `perf`: Performance improvement

**Examples:**

```bash
git commit -m "feat(auth): add two-factor authentication with TOTP

Add support for TOTP-based 2FA using Google Authenticator.
Includes setup wizard, backup codes, and recovery options.

Closes SIM-456
```

```bash
git commit -m "fix(billing): resolve double payment on BPJS claims

Fix race condition where user could click submit multiple times.
Added idempotency key check on payment endpoint.

Fixes #123"
```

**Rules:**
- Subject: 50 chars or less, imperative mood ("Fix bug", not "Fixed bug")
- Body: 72 chars per line, explain what & why (not how)
- Footer: Reference issues (`Closes #123`, `Fixes #456`, `See #789`)
- Use emojis sparingly (optional): `:bug:`, `:sparkles:`, `:hammer:` if you like

### 4. Keep Branch Updated

```bash
# Regularly rebase onto develop
git fetch upstream
git rebase upstream/develop

# If conflicts occur, resolve them
# Then continue rebase
git rebase --continue
```

**Alternative:** Merge instead of rebase (preserves merge commits):
```bash
git merge upstream/develop --no-ff
```

### 5. Push Branch

```bash
git push -u origin feature/your-feature-name
```

### 6. Create Pull Request

- Go ke GitHub repository
- Click "Compare & pull request"
- Target branch: `develop`
- Fill PR template:
  - Description (what & why)
  - Type of change (bug fix, feature, breaking change)
  - Screenshots (if UI changes)
  - Testing instructions
  - Related issues (link)

---

## Coding Standards

### PHP Standards

We adhere to:
- **PSR-12** (PHP coding style)
- **Laravel PER** (PHP-Extended-Recommendations)
- **Strict typing**: All files MUST start dengan `declare(strict_types=1);`

### Code Style

#### Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Classes | PascalCase | `PatientController` |
| Methods | camelCase | `getPatientDetails()` |
| Variables | camelCase | `$patientName` |
| Constants | UPPER_SNAKE_CASE | `MAX_RETRY_ATTEMPTS` |
| Tables | snake_case, plural | `medical_records` |
| Columns | snake_case | `birth_date` |
| Routes | kebab-case | `patient/{id}/visits` |

#### Type Declarations

Always use type hints:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Patient;

class PatientService
{
    public function getPatientById(int $id): ?Patient
    {
        return Patient::find($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createPatient(array $data): Patient
    {
        // ...
    }
}
```

#### Null Coalescing & Ternary

Prefer null coalescing operator:
```php
$name = $patient->name ?? 'Unknown';
```

Avoid ternary shorthand nested:
```php
// Bad
$value = $condition1 ? 'a' : $condition2 ? 'b' : 'c';

// Good
if ($condition1) {
    $value = 'a';
} elseif ($condition2) {
    $value = 'b';
} else {
    $value = 'c';
}
```

#### Error Handling

Use exceptions, don't suppress errors:
```php
// Bad
$result = @file_get_contents($url);

// Good
try {
    $result = file_get_contents($url);
} catch (\Exception $e) {
    Log::error("Failed to fetch URL: {$e->getMessage()}");
    throw new \RuntimeException("Unable to fetch data", 0, $e);
}
```

#### Dependency Injection

Use constructor injection, not service locator:
```php
// Good
class PatientController extends Controller
{
    public function __construct(
        private readonly PatientService $patientService
    ) {}

    public function index(): View
    {
        $patients = $this->patientService-> getAll();
        return view('patients.index', compact('patients'));
    }
}

// Bad
class PatientController extends Controller
{
    public function index(): View
    {
        $patientService = app(PatientService::class); // service locator
        // ...
    }
}
```

### Laravel Specific

#### Models
- Use `$fillable` atau `$guarded` (never both)
- Define relationships explicitly dengan return types
- Use accessors/mutators sparingly
- Use query scopes untuk reusable filters

```php
class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'nik',
        'birth_date',
        'gender',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
```

#### Controllers
- Keep controllers thin (delegate业务 logic ke services)
- Use Form Request classes untuk validation
- Use API Resources untuk JSON responses

```php
class PatientController extends Controller
{
    public function __construct(
        private readonly PatientService $patientService
    ) {}

    public function store(StorePatientRequest $request): JsonResponse
    {
        $patient = $this->patientService->create($request->validated());
        
        return (new PatientResource($patient))
            ->response()
            ->setStatusCode(201);
    }
}
```

#### Services
Business logic goes in service classes, not controllers or models:

```php
class BillingService
{
    public function __construct(
        private readonly PatientRepository $patients,
        private readonly InvoiceRepository $invoices,
        private readonly PaymentRepository $payments
    ) {}

    public function createInvoice(Patient $patient, array $services): Invoice
    {
        return DB::transaction(function () use ($patient, $services) {
            $invoice = $this->invoices->createForPatient($patient, $services);
            
            event(new InvoiceCreated($invoice));
            
            return $invoice;
        });
    }
}
```

#### Repositories (Optional)
For complex data access, consider repository pattern:

```php
interface PatientRepositoryInterface
{
    public function find(int $id): ?Patient;
    public function create(array $data): Patient;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}

class EloquentPatientRepository implements PatientRepositoryInterface
{
    public function __construct(private Patient $model) {}

    public function find(int $id): ?Patient
    {
        return $this->model->with(['visits', 'medicalRecords'])->find($id);
    }

    // ...
}
```

### Filament Resources

Follow conventions untuk Filament admin panels:

```php
class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Manajemen Pasien';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Dasar')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('nik')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(16)
                            ->inputMode('numeric'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nik')
                    ->searchable(),
                TextColumn::make('medical_record_number')
                    ->label('No. RM')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('gender')
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
```

---

## Testing

### Test Structure

```
tests/
├── Feature/
│   ├── Api/                    # API endpoint tests
│   ├── Http/                   # Web routes tests
│   └── Filament/               # Admin panel tests
├── Unit/
│   ├── Models/                 # Model tests
│   ├── Services/               # Service tests
│   └── Repositories/           # Repository tests (if any)
├── CreatesApplication.php
└── TestCase.php
```

### Running Tests

```bash
# All tests
make test
# or
php artisan test

# With coverage
make test-coverage
# or
php artisan test --coverage

# Specific test file
php artisan test tests/Feature/Api/PatientControllerTest.php

# Filter by test name pattern
php artisan test --filter=PatientTest

# Parallel (faster)
php artisan test --parallel

# Stop on first failure
php artisan test --stop-on-failure
```

### Writing Tests

#### Unit Tests

Test individual classes in isolation:

```php
<?php

namespace Tests\Unit\Services;

use App\Services\PatientService;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_patient(): void
    {
        $service = new PatientService(new PatientRepository());

        $data = [
            'name' => 'John Doe',
            'nik' => '1234567890123456',
            'birth_date' => '1990-01-01',
            'gender' => 'L',
        ];

        $patient = $service->create($data);

        $this->assertInstanceOf(Patient::class, $patient);
        $this->assertDatabaseHas('patients', ['nik' => '1234567890123456']);
        $this->assertEquals('John Doe', $patient->name);
    }

    public function test_throws_exception_for_duplicate_nik(): void
    {
        Patient::factory()->create(['nik' => '1234567890123456']);

        $service = new PatientService(new PatientRepository());

        $this->expectException(DuplicatePatientException::class);

        $service->create([
            'name' => 'Jane Doe',
            'nik' => '1234567890123456', // duplicate
            'birth_date' => '1992-05-15',
            'gender' => 'P',
        ]);
    }
}
```

#### Feature Tests

Test full HTTP request/response cycle:

```php
<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_patients(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Patient::factory()->count(5)->create();

        $response = $this->getJson('/api/patients');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'nik', 'medical_record_number']
                ],
                'meta'
            ]);
    }

    public function test_can_create_patient(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $data = [
            'name' => 'John Doe',
            'nik' => '1234567890123456',
            'birth_place' => 'Jakarta',
            'birth_date' => '1990-01-01',
            'gender' => 'L',
            'address' => 'Jl. Merdeka 123',
            'phone' => '081234567890',
        ];

        $response = $this->postJson('/api/patients', $data);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'John Doe')
            ->assertJsonPath('data.nik', '1234567890123456');

        $this->assertDatabaseHas('patients', ['nik' => '1234567890123456']);
    }

    public function test_cannot_create_patient_without_required_fields(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/patients', []);

        $response->assertUnprocessableEntity()
            ->assertJsonValidationErrors(['name', 'nik', 'birth_date', 'gender']);
    }
}
```

#### Factories

Use model factories untuk test data:

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PatientFactory extends Factory
{
    protected $model = \App\Models\Patient::class;

    public function definition(): array
    {
        $birthDate = $this->faker->dateTimeBetween('-80 years', '-18 years');

        return [
            'medical_record_number' => $this->faker->unique()->regexify('/\d{6}-\d{4}/'),
            'name' => $this->faker->name(),
            'nik' => $this->faker->unique()->numerify('##############'), // 14 digits, should be 16!
            'birth_place' => $this->faker->city(),
            'birth_date' => $birthDate,
            'gender' => $this->faker->randomElement(['L', 'P']),
            'blood_type' => $this->faker->randomElement(['A', 'B', 'AB', 'O', null]),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'is_active' => true,
        ];
    }

    public function active(): Factory
    {
        return $this->state([
            'is_active' => true,
        ]);
    }

    public function inactive(): Factory
    {
        return $this->state([
            'is_active' => false,
        ]);
    }

    public function withInsurance(string $type = 'bpjs'): Factory
    {
        return $this->state([
            'insurance_type' => $type,
            'insurance_number' => $this->faker->numerify('#############'),
            'bpjs_card_number' => $type === 'bpjs' ? $this->faker->numerify('#############') : null,
        ]);
    }
}
```

Use factories in tests:
```php
Patient::factory()->count(10)->create();
Patient::factory()->create(['gender' => 'P']);
```

---

## Pull Request Process

### Before Submitting PR

1. **Test your changes:**
   ```bash
   make test
   make analyze
   make format-check
   ```

2. **Check for breaking changes:**
   - API changes? Update API documentation
   - Database migrations? Ensure backward compatible
   - Configuration changes? Document in UPGRADE.md

3. **Update documentation:**
   - README.md jika user-facing changes
   - API docs jika endpoint changes
   - User guides jika UI changes
   - CHANGELOG.md (add entry for your version)

4. **Squash commits** (optional but recommended for clean history):
   ```bash
   git rebase -i HEAD~3  # interactive rebase
   # squash all feature commits into one
   git push -f origin feature/your-feature
   ```

### PR Template

```markdown
## Description
[Describe changes, why needed, what problem solved]

## Type of Change
- [ ] Bug fix (non-breaking change yang fix masalah)
- [ ] New feature (non-breaking change yang add functionality)
- [ ] Breaking change (fix atau feature会产生 backward-incompatible changes)
- [ ] Documentation update

## Testing
- [ ] Unit tests added/updated
- [ ] Feature tests added/updated
- [ ] All tests passing locally (`make test`)
- [ ] Static analysis passing (`make analyze`)
- [ ] Code style fixed (`make format`)

## Screenshots (if applicable)
[Add screenshots untuk UI changes]

## Checklist
- [ ] My code follows [coding standards](#coding-standards)
- [ ] I have performed self-review
- [ ] I have commented my code, particularly hard-to-understand areas
- [ ] I have added tests that prove my fix/feature works
- [ ] New and existing unit tests pass locally
- [ ] I have updated documentation accordingly
- [ ] My changes generate no new warnings
- [ ] Any dependent changes have been merged and published

## Related Issues
Closes #123 (if fix closes issue)
Related to #456
See #789

## Additional Notes
[Any additional information for reviewer]
```

---

## Code Review

### What Reviewers Look For

1. **Functionality**: Does code work as intended? Edge cases covered?
2. **Security**: SQL injection, XSS, CSRF, authorization checks, input validation
3. **Performance**: N+1 queries, efficient algorithms, caching considerations
4. **Maintainability**: Readable, well-structured, DRY, SOLID principles
5. **Testing**: Adequate coverage (80%+), meaningful assertions, edge cases
6. **Documentation**: PHPDoc untuk public APIs, README updates
7. **Coding Standards**: PSR-12, Laravel conventions

### Review Process

- At least **1 approval** required untuk merge
- CI checks must pass (tests, style, static analysis)
- No merge conflicts
- Branch up-to-date dengan target branch (develop)

### Responding to Reviews

- Address all comments (implement changes atau respond with reasoning)
- Push new commits ke same branch (no force-push after review started)
- Mark resolved comments di GitHub UI
- If disagree, discuss politely (reviewer adalah嘉宾, not adversary)

### Common Review Feedback

| Issue | Example | Fix |
|-------|---------|-----|
| N+1 Query | `User::all()->each(fn($u) => $u->posts)` | Use `with('posts')` eager loading |
| Missing Validation | `$request->all()` tanpa validation | Use Form Request atau `$request->validate()` |
| Hard-coded Values | `'admin@example.com'` | Use config: `config('mail.from.address')` |
| Debug Code Left | `dd($var)`, `dump($var)` | Remove all debugging statements |
| Missing PHPDoc | Public method tanpa docblock | Add `/** ... */` dengan @param, @return |
| Inefficient Loop | Loop查询 dalam loop | Use eager loading atau single query |
| Wrong HTTP Status | Return 200 untuk create | Return 201 untuk POST create |

---

## Issue Reporting

### Bug Reports

**Before creating bug report:**
1. Search existing issues
2. Ensure using latest version
3. Gather reproduction steps
4. Check logs di `storage/logs/laravel.log`

**Template:**

```markdown
## Bug Description
[Clear description of bug]

## Steps To Reproduce
1. Go to '...'
2. Click on '....'
3. See error

## Expected Behavior
[What you expected to happen]

## Actual Behavior
[What actually happened]

## Screenshots
[If applicable]

## Environment
- SIMRS Version: [e.g., 1.0.0]
- PHP Version: [e.g., 8.2.14]
- Database: [e.g., MySQL 8.0.36]
- Browser: [e.g., Chrome 120]
- OS: [e.g., Windows 11, Ubuntu 22.04]

## Additional Context
[Logs, error messages, etc. Paste relevant log output below]

```
[Paste log output here]
```
```

### Feature Requests

We use GitHub Discussions untuk feature requests (more interactive). Before submitting:
1. Check [ROADMAP.md](./ROADMAP.md) (might already be planned)
2. Search existing requests
3. Describe use case, not just implementation
4. Explain benefits untuk hospital/medical workflow

**Template:**
```markdown
## Feature Description
[What you want to happen]

## Problem Statement
[What problem does this solve?]

## Proposed Solution
[How should it work?]

## Alternatives Considered
[Other ways we could solve this]

## Impact
- Users affected: [All doctors / Admin only / etc]
- Estimated effort: [Small / Medium / Large]
- Priority: [Must-have / Should-have / Nice-to-have]
```

---

## Documentation

Good documentation is作为 important как good code!

### Types of Documentation

1. **README.md** - First-time user guide
   - Installation steps
   - Quick start
   - Tech stack overview
   - Links untuk detailed guides

2. **User Guides** (`docs/user-guide/`) - End-user documentation
   - Step-by-step procedures
   - Screenshots
   - Troubleshooting

3. **API Docs** (`docs/api/`) - For developers integrating
   - Endpoint specifications
   - Request/response examples
   - Error codes
   - Authentication

4. **Development Guides** (`docs/DEVELOPMENT.md`) - For developers
   - Coding standards
   - Architecture decisions
   - Testing approaches

5. **Deployment Guides** (`docs/DEPLOYMENT.md`) - For sysadmins
   - Server requirements
   - Configuration
   - Monitoring

6. **Inline Documentation** - Code comments, PHPDoc

### Documentation Guidelines

- Write in **English** (project official language)
- Use **active voice**: "Click the button" not "The button should be clicked"
- Include **screenshots** untuk UI documentation (place in `docs/images/`)
- Use **tables** untuk structured information
- Keep **examples** up-to-date dengan current code
- Test commands/docs before committing (copy-paste should work)

### Updating Documentation

Jika Anda change functionality:
1. Update relevant `.md` file(s)
2. Update inline PHPDoc jika public API
3. Add examples if helpful
4. Commit dengan `docs:` prefix:
   ```bash
   git commit -m "docs(api): update patient endpoint response format

   Add example response untuk single patient fetch.

   Closes #123"
   ```

---

## Security Disclosures

**DO NOT** create public issue untuk security vulnerabilities.

See [SECURITY.md](./SECURITY.md) untuk responsible disclosure process.

---

## Recognition

贡献者 akan dikenali di:
- [CONTRIBUTORS.md](./CONTRIBUTORS.md) (auto-generated from git)
- Release notes (major contributions)
- Hall of Fame di SECURITY.md (security researchers)

---

## Questions?

- **GitHub Issues**: https://github.com/muhammad-zainal-muttaqin/RumahSakitKu/issues
- **GitHub Discussions**: https://github.com/muhammad-zainal-muttaqin/RumahSakitKu/discussions
- **Email**: dev@rumahsakitku.com
- **WhatsApp**: +62 XXX-XXXX-XXXX (core team)

---

Thank you for contributing! 🎉

---

*Last Updated: 2026-02-14*  
*Based on [Contributor Covenant](https://www.contributor-covenant.org/) v2.1*
