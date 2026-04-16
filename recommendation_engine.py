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
    def fetch_resource_from_db(self, skill):
        self.cursor.execute("""
        SELECT *
        FROM skill_resources
        WHERE LOWER(skill_name) = LOWER(%s)
        LIMIT 1
    """, (skill,))
        return self.cursor.fetchone()

    def generate_recommendations(self, missing_skills):
        recommendations = []

        for skill in missing_skills:
            db_resource = self.fetch_resource_from_db(skill)
            if db_resource:
                #  Use DB resource
                recommendations.append({
                "skill_name": skill,
                "resource_type": db_resource["platform"],
                "resource_title": db_resource["resource_title"],
                "resource_link": db_resource["resource_link"],
                "difficulty": db_resource["difficulty"],
                "run_id": self.run_id
            })
            else:
                #  Fallback to Google
                recommendations.append({
                "skill_name": skill,
                "resource_type": "Google",
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