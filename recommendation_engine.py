"""
Gap2Grow – Recommendation Engine (Hybrid Model)
MODULE 3: Dynamic Recommendation + LLM Roadmap Generator

Features:
- Fetch latest skill gap result
- Delete old recommendations
- Insert new mapped resources
- Initialize progress tracking
- Generate personalized roadmap using Ollama (mandatory)
"""

import psycopg2
import json
import requests
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
# RECOMMENDATION ENGINE
# -------------------------------------------------------

class RecommendationEngine:

    def __init__(self, user_id: int):
        self.user_id = user_id
        self.conn = get_connection()
        self.cursor = self.conn.cursor(cursor_factory=RealDictCursor)

    # ---------------------------------------------------
    # FETCH LATEST GAP RESULT
    # ---------------------------------------------------

    def fetch_latest_gap(self):
        self.cursor.execute("""
            SELECT sgr.*, jr.job_title
            FROM skill_gap_results sgr
            JOIN job_roles jr ON sgr.job_id = jr.job_id
            WHERE sgr.user_id = %s
            ORDER BY analyzed_at DESC
            LIMIT 1
        """, (self.user_id,))

        result = self.cursor.fetchone()

        if not result:
            raise ValueError("No skill gap analysis found for this user.")

        result["missing_skills"] = json.loads(result["missing_skills"] or "[]")
        result["matched_skills"] = json.loads(result["matched_skills"] or "[]")

        return result

    # ---------------------------------------------------
    # DELETE OLD RECOMMENDATIONS
    # ---------------------------------------------------

    def clear_old_recommendations(self):
        self.cursor.execute("""
            DELETE FROM recommendations
            WHERE user_id = %s
        """, (self.user_id,))
        self.conn.commit()

    # ---------------------------------------------------
    # DETERMINE DIFFICULTY
    # ---------------------------------------------------

    def determine_difficulty(self, readiness_score: int):
        if readiness_score <= 40:
            return "Beginner"
        elif readiness_score <= 70:
            return "Intermediate"
        else:
            return "Advanced"

    # ---------------------------------------------------
    # FETCH RESOURCES FROM skill_resources
    # ---------------------------------------------------

    def fetch_resources(self, skill: str, difficulty: str):
        self.cursor.execute("""
            SELECT *
            FROM skill_resources
            WHERE LOWER(skill_name) = %s
            AND difficulty = %s
            LIMIT 2
        """, (skill.lower(), difficulty))

        return self.cursor.fetchall()

    # ---------------------------------------------------
    # INSERT RECOMMENDATIONS + INIT PROGRESS
    # ---------------------------------------------------

    def insert_recommendations(self, resources):
        for res in resources:
            self.cursor.execute("""
                INSERT INTO recommendations
                (user_id, skill_name, resource_type,
                 resource_title, resource_link, difficulty)
                VALUES (%s, %s, %s, %s, %s, %s)
                RETURNING recommendation_id
            """, (
                self.user_id,
                res["skill_name"],
                res["platform"],
                res["resource_title"],
                res["resource_link"],
                res["difficulty"]
            ))

            rec_id = self.cursor.fetchone()["recommendation_id"]

            # Initialize progress
            self.cursor.execute("""
                INSERT INTO user_progress
                (user_id, recommendation_id, status, progress_percent)
                VALUES (%s, %s, 'Pending', 0)
            """, (self.user_id, rec_id))

        self.conn.commit()

    # ---------------------------------------------------
    # GENERATE ROADMAP USING OLLAMA (MANDATORY)
    # ---------------------------------------------------

    def generate_roadmap(self, job_title, missing_skills, readiness_score, job_id):

        prompt = f"""
        User is preparing for the role of {job_title}.
        Missing skills: {', '.join(missing_skills)}.
        Current readiness score: {readiness_score}%.
        Generate a structured 4-week learning roadmap.
        Keep it practical and actionable.
        """

        try:
            response = requests.post(
                "http://localhost:11434/api/generate",
                json={
                    "model": "mistral",
                    "prompt": prompt,
                    "stream": False
                },
                timeout=60
            )

            data = response.json()
            roadmap_text = data.get("response", "").strip()

            if not roadmap_text:
                raise ValueError("Empty roadmap response from Ollama.")

        except Exception as e:
            raise RuntimeError("Ollama roadmap generation failed.") from e

        # Save roadmap to DB
        self.cursor.execute("""
            INSERT INTO learning_roadmaps
            (user_id, job_id, roadmap_text)
            VALUES (%s, %s, %s)
        """, (self.user_id, job_id, roadmap_text))

        self.conn.commit()

        return roadmap_text

    # ---------------------------------------------------
    # MAIN EXECUTION FUNCTION
    # ---------------------------------------------------

    def run(self):

        gap = self.fetch_latest_gap()
        readiness = gap["gap_score"]
        missing_skills = gap["missing_skills"]
        job_title = gap["job_title"]
        job_id = gap["job_id"]

        if not missing_skills:
            raise ValueError("No missing skills found. Nothing to recommend.")

        difficulty = self.determine_difficulty(readiness)

        # Clear old recommendations
        self.clear_old_recommendations()

        # Insert new recommendations
        for skill in missing_skills:
            resources = self.fetch_resources(skill, difficulty)
            if resources:
                self.insert_recommendations(resources)

        # Generate roadmap (mandatory)
        roadmap = self.generate_roadmap(
            job_title,
            missing_skills,
            readiness,
            job_id
        )

        return {
            "status": "success",
            "difficulty_level": difficulty,
            "roadmap_generated": True,
            "roadmap_preview": roadmap[:300] + "..."
        }


# -------------------------------------------------------
# EXAMPLE RUN
# -------------------------------------------------------

if __name__ == "__main__":

    engine = RecommendationEngine(user_id=2)
    result = engine.run()

    print(json.dumps(result, indent=4))