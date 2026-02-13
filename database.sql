CREATE DATABASE gap2grow;

\c gap2grow;

CREATE TABLE users (
    user_id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_skills (
    skill_id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    skill_name VARCHAR(100) NOT NULL,
    skill_type VARCHAR(50),
    proficiency VARCHAR(50),
    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
);

CREATE TABLE job_roles (
    job_id SERIAL PRIMARY KEY,
    job_title VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE job_required_skills (
    req_id SERIAL PRIMARY KEY,
    job_id INT NOT NULL,
    skill_name VARCHAR(100) NOT NULL,
    required_level VARCHAR(50),
    FOREIGN KEY (job_id)
        REFERENCES job_roles(job_id)
        ON DELETE CASCADE
);

CREATE TABLE skill_gap_results (
    result_id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    job_id INT NOT NULL,
    matched_skills TEXT[],
    missing_skills TEXT[],
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
    recommendation_id SERIAL PRIMARY KEY,
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
    progress_id SERIAL PRIMARY KEY,
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
