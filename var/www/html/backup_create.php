<?php
declare(strict_types=1);

include 'menu.php';

const BACKUP_MANAGER_SCRIPT = '/root/backup_manager.sh';
const INFO_BOX_MIN_WIDTH_PX = 525;
const MAX_BOX_WIDTH = '95%';

function renderPageStart(string $title): void
{
    echo '<html lang="en"><head><meta charset="utf-8"><title>WiPi Netbooter</title>';
    echo '<link rel="stylesheet" href="css/sidebarstyles.css">';
    echo '</head><body>';
    echo '<section><center>';
    echo '<h1><a href="backup.php">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</a></h1><br>';
}

function renderPageEnd(): void
{
    echo '</center></section></body></html>';
}

function renderBox(string $content, string $width = '350px'): void
{
    echo '<div class="box2" style="width:' . $width . ';max-width:' . MAX_BOX_WIDTH . ';word-break:break-word;overflow-wrap:anywhere;padding:12px 16px;box-sizing:border-box;">';
    echo $content;
    echo '</div><br>';
}

function parseSourcePaths(string $scriptPath): array
{
    $output = [];
    $returnCode = 0;

    exec('sudo cat ' . escapeshellarg($scriptPath) . ' 2>&1', $output, $returnCode);

    if ($returnCode !== 0 || $output === []) {
        return [];
    }

    $content = implode("\n", $output);

    if (!preg_match('/SOURCE_PATHS=\((.*?)\)/s', $content, $matches)) {
        return [];
    }

    $block = $matches[1];
    $paths = [];

    if (preg_match_all('/"([^"]+)"/', $block, $pathMatches)) {
        foreach ($pathMatches[1] as $path) {
            $path = trim((string) $path);

            if ($path !== '') {
                $paths[] = $path;
            }
        }
    }

    return $paths;
}

function renderCreateForm(array $sourcePaths): void
{
    $listHtml = 'The following paths will be included in the backup:<br><br>';

    foreach ($sourcePaths as $path) {
        $listHtml .= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '<br>';
    }

    echo '<div id="backuppathsbox" class="box2" style="display:inline-block;width:auto;min-width:' . INFO_BOX_MIN_WIDTH_PX . 'px;max-width:' . MAX_BOX_WIDTH . ';padding:12px 16px;box-sizing:border-box;text-align:left;word-break:break-word;overflow-wrap:anywhere;">' . $listHtml . '</div><br><br>';

    echo '<div id="createbackupbox" class="box2" style="display:inline-block;padding:12px 16px;box-sizing:border-box;">';
    echo '<form action="backup_create.php" method="post" style="margin:0;">';
    echo '<input type="submit" value="Create Backup" style="width:100%;">';
    echo '</form>';
    echo '</div><br><br>';

    echo '<div id="backbuttonbox" class="box2" style="display:inline-block;padding:12px 16px;box-sizing:border-box;">';
    echo '<a href="backup.php" style="display:block;width:100%;text-align:center;">Back</a>';
    echo '</div><br>';

    echo '<script>';
    echo 'window.addEventListener("load", function () {';
    echo '    const source = document.getElementById("backuppathsbox");';
    echo '    const createBox = document.getElementById("createbackupbox");';
    echo '    const backBox = document.getElementById("backbuttonbox");';
    echo '    if (source && createBox && backBox) {';
    echo '        const width = Math.ceil(source.getBoundingClientRect().width);';
    echo '        createBox.style.width = width + "px";';
    echo '        backBox.style.width = width + "px";';
    echo '    }';
    echo '});';
    echo '</script>';
}

function streamDownloadAndDelete(string $backupFile): void
{
    $downloadName = basename($backupFile);

    header('Content-Description: File Transfer');
    header('Content-Type: application/gzip');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . (string) filesize($backupFile));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: public');
    header('Expires: 0');

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $handle = fopen($backupFile, 'rb');

    if ($handle !== false) {
        while (!feof($handle)) {
            echo (string) fread($handle, 8192);
            flush();
        }

        fclose($handle);
    }

    exec('sudo rm -f ' . escapeshellarg($backupFile));
    exit;
}

renderPageStart('Create Backup');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $sourcePaths = parseSourcePaths(BACKUP_MANAGER_SCRIPT);

    if ($sourcePaths === []) {
        renderBox('Could not read SOURCE_PATHS from backup_manager.sh.', '700px');
        renderBox('<a href="backup.php" style="display:block;width:100%;text-align:center;">Back</a>', '350px');
        renderPageEnd();
        exit;
    }

    renderCreateForm($sourcePaths);
    renderPageEnd();
    exit;
}

$output = [];
$returnCode = 0;

exec('sudo ' . escapeshellarg(BACKUP_MANAGER_SCRIPT) . ' create 2>&1', $output, $returnCode);

$backupFile = '';

foreach ($output as $line) {
    $line = trim((string) $line);

    if (str_starts_with($line, '/tmp/') && str_ends_with($line, '.tar.gz') && is_file($line)) {
        $backupFile = $line;
    }
}

if ($returnCode !== 0 || $backupFile === '' || !is_file($backupFile)) {
    renderBox('The backup operation failed.', '700px');

    if ($output !== []) {
        echo '<div class="box2" style="width:700px;max-width:' . MAX_BOX_WIDTH . ';word-break:break-word;overflow-wrap:anywhere;padding:12px 16px;box-sizing:border-box;">';
        echo '<pre style="text-align:left;white-space:pre-wrap;word-break:break-word;overflow-wrap:anywhere;margin:0;">' . htmlspecialchars(implode("\n", $output), ENT_QUOTES, 'UTF-8') . '</pre>';
        echo '</div><br>';
    }

    renderBox('<a href="backup_create.php" style="display:block;width:100%;text-align:center;">Back</a>', '350px');
    renderPageEnd();
    exit;
}

streamDownloadAndDelete($backupFile);
?>
