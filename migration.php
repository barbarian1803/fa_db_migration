<?php

$page_security = 'SA_DB_Migration';
$path_to_root = "../../";
include($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/admin/db/maintenance_db.inc");

add_access_extensions();

$js = "";
if ($use_popup_windows)
    $js .= get_js_open_window(900, 500);
if ($use_date_picker)
    $js .= get_js_date_picker();

//Page start

page(_($help_context = "Migration"), false, false, $js);

if(isset($_POST["ADD_ITEM"]) && can_process()){
    submit_process();
}

if(isset($_POST["replace_submit"])){
    replace_process();
}

if(isset($_POST["up_one"])){
    migrate_up_one();
}

if(isset($_POST["up_fast_submit"])){
    migrate_fast_forward();
}

if(isset($_POST["roll_back_one"])){
    migrate_back_one();
}

if(isset($_POST["roll_back_fast_submit"])){
    migrate_back_fast();
}



$fp = fopen($path_to_root . '/modules/db_migration/files/'.$_SESSION["wa_current_user"]->company.'_migration_version.json', 'r');
$data = fread($fp, 4096);
$array = json_decode($data);
fclose($fp);

div_start('migration');

start_form(true);

tabbed_content_start('tabs', array(
    'info_status' => array(_('Info status'), true),
    'migration_file_list' => array(_('Migration file list'), true),
    'migration_file_uploader' => array(_('Migration file uploader'), true),
    'migration_file_replace' => array(_('Migration file replace'), true),
    'migration_process' => array(_('Migration process'), true),
));


switch (get_post('_tabs_sel')) {
    default:
        break;
    case 'info_status':
        info_status();
        break;
    case 'migration_file_list':
        migration_file_list();
        break;
    case 'migration_file_uploader':
        migration_file_uploader();
        break;
    case 'migration_file_replace':
        migration_file_replace();
        break;
    case 'migration_process':
        migration_process();
        break;
};
br();
tabbed_content_end();
end_form();
div_end();
end_page();

// Tab content
function info_status() {
    global $path_to_root,$array;

    br();
    echo "<center><h3>Migration version information</h3></center>";
    start_table(TABLESTYLE2,"width='25%'");
    label_row("Version", $array->version);
    label_row("Date", $array->date);
    end_table();
}

function migration_file_list() {
    global $path_to_root;

    $array = array_filter(scandir($path_to_root . '/modules/db_migration/files/'.$_SESSION["wa_current_user"]->company.'_migration_file'));

    br();
    echo "<center><h3>Migration file list</h3></center>";
    start_table(TABLESTYLE2,"width='25%'");
    rsort($array);
    foreach ($array as $name) {
        if($name[0]=='.')
            continue;
        label_row("File name", $name);
    }
    end_table();
}

function migration_file_uploader() {
    global $path_to_root, $array;
    
    br();
    echo "<center><h3>Migration file uploader</h3></center>";
    start_table(TABLESTYLE2,"width='30%'");
    
    label_row(_("Next migration version"), $array->version_counter);
    hidden("version", $array->version);
    hidden("version_counter", $array->version_counter);
    file_row(_("Migration up file") . ":", 'up_file', 'up_file');
    file_row(_("Migration down file") . ":", 'down_file', 'down_file');
    end_table(2);
    
    submit_add_or_update_center(true);
}

function migration_file_replace() {
    global $path_to_root, $array;
    
    br();
    echo "<center><h3>Replace migration file</h3></center>";
    if($array->version_counter>1){
        start_table(TABLESTYLE2,"width='30%'");
        $opt = array();
        for($i=1;$i<$array->version_counter;$i++){
            $opt[$i] = $i;
        }
        label_cells(_("Version to replace"), array_selector("version_replace", 0, $opt));
        file_row(_("Migration up file") . ":", 'up_file', 'up_file');
        file_row(_("Migration down file") . ":", 'down_file', 'down_file');
        end_table(2);
        echo "<center>";
        submit("replace_submit", "Upload");
        echo "</center>";
    }else{
        echo "<center><h4>No migration file version to replace</h4></center>";
    }
}

function can_process(){
    if(!isset($_FILES["up_file"])){
        display_error(_("Up file for migration is needed"));
        return false;
    }
    if(!isset($_FILES["down_file"])){
        display_error(_("Down file for migration is needed"));
        return false;
    }
    return true;
}

function process_upload($file,$type,$version){
    global $path_to_root;
    $upload_file = "";
    
    if (isset($_FILES[$file]) && $_FILES[$file]['name'] != '') {
        $result = $_FILES[$file]['error'];
        $upload_file = 'Yes'; //Assume all is well to start off with
        $filename = $path_to_root . '/modules/db_migration/files/'.$_SESSION["wa_current_user"]->company.'_migration_file';
        $filename .= "/".$type."_".$version.".sql";

        if ($_FILES[$file]['error'] == UPLOAD_ERR_INI_SIZE) {
            display_error(_('The file size is over the maximum allowed.'));
            $upload_file = 'No';
        } elseif ($_FILES[$file]['error'] > 0) {
            display_error(_('Error uploading file.'));
            $upload_file = 'No';
        }
        
        if ($upload_file == 'Yes') {
            $result = move_uploaded_file($_FILES[$file]['tmp_name'], $filename);
            return $filename;
        }else{
            return false;
        }
    }
}

function migration_process(){
    global $path_to_root,$array;
    br();
    start_table(TABLESTYLE2,"width=35%");
    $header = array(_("Action"),_("Option"),"");
    table_header($header);
    
    if($array->version<$array->version_counter-1){
        start_row();
        label_cell(_("Up one version"));
        label_cell(_("To version ".($array->version+1)));
        submit_cells("up_one", "Process");
        end_row();
    }
    if($array->version>0){
        start_row();
        label_cell(_("Roll back one version"));
        label_cell(_("To version ".($array->version-1)));
        submit_cells("roll_back_one", "Process");
        end_row();
    }
    if($array->version<$array->version_counter-1){
        $opt = array();
        for($i=$array->version+1;$i<$array->version_counter;$i++){
            $opt[$i]=$i;
        }
        start_row();
        label_cell(_("Up fast forward"));
        echo "<td>To version ".array_selector("up_fast", 0, $opt)."</td>";
        submit_cells("up_fast_submit", "Process");
        end_row();
    }
    if($array->version>0){
        $opt = array();
        for($i=$array->version-1;$i>=0;$i--){
            $opt[$i]=$i;
        }
        start_row();
        label_cell(_("Roll back"));
        echo "<td>To version ".array_selector("roll_back_fast", 0, $opt)."</td>";
        submit_cells("roll_back_fast_submit", "Process");
        end_row();
    }
    end_table(2);
}

function submit_process(){
    if(!process_upload("up_file","UP",$_POST["version_counter"])){
        display_error(_("Error upload file"));
        return;
    }
    if(!process_upload("down_file","DOWN",$_POST["version_counter"])){
        display_error(_("Error upload file"));
        return;
    }
    global $path_to_root;
    $array_json = array("version"=>$_POST["version"],"date"=>date("Y-m-d"),"version_counter"=>$_POST["version_counter"]+1);
    $fp = fopen($path_to_root.'/modules/db_migration/files/'.$_SESSION["wa_current_user"]->company.'_migration_version.json', 'w');
    fwrite($fp, json_encode($array_json));
    fclose($fp);
    $_POST["_tabs_sel"]="migration_file_list";
}

function replace_process(){
    if(!process_upload("up_file","UP",$_POST["version_replace"])){
        display_error(_("Error upload file"));
        return;
    }
    if(!process_upload("down_file","DOWN",$_POST["version_replace"])){
        display_error(_("Error upload file"));
        return;
    }
    $_POST["_tabs_sel"]="migration_file_list";
}

function migrate_up_one(){
    global $db_connections, $path_to_root;
    
    $fp = fopen($path_to_root . 'modules/db_migration/files/'.$_SESSION["wa_current_user"]->company.'_migration_version.json', 'r');
    $data = fread($fp, 4096);
    $array = json_decode($data);
    fclose($fp);
    
    $filename = $path_to_root . 'modules/db_migration/files/'.$_SESSION["wa_current_user"]->company.'_migration_file/'."UP_".($array->version+1).".sql";
    db_import($filename, $db_connections[$_SESSION["wa_current_user"]->company]);
    
    $array_json = array("version"=>($array->version+1),"date"=>date("Y-m-d"),"version_counter"=>($array->version_counter));
    $fp = fopen($path_to_root.'modules/db_migration/files/'.$_SESSION["wa_current_user"]->company.'_migration_version.json', 'w');
    fwrite($fp, json_encode($array_json));
    fclose($fp);
    $_POST["_tabs_sel"]="info_status";
}

function migrate_back_one(){
    global $db_connections, $path_to_root;
    
    $fp = fopen($path_to_root . 'modules/db_migration/files/'.$_SESSION["wa_current_user"]->company.'_migration_version.json', 'r');
    $data = fread($fp, 4096);
    $array = json_decode($data);
    fclose($fp);
    
    $filename = $path_to_root . 'modules/db_migration/files/'.$_SESSION["wa_current_user"]->company.'_migration_file/'."DOWN_".($array->version).".sql";
    db_import($filename, $db_connections[$_SESSION["wa_current_user"]->company]);
    
    $array_json = array("version"=>($array->version-1),"date"=>date("Y-m-d"),"version_counter"=>($array->version_counter));
    $fp = fopen($path_to_root.'modules/db_migration/files/'.$_SESSION["wa_current_user"]->company.'_migration_version.json', 'w');
    fwrite($fp, json_encode($array_json));
    fclose($fp);
    $_POST["_tabs_sel"]="info_status";
}

function migrate_fast_forward(){
    global $db_connections, $path_to_root;
    
    $to = get_post("up_fast");
    $fp = fopen($path_to_root . 'modules/db_migration/files/'.$_SESSION["wa_current_user"]->company.'_migration_version.json', 'r');
    $data = fread($fp, 4096);
    $array = json_decode($data);
    fclose($fp);
    
    for ($i=$array->version;$i<$to;$i++){
        migrate_up_one();
    }
}

function migrate_back_fast(){
    global $db_connections, $path_to_root;
    
    $to = get_post("roll_back_fast");
    
    $fp = fopen($path_to_root . 'modules/db_migration/files/'.$_SESSION["wa_current_user"]->company.'_migration_version.json', 'r');
    $data = fread($fp, 4096);
    $array = json_decode($data);
    fclose($fp);
    
    for ($i=$array->version;$i>$to;$i--){
        migrate_back_one();
    }
    
    $_POST["_tabs_sel"]="info_status";
}