# WordPuzzleGame

A Symfony 6.4+ word puzzle game where students create words from a given set of letters. Built with PHP 8.1+, Doctrine ORM, and follows PSR-12 coding standards.

## Features

- **Puzzle Generation**: Creates 14-letter puzzles guaranteed to allow at least one valid word
- **Word Validation**: Validates submissions against a real English dictionary API
- **Score Tracking**: Awards 1 point per letter used in valid words
- **Session Management**: Tracks each student's puzzle state and progress
- **Leaderboard**: Maintains top 10 highest-scoring submissions
- **RESTful API**: Clean JSON API with proper error handling
- **Unit Tests**: Comprehensive test coverage for all core business logic
- **OpenAPI Documentation**: Auto-generated API documentation

## Requirements

- PHP 8.1+
- Symfony 6.4+
- SQLite (default) or MySQL/PostgreSQL
- Composer

## Installation

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd WordPuzzleGame
   ```

2. **Install dependencies**:
   ```bash
   composer install
   ```

3. **Configure environment**:
   ```bash
   # Copy environment file
   cp .env .env.local
   
   # Edit .env.local and set your database URL
   # For SQLite (default):
   DATABASE_URL="sqlite:///%kernel.project_dir%/var/app.db"
   
   # For MySQL:
   DATABASE_URL="mysql://username:password@127.0.0.1:3306/wordpuzzlegame"
   
   # For PostgreSQL:
   # DATABASE_URL="postgresql://username:password@localhost:5432/wordpuzzlegame"
   ```

4. **Create database and run migrations**:
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. **Generate sample data** (optional):
   ```bash
   php bin/console app:generate-puzzle-data --students=5 --submissions=3
   ```

6. **Start the development server**:
   ```bash
   symfony server:start
   ```

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

## API Documentation

Interactive API documentation is available at:
- **Swagger UI**: `http://localhost:8000/api/doc`
- **JSON Schema**: `http://localhost:8000/api/doc.json`

## Architecture

### Domain Entities

1. **Puzzle**: Represents a 14-letter puzzle with remaining letters and submissions
2. **Student**: Represents a student session with puzzle relationship
3. **Submission**: Represents a word submission with score and timestamp
4. **LeaderboardEntry**: Represents top-scoring words for the leaderboard

### Service Layer

1. **GameService**: Core business logic for puzzle creation, word submission, and score calculation
2. **DictionaryService**: Handles English word validation using external API with caching

### Key Design Principles

- **Clean Architecture**: Separation of concerns with clear boundaries
- **Domain-Driven Design**: Rich domain models with business logic
- **Repository Pattern**: Data access abstraction
- **Service Layer**: Business logic encapsulation
- **Exception Handling**: Proper HTTP status codes and error messages
- **Caching**: Dictionary API responses to improve performance
- **Validation**: Input validation at multiple layers

### Database Schema

```sql
-- Puzzles table
CREATE TABLE puzzle (
    id INTEGER PRIMARY KEY,
    puzzle_string VARCHAR(14) NOT NULL,
    remaining_letters VARCHAR(14) NOT NULL,
    created_at DATETIME NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT 1
);

-- Students table
CREATE TABLE student (
    id INTEGER PRIMARY KEY,
    session_id VARCHAR(255) UNIQUE NOT NULL,
    created_at DATETIME NOT NULL,
    last_activity DATETIME NOT NULL,
    puzzle_id INTEGER NOT NULL,
    FOREIGN KEY (puzzle_id) REFERENCES puzzle (id)
);

-- Submissions table
CREATE TABLE submission (
    id INTEGER PRIMARY KEY,
    word VARCHAR(255) NOT NULL,
    score INTEGER NOT NULL,
    submitted_at DATETIME NOT NULL,
    puzzle_id INTEGER NOT NULL,
    FOREIGN KEY (puzzle_id) REFERENCES puzzle (id)
);

-- Leaderboard entries table
CREATE TABLE leaderboard_entry (
    id INTEGER PRIMARY KEY,
    word VARCHAR(255) UNIQUE NOT NULL,
    score INTEGER NOT NULL,
    created_at DATETIME NOT NULL
);
```

## Testing

### Run Tests
```bash
# Run all tests
php bin/phpunit

# Run specific test suite
php bin/phpunit tests/Service/GameServiceTest.php

# Run with coverage report
php bin/phpunit --coverage-html var/coverage
```

### Test Coverage
- **GameService**: Core business logic, puzzle creation, word submission
- **DictionaryService**: Word validation and caching
- **Entities**: Domain model behavior and relationships
- **API Controllers**: Endpoint functionality and error handling

## Development

### Code Standards
- Follows PSR-12 coding standards
- Uses PHP 8.1+ features (typed properties, constructor promotion)
- Comprehensive PHPDoc annotations
- OpenAPI/Swagger documentation

### Commands

```bash
# Generate puzzle data for testing
php bin/console app:generate-puzzle-data [--students=5] [--submissions=3] [--clear]

# Clear cache
php bin/console cache:clear

# Update database schema
php bin/console doctrine:schema:update --force

# Create new migration
php bin/console make:migration
```

## Deployment

### Production Setup
1. Set `APP_ENV=prod` in `.env.local`
2. Clear cache: `php bin/console cache:clear --env=prod`
3. Run migrations: `php bin/console doctrine:migrations:migrate --env=prod`
4. Configure web server (Apache/Nginx) to serve `public/` directory

### Environment Variables
- `DATABASE_URL`: Database connection string
- `APP_ENV`: Environment (dev/prod)
- `APP_SECRET`: Application secret for security

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make changes following PSR-12 standards
4. Add tests for new functionality
5. Submit a pull request

## License

This project is licensed under the MIT License.

## Support

For support and questions, please contact the development team or create an issue in the repository. 