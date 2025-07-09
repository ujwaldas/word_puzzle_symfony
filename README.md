# 🎯 Word Puzzle Game - Symfony Backend

A robust backend service for a word puzzle game where students create English words from a given set of letters. Built with Symfony 6+ and PHP 8.1+.

## 📋 Table of Contents

- [Features](#-features)
- [Technology Stack](#-technology-stack)
- [Project Structure](#-project-structure)
- [Installation & Setup](#-installation--setup)
- [API Documentation](#-api-documentation)
- [Game Workflow](#-game-workflow)
- [Testing](#-testing)
- [Development](#-development)
- [Deployment](#-deployment)

## ✨ Features

### Core Game Features
- **Random Puzzle Generation**: Creates 14-letter puzzles with guaranteed valid words
- **Word Validation**: Validates submissions against English dictionary
- **Letter Management**: Tracks remaining letters and prevents duplicate usage
- **Scoring System**: 1 point per letter used
- **Leaderboard**: Top 10 highest-scoring unique words
- **Game State Management**: Tracks active games and submissions

### Technical Features
- **RESTful API**: Clean, documented endpoints
- **Dependency Injection**: Proper service architecture
- **Unit Testing**: Comprehensive test coverage
- **Database Integration**: PostgreSQL for development, supports other databases
- **Exception Handling**: Proper error responses
- **Caching**: Dictionary word caching for performance

## 🛠 Technology Stack

- **Framework**: Symfony 6+
- **PHP Version**: 8.1+
- **Database**: PostgreSQL (development), MySQL/PostgreSQL (production)
- **Testing**: PHPUnit
- **Frontend**: HTML, CSS, JavaScript (jQuery)

## 📁 Project Structure

```
word_puzzle_symfony/
├── src/
│   ├── Controller/
│   │   └── Api/
│   │       └── GameController.php          # API endpoints
│   ├── Entity/
│   │   ├── Puzzle.php                     # Game puzzle entity
│   │   ├── Student.php                    # Student/session entity
│   │   ├── Submission.php                 # Word submission entity
│   │   └── LeaderboardEntry.php          # Leaderboard entity
│   ├── Repository/
│   │   ├── PuzzleRepository.php
│   │   ├── StudentRepository.php
│   │   ├── SubmissionRepository.php
│   │   └── LeaderboardEntryRepository.php
│   └── Service/
│       ├── GameService.php                # Core game logic
│       └── DictionaryService.php          # Word validation
├── tests/
│   └── Service/
│       └── GameServiceTest.php            # Unit tests
├── templates/
│   └── game/
│       └── index.html.twig               # Game interface
├── public/
│   └── index.php                         # Entry point
└── data/
    └── words.txt                         # Dictionary file
```

## 🚀 Installation & Setup

### Prerequisites
- PHP 8.1 or higher
- Composer
- PostgreSQL (via Docker or local installation)

### Step 1: Clone and Install Dependencies
```bash
# Clone the repository
git clone <repository-url>
cd word_puzzle_symfony

# Install dependencies
composer install
```

### Step 2: Environment Configuration
```bash
# Copy environment file for development
cp .env .env.dev

# Edit .env.dev and configure:
# - Database URL (PostgreSQL for development)
# - App secret
# - Other environment variables
```

### Step 3: Database Setup
```bash
# Create database
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate

### Step 4: Start Development Server
```bash
# Start Symfony development server
php -S localhost:8000 -t public/

# Or use Symfony CLI
symfony server:start
```

### Step 5: Access the Application
- **Web Interface**: http://localhost:8000

## API Endpoints

### 1. Create Puzzle
**POST** `/api/game/puzzle`

Creates a new puzzle for a student session.

**Request Body**:
```json
{
    "sessionId": "student123"
}
```

**Response**:
```json
{
    "puzzleString": "ETAOINSHRDLUCM",
    "remainingLetters": "ETAOINSHRDLUCM",
    "totalScore": 0,
    "isActive": true,
    "submissions": [],
    "createdAt": "2024-01-15 10:30:00"
}
```

### 2. Submit Word
**POST** `/api/game/submit`

Submit a word attempt for the current puzzle.

**Request Body**:
```json
{
    "sessionId": "student123",
    "word": "HEAT"
}
```

**Response**:
```json
{
    "word": "HEAT",
    "score": 4,
    "totalScore": 4,
    "remainingLetters": "STARMINDFIRE",
    "isComplete": false,
    "submissionId": 1
}
```

### 3. Get Puzzle State
**GET** `/api/game/state/{sessionId}`

Get the current state of a student's puzzle.

**Response**:
```json
{
    "puzzleString": "ETAOINSHRDLUCM",
    "remainingLetters": "STARMINDFIRE",
    "totalScore": 4,
    "isActive": true,
    "submissions": [
        {
            "word": "HEAT",
            "score": 4,
            "submittedAt": "2024-01-15 10:35:00"
        }
    ],
    "createdAt": "2024-01-15 10:30:00"
}
```

### 4. Get Leaderboard
**GET** `/api/game/leaderboard`

Get the top 10 highest-scoring submissions.

**Response**:
```json
[
    {
        "word": "HEAT",
        "score": 4,
        "createdAt": "2024-01-15 10:35:00"
    },
    {
        "word": "STAR",
        "score": 4,
        "createdAt": "2024-01-15 10:40:00"
    }
]
```

### Base URL
```
http://localhost:8000/api/game
```

### Endpoints

#### 1. Create Puzzle
```http
POST /api/game/puzzle
Content-Type: application/json

{
    "sessionId": "student123"
}
```

**Response:**
```json
{
    "puzzleString": "ETAOINSHRDLUCM",
    "remainingLetters": "ETAOINSHRDLUCM",
    "totalScore": 0,
    "isActive": true,
    "createdAt": "2024-01-15T10:30:00+00:00"
}
```

#### 2. Submit Word
```http
POST /api/game/submit
Content-Type: application/json

{
    "sessionId": "student123",
    "word": "HEAT"
}
```

**Response:**
```json
{
    "word": "HEAT",
    "score": 4,
    "totalScore": 4,
    "remainingLetters": "STARMINDFIRE",
    "isComplete": false,
    "submissionId": 1
}
```

#### 3. Get Game State
```http
GET /api/game/state/{sessionId}
```

**Response:**
```json
{
    "puzzleString": "ETAOINSHRDLUCM",
    "remainingLetters": "STARMINDFIRE",
    "totalScore": 4,
    "isActive": true,
    "submissions": [
        {
            "word": "HEAT",
            "score": 4,
            "submittedAt": "2024-01-15T10:30:00+00:00"
        }
    ],
    "createdAt": "2024-01-15T10:30:00+00:00"
}
```

#### 4. Get Leaderboard
```http
GET /api/game/leaderboard
```

**Response:**
```json
[
    {
        "word": "HEAT",
        "score": 4,
        "createdAt": "2024-01-15T10:30:00+00:00"
    },
    {
        "word": "STAR",
        "score": 4,
        "createdAt": "2024-01-15T10:31:00+00:00"
    }
]
```

#### 5. End Game
```http
POST /api/game/end
Content-Type: application/json

{
    "sessionId": "student123"
}
```

**Response:**
```json
{
    "remainingWords": ["STAR", "MIND", "FIRE"],
    "totalScore": 8
}
```

### Interactive Documentation
- **Swagger UI**: `http://localhost:8000/api/doc`
- **JSON Schema**: `http://localhost:8000/api/doc.json`

## 🎮 Game Workflow

### 1. **Game Initialization**
- Student enters session ID
- System creates new puzzle with 14 random letters
- Puzzle guaranteed to have at least one valid English word

### 2. **Word Submission Process**
- Student submits a word
- System validates:
  - Word is not empty
  - Word contains only letters
  - Word is not too long (max 14 characters)
  - Word is a valid English word
  - Word can be formed from remaining letters
  - Word hasn't been submitted before

### 3. **Scoring & Letter Management**
- Score = 1 point per letter used
- Used letters are removed from remaining pool
- Total score accumulates across submissions

### 4. **Game Completion**
- Game ends when:
  - No more valid words can be formed
  - Student manually ends the game
- System shows final score and remaining valid words

### 5. **Leaderboard Management**
- Top 10 highest-scoring unique words
- No duplicate words allowed
- Automatic cleanup of lower-scoring entries

## 🧪 Testing

### Running Tests
```bash
# Run all tests
php bin/phpunit

# Run specific test file
php bin/phpunit tests/Service/GameServiceTest.php

# Run specific test method
php bin/phpunit --filter testSubmitWordSuccess

# Run with coverage
php bin/phpunit --coverage-html coverage/
```

### Test Coverage
The project includes comprehensive unit tests covering:

- **Game Creation**: New student, existing student scenarios
- **Word Submission**: Success cases, validation errors
- **Game State**: State retrieval, error handling
- **Leaderboard**: Score management, cleanup
- **Puzzle Logic**: Letter usage, scoring calculation
- **Game End**: Completion logic, remaining words

### Test Structure
```php
class GameServiceTest extends TestCase
{
    // Setup with mocked dependencies
    protected function setUp(): void
    {
        // Create mocks for all dependencies
        // Inject mocks into service
    }

    // Test methods follow AAA pattern:
    // Arrange - Set up test data and mocks
    // Act - Execute the method being tested
    // Assert - Verify expected outcomes
}
```

## 🔧 Development

### Key Services

#### GameService
- **Purpose**: Core game logic and business rules
- **Responsibilities**:
  - Puzzle creation and management
  - Word submission validation
  - Score calculation
  - Game state management
  - Leaderboard updates

#### DictionaryService
- **Purpose**: Word validation and dictionary operations
- **Responsibilities**:
  - English word validation
  - Remaining word calculation
  - Dictionary caching
  - Word frequency analysis

### Database Schema

#### Puzzle Entity
- `id`: Primary key
- `puzzleString`: 14-letter puzzle string
- `remainingLetters`: Available letters for word formation
- `isActive`: Game status
- `createdAt`: Creation timestamp

#### Student Entity
- `id`: Primary key
- `sessionId`: Unique session identifier
- `puzzle`: Associated puzzle
- `lastActivity`: Last activity timestamp

#### Submission Entity
- `id`: Primary key
- `word`: Submitted word
- `score`: Word score
- `puzzle`: Associated puzzle
- `submittedAt`: Submission timestamp

#### LeaderboardEntry Entity
- `id`: Primary key
- `word`: Word entry
- `score`: Word score
- `createdAt`: Entry timestamp

### Error Handling
The application uses proper exception handling:

```php
// Custom exceptions for different scenarios
throw new BadRequestHttpException('Word cannot be empty');
throw new NotFoundHttpException('No active puzzle found');
```

## 🚀 Deployment

### Production Setup

#### 1. Environment Configuration
```bash
# Set production environment
APP_ENV=prod
APP_DEBUG=0

# Configure database
DATABASE_URL="mysql://user:pass@host:port/database"

# Set secret
APP_SECRET=your-secret-key
```

#### 2. Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

#### 3. Database Setup
```bash
php bin/console doctrine:migrations:migrate --env=prod
```

#### 4. Clear Cache
```bash
php bin/console cache:clear --env=prod
```

#### 5. Web Server Configuration
Configure your web server (Apache/Nginx) to point to the `public/` directory.

### Docker Deployment
```dockerfile
FROM php:8.1-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev

# Install PHP extensions
RUN docker-php-ext-install zip pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data var/

EXPOSE 80

CMD ["php", "-S", "0.0.0.0:80", "-t", "public/"]
```

## 📝 Contributing

### Development Workflow
1. **Fork** the repository
2. **Create** a feature branch
3. **Write** tests for new functionality
4. **Implement** the feature
5. **Run** tests to ensure everything works
6. **Submit** a pull request

### Code Standards
- Follow PSR-12 coding standards
- Write comprehensive unit tests
- Document all public methods
- Use dependency injection
- Handle exceptions properly

## 🤝 Support

For support and questions:
- Create an issue in the repository
- Check the API
- Review the test cases for usage examples

---

**Happy Word Puzzling! 🎯** 