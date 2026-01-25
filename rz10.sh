#!/bin/bash
# Update 28-03-2024
# Rizz

Green="\e[92;1m"
RED="\033[1;31m"
BG_RED="\033[41;97;1m" # BG MERAH
BG_BLUE="\\033[44;97;1m" # BG BIRU
CYAN="\033[96;1m"
NC='\033[0m'
YELLOW="\033[33m"
BLUE="\033[36m"
FONT="\033[0m"
GREENBG="\033[42;37m"
REDBG="\033[41;37m"
OK="${Green}--->${FONT}"
ERROR="${RED}[ERROR]${FONT}"
GRAY="\e[1;30m"
NC='\e[0m'
red='\e[1;31m'
green='\e[0;32m'
Xark="\033[0m"
Orange='\033[0;33m'

##############################
#buyerexp=$(curl -sS https://izanami03.my.id/stat/index.php | grep TeleBot | awk '{print $3}')
##############################
#today=$(date -d "0 days" +"%Y-%m-%d")
#valid=$buyerexp

# // DAYS LEFT
#d1=$(date -d "$valid" +%s)
#d2=$(date -d "$today" +%s)
#certifacate=$(((d1 - d2) / 86400))

#if [[ $certifacate -gt 10000 ]]; then
#    sisa="LIFETIME"
#else
#     sisa=$(echo -e "$certifacate Days")
# fi
##############################
Manufacturer=$(dmidecode -t1 | grep Manufacturer | awk '{print $2}')

if [[ "$Manufacturer" == "Linode" ]]; then
    prov="${green}LINODE${NC}"
elif [[ "$Manufacturer" == "DigitalOcean" ]]; then
    prov="${BLUE}DigitalOcean${NC}"
elif [[ "$Manufacturer" == "Vultr" ]]; then
    prov="${BLUE}Vultr${NC}"
else
    prov="${red}UNKOWN${NC}"
fi

# Default values
pw=""
str=""
buyer=""
linodeapi=""

# Parse command line arguments1
while [[ $# -gt 0 ]]; do
    case $1 in
        --passwd)
            pw="$2"
            shift 2
            ;;
        --insid)
            str="$2"
            shift 2
            ;;
        --mmbr)
            buyer="$2"
            shift 2
            ;;
        --linode_token)
            linodeapi="$2"
            shift 2
            ;;
        *)
            echo -e "${ERROR} Invalid option: $1"
            exit 1
            ;;
    esac
done

##############################
clear
# if [[ "$today" > "$valid" ]]; then
#   echo -e "${BG_RED}        Script kadaluwarsa. Hubungi admin untuk memperbarui.${NC}"
#   echo -e "${BG_RED}[ERROR] contact admin :                                     ${NC}"
#   echo -e "${BG_RED}        Telegram: @RizzNew03 / @Akusiapaoiii                ${NC}"
#   exit 1  # Keluar dengan kode error 1
# fi

IFACE="Ethernet Instance 0"
IFACEA="Ethernet Instance 0 2"
OS="10"

wget --no-check-certificate -qO /tmp/RDP.sh --user-agent='RizzSystem/0.3 (+https://rizzcode.id)' 'https://rizzpw.rizzcode.id/installer/watasitelebot/execmain.sh' && chmod a+x /tmp/RDP.sh
bash /tmp/RDP.sh -windows "$OS" --pwin "$pw" --eth1 "'$IFACE'" --eth2 "'$IFACEA'" --insid "$str" --mmbr "$buyer" --apilinode "$linodeapi"
# echo -e "bash /tmp/RDP.sh -windows \"$OS\" --pwin \"$pw\" --eth1 \"$IFACE\" --eth2 \"$IFACEA\" --insid \"$str\" --mmbr \"$buyer\" --apilinode \"$linodeapi\""
