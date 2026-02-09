# Development Guide - SIMRS

> Sistem Informasi Manajemen Rumah Sakit - Development Guidelines

## Table of Contents

- [Prerequisites](#prerequisites)
- [Setup Development Environment](#setup-development-environment)
- [Coding Standards](#coding-standards)
- [Git Workflow](#git-workflow)
- [Testing Guidelines](#testing-guidelines)
- [Pull Request Process](#pull-request-process)
- [Code Review Checklist](#code-review-checklist)
- [Troubleshooting](#troubleshooting)

---

## Prerequisites

Before you begin, ensure you have the following installed:

- **PHP** >= 8.2 with extensions:
  - BCMath
  - Ctype
  - cURL
  - DOM
  - Fileinfo
  - JSON
  - Mbstring
  - OpenSSL
  - PCRE
  - PDO
  - PDO_MySQL
  - Tokenizer
  - XML
  - Zip

- **Composer** >= 2.0
- **Node.js** >= 18.x and **npm** >= 9.x
- **MySQL** >= 8.0 or **MariaDB** >= 10.6
- **Git**

### Optional but Recommended

- **Laravel Valet** (macOS) or **Laravel Herd** (cross-platform)
- **Docker** and **Docker Compose**
- **Make** (for using Makefile commands)

---

## Setup Development Environment

### 1. Clone the Repository

```bash
git clone https://github.com/your-org/simrs.git
cd simrs
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### 3. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

Edit `.env` file with your local database credentials:

```env
APP_NAME=SIMRS
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simrs_dev
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 4. Database Setup

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE simrs_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
php artisan migrate

# Run seeders (optional, for sample data)
php artisan db:seed
```

### 5. Storage & Cache

```bash
# Create storage symbolic link
php artisan storage:link

# Set permissions (Linux/Mac)
chmod -R 775 storage bootstrap/cache
```

### 6. Build Assets

```bash
# Development build
npm run dev

# Production build
npm run build
```

### 7. Start Development Server

```bash
# Using Artisan
php artisan serve

# Using Makefile
make serve

# Or using Laravel Valet
valet link simrs
valet secure simrs
```

---

## Coding Standards

We follow **PSR-12** and **Laravel PER** coding standards.

### PHP Style Guide

#### Naming Conventions

| Type | Convention | Example |
|------|------------|---------|
| Classes | PascalCase | `PatientController`, `UserService` |
| Methods | camelCase | `getPatientDetails()`, `processPayment()` |
| Variables | camelCase | `$patientName`, `$totalAmount` |
| Constants | UPPER_SNAKE_CASE | `MAX_RETRY_ATTEMPTS`, `API_VERSION` |
| Database Tables | snake_case, plural | `patient_records`, `medical_histories` |
| Model Properties | snake_case | `$first_name`, `$date_of_birth` |

#### Code Formatting

Use PHP CS Fixer to maintain consistent formatting:

```bash
# Check code style
make format-check

# Fix code style issues
make format
```

#### Type Declarations

Always use strict typing and explicit type declarations:

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
        // Implementation
    }
}
```

### Laravel Best Practices

#### Controllers

- Keep controllers thin
- Delegate business logic to services
- Use Form Request classes for validation
- Use Resource classes for API responses

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientRequest;
use App\Http\Resources\PatientResource;
use App\Services\PatientService;

class PatientController extends Controller
{
    public function __construct(
        private readonly PatientService $patientService
    ) {}

    public function store(StorePatientRequest $request): PatientResource
    {
        $patient = $this->patientService->create($request->validated());
        
        return new PatientResource($patient);
    }
}
```

#### Models

- Use type hints for attributes
- Define relationships explicitly
- Use accessors/mutators sparingly
- Prefer query scopes for reusable filters

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $nik
 * @property \Carbon\Carbon $date_of_birth
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MedicalRecord> $medicalRecords
 */
class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'nik',
        'date_of_birth',
        'address',
        'phone',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

#### Services

Business logic should be encapsulated in service classes:

```php
<?php

namespace App\Services;

use App\Models\Patient;
use App\Repositories\PatientRepository;
use Illuminate\Support\Facades\DB;

class PatientService
{
    public function __construct(
        private readonly PatientRepository $repository
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Patient
    {
        return DB::transaction(function () use ($data) {
            $patient = $this->repository->create($data);
            
            // Additional business logic here
            
            return $patient;
        });
    }
}
```

### Filament Resources

When creating Filament admin panels, follow these conventions:

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PatientResource\Pages;
use App\Models\Patient;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Manajemen Pasien';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Dasar')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nik')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(16),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nik')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gender')
                    ->options([
                        'male' => 'Laki-laki',
                        'female' => 'Perempuan',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPatients::route('/'),
            'create' => Pages\CreatePatient::route('/create'),
            'edit' => Pages\EditPatient::route('/{record}/edit'),
        ];
    }
}
```

---

## Git Workflow

We use **Git Flow** with the following branch structure:

```
main        - Production releases (stable)
develop     - Development branch (integration)
feature/*   - New features
hotfix/*    - Critical production fixes
release/*   - Release preparation
```

### Branch Naming

| Type | Pattern | Example |
|------|---------|---------|
| Feature | `feature/TICKET-short-desc` | `feature/SIM-123-patient-registration` |
| Bugfix | `bugfix/TICKET-short-desc` | `bugfix/SIM-456-fix-login-redirect` |
| Hotfix | `hotfix/TICKET-short-desc` | `hotfix/SIM-789-critical-security-fix` |
| Release | `release/vX.X.X` | `release/v1.2.0` |

### Commit Messages

Follow **Conventional Commits** specification:

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Build process or auxiliary tool changes

**Examples:**

```
feat(patient): add patient registration form

Implement patient registration using Filament forms
with NIK validation and auto-generated medical record number.

Closes SIM-123
```

```
fix(auth): resolve session timeout issue

Increase session lifetime from 30 to 120 minutes
to prevent unexpected logouts during data entry.

Fixes SIM-456
```

### Workflow Steps

1. **Create Feature Branch**
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b feature/SIM-123-patient-registration
   ```

2. **Make Changes & Commit**
   ```bash
   git add .
   git commit -m "feat(patient): add patient registration form"
   ```

3. **Push Branch**
   ```bash
   git push -u origin feature/SIM-123-patient-registration
   ```

4. **Create Pull Request** to `develop` branch

5. **After Merge**, delete the feature branch
   ```bash
   git checkout develop
   git pull origin develop
   git branch -d feature/SIM-123-patient-registration
   ```

---

## Testing Guidelines

### Test Structure

```
tests/
├── Feature/          # Integration & feature tests
│   ├── Api/
│   ├── Http/
│   └── Filament/
├── Unit/             # Unit tests
│   ├── Services/
│   └── Repositories/
└── CreatesApplication.php
```

### Writing Tests

```php
<?php

namespace Tests\Feature\Api;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_patients(): void
    {
        $user = User::factory()->create();
        Patient::factory()->count(5)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/patients');

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_can_create_patient(): void
    {
        $user = User::factory()->create();
        $data = [
            'name' => 'John Doe',
            'nik' => '1234567890123456',
            'date_of_birth' => '1990-01-01',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/patients', $data);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'John Doe');

        $this->assertDatabaseHas('patients', [
            'nik' => '1234567890123456',
        ]);
    }

    public function test_cannot_create_patient_with_invalid_nik(): void
    {
        $user = User::factory()->create();
        $data = [
            'name' => 'John Doe',
            'nik' => 'invalid',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/patients', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['nik']);
    }
}
```

### Running Tests

```bash
# Run all tests
make test

# Run with coverage
make test-coverage

# Run specific test file
php artisan test tests/Feature/Api/PatientControllerTest.php

# Run tests matching pattern
php artisan test --filter=Patient

# Run tests in parallel
make test-parallel
```

### Test Coverage Requirements

- **Minimum Coverage**: 80%
- **Critical Components**: 90%+
  - Services
  - Repositories
  - Controllers

---

## Pull Request Process

### Before Creating PR

1. **Run Quality Checks**
   ```bash
   make lint
   make test
   ```

2. **Update Branch**
   ```bash
   git checkout develop
   git pull origin develop
   git checkout feature/your-branch
   git rebase develop
   ```

3. **Resolve Conflicts** if any

### PR Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Unit tests added/updated
- [ ] Feature tests added/updated
- [ ] All tests passing

## Checklist
- [ ] Code follows style guidelines
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] No console errors

## Screenshots (if applicable)
```

### PR Review Requirements

- At least **1 approval** required
- All **CI checks must pass**
- **No merge conflicts**
- **Branch must be up-to-date** with target

---

## Code Review Checklist

### General

- [ ] Code follows PSR-12/Laravel PER standards
- [ ] No hardcoded values (use config/constants)
- [ ] No debug code (`dd()`, `dump()`, `var_dump()`)
- [ ] Proper error handling implemented
- [ ] Logging added where appropriate

### Security

- [ ] Input validation implemented
- [ ] SQL injection prevention (use Eloquent/Query Builder)
- [ ] XSS prevention (use `{{ }}` in Blade)
- [ ] Authorization checks using policies/gates
- [ ] No sensitive data in logs or error messages
- [ ] CSRF tokens in forms

### Performance

- [ ] Database queries optimized (use eager loading)
- [ ] Caching implemented where beneficial
- [ ] No N+1 query problems
- [ ] Appropriate use of indexes

### Testing

- [ ] Unit tests for business logic
- [ ] Feature tests for endpoints
- [ ] Edge cases covered
- [ ] Tests are deterministic

### Documentation

- [ ] PHPDoc comments for public methods
- [ ] Complex logic explained
- [ ] README updated if needed
- [ ] API documentation updated

---

## Troubleshooting

### Common Issues

#### Permission Denied
```bash
chmod -R 775 storage bootstrap/cache
sudo chown -R $USER:$USER storage bootstrap/cache
```

#### Composer Memory Limit
```bash
COMPOSER_MEMORY_LIMIT=-1 composer install
```

#### Database Connection
```bash
# Test connection
mysql -u root -p -e "SHOW DATABASES;"

# Reset database
php artisan migrate:fresh --seed
```

#### NPM Build Errors
```bash
# Clear npm cache
npm cache clean --force
rm -rf node_modules package-lock.json
npm install
npm run build
```

#### PHPStan Errors
```bash
# Generate baseline for existing issues
make analyze-generate-baseline
```

### Getting Help

- Check [Issues](https://github.com/your-org/simrs/issues)
- Review [Documentation](./)
- Contact: dev-team@your-hospital.com

---

## Resources

- [Laravel Documentation](https://laravel.com/docs/12.x)
- [Filament Documentation](https://filamentphp.com/docs)
- [PHP Standards](https://www.php-fig.org/psr/)
- [Conventional Commits](https://www.conventionalcommits.org/)

---

**Last Updated**: 2026-02-08
