; v0.2 - Fix for Dota 2 reborn Oct 11 2015
; Modify the following line to your Dota 2 directory
filepath=C:\Program Files (x86)\Steam\steamapps\common\dota 2 beta\game\dota\server_log.txt

;
;
; Do not edit below
;
;

Tail(k,file)   ; Return the last k lines of file
{
   Loop Read, %file%
   {
      i := Mod(A_Index,k)
      L%i% = %A_LoopReadLine%
   }
   L := L%i%
   Loop % k-1
   {
      IfLess i,1, SetEnv i,%k%
      i--      ; Mod does not work here
      L := L%i% "`n" L
   }
   Return L
}

FileGetTime, previoustime, %filepath%
SetTimer, check_time, 1000

check_time:
Process, Exist, dota2.exe
If ErrorLevel
{
	FileGetTime, time, %filepath%
	If (time != previoustime)
	{
		previoustime := time

		StartTime := A_TickCount
		lastLine := Tail(1, filepath)
		StringReplace, lastLine, lastLine, %A_SPACE%, +, All

		Needle = Lobby
		IfInString, lastLine, %Needle%
		{
			Run, chrome.exe http://valueof.me/dota/?serverLog=%lastLine%
		}
	}
	return
}
#Persistent
#singleinstance force