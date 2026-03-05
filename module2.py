"""
Gap2Grow – Skill Gap Analyzer
MODULE 2: Skill Gap Analysis Engine (Production Ready)

Handles:
- Invalid user IDs
- Invalid job titles
- Missing skills
- Empty tables
- Safe readiness score calculation
"""

import psycopg2
import json
from psycopg2.extras import RealDictCursor


# -------------------------------------------------------
# DATABASE CONNECTION
# -------------------------------------------------------

def get_connection():
    try:
        return psycopg2.connect(
            dbname="gap2grow",
            user="postgres",
            password="root",
            host="localhost",
            port="5432"
        )
    except Exception as e:
        raise RuntimeError("Database connection failed") from e


# -------------------------------------------------------
# SKILL GAP ANALYZER
# -------------------------------------------------------

class SkillGapAnalyzer:

    def __init__(self, user_id: int, job_title: str):
        self.user_id = user_id
        self.job_title = job_title.strip()
        self.conn = get_connection()
        self.cursor = self.conn.cursor(cursor_factory=RealDictCursor)

    # ---------------------------------------------------
    # Validation Helpers
    # ---------------------------------------------------

    def user_exists(self) -> bool:
        self.cursor.execute(
            "SELECT 1 FROM users WHERE user_id = %s",
            (self.user_id,)
        )
        return self.cursor.fetchone() is not None

    def get_job_id(self):
        self.cursor.execute(
            "SELECT job_id FROM job_roles WHERE job_title = %s",
            (self.job_title,)
        )
        row = self.cursor.fetchone()
        return row["job_id"] if row else None

    # ---------------------------------------------------
    # Fetch User Skills
    # ---------------------------------------------------

    def fetch_user_skills(self) -> dict:
        self.cursor.execute(
            """
            SELECT skill_name, skill_type, proficiency
            FROM user_skills
            WHERE user_id = %s
            """,
            (self.user_id,)
        )

        skills = {}
        for row in self.cursor.fetchall():
            try:
                skills[row["skill_name"].lower()] = {
                    "type": row["skill_type"] or "education",
                    "level": int(row["proficiency"])
                }
            except ValueError:
                # Skip corrupted data safely
                continue

        return skills

    # ---------------------------------------------------
    # Fetch Job Required Skills
    # ---------------------------------------------------

    def fetch_job_skills(self, job_id: int) -> list:
        self.cursor.execute(
            """
            SELECT skill_name, required_level
            FROM job_required_skills
            WHERE job_id = %s
            """,
            (job_id,)
        )

        skills = []
        for row in self.cursor.fetchall():
            try:
                skills.append({
                    "skill": row["skill_name"].lower(),
                    "required_level": int(row["required_level"])
                })
            except ValueError:
                continue

        return skills

    # ---------------------------------------------------
    # Core Analysis Logic
    # ---------------------------------------------------

    def analyze(self) -> dict:

        # ---------- Validation ----------
        if not self.user_exists():
            return {"status": "error", "message": "User not found"}

        job_id = self.get_job_id()
        if not job_id:
            return {"status": "error", "message": "Job role not found"}

        user_skills = self.fetch_user_skills()
        job_skills = self.fetch_job_skills(job_id)

        if not job_skills:
            return {"status": "error", "message": "No skills defined for this job role"}

        # ---------- Analysis ----------
        education = {"matched": [], "partial": [], "missing": []}
        job = {"matched": [], "partial": [], "missing": []}

        total_score = 0
        total_required = len(job_skills)

        for req in job_skills:
            skill = req["skill"]
            required_level = req["required_level"]

            user_skill = user_skills.get(skill)
            skill_type = user_skill["type"] if user_skill else "education"
            bucket = education if skill_type == "education" else job

            if user_skill:
                user_level = user_skill["level"]

                if user_level >= required_level:
                    bucket["matched"].append(skill)
                    total_score += 1
                elif required_level - user_level <= 2:
                    bucket["partial"].append(skill)
                    total_score += 0.5
                else:
                    bucket["missing"].append(skill)
            else:
                bucket["missing"].append(skill)

        readiness = round((total_score / total_required) * 100)

        # ---------- Persist Result ----------
        self.save_result(job_id, education, job, readiness)

        return {
            "status": "success",
            "user_id": self.user_id,
            "job_title": self.job_title,
            "readiness_score": readiness,
            "education_skill_upgrade_set": education,
            "job_finding_skill_set": job
        }

    # ---------------------------------------------------
    # Save Analysis Result
    # ---------------------------------------------------

    def save_result(self, job_id, edu, job, score):

        matched = edu["matched"] + job["matched"]
        missing = edu["missing"] + job["missing"]

        self.cursor.execute(
            """
            INSERT INTO skill_gap_results
            (user_id, job_id, matched_skills, missing_skills, gap_score)
            VALUES (%s, %s, %s, %s, %s)
            """,
            (
                self.user_id,
                job_id,
                json.dumps(matched),
                json.dumps(missing),
                score
            )
        )

        self.conn.commit()


# -------------------------------------------------------
# Example Run
# -------------------------------------------------------

if __name__ == "__main__":

    analyzer = SkillGapAnalyzer(
        user_id=2,
        job_title="Data Analyst"
    )

    result = analyzer.analyze()
    print(json.dumps(result, indent=4))