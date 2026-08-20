import pymysql

def connect_db():
    conn = pymysql.connect(
        host="127.0.0.1",
        user="root",
        password="",
        database="greenchoice7"
    )
    return conn