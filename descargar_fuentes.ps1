$dest = 'C:\xampp\htdocs\APP3\publico\fonts'
$base = 'https://cdn.jsdelivr.net/npm/@fontsource/inter@5/files'
$weights = 300, 400, 500, 600, 700, 800
foreach ($w in $weights) {
    foreach ($ext in 'woff2', 'woff') {
        $file = "inter-latin-$w-normal.$ext"
        $url  = "$base/$file"
        $out  = "$dest\$file"
        Write-Host "Descargando $file..."
        Invoke-WebRequest -Uri $url -OutFile $out -UseBasicParsing
        Write-Host "OK: $file"
    }
}
Write-Host 'COMPLETADO - Todas las fuentes Inter descargadas'
