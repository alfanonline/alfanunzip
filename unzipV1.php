<?php
session_start();
$startTime = microtime(true); // mulai timer
$statusMessage = ''; // untuk menyimpan pesan status

/* ===== BASE PATH ===== */
$base = realpath('.');
$path = realpath($_GET['path'] ?? '.') ?: $base;
if (strpos($path, $base) !== 0) $path = $base;

// Fungsi untuk mengubah path full jadi relatif
function urlPath($fullPath, $base){
    return str_replace('\\','/', substr($fullPath, strlen($base)+1));
}

/* ===== CREATE FOLDER ===== */
if (isset($_POST['newfolder']) && !empty($_POST['foldername'])) {
    $new = $path.'/'.basename($_POST['foldername']);
    if (!file_exists($new)) {
        mkdir($new,0777,true);
        $elapsed = microtime(true) - $startTime;
        $statusMessage = "Status: Folder berhasil dibuat: ".htmlspecialchars(basename($new))
                       ."<br>Processing Time: ".number_format($elapsed,4)." seconds";
    }
}

/* ===== CREATE FILE ===== */
if(isset($_POST['newfile']) && !empty($_POST['filename'])){
    $name = basename($_POST['filename']);
    $ext  = preg_replace('/[^a-zA-Z0-9]/','',$_POST['fileext'] ?? ''); 
    if($ext === '') $ext = 'txt'; // default txt jika kosong
    $newFile = $path.'/'.$name.($ext ? '.'.$ext : '');
    if(!file_exists($newFile)){
        file_put_contents($newFile, ""); // buat file kosong
        $elapsed = microtime(true) - $startTime;
        $statusMessage = "Status: File berhasil dibuat: ".htmlspecialchars(basename($newFile))
                       ."<br>Processing Time: ".number_format($elapsed,4)." seconds";
    } else {
        $statusMessage = "Status: File sudah ada!";
    }
}

/* ===== UPLOAD ===== */
if (isset($_FILES['upload']) && $_FILES['upload']['name'] !== '') {
    $destFile = $path.'/'.basename($_FILES['upload']['name']);
    if(move_uploaded_file($_FILES['upload']['tmp_name'], $destFile)){
        $elapsed = microtime(true) - $startTime;
        $statusMessage = "Status: File berhasil diunggah: ".htmlspecialchars(basename($destFile))
                       ."<br>Processing Time: ".number_format($elapsed,4)." seconds";
    } else {
        $elapsed = microtime(true) - $startTime;
        $statusMessage = "Status: Gagal mengunggah file<br>Processing Time: ".number_format($elapsed,4)." seconds";
    }
}

/* ===== DELETE ===== */
if (isset($_GET['delete'])) {
    $d = realpath($_GET['delete']);
    if ($d && strpos($d,$base)===0) {
        if(is_file($d)) unlink($d);
        if(is_dir($d)) {
            function rrmdir($dir){
                foreach(array_diff(scandir($dir), ['.','..']) as $f){
                    $p="$dir/$f";
                    is_dir($p)? rrmdir($p) : unlink($p);
                }
                rmdir($dir);
            }
            rrmdir($d);
        }
        $elapsed = microtime(true) - $startTime;
        $statusMessage = "Status: Berhasil menghapus ".htmlspecialchars(basename($d))
                       ."<br>Processing Time: ".number_format($elapsed,4)." seconds";
    }
}

/* ===== DOWNLOAD FILE ===== */
if (isset($_GET['download'])) {
    $file = realpath($_GET['download']);
    if ($file && strpos($file, $base) === 0 && is_file($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: no-cache');
        readfile($file);
        exit;
    }
}

/* ===== RENAME ===== */
if (isset($_POST['rename'])) {
    $old = $_POST['old'];
    $new = dirname($old).'/'.basename($_POST['new']);
    if(rename($old,$new)){
        $elapsed = microtime(true) - $startTime;
        $statusMessage = "Status: Berhasil merename "
                       .htmlspecialchars(basename($old))
                       ." <i class='bi bi-arrow-right'></i> "
                       .htmlspecialchars(basename($new))
                       ."<br>Processing Time: ".number_format($elapsed,4)." seconds";
    }
}

/* ===== MOVE ===== */
if (isset($_POST['move'])) {
    $sources = $_POST['src'] ?? []; 
    $destFolder = rtrim($_POST['dest'],'/');
    foreach ((array)$sources as $src) {
        $dest = $destFolder.'/'.basename($src);
        if(rename($src,$dest)){
            $elapsed = microtime(true) - $startTime;
            $statusMessage .= "Berhasil memindahkan ".htmlspecialchars(basename($src))
                            ." <i class='bi bi-arrow-right'></i> "
                            .htmlspecialchars(basename($dest))
                            ."<br>";
        }
    }
    if(isset($elapsed)) $statusMessage .= "Processing Time: ".number_format(microtime(true) - $startTime,4)." seconds";
}

/* ===== EXTRACT ZIP ===== */
if (isset($_POST['extractzip'])) {
    $zipPath = realpath($_POST['zipfile']);
    $dest = $_POST['dest'];
    if($zipPath && strpos($zipPath,$base)===0){
        $zip = new ZipArchive;
        if($zip->open($zipPath) === TRUE){
            if (!is_dir($dest)) mkdir($dest,0777,true);
            $zip->extractTo($dest);
            $zip->close();
            $elapsed = microtime(true) - $startTime;
            $statusMessage = "Status: Berhasil extract ".htmlspecialchars(basename($zipPath))
                           ."<br>Processing Time: ".number_format($elapsed,4)." seconds";
        }
    }
}

/* ===== LOAD FILE ===== */
if (isset($_GET['load'])) {
    header('Content-Type:text/plain; charset=utf-8');
    echo file_get_contents($_GET['load']);
    exit;
}

/* ===== SAVE FILE ===== */
if (isset($_POST['savefile'])) {
    if(file_put_contents($_POST['filepath'], $_POST['content'])){
        $elapsed = microtime(true) - $startTime;
        $statusMessage = "Status: Berhasil menyimpan ".htmlspecialchars(basename($_POST['filepath']))
                       ."<br>Processing Time: ".number_format($elapsed,4)." seconds";
    }
}

/* ===== LIST FILES ===== */
$files = array_diff(scandir($path), ['.','..']);
sort($files, SORT_NATURAL | SORT_FLAG_CASE); 

/* ===== LIST FOLDERS REKURSIF ===== */
function listFolders($dir,$base){
    foreach(scandir($dir) as $f){
        if($f=='.'||$f=='..') continue;
        $p="$dir/$f";
        if(is_dir($p)){
            echo "<option value=\"$p\">".htmlspecialchars(str_replace($base,'',$p))."</option>";
            listFolders($p,$base);
        }
    }
}
/* ===== DUPLICATE ===== */
if (isset($_GET['duplicate'])) {

    $src = realpath($_GET['duplicate']);

    if ($src && strpos($src, $base) === 0 && is_file($src)) {

        // ambil info file
        $info = pathinfo($src);

        $dir  = $info['dirname'];
        $name = $info['filename'];
        $ext  = isset($info['extension']) ? '.' . $info['extension'] : '';

        // mulai nomor dari 2
        $i = 2;

        do{
            $newFile = $dir . '/' . $name . $i . $ext;
            $i++;
        } while(file_exists($newFile));

        // copy file
        if(copy($src, $newFile)){

            $elapsed = microtime(true) - $startTime;

            $statusMessage = "Status: Berhasil menduplikat "
                           . htmlspecialchars(basename($src))
                           . " <i class='bi bi-arrow-right'></i> "
                           . htmlspecialchars(basename($newFile))
                           . "<br>Processing Time: "
                           . number_format($elapsed,4)
                           . " seconds";

        } else {

            $statusMessage = "Status: Gagal menduplikat file";

        }
    }
}

?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Alfan Unzip</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.3/ace.js"></script>
<style>
body.dark{background:#020617;color:#e5e7eb}
.editor{height:60vh;resize:both;overflow:auto;border:1px solid #ccc}
</style>
</head>
<body>
<div class="container mt-3">

<div class="d-flex mb-2 gap-2 align-items-center">
  <h4>
  <a href="?path=<?=urlencode($base)?>" style="text-decoration:none; color:inherit;">
    Alfan Unzip
  </a>
  </h4>

  <div class="ms-auto d-flex gap-1">
    <!-- Dark mode icon diganti Bootstrap Icon -->
    <button onclick="toggleDark()" class="btn btn-sm btn-dark">
      <i class="bi bi-moon-stars"></i>
    </button>
  </div>
</div>

<!-- STATUS ALERT -->
<?php if($statusMessage): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
  <?= $statusMessage ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex mb-2 gap-2 align-items-center">
<a href="?path=<?=dirname($path)?>" class="btn btn-sm btn-secondary">Back</a>

<span>
<?php
$relativePath = str_replace($base,'',$path); // ambil path relatif
$parts = explode('/', trim($relativePath,'/')); // pecah per folder
$breadcrumb = [];
$accum = $base; // path akumulasi

// Tambahkan root
$breadcrumb[] = '<a href="?path='.urlencode($base).'">home</a>';

// Loop tiap folder
foreach($parts as $p){
    $accum .= '/'.$p;
    $breadcrumb[] = '<a href="?path='.urlencode($accum).'">'.htmlspecialchars($p).'</a>';
}

// Tampilkan breadcrumb
echo implode(' &gt; ', $breadcrumb);
?>
</span>

<div class="ms-auto d-flex gap-1">
    <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#fileModal">
      <span style="color:white !important;">➕</span>
    </button>
</div>

<button id="toggleSelect" class="btn btn-sm btn-secondary">
  <i class="bi bi-square"></i> Tandai
</button>

<button id="moveBatchBtn" class="btn btn-sm btn-primary d-none">Move</button>
<button id="deleteBatchBtn" class="btn btn-sm btn-danger d-none">Hapus</button>

</div>

<!-- MODAL BUAT FOLDER / UPLOAD -->
<div class="modal fade" id="fileModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content rounded-4 overflow-hidden">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title m-0">Fitur</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <div class="container py-4">
          <form method="post" enctype="multipart/form-data" class="row g-4">
            <div class="col-12 col-md-6">
              <div class="p-4 bg-light rounded-3 shadow-sm d-flex flex-column gap-3 align-items-center h-100">
                <h6 class="fw-semibold text-center mb-2">
                  <i class="bi bi-folder-plus"></i> Buat Folder Baru
                </h6>
                <input name="foldername" placeholder="Nama folder" class="form-control">
                <button name="newfolder" class="btn btn-primary w-100 fw-semibold">Buat Folder</button>
              </div>
            </div>
            <div class="col-12 col-md-6">
              <div class="p-4 bg-light rounded-3 shadow-sm d-flex flex-column gap-3 align-items-center h-100">
                <h6 class="fw-semibold text-center mb-2">
                  <i class="bi bi-upload"></i> Upload File
                </h6>
                <input type="file" name="upload" class="form-control">
                <button class="btn btn-primary w-100 fw-semibold">Upload File</button>
              </div>
            </div>
            <div class="col-12 col-md-6">
              <div class="p-4 bg-light rounded-3 shadow-sm d-flex flex-column gap-3 align-items-stretch h-100">
                <h6 class="fw-semibold text-center mb-2">
                  <i class="bi bi-file-earmark-plus"></i> Buat File Baru
                </h6>
                <div class="d-flex gap-2">
                  <input name="filename" placeholder="Nama file" class="form-control" >
                  <h1>.</h1>
                  <input name="fileext" placeholder="Ektensi" class="form-control" style="width:100px">
                </div>
                <button name="newfile" class="btn btn-primary w-100 fw-semibold mt-2">Buat File</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- SEARCH & TABLE -->
<input id="search" class="form-control mb-2" placeholder="🔍 Search">
<hr>
<table class="table table-hover table-bordered">
<tbody id="fileTable">
<?php foreach($files as $f):
$p="$path/$f";

// CEK TIPE FILE
$ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
$imgExt = ['jpg','jpeg','png','gif','webp','bmp'];
$isImage = in_array($ext, $imgExt);

$audioExt = ['mp3','wav','ogg','m4a'];
$isAudio = in_array($ext, $audioExt);

$videoExt = ['mp4','webm','ogv','mov'];
$isVideo = in_array($ext, $videoExt);
?>
<tr class="rowfile">
	<td class="selectCol" style="display:none; vertical-align:middle;">
  <div style="display:flex; justify-content:center; align-items:center; height:100%;">
    <input type="checkbox" class="selectItem" value="<?=urlPath($p,$base)?>">
  </div>
</td>

<td style="width:40px; text-align:center; font-weight:bold;">
<?php 
$initial = strtoupper(substr($f, 0, 1));
if(!ctype_alpha($initial)){
    $initial = '#';
}
echo $initial;
?>
</td>

<!-- KOLM 1: ICON -->
<td style="width:40px; text-align:center;">
<?php 
if(is_dir($p)){
    echo '<i class="bi bi-folder-fill text-warning fs-5"></i>';
} elseif($isImage) {
    echo '<i class="bi bi-image-fill text-success fs-5"></i>';
} elseif($isAudio) {
    echo '<i class="bi bi-music-note-beamed text-primary fs-5"></i>';
} elseif($isVideo) {
    echo '<i class="bi bi-camera-video-fill text-danger fs-5"></i>';
} else {
    echo '<i class="bi bi-file-earmark-fill fs-5"></i>';
}
?>
</td>

<!-- KOLM 2: NAMA FILE/FOLDER -->
<td>
<?php if(is_dir($p)): ?>
    <a href="?path=<?= urlencode($p) ?>"><?= htmlspecialchars($f) ?></a>
<?php else: ?>
    <?= htmlspecialchars($f) ?>
<?php endif; ?>
</td>

<!-- KOLM 3: ACTIONS -->
<td class="text-end">
<div class="dropdown">
  <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
    Actions
  </button>
  <ul class="dropdown-menu dropdown-menu-end">
    <?php if(is_file($p)): ?>
        <?php if($isImage): ?>
        <li><a class="dropdown-item" href="#" onclick="viewImage('<?=urlPath($p,$base)?>')">View Image</a></li>
        <?php endif; ?>
        <?php if($isAudio): ?>
        <li><a class="dropdown-item" href="#" onclick="playAudio('<?=urlPath($p,$base)?>')">Play Audio</a></li>
        <?php endif; ?>
        <?php if($isVideo): ?>
        <li><a class="dropdown-item" href="#" onclick="playVideo('<?=urlPath($p,$base)?>')">Play Video</a></li>
        <?php endif; ?>
        <?php if(!$isImage && !$isAudio && !$isVideo): ?>
        <li><a class="dropdown-item" href="#" onclick="editFile('<?=urlPath($p,$base)?>')">Edit</a></li>
        <?php endif; ?>
        <li><a class="dropdown-item" href="?download=<?=urlPath($p,$base)?>">Download</a></li>
        <?php if($ext=='zip'): ?>
        <li><a class="dropdown-item" href="#" onclick="extractZip('<?=urlPath($p,$base)?>')">Extract ZIP</a></li>
        <?php endif; ?>
    <?php endif; ?>
<li>
<a class="dropdown-item"
   href="?duplicate=<?=urlPath($p,$base)?>&path=<?=urlencode($path)?>">
   Duplicate
</a>
</li>
    <li><a class="dropdown-item" href="#" onclick="renameItem('<?=urlPath($p,$base)?>')">Rename</a></li>
    <li><a class="dropdown-item" href="#" onclick="moveItem('<?=urlPath($p,$base)?>')">Move</a></li>
    <li><a class="dropdown-item text-danger" href="#" onclick="deleteItem('<?=urlPath($p,$base)?>')">Delete</a></li>
  </ul>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- EDITOR MODAL -->
<div class="modal fade" id="editorModal">
<div class="modal-dialog modal-xl">
<form method="post" class="modal-content">
<div class="modal-header">
<h5>Edit File</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<input type="hidden" name="filepath" id="filepath">
<div id="editor" class="editor"></div>
<textarea name="content" id="content" hidden></textarea>
</div>
<div class="modal-footer">
  <button type="button" class="btn btn-danger" onclick="clearEditor()">Hapus Text</button>
  <button type="button" class="btn btn-secondary" onclick="copyEditor()">Salin Text</button>
  <button name="savefile" class="btn btn-primary" onclick="saveEditor()">Save</button>
</div>

</form>
</div>
</div>

<!-- VIDEO PLAYER MODAL -->
<div class="modal fade" id="videoModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="videoModalTitle">Play Video</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <video id="videoModalContent" controls style="width:100%"></video>
      </div>
    </div>
  </div>
</div>

<!-- IMAGE VIEWER MODAL -->
<div class="modal fade" id="imageModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imageModalTitle">View Image</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <img id="imageModalContent" src="" class="img-fluid rounded">
      </div>
    </div>
  </div>
</div>

<!-- AUDIO PLAYER MODAL -->
<div class="modal fade" id="audioModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="audioModalTitle">Play Audio</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <audio id="audioModalContent" controls style="width:100%"></audio>
      </div>
    </div>
  </div>
</div>

<!-- MOVE MODAL -->
<div class="modal fade" id="moveModal">
<div class="modal-dialog">
<form method="post" class="modal-content">
<div class="modal-header">
<h5>Pindahkan ke Folder</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<input type="hidden" name="src" id="moveSrc">
<select name="dest" class="form-select">
<option value="<?= htmlspecialchars(realpath('.')) ?>">Home</option>
<?php listFolders($base,$base); ?>
</select>
</div>
<div class="modal-footer">
<button name="move" class="btn btn-primary">Move</button>
</div>
</form>
</div>
</div>

<!-- EXTRACT ZIP MODAL -->
<div class="modal fade" id="zipModal">
<div class="modal-dialog">
<form method="post" class="modal-content">
<div class="modal-header">
<h5>Extract ZIP</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<input type="hidden" name="zipfile" id="zipPath">
<select name="dest" class="form-select">
<option value="<?=$path?>">(Folder saat ini)</option>
<?php listFolders($base,$base); ?>
</select>
</div>
<div class="modal-footer">
<button name="extractzip" class="btn btn-success">Extract</button>
</div>
</form>
</div>
</div>

<!-- RENAME FORM -->
<form method="post" id="renameForm" hidden>
<input name="old" id="oldPath">
<input name="new" id="newName">
<input name="rename">
</form>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
	
const toggleSelectBtn = document.getElementById('toggleSelect');
const toggleIcon = toggleSelectBtn.querySelector('i'); // icon di dalam tombol
const moveBatchBtn = document.getElementById('moveBatchBtn');
const deleteBatchBtn = document.getElementById('deleteBatchBtn');
const checkboxes = document.querySelectorAll('.selectItem');

let selecting = false;

toggleSelectBtn.addEventListener('click', () => {
  selecting = !selecting;

  // Tampilkan / sembunyikan kolom checkbox
  document.querySelectorAll('.selectCol').forEach(td => {
    td.style.display = selecting ? 'table-cell' : 'none';
  });

  // Reset semua checkbox
  checkboxes.forEach(cb => cb.checked = false);

  // Sembunyikan tombol batch
  moveBatchBtn.classList.add('d-none');
  deleteBatchBtn.classList.add('d-none');

  // Ganti icon
  toggleIcon.className = selecting ? 'bi bi-check-square' : 'bi bi-square';
});


checkboxes.forEach(cb => {
  cb.addEventListener('change', () => {
    const anyChecked = Array.from(checkboxes).some(c => c.checked);
    moveBatchBtn.classList.toggle('d-none', !anyChecked);
    deleteBatchBtn.classList.toggle('d-none', !anyChecked);
  });
});

// MOVE BATCH
moveBatchBtn.addEventListener('click', () => {
  const selected = Array.from(checkboxes).filter(cb => cb.checked).map(cb => cb.value);
  if (!selected.length) return;
  
  const moveModalEl = document.getElementById('moveModal');
  const moveForm = moveModalEl.querySelector('form');
  
  moveForm.querySelectorAll('input[name="src[]"]').forEach(e=>e.remove());
  
  selected.forEach(path => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'src[]';
    input.value = path;
    moveForm.appendChild(input);
  });
  
  new bootstrap.Modal(moveModalEl).show();
});

// DELETE BATCH
deleteBatchBtn.addEventListener('click', () => {
  const selected = Array.from(checkboxes).filter(cb => cb.checked).map(cb => cb.value);
  if (!selected.length) return;
  if (!confirm(`Hapus ${selected.length} item?`)) return;
  
  const currentPath = '<?= $path ?>';
  selected.forEach(p => {
    window.location.href = '?delete=' + encodeURIComponent(p) + '&path=' + encodeURIComponent(currentPath);
  });
});


	
	
let editor = ace.edit("editor");
editor.setTheme("ace/theme/monokai");
editor.session.setMode("ace/mode/php");

function editFile(p){
 fetch('?load='+encodeURIComponent(p))
 .then(r=>r.text())
 .then(t=>{
   editor.setValue(t,-1);
   filepath.value=p;
   new bootstrap.Modal(editorModal).show();
 });
}
function saveEditor(){ content.value = editor.getValue(); }
function renameItem(p){
 let n=prompt("Nama baru:", p.split('/').pop()); 
 if(!n) return;
 oldPath.value=p; newName.value=n;
 renameForm.submit();
}
function moveItem(p){ moveSrc.value=p; new bootstrap.Modal(moveModal).show(); }
function extractZip(p){ zipPath.value=p; new bootstrap.Modal(zipModal).show(); }

// DELETE
function deleteItem(p){
    if(!confirm('Hapus?')) return;
    const currentPath = '<?= $path ?>';
    window.location.href = '?delete=' + encodeURIComponent(p) + '&path=' + encodeURIComponent(currentPath);
}

// SEARCH
document.getElementById('search').addEventListener('keyup',e=>{
 let v=e.target.value.toLowerCase();
 document.querySelectorAll('.rowfile').forEach(r=>{
  r.style.display=r.innerText.toLowerCase().includes(v)?'':'none';
 });
});

// DARK MODE
function toggleDark(){
 document.body.classList.toggle('dark');
 editor.setTheme(document.body.classList.contains('dark')
  ? 'ace/theme/monokai'
  : 'ace/theme/github');
}

// VIEW IMAGE
function viewImage(path){
  const modalTitle = document.getElementById('imageModalTitle');
  const modalImg = document.getElementById('imageModalContent');
  modalTitle.innerText = path.split('/').pop();
  modalImg.src = path;
  new bootstrap.Modal(document.getElementById('imageModal')).show();
}

// PLAY AUDIO
function playAudio(path){
  const modalTitle = document.getElementById('audioModalTitle');
  const audio = document.getElementById('audioModalContent');
  modalTitle.innerText = path.split('/').pop();
  audio.src = path;
  audio.play();
  new bootstrap.Modal(document.getElementById('audioModal')).show();
}

// PLAY VIDEO
function playVideo(path){
  const modalTitle = document.getElementById('videoModalTitle');
  const video = document.getElementById('videoModalContent');
  modalTitle.innerText = path.split('/').pop();
  video.src = path;
  video.load();
  video.play();
  new bootstrap.Modal(document.getElementById('videoModal')).show();
}

// Hapus semua isi editor
function clearEditor(){
    if(confirm("Yakin ingin menghapus semua text?")) {
        editor.setValue("", -1);
    }
}

// Salin semua isi editor
function copyEditor(){
    const text = editor.getValue();
    navigator.clipboard.writeText(text)
        .then(()=> alert("Teks berhasil disalin!"))
        .catch(()=> alert("Gagal menyalin teks"));
}


</script>
</body>
</html>
