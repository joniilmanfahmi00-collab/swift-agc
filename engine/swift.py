import os
import json
import sqlite3
import argparse
import time
import requests
import random
import threading
from concurrent.futures import ThreadPoolExecutor, as_completed
from ddgs import DDGS
from groq import Groq
import google.generativeai as genai # <-- Tambihan kanggé Néng Gemini
from colorama import Fore, Style, init

init(autoreset=True)

# --- 1. SETUP ARGUMENTS (DUAL ENGINE) ---
parser = argparse.ArgumentParser(description="Swift AGC V9 (Dual Engine) oleh Aa Ilman")
parser.add_argument("--duckduckgo", action="store_true", help="Ambil gambar dari DuckDuckGo")
parser.add_argument("--pexels", action="store_true", help="Ambil gambar dari Pexels")
parser.add_argument("--groq_agents", action="store_true", help="Gunakan mesin Groq (Llama 3) untuk Kecepatan")
parser.add_argument("--gemini_agents", action="store_true", help="Gunakan mesin Gemini untuk Kualitas")
# --- 1a. tambahan qwen alibaba
parser.add_argument("--qwen_agents", "--qwen", action="store_true", help="Gunakan mesin Qwen (Alibaba Cloud) untuk Alternatif")
parser.add_argument("--threads", type=int, default=3, help="Jumlah worker sekaligus (Max aman: 3-5)")
args = parser.parse_args()

def load_config():
    with open('config.json', 'r') as file:
        return json.load(file)

config = load_config()
db_lock = threading.Lock()

# --- 2. SETUP SQLITE DATABASE ---
def setup_database():
    db_path = config.get('db_name', 'data/database_agc.sqlite')
    os.makedirs(os.path.dirname(db_path), exist_ok=True)
    conn = sqlite3.connect(db_path)
    cursor = conn.cursor()
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS articles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            keyword TEXT,
            title TEXT,
            content TEXT,
            image_url TEXT,
            slug TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ''')
    conn.commit()
    conn.close()

# --- 3. MODUL GAMBAR ---
def get_image_duckduckgo(keyword):
    print(f"{Fore.CYAN}[🔍] [{threading.current_thread().name}] Mencari gambar DDG: {keyword}...")
    try:
        # Cobi milarian 5 gambar, sanes hiji, kanggo cadangan
        # safesearch='off' ameh hasilna langkung seueur
        results = DDGS().images(
            keyword, 
            max_results=5,
            safesearch='off',
            size='Large' # Milari gambar nu ageung
        )
        if results:
            # Loop ngaliwatan hasil pami nu kahiji gagal
            for result in results:
                # Pastikeun 'image' key na aya sareng teu kosong
                if 'image' in result and result['image']:
                    return result['image']
    except Exception as e:
        # Tampilkeun errorna, tapi tong ngeureunkeun program
        print(f"{Fore.YELLOW}[!] Peringatan DDG: Gagal nyandak gambar ({e})")
    return "https://via.placeholder.com/800x400?text=Gambar+Teu+Aya"

def get_image_pexels(keyword, api_key):
    print(f"{Fore.CYAN}[🔍] [{threading.current_thread().name}] Mencari gambar Pexels: {keyword}...")
    headers = {"Authorization": api_key}
    url = f"https://api.pexels.com/v1/search?query={keyword}&per_page=1"
    try:
        response = requests.get(url, headers=headers)
        data = response.json()
        if data.get('photos') and len(data['photos']) > 0:
            return data['photos'][0]['src']['large']
    except Exception as e:
        pass
    return "https://via.placeholder.com/800x400?text=Gambar+Teu+Aya"

def generate_article_groq(keyword, api_key):
    print(f"[⚡ Groq] [{threading.current_thread().name}] Tulis cepat keyword: {keyword}...")
    client = Groq(api_key=api_key)
    base_prompt = config['rag_prompt'].replace('{keyword}', keyword)
    prompt = f"{base_prompt}\n\nINSTRUKSI PENTING: Judul artikel harus ditulis di baris pertama (paling atas) dengan bahasa yang menarik/clickbait, tanpa format markdown (jangan pakai tanda #). Baris kedua dan seterusnya adalah isi artikel."
    try:
        chat_completion = client.chat.completions.create(
            messages=[
                {"role": "system", "content": "You are an expert SEO article writer. Use proper markdown formatting."},
                {"role": "user", "content": prompt}
            ],
            # TAH IEU ANU DIROBIH AA:
            model="moonshotai/kimi-k2-instruct", 
            temperature=0.3
        )
        full_text = chat_completion.choices[0].message.content.strip()
        if '\n' in full_text:
            parts = full_text.split('\n', 1)
            title = parts[0].strip().replace('#', '').replace('*', '')
            content = parts[1].strip()
        else:
            title = keyword.title()
            content = full_text
        return title, content
    except Exception as e:
        print(f"[!] Error Groq: {e}")
        return keyword.title(), "<p>Error generating content.</p>"

def generate_article_gemini(keyword, api_key):
    print(f"{Fore.MAGENTA}[🧠 Gemini] [{threading.current_thread().name}] Berpikir & Menulis tentang: {keyword}...")
    genai.configure(api_key=api_key)
    
    prompt_lengkap = "You are an expert SEO article writer. Use proper markdown formatting.\n\n"
    prompt_lengkap += config['rag_prompt'].replace('{keyword}', keyword)
    prompt_lengkap += "\n\nINSTRUKSI PENTING: Judul artikel harus ditulis di baris pertama (paling atas) dengan bahasa yang menarik/clickbait, tanpa format markdown (jangan pakai tanda #). Baris kedua dan seterusnya adalah isi artikel."
    
    full_text = ""
    try:
        # Pilihan 1: Nganggo Flash-Lite (Jatah 1.000/dinten & 15/menit)
        model = genai.GenerativeModel('gemini-2.5-flash-lite')
        response = model.generate_content(prompt_lengkap)
        full_text = response.text.strip()
    except Exception as e:
        # Pilihan 2: Pami Lite nuju sibuk/error, mundur ka Flash biasa
        try:
            model = genai.GenerativeModel('gemini-2.5-flash')
            response = model.generate_content(prompt_lengkap)
            full_text = response.text.strip()
        except Exception as e2:
            print(f"{Fore.RED}[!] Error Gemini: {e2}")
            return keyword.title(), "<p>Error generating content.</p>"
            
    if '\n' in full_text:
        parts = full_text.split('\n', 1)
        title = parts[0].strip().replace('#', '').replace('*', '')
        content = parts[1].strip()
    else:
        title = keyword.title()
        content = full_text
    return title, content

# logika qwen di dieu
def generate_article_qwen(keyword, api_key):
    print(f"{Fore.BLUE}[☁️ Qwen] [{threading.current_thread().name}] Menulis dengan Alibaba Qwen: {keyword}...")
    client = Groq(api_key=api_key, base_url="https://dashscope.aliyuncs.com/compatible-mode/v1")
    base_prompt = config['rag_prompt'].replace('{keyword}', keyword)
    prompt = f"{base_prompt}\n\nINSTRUKSI PENTING: Judul artikel harus ditulis di baris pertama (paling atas) dengan bahasa yang menarik/clickbait, tanpa format markdown (jangan pakai tanda #). Baris kedua dan seterusnya adalah isi artikel."
    try:
        chat_completion = client.chat.completions.create(
            messages=[
                {"role": "system", "content": "You are an expert SEO article writer. Use proper markdown formatting."},
                {"role": "user", "content": prompt}
            ],
            model="qwen3.6-plus", 
            temperature=0.7
        )
        full_text = chat_completion.choices[0].message.content.strip()
        if '\n' in full_text:
            parts = full_text.split('\n', 1)
            title = parts[0].strip().replace('#', '').replace('*', '')
            content = parts[1].strip()
        else:
            title = keyword.title()
            content = full_text
        return title, content
    except Exception as e:
        print(f"[!] Error Qwen: {e}")
        return keyword.title(), "<p>Error generating content with Qwen.</p>"

# --- 5. FUNGSI PAGAWÉ (WORKER) ---
def process_keyword(kw):
    image_url = ""
    if args.duckduckgo:
        time.sleep(random.randint(4, 8)) 
        image_url = get_image_duckduckgo(kw)
        
        # FALLBACK: Pami DDG gagal (masih placeholder), cobian Pexels
        if "placeholder" in image_url:
            print(f"{Fore.YELLOW}[🔄] [{threading.current_thread().name}] DDG gagal/kosong, dialihkeun milari ka Pexels...")
            image_url = get_image_pexels(kw, config['api_keys']['pexels'])
            
    elif args.pexels:
        image_url = get_image_pexels(kw, config['api_keys']['pexels'])
        
    title = kw.title()
    content = ""
    
    # Milih Mesin AI dumasar kana terminal
    if args.gemini_agents:
        title, content = generate_article_gemini(kw, config['api_keys']['gemini'])
        time.sleep(3) # Delay améh aman tina limit 15 request/menit Gemini
    elif args.groq_agents:
        title, content = generate_article_groq(kw, config['api_keys']['groq'])
        time.sleep(3) # Delay améh aman tina limit request/menit Groq
    elif args.qwen_agents:
        title, content = generate_article_qwen(kw, config['api_keys'].get('qwen', config['api_keys']['groq']))
        time.sleep(3)
        
    slug = kw.lower().replace(' ', '-')
    
    with db_lock:
        try:
            db_path = config.get('db_name', 'data/database_agc.sqlite')
            conn = sqlite3.connect(db_path, timeout=10)
            cursor = conn.cursor()
            cursor.execute('''
                INSERT INTO articles (keyword, title, content, image_url, slug)
                VALUES (?, ?, ?, ?, ?)
            ''', (kw, title, content, image_url, slug))
            conn.commit()
            conn.close()
            print(f"{Fore.GREEN}[✅] [{threading.current_thread().name}] Sukses menyimpan: {title}")
        except Exception as e:
            print(f"{Fore.RED}[!] Gagal menyimpan ke DB: {e}")

# --- 6. ALUR UTAMA ---
def main():
    print(f"\n{Style.BRIGHT}{Fore.BLUE}=== Memulai Swift AGC V9 (Dual Engine) - {config['site_name']} ==={Style.RESET_ALL}")
    
    # Pangecekan Validasi Terminal
    if not args.duckduckgo and not args.pexels:
        print(f"{Fore.RED}[!] Lupa! Pilih sumber gambar dulu (--duckduckgo atau --pexels)")
        return

    selected_agents = [args.groq_agents, args.gemini_agents, args.qwen_agents]
    if not any(selected_agents):
        print(f"{Fore.RED}[!] Lupa! Pilih mesin AI dulu (--groq_agents, --gemini_agents, atau --qwen_agents)")
        return

    if sum(selected_agents) > 1:
        print(f"{Fore.YELLOW}[!] Pilih salah satu mesin saja, jangan serakah dinyalakan keduanya nanti laptop meledak :D")
        return

    setup_database()

    if not os.path.exists('keyword.txt'):
        print(f"{Fore.RED}[!] File keyword.txt tidak ada. Silakan buat dan isi dulu!")
        return

    with open('keyword.txt', 'r') as file:
        keywords = [line.strip() for line in file if line.strip()]

    print(f"{Fore.BLUE}[*] Total ada {len(keywords)} keyword. Digas menggunakan {args.threads} threads sekaligus!\n")

    start_time = time.time()
    with ThreadPoolExecutor(max_workers=args.threads, thread_name_prefix="PAGAWÉ") as executor:
        futures = [executor.submit(process_keyword, kw) for kw in keywords]
        for future in as_completed(futures):
            pass

    end_time = time.time()
    print(f"\n{Fore.GREEN}=== Proses Selesai dalam {round(end_time - start_time, 2)} detik! ==={Style.RESET_ALL}")

if __name__ == "__main__":
    main()