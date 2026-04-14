import psycopg2
import json
import sys
import uuid
from psycopg2.extras import RealDictCursor


class SkillGapAnalyzer:

    def __init__(self, user_id, job_id):
        self.user_id = int(user_id)
        self.job_id = int(job_id)
        self.run_id = str(uuid.uuid4())

        self.conn = psycopg2.connect(
            dbname="gap2grow",
            user="postgres",
            password="root",
            host="localhost",
            port="5432"
        )
        self.cursor = self.conn.cursor(cursor_factory=RealDictCursor)

    def normalize(self, text):
        return text.strip().lower() if text else ""

    def fetch_job(self):
        self.cursor.execute(
            "SELECT job_title FROM job_roles WHERE job_id=%s",
            (self.job_id,)
        )
        return self.cursor.fetchone()

    def fetch_user_skills(self):
        self.cursor.execute("""
            SELECT skill_name, proficiency
            FROM user_skills
            WHERE user_id=%s
        """, (self.user_id,))

        return {
            self.normalize(r["skill_name"]): int(r["proficiency"] or 0)
            for r in self.cursor.fetchall()
        }

    def fetch_job_skills(self):
        self.cursor.execute("""
            SELECT skill_name, required_level
            FROM job_required_skills
            WHERE job_id=%s
        """, (self.job_id,))

        return [
            {
                "skill": self.normalize(r["skill_name"]),
                "level": int(r["required_level"] or 0)
            }
            for r in self.cursor.fetchall()
        ]

    def save_result(self, matched, missing, score):
        self.cursor.execute("""
            INSERT INTO skill_gap_results
            (user_id, job_id, matched_skills, missing_skills, gap_score, run_id)
            VALUES (%s,%s,%s,%s,%s,%s)
        """, (
            self.user_id,
            self.job_id,
            json.dumps(matched),
            json.dumps(missing),
            int(score),
            self.run_id
        ))
        self.conn.commit()

    def analyze(self):

        job = self.fetch_job()
        if not job:
            return {"status": "error", "message": "Invalid job_id"}

        job_skills = self.fetch_job_skills()
        user_skills = self.fetch_user_skills()

        if not job_skills:
            return {"status": "error", "message": "No job skills found"}

        matched = []
        missing = []
        score = 0

        for req in job_skills:
            skill = req["skill"]
            level = req["level"]

            user_level = user_skills.get(skill, 0)

            if user_level >= level:
                matched.append(skill)
                score += 1
            else:
                missing.append(skill)

        readiness = round((score / len(job_skills)) * 100)

        self.save_result(matched, missing, readiness)

        return {
    "status": "success",
    "run_id": self.run_id,
    "user_id": self.user_id,
    "job_id": self.job_id,
    "job_title": job["job_title"],
    "gap_score": readiness,
    "matched_skills": matched,
    "missing_skills": missing
}


if __name__ == "__main__":
    try:
        user_id = sys.argv[1]
        job_id = sys.argv[2]

        analyzer = SkillGapAnalyzer(user_id, job_id)
        print(json.dumps(analyzer.analyze()))

    except Exception as e:
        print(json.dumps({
            "status": "error",
            "message": str(e)
        }))