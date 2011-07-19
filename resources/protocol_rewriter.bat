rem Rewrites a file/line URI into two separate variables, one for file and one for line.
rem We then kick off an editor and pass these values to it.

set openswith="C:\\Program Files\\Notepad++\\Notepad++.exe"
set fpath=%1
set fpath=%fpath:"=%
set delim=#
for /f "tokens=1,2 delims=%delim% " %%a in ("%fpath%") do set file=%%a&set line=%%b

%openswith% -n%line% %file%