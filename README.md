# Mautic_Tabdeal

**This project is based on the official [Mautic](https://github.com/mautic/mautic) repository.**

## About

This repository is a customized version of Mautic with the following additions:
- A custom plugin (CustomFormBundle) that adds an admin page with a form (checkbox + 2 text fields) and stores data in the database according to Mautic standards.
- Docker support and configuration files for easy containerization.
- All JavaScript dependencies are managed with Yarn instead of npm.

> **Note:** All build files, node_modules, and vendor are excluded from the repository for optimal size and best practice.

---

## Installation & Configuration

### Prerequisites
- Docker & Docker Compose

### Installation Steps
1. Clone the repository:
   ```bash
   git clone https://github.com/shahriyar3/tabdeal.git
   cd tabdeal
   ```
2. Build and start the project (all dependencies and builds are handled inside Docker):
   ```bash
   docker compose up -d --build
   ```

### Configuration
- Docker: Main configuration is in `docker-compose.yaml` and `Dockerfile`.
- Database & Services: MySQL and Redis are included as services in `docker-compose.yaml`.
- Environment Variables: Set your environment variables in the `.env` file as needed.

---

## Custom Plugin: CustomFormBundle

### Features
- Adds an admin page in Mautic with a form (checkbox + 2 text fields)
- Data is stored in the database according to Mautic and Symfony standards
- Clean, readable code following Symfony best practices

### Structure
```
plugins/
  CustomFormBundle/
    ├── Config/
    ├── Controller/
    ├── Entity/
    ├── Form/
    ├── Model/
    ├── Resources/
    └── ...
```

---

## Notes
- All build files, node_modules, and vendor are excluded from the repository.
- The project structure and size are kept as close as possible to the official Mautic repository.
- For more information, see the official [Mautic documentation](https://github.com/mautic/mautic).

---

**Task Description:**

> Develop a plugin for Mautic that adds an admin page with a form (checkbox + 2 text fields). The form must follow Mautic standards and store data properly in the database. Code must be clean, readable, and follow Symfony best practices. Bonus: Dockerize the project.
