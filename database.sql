CREATE DATABASE gap2grow;

\c gap2grow;

CREATE TABLE users (
    user_id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_skills (
    skill_id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id INT NOT NULL,
    skill_name VARCHAR(100) NOT NULL,
    skill_type VARCHAR(50) CHECK (skill_type IN ('education','job')),
    proficiency INT CHECK (proficiency BETWEEN 1 AND 5),
    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
);

CREATE TABLE job_roles (
    job_id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    job_title VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
);

CREATE TABLE job_required_skills (
    req_id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    job_id INT NOT NULL,
    skill_name VARCHAR(100) NOT NULL,
    required_level INT CHECK (required_level BETWEEN 1 AND 5),
    FOREIGN KEY (job_id)
        REFERENCES job_roles(job_id)
        ON DELETE CASCADE
);

CREATE TABLE skill_gap_results (
    result_id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id INT NOT NULL,
    job_id INT NOT NULL,
    matched_skills JSON,
    missing_skills JSON,
    gap_score INT CHECK (gap_score BETWEEN 0 AND 100),
    analyzed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,
    FOREIGN KEY (job_id)
        REFERENCES job_roles(job_id)
        ON DELETE CASCADE
);

CREATE TABLE recommendations (
    recommendation_id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id INT NOT NULL,
    skill_name VARCHAR(100) NOT NULL,
    resource_type VARCHAR(50),
    resource_title VARCHAR(200),
    resource_link TEXT,
    difficulty VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
);

CREATE TABLE user_progress (
    progress_id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id INT NOT NULL,
    recommendation_id INT NOT NULL,
    status VARCHAR(50) CHECK (status IN ('Pending','In Progress','Completed')),
    progress_percent INT CHECK (progress_percent BETWEEN 0 AND 100),
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,
    FOREIGN KEY (recommendation_id)
        REFERENCES recommendations(recommendation_id)
        ON DELETE CASCADE
);

CREATE TABLE skill_resources (
    resource_id SERIAL PRIMARY KEY,
    skill_name VARCHAR(100),
    platform VARCHAR(100),
    resource_title VARCHAR(200),
    resource_link TEXT,
    difficulty VARCHAR(50)
);

CREATE TABLE learning_roadmaps (
    roadmap_id SERIAL PRIMARY KEY,
    user_id INT,
    job_id INT,
    roadmap_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);