# Script d'integration de la branche gmedicament (repo medilink)
# 1. Modifie MEDILINK_URL ci-dessous avec l'URL de ton repo medilink
# 2. Execute: .\integrate-gmedicament.ps1

$MEDILINK_URL = "https://github.com/MohamedZribi69/medilink.git"

Set-Location $PSScriptRoot

if (-not (Test-Path .git)) {
    Write-Host "Erreur: ce dossier n'est pas un depot Git." -ForegroundColor Red
    exit 1
}

Write-Host "Ajout du remote medilink..." -ForegroundColor Cyan
git remote add medilink $MEDILINK_URL 2>$null
if ($LASTEXITCODE -ne 0) {
    Write-Host "Le remote 'medilink' existe peut-etre deja. Continuation du fetch." -ForegroundColor Yellow
}

Write-Host "Fetch de la branche gmedicament..." -ForegroundColor Cyan
git fetch medilink gmedicament
if ($LASTEXITCODE -ne 0) {
    Write-Host "Echec du fetch. Verifie l'URL et que la branche gmedicament existe." -ForegroundColor Red
    exit 1
}

Write-Host "Merge de medilink/gmedicament dans la branche actuelle..." -ForegroundColor Cyan
git merge medilink/gmedicament -m "Merge branche gmedicament (medilink)"
if ($LASTEXITCODE -ne 0) {
    Write-Host "Conflits de merge. Ouvre les fichiers indiques, resous les conflits, puis:" -ForegroundColor Yellow
    Write-Host "  git add ." -ForegroundColor White
    Write-Host "  git commit -m 'Resolution conflits gmedicament'" -ForegroundColor White
    exit 1
}

Write-Host "Integration terminee." -ForegroundColor Green
git log --oneline -3
