@echo off

SET tempset_OUTPATH=..\

rem Android
CALL _svg2png.bat logo.svg %tempset_OUTPATH%logo036.png 36
CALL _svg2png.bat logo.svg %tempset_OUTPATH%logo048.png 48
rem iPhone
CALL _svg2png.bat logo.svg %tempset_OUTPATH%logo057.png 57
rem iPhone + Android
CALL _svg2png.bat logo.svg %tempset_OUTPATH%logo072.png 72
rem Nokia
CALL _svg2png.bat logo.svg %tempset_OUTPATH%logo080.png 80
rem Nokia + iPad
CALL _svg2png.bat logo.svg %tempset_OUTPATH%logo114.png 114
rem Default
CALL _svg2png.bat logo.svg %tempset_OUTPATH%logo128.png 128

echo Koniec.
pause
