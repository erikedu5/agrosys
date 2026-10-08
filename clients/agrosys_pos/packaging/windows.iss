; Run ISCC from Windows after flutter build windows --release.
#define AppRoot AddBackslash(SourcePath) + ".."
#ifndef AppVersion
  #define AppVersion "1.0.0"
#endif

[Setup]
AppId=mx.agrosys.pos.windows
AppName=AgroSys POS
AppVersion={#AppVersion}
AppPublisher=AgroSys
DefaultDirName={localappdata}\Programs\AgroSys POS
DefaultGroupName=AgroSys POS
PrivilegesRequired=lowest
ArchitecturesAllowed=x64compatible
ArchitecturesInstallIn64BitMode=x64compatible
MinVersion=10.0
UsePreviousAppDir=yes
OutputDir={#AppRoot}\dist
OutputBaseFilename=AgroSys-POS-Setup
SetupIconFile={#AppRoot}\windows\runner\resources\app_icon.ico
UninstallDisplayIcon={app}\agrosys_pos.exe
Compression=lzma2
SolidCompression=yes
WizardStyle=modern
CloseApplications=yes
RestartApplications=no

[Tasks]
Name: "desktopicon"; Description: "Crear un acceso directo en el escritorio"; Flags: unchecked

[Files]
Source: "{#AppRoot}\build\windows\x64\runner\Release\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs

[Icons]
Name: "{group}\AgroSys POS"; Filename: "{app}\agrosys_pos.exe"
Name: "{autodesktop}\AgroSys POS"; Filename: "{app}\agrosys_pos.exe"; Tasks: desktopicon

[Run]
Filename: "{app}\agrosys_pos.exe"; Description: "Abrir AgroSys POS"; Flags: nowait postinstall skipifsilent
