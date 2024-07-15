<?php
define("webapp_path", dirname(dirname(__FILE__)));
define("GITURL", "https://raw.githubusercontent.com/ruvenss/LightWeb-WordPress/main/");
if (!file_exists("lightweb-wordpress.json")) {
    $LW_LOCAL = array("version" => 0);
} else {
    $LW_LOCAL = json_decode(file_get_contents(GITURL . "lightweb-wordpress.json"), true);
}
define("LW_LOCAL", $LW_LOCAL);
define("LW_RELEASE", json_decode(file_get_contents(GITURL . "lightweb-wordpress.json"), true));
if (LW_LOCAL['version'] === LW_RELEASE['version']) {
    echo "\nNothing to update\n";
} else {
    echo "\nNew release detected.\nCurrent version: " . LW_LOCAL['version'] . "\nLast Version: " . LW_RELEASE['version'] . "\n";
    define("LW_UPDATABLE_FILES", LW_RELEASE['updatable_files']);
    file_put_contents("lightweb-wordpress.json", json_encode(LW_RELEASE, JSON_PRETTY_PRINT));
    foreach (LW_UPDATABLE_FILES as $file2update) {
        $local_dest = $file2update;
        verify_path($file2update);
        echo "📁 " . $file2update;
        $file_content = file_get_contents(GITURL . $file2update);
        file_put_contents($local_dest, $file_content);
        echo " ✅\n";
    }
}
function verify_path($thifile)
{
    $file_arr = explode("/", $thifile);
    if ($file_arr > 1) {
        $ffpath = "";
        for ($i = 0; $i < sizeof($file_arr); $i++) {
            $ffpath .= "/" . $file_arr[$i];
            if (!str_contains($ffpath, ".")) {
                $path2check = str_replace("//", "/", webapp_path . $ffpath);
                if (!file_exists($path2check)) {
                    echo "Directory missing: " . "$path2check\n";
                    mkdir($path2check);
                }
            }
        }
    }
}
