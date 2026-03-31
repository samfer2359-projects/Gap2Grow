import psycopg2
import json
import sys
from psycopg2.extras import RealDictCursor
import logging

logging.basicConfig(filename="recommendation_engine.log", level=logging.DEBUG, format="%(asctime)s %(message)s")

def get_connection():
    return psycopg2.connect(dbname="gap2grow", user="postgres", password="root", host="localhost", port="5432")

class RecommendationEngine:
    def __init__(self, user_id: int):
        self.user_id = user_id
        self.conn = get_connection()
        self.cursor = self.conn.cursor(cursor_factory=RealDictCursor)
        self.skill_tips = { "python": "...", "sql":"...", "excel":"...", "statistics":"...", "data visualization":"..." }
        self.job_focus = { "Data Analyst": ["excel","sql","statistics","data visualization"] }

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
            raise ValueError("No skill gap analysis found.")
        result["missing_skills"] = result.get("missing_skills") or []
        return result

    def clear_old_recommendations(self):
        self.cursor.execute("DELETE FROM recommendations WHERE user_id = %s", (self.user_id,))
        self.conn.commit()

    def determine_difficulty(self, score):
        if score <= 40:
            return "Beginner"
        elif score <= 70:
            return "Intermediate"
        return "Advanced"

    def fetch_resources(self, skill, difficulty):
        self.cursor.execute("""
            SELECT * FROM skill_resources
            WHERE LOWER(skill_name) = %s AND difficulty = %s
            LIMIT 2
        """, (skill.lower(), difficulty))
        return self.cursor.fetchall()

    def insert_recommendations(self, resources):
        for res in resources:
            self.cursor.execute("""
                INSERT INTO recommendations
                (user_id, skill_name, resource_type, resource_title, resource_link, difficulty)
                VALUES (%s, %s, %s, %s, %s, %s) RETURNING recommendation_id
            """, (self.user_id, res["skill_name"], res["platform"], res["resource_title"], res["resource_link"], res["difficulty"]))
            rec_id = self.cursor.fetchone()["recommendation_id"]
            self.cursor.execute("INSERT INTO user_progress (user_id, recommendation_id, status, progress_percent) VALUES (%s, %s,'Pending',0)", (self.user_id, rec_id))
        self.conn.commit()

    def generate_roadmap(self, job_title, missing_skills, score, job_id):
        difficulty_note = "Start from beginner level." if score<30 else "Focus on intermediate concepts." if score<70 else "Polish advanced concepts."
        roadmap = f"🎯 Career Goal: {job_title}\n📊 Current Readiness: {score}%\n📌 Strategy: {difficulty_note}\n\n"
        focus_skills = self.job_focus.get(job_title, [])
        final_skills = list(set(missing_skills + focus_skills))
        weeks = [final_skills[i:i+2] for i in range(0, len(final_skills), 2)]
        for i, skills in enumerate(weeks):
            roadmap += f"📅 Week {i+1}:\n"
            for skill in skills:
                tip = self.skill_tips.get(skill.lower(), "Practice consistently")
                roadmap += f"  - {skill.title()}: {tip}\n"
            roadmap += "\n"
        roadmap += "🚀 Final Week:\n  - Build a real-world project\n  - Prepare resume and portfolio\n  - Practice interview questions\n"
        self.cursor.execute("INSERT INTO learning_roadmaps (user_id, job_id, roadmap_text) VALUES (%s,%s,%s)", (self.user_id, job_id, roadmap))
        self.conn.commit()
        return roadmap

    def run(self):
        try:
            gap = self.fetch_latest_gap()
            score = gap["gap_score"]
            missing_skills = gap["missing_skills"]
            job_title = gap["job_title"]
            job_id = gap["job_id"]
            difficulty = self.determine_difficulty(score)
            self.clear_old_recommendations()
            for skill in missing_skills:
                resources = self.fetch_resources(skill, difficulty)
                if resources:
                    self.insert_recommendations(resources)
            roadmap = self.generate_roadmap(job_title, missing_skills, score, job_id)
            return {"status": "success", "difficulty": difficulty, "roadmap_preview": roadmap[:300]+"..."}
        except Exception as e:
            logging.error("ERROR", exc_info=True)
            return {"status":"error","message": str(e)}

if __name__ == "__main__":
    try:
        user_id = int(sys.argv[1])
        engine = RecommendationEngine(user_id)
        result = engine.run()
        print(json.dumps(result))
    except Exception as e:
        logging.error("FATAL ERROR", exc_info=True)
        print(json.dumps({"status":"error","message": str(e)}))