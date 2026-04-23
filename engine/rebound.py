import sqlite3
import json
import os
from colorama import Fore, Style, init

init(autoreset=True)

print(f"{Fore.YELLOW}=== Memulai Proses Rebound (Reset Database) ===")

# Maca rute database tina config.json
try:
    with open('config.json', 'r') as file:
        config = json.load(file)
        db_path = config.get('db_name', 'data/database_agc.sqlite')
except Exception as e:
    print(f"{Fore.RED}[!] Gagal membaca config.json: {e}")
    db_path = 'data/database_agc.sqlite'

if not os.path.exists(db_path):
    print(f"{Fore.RED}[!] Database {db_path} tidak ditemukan. Mungkin belum dibuat?")
else:
    try:
        conn = sqlite3.connect(db_path)
        cursor = conn.cursor()
        
        # Ngahapus sadaya eusi tabel
        cursor.execute("DELETE FROM articles")
        
        # Ngarését ID Auto Increment balik ka 1
        cursor.execute("DELETE FROM sqlite_sequence WHERE name='articles'")
        
        conn.commit()
        conn.close()
        print(f"{Fore.GREEN}[V] Mantap Gan! Database '{db_path}' sudah bersih, siap digunakan lagi dari nol.")
    except Exception as e:
        print(f"{Fore.RED}[!] Ada error saat membersihkan database: {e}")