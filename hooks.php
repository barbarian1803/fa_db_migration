<?php

define('SS_DBMANAGER', 101 << 9);

class hooks_db_migration extends hooks {

    var $module_name = 'db_migration';

    /*
      Install additonal menu options provided by module
     */

    function install_options($app) {
        global $path_to_root;

        switch ($app->id) {
            case 'system':
                $app->add_rapp_function(2, _("&Database migration"), $path_to_root . "/modules/db_migration/migration.php?", 'SA_DB_Migration', MENU_SYSTEM);
                break;
        }
    }

    function install_access() {

        $security_sections[SS_DBMANAGER] = _("Database manager");
        $security_areas['SA_DB_Migration'] = array(SS_DBMANAGER | 1, _("Migration manager"));

        return array($security_areas, $security_sections);
    }

    /* This method is called on extension activation for company. 	 */

    function activate_extension($company, $check_only = true) {
        global $path_to_root;
        $array_json = array("version" => 0, "date" => date("Y-m-d"), "version_counter" => 1);
        $fp = fopen($path_to_root . '/modules/db_migration/files/' . $company . '_migration_version.json', 'w');
        fwrite($fp, json_encode($array_json));
        fclose($fp);
        mkdir($path_to_root . '/modules/db_migration/files/' . $company . '_migration_file');
        return;
    }

    function deactivate_extension($company, $check_only = true) {
        global $path_to_root;
        foreach(glob($path_to_root . '/modules/db_migration/files/' . $company . '_migration_file/*') as $file){
            unlink($file);
        }
        rmdir($path_to_root . '/modules/db_migration/files/' . $company . '_migration_file');
        unlink($path_to_root . '/modules/db_migration/files/' . $company . '_migration_version.json');
        return true;
    }

}

?>