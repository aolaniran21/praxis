# Database Design

The schema is relational and uses foreign keys, unique constraints, indexes, and PostgreSQL boolean columns. Linked academic records generally use restricted deletion so that historical registrations and results are not silently removed. Archiving is represented by `is_active` flags.

## Entity relationship diagram

```mermaid
erDiagram
    FACULTIES ||--o{ DEPARTMENTS : contains
    DEPARTMENTS ||--o{ PROGRAMS : offers
    DEPARTMENTS ||--o{ COURSES : owns
    DEPARTMENTS ||--o{ LECTURERS : employs
    DEPARTMENTS o|--o{ USERS : scopes
    PROGRAMS ||--o{ STUDENTS : enrolls
    USERS ||--o| STUDENTS : student_profile
    USERS ||--o| LECTURERS : lecturer_profile

    ACADEMIC_SESSIONS ||--o{ SEMESTERS : contains
    COURSES ||--o{ COURSE_ASSIGNMENTS : assigned
    LECTURERS ||--o{ COURSE_ASSIGNMENTS : teaches
    SEMESTERS ||--o{ COURSE_ASSIGNMENTS : scheduled_for

    STUDENTS ||--o{ COURSE_REGISTRATIONS : registers
    COURSES ||--o{ COURSE_REGISTRATIONS : selected_for
    SEMESTERS ||--o{ COURSE_REGISTRATIONS : during
    COURSE_REGISTRATIONS ||--o| RESULTS : produces

    COURSES ||--o{ COURSE_PREREQUISITES : has
    COURSES ||--o{ COURSE_PREREQUISITES : required_course

    RESULTS ||--o{ RESULT_AUDIT_LOGS : records
    USERS o|--o{ RESULT_AUDIT_LOGS : acts
    COURSE_REGISTRATIONS ||--o{ COURSE_REGISTRATION_AUDIT_LOGS : records
    USERS o|--o{ COURSE_REGISTRATION_AUDIT_LOGS : acts

    USERS ||--o{ MODEL_HAS_ROLES : assigned
    ROLES ||--o{ MODEL_HAS_ROLES : grants
    ROLES ||--o{ ROLE_HAS_PERMISSIONS : contains
    PERMISSIONS ||--o{ ROLE_HAS_PERMISSIONS : included
    USERS ||--o{ MODEL_HAS_PERMISSIONS : assigned
    PERMISSIONS ||--o{ MODEL_HAS_PERMISSIONS : grants

    FACULTIES {
        bigint id PK
        string name UK
        string code UK
        boolean is_active
    }
    DEPARTMENTS {
        bigint id PK
        bigint faculty_id FK
        string name
        string code UK
        boolean is_active
    }
    PROGRAMS {
        bigint id PK
        bigint department_id FK
        string name
        string code UK
        boolean is_active
    }
    ACADEMIC_SESSIONS {
        bigint id PK
        string name UK
        date starts_on
        date ends_on
        boolean is_active
    }
    SEMESTERS {
        bigint id PK
        bigint academic_session_id FK
        string type
        date starts_on
        date ends_on
        boolean is_current
        boolean is_active
    }
    USERS {
        bigint id PK
        bigint department_id FK
        string name
        string email UK
        string password
        boolean is_active
    }
    STUDENTS {
        bigint id PK
        bigint user_id FK, UK
        bigint program_id FK
        string student_number UK
        smallint level
        boolean is_active
    }
    LECTURERS {
        bigint id PK
        bigint user_id FK, UK
        bigint department_id FK
        string staff_number UK
    }
    COURSES {
        bigint id PK
        bigint department_id FK
        string code UK
        string title
        smallint credit_units
        smallint level
        string semester_type
        boolean is_active
    }
    COURSE_PREREQUISITES {
        bigint course_id PK, FK
        bigint prerequisite_course_id PK, FK
    }
    COURSE_ASSIGNMENTS {
        bigint id PK
        bigint course_id FK
        bigint lecturer_id FK
        bigint semester_id FK
        boolean is_active
    }
    COURSE_REGISTRATIONS {
        bigint id PK
        bigint student_id FK
        bigint course_id FK
        bigint semester_id FK
        string status
        timestamp dropped_at
    }
    RESULTS {
        bigint id PK
        bigint course_registration_id FK, UK
        smallint ca_score
        smallint exam_score
        smallint total_score
        string grade
        decimal grade_point
        string status
        timestamp published_at
    }
    RESULT_AUDIT_LOGS {
        bigint id PK
        bigint result_id FK
        bigint actor_id FK
        string action
        string from_status
        string to_status
        text feedback
        json metadata
    }
    COURSE_REGISTRATION_AUDIT_LOGS {
        bigint id PK
        bigint course_registration_id FK
        bigint actor_id FK
        string action
        string from_status
        string to_status
        text feedback
    }
    ROLES {
        bigint id PK
        string name
        string guard_name
    }
    PERMISSIONS {
        bigint id PK
        string name
        string guard_name
    }
    MODEL_HAS_ROLES {
        bigint role_id PK, FK
        string model_type PK
        bigint model_id PK
    }
    ROLE_HAS_PERMISSIONS {
        bigint permission_id PK, FK
        bigint role_id PK, FK
    }
    MODEL_HAS_PERMISSIONS {
        bigint permission_id PK, FK
        string model_type PK
        bigint model_id PK
    }
```

`MODEL_HAS_ROLES` and `MODEL_HAS_PERMISSIONS` are Spatie polymorphic assignments: `model_type` and `model_id` identify the role-bearing model (currently `users`). `COURSE_PREREQUISITES` is a self-referencing many-to-many relation between courses. The database also has framework support tables for sessions, password reset tokens, cache, jobs, and migrations; they are omitted from this academic-domain diagram.

## Important constraints

- A student and lecturer each have at most one profile per user (`user_id` is unique).
- A course can be registered once per student per semester (`student_id`, `course_id`, `semester_id` unique). Dropping and re-adding updates the same registration and records the transition.
- Each registration has at most one result (`results.course_registration_id` is unique), preserving the specific course attempt.
- A course assignment is unique per course, lecturer, and semester.
- One active academic session and one current semester are protected by partial unique indexes.
- Deleting referenced academic structures is restricted; audit actor references become null if an actor account is deleted, and audit history cascades with its result.
- Result records hold score totals, calculated grade/point, workflow state, timestamps, and feedback. Detailed state transitions are separately recorded in `result_audit_logs`.
