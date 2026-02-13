

import psycopg2
import json
from psycopg2.extras import RealDictCursor




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




class SkillGapAnalyzer:

    def __init__(self, user_id: int, job_title: str):
        self.user_id = user_id
        self.job_title = job_title.strip()
        self.conn = get_connection()
        self.cursor = self.conn.cursor(cursor_factory=RealDictCursor)
\

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

   