' run_monitor.vbs
' Runs monitor.php silently in the background with no visible CMD window.
Dim objShell
Set objShell = CreateObject("WScript.Shell")
objShell.Run """C:\xampp\php\php.exe"" ""C:\xampp\htdocs\website-monitor\cron\monitor.php""", 0, False
Set objShell = Nothing
