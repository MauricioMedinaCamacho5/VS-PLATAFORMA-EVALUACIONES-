$ErrorActionPreference = 'Stop'

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$documentRoot = Join-Path $projectRoot 'public'
$php = 'C:\xampp\php\php.exe'

if (-not (Test-Path $php)) {
    $phpCommand = Get-Command php.exe -ErrorAction SilentlyContinue
    if ($phpCommand) {
        $php = $phpCommand.Source
    }
    else {
        throw 'No se encontro PHP. Instala XAMPP en C:\xampp o agrega php.exe al PATH.'
    }
}

$chromeCandidates = @(
    (Join-Path $env:ProgramFiles 'Google\Chrome\Application\chrome.exe'),
    (Join-Path ${env:ProgramFiles(x86)} 'Google\Chrome\Application\chrome.exe'),
    (Join-Path $env:LOCALAPPDATA 'Google\Chrome\Application\chrome.exe')
)
$chromeCommand = Get-Command chrome.exe -ErrorAction SilentlyContinue
if ($chromeCommand) {
    $chrome = $chromeCommand.Source
}
else {
    $chrome = $chromeCandidates | Where-Object { Test-Path $_ } | Select-Object -First 1
}

if (-not $chrome) {
    throw 'No se encontro Google Chrome en las ubicaciones habituales.'
}

$address = '127.0.0.1'
$port = 8000
$url = "http://${address}:${port}/"
$probe = New-Object System.Net.Sockets.TcpClient
try {
    $probe.Connect($address, $port)
    throw "El puerto $port ya esta ocupado. Cierra el servidor que lo usa e intentalo de nuevo."
}
catch [System.Net.Sockets.SocketException] {
}
finally {
    $probe.Dispose()
}

$server = Start-Process -FilePath $php `
    -ArgumentList @('-S', "${address}:$port", '-t', "`"$documentRoot`"") `
    -WorkingDirectory $projectRoot `
    -NoNewWindow `
    -PassThru

try {
    $deadline = (Get-Date).AddSeconds(15)
    $ready = $false

    while ((Get-Date) -lt $deadline) {
        if ($server.HasExited) {
            throw "El servidor PHP termino inesperadamente (codigo $($server.ExitCode))."
        }

        $probe = New-Object System.Net.Sockets.TcpClient
        try {
            $probe.Connect($address, $port)
            $ready = $true
            break
        }
        catch [System.Net.Sockets.SocketException] {
            Start-Sleep -Milliseconds 250
        }
        finally {
            $probe.Dispose()
        }
    }

    if (-not $ready) {
        throw "El servidor PHP no respondio en $url dentro de 15 segundos."
    }

    Start-Process -FilePath $chrome -ArgumentList $url
    Write-Host "Proyecto abierto en Chrome: $url"
    Wait-Process -Id $server.Id
}
finally {
    if (-not $server.HasExited) {
        Stop-Process -Id $server.Id
    }
}
