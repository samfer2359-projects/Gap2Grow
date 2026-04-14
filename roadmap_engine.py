import psycopg2
import sys
import json
from psycopg2.extras import RealDictCursor


def get_conn():
    return psycopg2.connect(
        dbname="gap2grow",
        user="postgres",
        password="root",
        host="localhost",
        port="5432"
    )


class RoadmapEngine:

    def __init__(self, run_id):
        self.run_id = run_id
        self.conn = get_conn()
        self.cursor = self.conn.cursor(cursor_factory=RealDictCursor)

    # FETCH SKILL GAP 
    def fetch_gap(self):
        self.cursor.execute("""
            SELECT 
                sgr.*,
                jr.job_title
            FROM skill_gap_results sgr
            JOIN job_roles jr ON jr.job_id = sgr.job_id
            WHERE sgr.run_id = %s
        """, (self.run_id,))

        return self.cursor.fetchone()

    #  ROADMAP GENERATION 
    def generate_roadmap(self, missing_skills, score, job_title):

        weeks = []
        week_no = 1

        # STEP 1: map missing skills to weeks
        for skill in missing_skills:
            weeks.append({
                "week": week_no,
                "focus": skill,
                "tasks": [
                    f"Learn {skill} fundamentals",
                    f"Practice {skill} exercises",
                    f"Build mini project using {skill}"
                ]
            })
            week_no += 1

        # STEP 2: ensure minimum 4 weeks
        default_plan = [
            "Revision + Practice",
            "Hands-on Projects",
            "Interview Preparation",
            "Portfolio Building"
        ]

        while len(weeks) < 4:
            focus = default_plan[len(weeks)]

            weeks.append({
                "week": week_no,
                "focus": focus,
                "tasks": [
                    f"Strengthen basics of {focus}",
                    "Solve practice problems",
                    "Apply concepts in mini project"
                ]
            })
            week_no += 1

        # FINAL PHASE
        final_phase = {
            "week": "Final",
            "focus": "Career Preparation",
            "tasks": [
                "Build real-world project",
                "Prepare resume",
                "Practice interviews"
            ]
        }

        return {
            "job_title": job_title,
            "readiness": score,
            "weeks": weeks,
            "final": final_phase
        }

    #  SAVE TO DB 
    def save(self, roadmap):
        self.cursor.execute("""
            INSERT INTO learning_roadmaps
            (user_id, job_id, roadmap_text)
            VALUES (%s, %s, %s)
        """, (
            roadmap["user_id"],
            roadmap["job_id"],
            json.dumps(roadmap, ensure_ascii=False)
        ))

        self.conn.commit()

    #  MAIN RUN 
    def run(self):
        gap = self.fetch_gap()

        if not gap:
            return {"status": "error", "message": "No gap data found"}

        missing = gap["missing_skills"]

        # safe JSON handling
        if isinstance(missing, str):
            missing = json.loads(missing or "[]")

        if not isinstance(missing, list):
            missing = []

        roadmap = self.generate_roadmap(
            missing,
            gap["gap_score"],
            gap["job_title"]
        )

        roadmap["user_id"] = gap["user_id"]
        roadmap["job_id"] = gap["job_id"]

        self.save(roadmap)

        return {
            "status": "success",
            "run_id": self.run_id
        }


if __name__ == "__main__":
    run_id = sys.argv[1]
    engine = RoadmapEngine(run_id)
    print(json.dumps(engine.run()))