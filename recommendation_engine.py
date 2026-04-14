import psycopg2
import json
import sys
from psycopg2.extras import RealDictCursor


def get_conn():
    return psycopg2.connect(
        dbname="gap2grow",
        user="postgres",
        password="root",
        host="localhost",
        port="5432"
    )


class RecommendationEngine:

    def __init__(self, run_id):
        self.run_id = run_id
        self.conn = get_conn()
        self.cursor = self.conn.cursor(cursor_factory=RealDictCursor)

    def fetch_gap(self):
        self.cursor.execute("""
            SELECT sgr.*, jr.job_title
            FROM skill_gap_results sgr
            JOIN job_roles jr ON sgr.job_id = jr.job_id
            WHERE sgr.run_id = %s
        """, (self.run_id,))

        row = self.cursor.fetchone()

        if not row:
            raise Exception("Gap result not found")

        return row

    def generate_recommendations(self, missing_skills):
        recommendations = []

        for skill in missing_skills:
            recommendations.append({
                "skill_name": skill,
                "resource_type": "Platform",
                "resource_title": f"Learn {skill}",
                "resource_link": f"https://www.google.com/search?q={skill}",
                "difficulty": "Beginner",
                "run_id": self.run_id
            })

        return recommendations

    def save_recommendations(self, recommendations, user_id, job_id):
        for r in recommendations:
            self.cursor.execute("""
            INSERT INTO recommendations
            (user_id, job_id, skill_name, resource_type, resource_title, resource_link, difficulty, run_id)
            VALUES (%s,%s,%s,%s,%s,%s,%s,%s)
        """, (
            user_id,
            job_id,
            r["skill_name"],
            r["resource_type"],
            r["resource_title"],
            r["resource_link"],
            r["difficulty"],
            self.run_id
        ))
            self.conn.commit()

    def run(self):

        gap = self.fetch_gap()

        missing = gap["missing_skills"]
        if isinstance(missing, str):
            missing = json.loads(missing)

        recommendations = self.generate_recommendations(missing)

        self.save_recommendations(
    recommendations,
    gap["user_id"],
    gap["job_id"]
)

        return {
            "status": "success",
            "run_id": self.run_id,
            "job_title": gap["job_title"],
            "missing_skills": missing
        }


if __name__ == "__main__":
    try:
        run_id = sys.argv[1]
        engine = RecommendationEngine(run_id)
        print(json.dumps(engine.run()))

    except Exception as e:
        print(json.dumps({"status": "error", "message": str(e)}))