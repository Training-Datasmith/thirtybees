<?php

/** @noinspection PhpUnhandledExceptionInspection */
include 'config/config.php';
$action = Tools::get_value('action', '');
switch ($action) {
    case 'view':
        set_view_type(Tools::get_int_value('type', 0));
        break;
    case 'sort':
        set_sort_by(Tools::get_value('sort_by'));
        set_descending(Tools::get_value('descending') === 'true');
        break;
    case 'extract':
        $path = normalize_path(Tools::get_value('path', ''));
        $path = FILE_MANAGER_BASE_DIR . $path;
        $info = pathinfo($path);
        $base_folder = FILE_MANAGER_BASE_DIR . fix_dirname($path) . '/';
        switch ($info['extension']) {
            case 'zip':
                $zip = new Zip_Archive();
                if ($zip->open($path) === true) {
                    //make all the folders
                    for ($i = 0; $i < $zip->num_files; $i++) {
                        $only_file_name = $zip->get_name_index($i);
                        $full_file_name = $zip->stat_index($i);
                        if ($full_file_name['name'][strlen($full_file_name['name']) - 1] == '/') {
                            create_folder($base_folder . $full_file_name['name']);
                        }
                    }
                    //unzip into the folders
                    for ($i = 0; $i < $zip->num_files; $i++) {
                        $only_file_name = $zip->get_name_index($i);
                        $full_file_name = $zip->stat_index($i);
                        if (!($full_file_name['name'][strlen($full_file_name['name']) - 1] == '/')) {
                            $fileinfo = pathinfo($only_file_name);
                            if (in_array(strtolower($fileinfo['extension']), get_file_extensions())) {
                                copy('zip://' . $path . '#' . $only_file_name, $base_folder . $full_file_name['name']);
                            }
                        }
                    }
                    $zip->close();
                } else {
                    echo 'failed to open file';
                }
                break;
            case 'gz':
                $p = new Phar_Data($path);
                $p->decompress();
                // creates files.tar
                break;
            case 'tar':
                // unarchive from the tar
                $phar = new Phar_Data($path);
                $phar->decompress_files();
                $files = [];
                check_files_extensions_on_phar($phar, $files, '', get_file_extensions());
                $phar->extract_to($base_folder, $files, true);
                break;
        }
        break;
    case 'media_preview':
        $preview_file = Tools::get_value('file', '');
        $info = pathinfo($preview_file);
        ?>
        <div id="jp_container_1" class="jp-video " style="margin:0 auto;">
            <div class="jp-type-single">
                <div id="jquery_jplayer_1" class="jp-jplayer"></div>
                <div class="jp-gui">
                    <div class="jp-video-play">
                        <a href="javascript:;" class="jp-video-play-icon" tabindex="1">play</a>
                    </div>
                    <div class="jp-interface">
                        <div class="jp-progress">
                            <div class="jp-seek-bar">
                                <div class="jp-play-bar"></div>
                            </div>
                        </div>
                        <div class="jp-current-time"></div>
                        <div class="jp-duration"></div>
                        <div class="jp-controls-holder">
                            <ul class="jp-controls">
                                <li><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>
                                <li><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>
                                <li><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>
                                <li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a></li>
                                <li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a>
                                </li>
                                <li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">max
                                        volume</a></li>
                            </ul>
                            <div class="jp-volume-bar">
                                <div class="jp-volume-bar-value"></div>
                            </div>
                            <ul class="jp-toggles">
                                <li><a href="javascript:;" class="jp-full-screen" tabindex="1" title="full screen">full
                                        screen</a></li>
                                <li>
                                    <a href="javascript:;" class="jp-restore-screen" tabindex="1" title="restore screen">restore
                                        screen</a></li>
                                <li><a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat">repeat</a>
                                </li>
                                <li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off">repeat
                                        off</a></li>
                            </ul>
                        </div>
                        <div class="jp-title" style="display:none;">
                            <ul>
                                <li></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="jp-no-solution">
                    <span>Update Required</span>
                    To play the media you will need to either update your browser to a recent version or update your
                    <a href="http://get.adobe.com/flashplayer/" class="_blank">Flash plugin</a>.
                </div>
            </div>
        </div>
        <?php 
        if (in_array(strtolower($info['extension']), get_file_extensions('audio'))) {
            ?>
            <script type="text/javascript">
                $(document).ready(function () {

                    $("#jquery_jplayer_1").jPlayer({
                        ready: function () {
                            $(this).jPlayer("setMedia", {
                                title: "<?php 
            Tools::safe_output(Tools::get_value('title'));
            ?>",
                                mp3: "<?php 
            echo Tools::safe_output($preview_file);
            ?>",
                                m4a: "<?php 
            echo Tools::safe_output($preview_file);
            ?>",
                                oga: "<?php 
            echo Tools::safe_output($preview_file);
            ?>",
                                wav: "<?php 
            echo Tools::safe_output($preview_file);
            ?>"
                            });
                        },
                        swfPath: "js",
                        solution: "html,flash",
                        supplied: "mp3, m4a, midi, mid, oga,webma, ogg, wav",
                        smoothPlayBar: true,
                        keyEnabled: false
                    });
                });
            </script>

        <?php 
        } elseif (in_array(strtolower($info['extension']), get_file_extensions('video'))) {
            ?>

            <script type="text/javascript">
                $(document).ready(function () {

                    $("#jquery_jplayer_1").jPlayer({
                        ready: function () {
                            $(this).jPlayer("setMedia", {
                                title: "<?php 
            Tools::safe_output(Tools::get_value('title'));
            ?>",
                                m4v: "<?php 
            echo Tools::safe_output($preview_file);
            ?>",
                                ogv: "<?php 
            echo Tools::safe_output($preview_file);
            ?>"
                            });
                        },
                        swfPath: "js",
                        solution: "html,flash",
                        supplied: "mp4, m4v, ogv, flv, webmv, webm",
                        smoothPlayBar: true,
                        keyEnabled: false
                    });

                });
            </script>

        <?php 
        }
        break;
    default:
        die('no action passed');
}