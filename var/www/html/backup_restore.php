<?php
declare(strict_types=1);

session_start();
include 'menu.php';

const BACKUP_MANAGER_SCRIPT = '/root/backup_manager.sh';
const DEFAULT_BOX_WIDTH = '385px';
const OUTPUT_BOX_WIDTH = '700px';
const MAX_BOX_WIDTH = '95%';

function renderPageStart(string $title): void
{
    echo '<html lang="en"><head><meta charset="utf-8"><title>WiPi Netbooter</title>';
    echo '<link rel="stylesheet" href="css/sidebarstyles.css">';
    echo '</head><body><section><center>';
    echo '<h1><a href="backup.php">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</a></h1><br>';
}

function renderPageEnd(): void
{
    echo '</center></section></body></html>';
}

function renderFormBoxStart(string $width = DEFAULT_BOX_WIDTH, string $id = ''): void
{
    $idAttribute = $id !== '' ? ' id="' . $id . '"' : '';
    echo '<div' . $idAttribute . ' class="box2" style="width:' . $width . ';max-width:' . MAX_BOX_WIDTH . ';padding:12px 16px;box-sizing:border-box;">';
}

function renderFormBoxEnd(): void
{
    echo '</div><br>';
}

function renderOutputBox(string $content, string $id = ''): void
{
    $idAttribute = $id !== '' ? ' id="' . $id . '"' : '';
    echo '<div' . $idAttribute . ' class="box2" style="width:' . OUTPUT_BOX_WIDTH . ';max-width:' . MAX_BOX_WIDTH . ';padding:12px 16px;box-sizing:border-box;word-break:break-word;overflow-wrap:anywhere;text-align:left;">';
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

    $paths = [];

    if (preg_match_all('/"([^"]+)"/', $matches[1], $pathMatches)) {
        foreach ($pathMatches[1] as $path) {
            $path = trim((string) $path);

            if ($path !== '') {
                $paths[] = $path;
            }
        }
    }

    return $paths;
}

function normalizeArchivePath(string $path): string
{
    $path = str_replace('\\', '/', $path);
    $path = ltrim($path, '/');

    while (str_contains($path, '//')) {
        $path = str_replace('//', '/', $path);
    }

    return rtrim($path, '/');
}

function listArchiveEntries(string $archivePath, string &$message): array
{
    $output = [];
    $returnCode = 0;

    exec('tar -tzf ' . escapeshellarg($archivePath) . ' 2>&1', $output, $returnCode);

    if ($returnCode !== 0) {
        $message = 'Could not read the backup archive.';
        return [];
    }

    $message = 'OK';
    return $output;
}

function validateBackupArchive(string $archivePath, array $sourcePaths, string &$message, array &$matchedPaths, array &$archiveEntries): bool
{
    $matchedPaths = [];
    $archiveEntries = [];

    if ($sourcePaths === []) {
        $message = 'Could not read SOURCE_PATHS from backup_manager.sh.';
        return false;
    }

    $allowedPaths = [];

    foreach ($sourcePaths as $path) {
        $normalized = normalizeArchivePath($path);

        if ($normalized !== '') {
            $allowedPaths[$normalized] = $path;
        }
    }

    if ($allowedPaths === []) {
        $message = 'No valid SOURCE_PATHS were found in backup_manager.sh.';
        return false;
    }

    $entries = listArchiveEntries($archivePath, $message);

    if ($message !== 'OK') {
        return false;
    }

    $foundAllowedContent = false;

    foreach ($entries as $entryRaw) {
        $entry = normalizeArchivePath(trim((string) $entryRaw));

        if ($entry === '') {
            continue;
        }

        if (str_contains($entry, '../') || str_starts_with($entry, '../')) {
            $message = 'The backup archive contains invalid paths.';
            return false;
        }

        $validEntry = false;

        foreach ($allowedPaths as $normalizedPath => $originalPath) {
            if ($entry === $normalizedPath || str_starts_with($entry, $normalizedPath . '/')) {
                $validEntry = true;
                $foundAllowedContent = true;
                $matchedPaths[$originalPath] = $originalPath;
                $archiveEntries[] = '/' . $entry;
                break;
            }
        }

        if (!$validEntry) {
            $message = 'The selected file is not a valid WiPi backup.';
            return false;
        }
    }

    if (!$foundAllowedContent) {
        $message = 'The backup archive does not contain valid backup paths.';
        return false;
    }

    foreach ($sourcePaths as $path) {
        if (!isset($matchedPaths[$path])) {
            $matchedPaths[$path] = $path;
        }
    }

    $archiveEntries = array_values(array_unique($archiveEntries));
    sort($archiveEntries, SORT_NATURAL);
    $message = 'OK';

    return true;
}

function extractBackupDatetime(string $filename): string
{
    $base = basename($filename);

    if (preg_match('/backup_(.+)_(\d{8})_(\d{6})\.tar\.gz$/i', $base, $matches)) {
        $dateTime = DateTime::createFromFormat('Ymd His', $matches[2] . ' ' . $matches[3]);

        if ($dateTime instanceof DateTime) {
            return $dateTime->format('Y-m-d H:i:s');
        }
    }

    return 'Unknown';
}

function cleanupValidatedRestoreFile(): void
{
    if (isset($_SESSION['validated_restore_file']) && $_SESSION['validated_restore_file'] !== '') {
        exec('sudo rm -f ' . escapeshellarg((string) $_SESSION['validated_restore_file']));
    }

    unset(
        $_SESSION['validated_restore_file'],
        $_SESSION['validated_restore_name'],
        $_SESSION['validated_restore_paths'],
        $_SESSION['validated_restore_datetime'],
        $_SESSION['validated_restore_entries']
    );
}

function renderSyncWidthScript(string $sourceId, string $targetAId, string $targetBId): void
{
    echo '<script>';
    echo 'window.addEventListener("load", function () {';
    echo '    const source = document.getElementById("' . $sourceId . '");';
    echo '    const targetA = document.getElementById("' . $targetAId . '");';
    echo '    const targetB = document.getElementById("' . $targetBId . '");';
    echo '    if (source && targetA && targetB) {';
    echo '        const width = Math.ceil(source.getBoundingClientRect().width);';
    echo '        targetA.style.width = width + "px";';
    echo '        targetB.style.width = width + "px";';
    echo '    }';
    echo '});';
    echo '</script>';
}

function renderBackLink(string $href, string $label = 'Back'): void
{
    echo '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" style="display:block;width:100%;text-align:center;">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
}

function renderRestoreValidationView(string $fileName, string $backupDateTime, array $paths): void
{
    $infoHtml = '<strong>Selected backup file:</strong><br><br>' .
        htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8') .
        '<br><br><strong>Backup date and time:</strong><br><br>' .
        htmlspecialchars($backupDateTime, ENT_QUOTES, 'UTF-8') .
        '<br><br><strong>Paths that will be restored:</strong><br><br>';

    foreach ($paths as $path) {
        $infoHtml .= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '<br>';
    }

    renderOutputBox($infoHtml, 'restoreinfobox');

    echo '<form action="backup_restore.php" method="post">';
    echo '<input type="hidden" name="action" value="restore">';

    renderFormBoxStart(DEFAULT_BOX_WIDTH, 'restoreactionbox');
    echo '<input type="submit" value="Restore Backup" style="width:100%;">';
    renderFormBoxEnd();

    echo '</form>';

    renderFormBoxStart(DEFAULT_BOX_WIDTH, 'restorebackbox');
    renderBackLink('backup_restore.php');
    renderFormBoxEnd();

    renderSyncWidthScript('restoreinfobox', 'restoreactionbox', 'restorebackbox');
}

function renderRestoreResultView(string $title, string $fileName, string $backupDateTime, array $configuredPaths, array $archiveEntries, array $commandOutput, string $backHref, string $backLabel): void
{
    renderOutputBox($title);

    $detailsHtml = '<strong>Restored backup file:</strong><br><br>' .
        htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8') .
        '<br><br><strong>Backup date and time:</strong><br><br>' .
        htmlspecialchars($backupDateTime, ENT_QUOTES, 'UTF-8') .
        '<br><br><strong>Configured paths restored:</strong><br><br>';

    foreach ($configuredPaths as $path) {
        $detailsHtml .= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '<br>';
    }

    if ($archiveEntries !== []) {
        $detailsHtml .= '<br><strong>Archive entries restored:</strong><br><br><pre style="text-align:left;white-space:pre-wrap;word-break:break-word;overflow-wrap:anywhere;margin:0;">' .
            htmlspecialchars(implode("\n", $archiveEntries), ENT_QUOTES, 'UTF-8') .
            '</pre>';
    }

    if ($commandOutput !== []) {
        $detailsHtml .= '<br><br><strong>Restore command output:</strong><br><br><pre style="text-align:left;white-space:pre-wrap;word-break:break-word;overflow-wrap:anywhere;margin:0;">' .
            htmlspecialchars(implode("\n", $commandOutput), ENT_QUOTES, 'UTF-8') .
            '</pre>';
    }

    renderOutputBox($detailsHtml);

    renderFormBoxStart();
    renderBackLink($backHref, $backLabel);
    renderFormBoxEnd();
}

renderPageStart('Restore Backup');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cleanupValidatedRestoreFile();

    echo '<form action="backup_restore.php" method="post" enctype="multipart/form-data" id="restoreform">';

    renderFormBoxStart();
    echo '<label>Select backup file to restore:</label><br><br>';
    echo '<input type="hidden" name="action" value="validate">';
    echo '<input type="file" id="backupfile" name="backupfile" accept=".gz,.tar.gz,application/gzip,application/x-gzip,application/octet-stream" style="display:none;" onchange="document.getElementById(\'selectedfile\').innerText=this.files.length?this.files[0].name:\'No file selected\';if(this.files.length){document.getElementById(\'restoreform\').submit();}">';
    echo '<input type="button" value="Choose File" onclick="document.getElementById(\'backupfile\').click();" style="width:100%;"><br><br>';
    echo '<div id="selectedfile" style="word-break:break-word;overflow-wrap:anywhere;">No file selected</div>';
    renderFormBoxEnd();

    echo '</form>';

    renderFormBoxStart();
    renderBackLink('backup.php');
    renderFormBoxEnd();

    renderPageEnd();
    exit;
}

$action = (string) ($_POST['action'] ?? 'validate');

if ($action === 'validate') {
    cleanupValidatedRestoreFile();

    if (isset($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > 0 && empty($_FILES)) {
        renderOutputBox('The uploaded file is too large for the current PHP upload limits.');
        renderOutputBox('Please increase upload_max_filesize and post_max_size in PHP configuration.');

        renderFormBoxStart();
        renderBackLink('backup_restore.php');
        renderFormBoxEnd();

        renderPageEnd();
        exit;
    }

    if (!isset($_FILES['backupfile']) || !is_array($_FILES['backupfile'])) {
        renderOutputBox('No file was uploaded.');

        renderFormBoxStart();
        renderBackLink('backup_restore.php');
        renderFormBoxEnd();

        renderPageEnd();
        exit;
    }

    $file = $_FILES['backupfile'];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errorMessage = 'The file upload failed.';

        switch ((int) $file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errorMessage = 'The uploaded file is too large for the current PHP upload limits.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMessage = 'The file was only partially uploaded.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMessage = 'No file was uploaded.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errorMessage = 'The temporary upload directory is missing.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errorMessage = 'The server could not write the uploaded file.';
                break;
            case UPLOAD_ERR_EXTENSION:
                $errorMessage = 'A PHP extension stopped the file upload.';
                break;
        }

        renderOutputBox($errorMessage);

        renderFormBoxStart();
        renderBackLink('backup_restore.php');
        renderFormBoxEnd();

        renderPageEnd();
        exit;
    }

    $originalName = (string) ($file['name'] ?? '');

    if (!preg_match('/\.tar\.gz$/i', $originalName)) {
        renderOutputBox('Invalid file type. Please upload a .tar.gz file.');

        renderFormBoxStart();
        renderBackLink('backup_restore.php');
        renderFormBoxEnd();

        renderPageEnd();
        exit;
    }

    if (!is_uploaded_file((string) $file['tmp_name'])) {
        renderOutputBox('The uploaded file is invalid.');

        renderFormBoxStart();
        renderBackLink('backup_restore.php');
        renderFormBoxEnd();

        renderPageEnd();
        exit;
    }

    $sourcePaths = parseSourcePaths(BACKUP_MANAGER_SCRIPT);
    $validationMessage = '';
    $matchedPaths = [];
    $archiveEntries = [];

    if (!validateBackupArchive((string) $file['tmp_name'], $sourcePaths, $validationMessage, $matchedPaths, $archiveEntries)) {
        renderOutputBox(htmlspecialchars($validationMessage, ENT_QUOTES, 'UTF-8'));

        renderFormBoxStart();
        renderBackLink('backup_restore.php');
        renderFormBoxEnd();

        renderPageEnd();
        exit;
    }

    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);

    if ($safeName === null || $safeName === '') {
        $safeName = 'restore_backup.tar.gz';
    }

    $targetFile = '/tmp/upload_restore_' . date('Ymd_His') . '_' . $safeName;

    if (!move_uploaded_file((string) $file['tmp_name'], $targetFile)) {
        renderOutputBox('Could not move the uploaded file.');

        renderFormBoxStart();
        renderBackLink('backup_restore.php');
        renderFormBoxEnd();

        renderPageEnd();
        exit;
    }

    $_SESSION['validated_restore_file'] = $targetFile;
    $_SESSION['validated_restore_name'] = $originalName;
    $_SESSION['validated_restore_paths'] = array_values($matchedPaths);
    $_SESSION['validated_restore_datetime'] = extractBackupDatetime($originalName);
    $_SESSION['validated_restore_entries'] = $archiveEntries;

    renderRestoreValidationView(
        (string) $_SESSION['validated_restore_name'],
        (string) $_SESSION['validated_restore_datetime'],
        (array) $_SESSION['validated_restore_paths']
    );

    renderPageEnd();
    exit;
}

if ($action === 'restore') {
    $targetFile = (string) ($_SESSION['validated_restore_file'] ?? '');

    if ($targetFile === '' || !is_file($targetFile)) {
        renderOutputBox('No validated backup file is available. Please select a file again.');

        renderFormBoxStart();
        renderBackLink('backup_restore.php');
        renderFormBoxEnd();

        renderPageEnd();
        exit;
    }

    $commandOutput = [];
    $returnCode = 0;

    exec('sudo ' . escapeshellarg(BACKUP_MANAGER_SCRIPT) . ' restore ' . escapeshellarg($targetFile) . ' 2>&1', $commandOutput, $returnCode);
    exec('sudo rm -f ' . escapeshellarg($targetFile));
    unset($_SESSION['validated_restore_file']);

    $fileName = (string) ($_SESSION['validated_restore_name'] ?? 'Unknown');
    $backupDateTime = (string) ($_SESSION['validated_restore_datetime'] ?? 'Unknown');
    $configuredPaths = (array) ($_SESSION['validated_restore_paths'] ?? []);
    $archiveEntries = (array) ($_SESSION['validated_restore_entries'] ?? []);

    if ($returnCode !== 0) {
        renderRestoreResultView(
            'The restore operation failed.',
            $fileName,
            $backupDateTime,
            $configuredPaths,
            $archiveEntries,
            $commandOutput,
            'backup_restore.php',
            'Back'
        );

        unset(
            $_SESSION['validated_restore_name'],
            $_SESSION['validated_restore_paths'],
            $_SESSION['validated_restore_datetime'],
            $_SESSION['validated_restore_entries']
        );

        renderPageEnd();
        exit;
    }

    renderRestoreResultView(
        'The backup was restored successfully.',
        $fileName,
        $backupDateTime,
        $configuredPaths,
        $archiveEntries,
        $commandOutput,
        'backup.php',
        'Back to Backup Menu'
    );

    unset(
        $_SESSION['validated_restore_name'],
        $_SESSION['validated_restore_paths'],
        $_SESSION['validated_restore_datetime'],
        $_SESSION['validated_restore_entries']
    );

    renderPageEnd();
    exit;
}

cleanupValidatedRestoreFile();
renderOutputBox('Invalid request.');

renderFormBoxStart();
renderBackLink('backup_restore.php');
renderFormBoxEnd();

renderPageEnd();
?>
