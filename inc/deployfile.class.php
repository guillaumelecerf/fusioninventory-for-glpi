<?php

/*
   ------------------------------------------------------------------------
   FusionInventory
   Copyright (C) 2010-2012 by the FusionInventory Development Team.

   http://www.fusioninventory.org/   http://forge.fusioninventory.org/
   ------------------------------------------------------------------------

   LICENSE

   This file is part of FusionInventory project.

   FusionInventory is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   FusionInventory is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with Behaviors. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   FusionInventory
   @author    Alexandre Delaunay
   @co-author
   @copyright Copyright (c) 2010-2012 FusionInventory team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      http://www.fusioninventory.org/
   @link      http://forge.fusioninventory.org/projects/fusioninventory-for-glpi/
   @since     2010

   ------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginFusioninventoryDeployFile extends CommonDBTM {

   static function getTypeName($nb=0) {
      return __('Files', 'fusioninventory');
   }

   static function canCreate() {
      return TRUE;
   }

   static function canView() {
      return TRUE;
   }

   
   
   static function getTypes() {
      return array(
         'Computer' => __("Upload from computer", 'fusioninventory'),
         'Server'   => __("Upload from server", 'fusioninventory')
      );
   }

   
   
   static function displayForm($orders_id, $datas, $rand) {
      global $CFG_GLPI;

      if (!isset($datas['index'])) {
         echo "<div style='display:none' id='files_block$rand' >";
      } else {
         //== edit selected data ==
         
         //get current order json
         $datas_o = json_decode(PluginFusioninventoryDeployOrder::getJson($orders_id), TRUE);

         //get data on index
         $sha512 = $datas_o['jobs']['associatedFiles'][$datas['index']];   
         $file = $datas_o['associatedFiles'][$sha512]; 
      }


      echo "<span id='showFileType$rand'></span>";
      echo "<script type='text/javascript'>";
      $params = array(
         'rand'    => $rand,
         'subtype' => "file"
      );
      if (isset($datas['index'])) {
         $params['edit']                   = "true";
         $params['index']                  = $datas['index'];
         $params['p2p']                    = $file['p2p'];
         $params['p2p-retention-duration'] = $file['p2p-retention-duration'];
         $params['uncompress']             = $file['uncompress'];
      }
      Ajax::UpdateItemJsCode("showFileType$rand",
                             $CFG_GLPI["root_doc"].
                             "/plugins/fusioninventory/ajax/deploydropdown_packagesubtypes.php",
                             $params,
                             "dropdown_deploy_filetype");
      echo "</script>";


      echo "<span id='showFileValue$rand'></span>";
      
      echo "<hr>";
      echo "</div>";
      Html::closeForm();

      //display stored files datas
      if (!isset($datas['jobs']['associatedFiles']) || empty($datas['jobs']['associatedFiles'])) {
         return;
      }
      echo "<form name='removefiles' method='post' action='deploypackage.form.php?remove_item' ".
         "id='filesList$rand'>";
      echo "<input type='hidden' name='itemtype' value='PluginFusioninventoryDeployFile' />";
      echo "<input type='hidden' name='orders_id' value='$orders_id' />";
      echo "<div id='drag_files'>";
      echo "<table class='tab_cadrehov package_item_list' id='table_file_$rand'>";
      $i = 0;
      foreach ($datas['jobs']['associatedFiles'] as $sha512) {
         echo Search::showNewLine(Search::HTML_OUTPUT, ($i%2));
         echo "<td class='control'><input type='checkbox' name='file_entries[]' value='$i' /></td>";
         $filename = $datas['associatedFiles'][$sha512]['name'];
         $filesize = $datas['associatedFiles'][$sha512]['filesize'];

         //mimetype icon
         $mimetype = isset($datas['associatedFiles'][$sha512]['mimetype'])?
            str_replace('/', '__', $datas['associatedFiles'][$sha512]['mimetype']):NULL;
         echo "<td class='filename'>";
         if (!empty($mimetype) 
           && file_exists(GLPI_ROOT."/plugins/fusioninventory/pics/ext/extensions/$mimetype.png")) {
               echo "<img src='".$CFG_GLPI['root_doc'].
                  "/plugins/fusioninventory/pics/ext/extensions/$mimetype.png' />";
         } else echo "<img src='".$CFG_GLPI['root_doc'].
               "/plugins/fusioninventory/pics/ext/extensions/documents.png' />";

         //filename      
         echo"&nbsp;<a class='edit' onclick='edit_files($i)'>$filename</a>";

         //p2p icon
         if (isset($datas['associatedFiles'][$sha512]['p2p'])
            && $datas['associatedFiles'][$sha512]['p2p'] != 0) {
            echo "<a title='".__('p2p', 'fusioninventory').", "
            .__("retention", 'fusioninventory')." : ".
               $datas['associatedFiles'][$sha512]['p2p-retention-duration']." ".
               __("days", 'fusioninventory')."' class='more'>";
               echo "<img src='".$CFG_GLPI['root_doc'].
               "/plugins/fusioninventory/pics/p2p.png' />";
               echo "<sup>".$datas['associatedFiles'][$sha512]['p2p-retention-duration']."</sup>";
               echo "</a>";
         }

         //uncompress icon
         if (isset($datas['associatedFiles'][$sha512]['uncompress']) 
            && $datas['associatedFiles'][$sha512]['uncompress'] != 0) {
               echo "<a title='".__('uncompress', 'fusioninventory')."' class='more'><img src='".
                  $CFG_GLPI['root_doc']."/plugins/fusioninventory/pics/uncompress.png' /></a>";
         }

         //filesize
         echo "<br />";
         echo self::processFilesize($filesize);
         echo "</td>";
         echo "<td class='rowhandler control' title='".__('drag', 'fusioninventory').
            "'><div class='drag row'></div></td>";
         $i++;
      }
      echo "<tr><th>";
      Html::checkAllAsCheckbox("filesList$rand", mt_rand());
      echo "</th><th colspan='3'></th></tr>";
      echo "</table></div>";
      echo "&nbsp;&nbsp;<img src='".$CFG_GLPI["root_doc"]."/pics/arrow-left.png' alt=''>";
      echo "<input type='submit' name='delete' value=\"".
         __('Delete', 'fusioninventory')."\" class='submit'>";
      Html::closeForm();

      echo "<script type='text/javascript'>
         function edit_files(index) {
            //remove all border to previous selected item (remove classes)
            Ext.select('#table_file_$rand tr').removeClass('selected');

            //add border to selected index (add class)
            Ext.select('#table_file_$rand tr:nth-child('+(index+1)+')').addClass('selected');

            //scroll to edit form
            document.getElementById('th_title_file_$rand').scrollIntoView();

            //remove plus button
            if (Ext.get('plus_files_block$rand')) Ext.get('plus_files_block$rand').remove();

            //show and load form
            Ext.get('files_block$rand').setDisplayed('block');
            Ext.get('files_block$rand').load({
                  'url': '".$CFG_GLPI["root_doc"].
                             "/plugins/fusioninventory/ajax/deploypackage_form.php',
                  'scripts': true,
                  'params' : {
                     'subtype': 'file',
                     'index': index, 
                     'orders_id': $orders_id, 
                     'rand': '$rand'
                  }
               });
         }
      </script>";
   }

   
   
   static function dropdownType($datas) {
      global $CFG_GLPI;

      $rand = $datas['rand'];

      $file_types = self::getTypes();
      array_unshift($file_types, "---");

      $style = "";
      if (isset($datas['edit'])) {
         $style = "style='display:none'";
      }
      echo "<table class='package_item' $style>";
      echo "<tr>";
      echo "<th>".__("Source", 'fusioninventory')."</th>";
      echo "<td>";
      $options['rand'] = $datas['rand'];
      Dropdown::showFromArray("deploy_filetype", $file_types, $options);
      echo "</td>";
      echo "</tr></table>";

      //ajax update of file value span
      $params = array(
                      'value'      => '__VALUE__',
                      'rand'       => $rand,
                      'myname'     => 'method',
                      'type'       => "file", 
                      'p2p'        => 0,
                      'uncompress' => 0,
               );
      if (isset($datas['edit'])) {
         $params['edit']                   = "true";
         $params['index']                  = $datas['index'];
         $params['p2p']                    = $datas['p2p'];
         $params['p2p-retention-duration'] = $datas['p2p-retention-duration'];
         $params['uncompress']             = $datas['uncompress'];
      }
      Ajax::updateItemOnEvent("dropdown_deploy_filetype".$rand,
                              "showFileValue$rand",
                              $CFG_GLPI["root_doc"].
                              "/plugins/fusioninventory/ajax/deploy_displaytypevalue.php",
                              $params,
                              array("change", "load"));
      if (isset($datas['edit'])) {
         echo "<script type='text/javascript'>";
         Ajax::UpdateItemJsCode("showFileValue$rand",
                                $CFG_GLPI["root_doc"].
                                 "/plugins/fusioninventory/ajax/deploy_displaytypevalue.php",
                                $params,
                                "dropdown_deploy_filetype$rand");
         echo "</script>";
      }

   }

   
   
   static function displayAjaxValue($datas) {
      global $CFG_GLPI;

      $source = $datas['value'];
      $rand  = $datas['rand'];

      $p2p_checked = $datas['p2p'] == 1?"checked='checked'":"";
      $p2p_ret_value = isset($datas['p2p-retention-duration'])?$datas['p2p-retention-duration']:"";
      $uncompress_checked = $datas['uncompress'] == 1?"checked='checked'":"";

      echo "<table class='package_item'>";
      if (!isset($datas['edit']) || $datas['edit'] !== "true") {
         echo "<tr>";
         echo "<th>".__("File", 'fusioninventory')."</th>";
         echo "<td>";
         switch ($source) {
            case "Computer":
               echo "<input type='file' name='file' value='".
                  __("filename", 'fusioninventory')."' />";
               break;
            case "Server":
               echo "<input type='text' name='filename' id='server_filename$rand'".
                  " style='width:120px;float:left' />";
               echo "<input type='button' class='submit' value='".__("Choose", 'fusioninventory').
                  "' onclick='fileModal$rand.show();' style='width:50px' />";
               Ajax::createModalWindow("fileModal$rand", 
                        $CFG_GLPI['root_doc']."/plugins/fusioninventory/ajax/deployfilemodal.php",
                        array('title' => __('Select the file on server', 'fusioninventory'), 
                        'extraparams' => array(
                           'rand' => $rand
                        )));
               break;
         }
         echo "</td>";
         echo "</tr>";
      }
      echo "<tr>";
      echo "<th>".__("Uncompress", 'fusioninventory')."<img style='float:right' ".
             "src='".$CFG_GLPI["root_doc"]."/plugins/fusioninventory//pics/uncompress.png' /></th>";
      echo "<td><input type='checkbox' name='uncompress' $uncompress_checked /></td>";
      echo "</tr><tr>";
      echo "<th>".__("P2p", 'fusioninventory').
              "<img style='float:right' src='".$CFG_GLPI["root_doc"].
              "/plugins/fusioninventory//pics/p2p.png' /></th>";
      echo "<td><input type='checkbox' name='p2p' $p2p_checked /></td>";
      echo "</tr><tr>";
      echo "<th>".__("retention days", 'fusioninventory')."</th>";
      echo "<td><input type='text' name='p2p-retention-duration' style='width:30px' 
         value='$p2p_ret_value' /></td>";
      echo "</tr><tr>";
      echo "<td>";
      if ($source === "Computer") {
         echo "<i>".self::getMaxUploadSize()."</i>";
      }
      echo "</td><td>";
      if (isset($datas['edit'])) {
         echo "<input type='hidden' name='index' value='".$datas['index']."' />";
         echo "<input type='submit' name='save_item' value=\"".
            _sx('button', 'Save')."\" class='submit' >";
      } else {
         echo "<input type='submit' name='add_item' value=\"".
            _sx('button', 'Add')."\" class='submit' >";
      }
      echo "</td>";
      echo "</tr></table>";
   }

   
   
   static function showServerFileTree($params) {
      global $CFG_GLPI;

      $rand = $params['rand'];

      echo "<script type='javascript'>";
      echo "var Tree_Category_Loader$rand = new Ext.tree.TreeLoader({
         dataUrl:'".$CFG_GLPI["root_doc"]."/plugins/fusioninventory/ajax/serverfilestreesons.php'
      });";

      echo "var Tree_Category$rand = new Ext.tree.TreePanel({
         collapsible      : false,
         animCollapse     : false,
         border           : false,
         id               : 'tree_projectcategory$rand',
         el               : 'tree_projectcategory$rand',
         autoScroll       : true,
         animate          : false,
         enableDD         : true,
         containerScroll  : true,
         height           : 320,
         width            : 770,
         loader           : Tree_Category_Loader$rand,
         rootVisible      : false, 
         listeners: {
            click: function(node, event){
               if (node.leaf == true) {
                  console.log('server_filename$rand');
                  Ext.get('server_filename$rand').dom.value = node.id;
                  fileModal$rand.hide();
               }
            }
         }
      });";

      // SET the root node.
      echo "var Tree_Category_Root$rand = new Ext.tree.AsyncTreeNode({
         text     : '',
         draggable   : false,
         id    : '-1'                  // this IS the id of the startnode
      });
      Tree_Category$rand.setRootNode(Tree_Category_Root$rand);";

      // Render the tree.
      echo "Tree_Category$rand.render();
            Tree_Category_Root$rand.expand();";

      echo "</script>";

      echo "<div id='tree_projectcategory$rand' ></div>";
      echo "</div>";
   }

   
   
   static function getServerFileTree($params) {

      $nodes = array();

      if (isset($params['node'])) {

         //root node
         $pfConfig = new PluginFusioninventoryConfig();
         $dir = $pfConfig->getValue('server_upload_path');

         // leaf node
         if ($params['node'] != -1) {
            $dir = $params['node'];
         }
         
         if (($handle = opendir($dir))) {
            $folders = $files = array();

            //list files in dir selected
            //we store folders and files separately to sort them alphabeticaly separatly
            while (FALSE !== ($entry = readdir($handle))) {
               if ($entry != "." && $entry != "..") {
                  $filepath = $dir."/".$entry;
                  if (is_dir($filepath)) {
                     $folders[$filepath] = $entry;
                  } else {
                     $files[$filepath] = $entry;
                  }
               }
            }

            //sort folders and files (and maintain index association)
            asort($folders);
            asort($files);

            //add folders in json
            foreach ($folders as $filepath => $entry) {
               $path['text'] = $entry;
               $path['id'] = $filepath;
               $path['draggable'] = FALSE;
               $path['leaf']      = FALSE;
               $path['cls']       = 'folder';

               $nodes[] = $path;
            }

            //add files in json
            foreach ($files as $filepath => $entry) {
               $path['text'] = $entry;
               $path['id'] = $filepath;
               $path['draggable'] = FALSE;
               $path['leaf']      = TRUE;
               $path['cls']       = 'file';

               $nodes[] = $path;
            }

            closedir($handle);
         }        
      }

      print json_encode($nodes);
   }


   
   static function getExtensionsWithAutoAction() {
      $ext = array();

      $ext['msi']['install']     = "msiexec /qb /i ##FILENAME## REBOOT=ReallySuppress";
      $ext['msi']['uninstall']   = "msiexec /qb /x ##FILENAME## REBOOT=ReallySuppress";

      $ext['deb']['install']     = "dpkg -i ##FILENAME## ; apt-get install -f";
      $ext['deb']['uninstall']   = "dpkg -P ##FILENAME## ; apt-get install -f";

      $ext['rpm']['install']     = "rpm -Uvh ##FILENAME##";
      $ext['rpm']['install']     = "rpm -ev ##FILENAME##";

      return $ext;
   }

   
   
   static function add_item($params) {
      switch ($params['deploy_filetype']) {
         case 'Server':
            self::uploadFileFromServer($params);
            break;
         default:
            self::uploadFileFromComputer($params);
      }
   }
   
   
   static function remove_item($params) {
      if (!isset($params['file_entries'])) {
         return FALSE;
      }

      //get current order json
      $datas = json_decode(PluginFusioninventoryDeployOrder::getJson($params['orders_id']), TRUE);

      //remove selected checks
      foreach ($params['file_entries'] as $index) {
         //get sha512
         $sha512 = $datas['jobs']['associatedFiles'][$index];

         //remove file
         unset($datas['jobs']['associatedFiles'][$index]);
         unset($datas['associatedFiles'][$sha512]);

         //remove file in repo
         self::removeFileInRepo($sha512, $params['orders_id']);
      }

      //update order
      PluginFusioninventoryDeployOrder::updateOrderJson($params['orders_id'], $datas);
   }

   
   
   static function move_item($params) {
      //get current order json
      $datas = json_decode(PluginFusioninventoryDeployOrder::getJson($params['orders_id']), TRUE);

      //get data on old index
      $moved_check = $datas['jobs']['associatedFiles'][$params['old_index']];

      //remove this old index in json
      unset($datas['jobs']['associatedFiles'][$params['old_index']]);

      //insert it in new index (array_splice for insertion, ex : http://stackoverflow.com/a/3797526)
      array_splice($datas['jobs']['associatedFiles'], $params['new_index'], 0, array($moved_check));

      //update order
      PluginFusioninventoryDeployOrder::updateOrderJson($params['orders_id'], $datas);
   }


   static function save_item($params) {
      //get current order json
      $datas = json_decode(PluginFusioninventoryDeployOrder::getJson($params['orders_id']), TRUE);

      //get sha512
      $sha512 = $datas['jobs']['associatedFiles'][$params['index']];

      //get file in json
      $file = $datas['associatedFiles'][$sha512];

      //remove value in json
      unset($datas['associatedFiles'][$sha512]);

      //update values
      $file['p2p']                    = isset($params['p2p']) ? 1 : 0;
      $file['p2p-retention-duration'] = $params['p2p-retention-duration'];
      $file['uncompress']             = isset($params['uncompress']) ? 1 : 0;

      //add modified entry
      $datas['associatedFiles'][$sha512] = $file;

      //update order
      PluginFusioninventoryDeployOrder::updateOrderJson($params['orders_id'], $datas);
   }

   static function uploadFileFromComputer($params) {
      if (isset($params["orders_id"])) {

         //file uploaded?
         if (isset($_FILES['file']['tmp_name']) and !empty($_FILES['file']['tmp_name'])){
            $file_tmp_name = $_FILES['file']['tmp_name'];
         } 
         if (isset($_FILES['file']['name']) 
                 && !empty($_FILES['file']['name'])) {
            $filename = $_FILES['file']['name'];
         }

         //file upload errors
         if (isset($_FILES['file']['error'])) {
            $error = TRUE;
            switch ($_FILES['file']['error']) {
               case UPLOAD_ERR_INI_SIZE:
               case UPLOAD_ERR_FORM_SIZE:
                  $msg = __("Transfer error: the file size is too big", 'fusioninventory');
                  break;
               case UPLOAD_ERR_PARTIAL:
                  $msg = __("he uploaded file was only partially uploaded", 'fusioninventory');
                  break;
               case UPLOAD_ERR_NO_FILE:
                  $msg = __("No file was uploaded", 'fusioninventory');
                  break;
               case UPLOAD_ERR_NO_TMP_DIR:
                  $msg = __("Missing a temporary folder", 'fusioninventory');
                  break;
               case UPLOAD_ERR_CANT_WRITE:
                  $msg = __("Failed to write file to disk", 'fusioninventory');
                  break;
               case UPLOAD_ERR_CANT_WRITE:
                  $msg = __("PHP extension stopped the file upload", 'fusioninventory');
                  break;
               case UPLOAD_ERR_OK:
                  //no error, continue
                  $error = FALSE;
            }
            if ($error) {
               Session::addMessageAfterRedirect($msg);
               return FALSE;
            }
         }

         //prepare file data for insertion in repo
         $datas = array(
            'file_tmp_name' => $file_tmp_name,
            'mime_type' => $_FILES['file']['type'],
            'filesize' => $_FILES['file']['size'],
            'filename' => $filename,
            'p2p' => isset($_POST['p2p']) ? 1 : 0,
            'uncompress' => isset($_POST['uncompress']) ? 1 : 0,
            'p2p-retention-duration' => is_numeric($params['p2p-retention-duration']) ? 
               $params['p2p-retention-duration'] : 0,
            'orders_id' => $params['orders_id']
         );

         //Add file in repo
         if ($filename && self::addFileInRepo($datas)) {
            Session::addMessageAfterRedirect(__('File saved!', 'fusioninventory'));
            return TRUE;
         } else {
            Session::addMessageAfterRedirect(__('Failed to copy file', 'fusioninventory'));
            return FALSE;
         }
      }
      Session::addMessageAfterRedirect(__('File missing', 'fusioninventory'));
      return FALSE;
   }

   static function uploadFileFromServer($params) {
      if (preg_match('/\.\./', $params['filename'])) {
         die;
      }

      if (isset($params["orders_id"])) {
         $file_path = $params['filename'];
         $filename = basename($file_path);
         $mime_type = @mime_content_type($file_path);
         $filesize = filesize($file_path);

         //prepare file data for insertion in repo
         $datas = array(
            'file_tmp_name' => $file_path,
            'mime_type' => $mime_type,
            'filesize' => $filesize,
            'filename' => $filename,
            'p2p' => isset($_POST['p2p']) ? 1 : 0,
            'uncompress' => isset($_POST['uncompress']) ? 1 : 0,
            'p2p-retention-duration' => is_numeric($_POST['p2p-retention-duration']) ? 
               $_POST['p2p-retention-duration'] : 0,
            'orders_id' => $params['orders_id']
         );

         //Add file in repo
         if ($filename && self::addFileInRepo($datas)) {
            Session::addMessageAfterRedirect(__('File saved!', 'fusioninventory'));
            return TRUE;
         } else {
            Session::addMessageAfterRedirect(__('Failed to copy file', 'fusioninventory'));
            return FALSE;
         }
      }
      Session::addMessageAfterRedirect(__('File missing', 'fusioninventory'));
      return FALSE;
   }

   
   
   static function getDirBySha512 ($sha512) {
      $first = substr($sha512, 0, 1);
      $second = substr($sha512, 0, 2);

      return "$first/$second";
   }

   
   
   function registerFilepart ($repoPath, $filePath, $skip_creation = FALSE) {
      $sha512 = hash_file('sha512', $filePath);

      if (!$skip_creation) {
         $dir = $repoPath.'/'.self::getDirBySha512($sha512);

         if (!file_exists ($dir)) {
            mkdir($dir, 0700, TRUE);
         }
         copy ($filePath, $dir.'/'.$sha512.'.gz');
      }

      return $sha512;
   }

   
   
   static function addFileInRepo ($params) {
      set_time_limit(600);

      $deployFile = new self;

      $filename = addslashes($params['filename']);
      $file_tmp_name = $params['file_tmp_name'];

      $maxPartSize = 1024*1024;
      $repoPath = GLPI_PLUGIN_DOC_DIR."/fusioninventory/files/repository/";
      $tmpFilepart = tempnam(GLPI_PLUGIN_DOC_DIR."/fusioninventory/", "filestore");

      $sha512 = hash_file('sha512', $file_tmp_name);
      $short_sha512 = substr($sha512, 0, 6);

      $file_present_in_repo = FALSE;
      if($deployFile->checkPresenceFile($sha512)) {
         $file_present_in_repo = TRUE;
      }
      
      $new_entry = array(
         'name' => $filename,
         'p2p' => $params['p2p'],
         'mimetype' => $params['mime_type'],
         'filesize' => $params['filesize'],
         'p2p-retention-duration' => $params['p2p-retention-duration'],
         'uncompress' => $params['uncompress'],
      );

      $fdIn = fopen ( $file_tmp_name, 'rb' );
      if (!$fdIn) {
         return FALSE;
      }

      $fdPart = NULL;
      $multiparts = array();
      do {
         clearstatcache();
         if (file_exists($tmpFilepart)) {
            if (feof($fdIn) || filesize($tmpFilepart)>= $maxPartSize) {
               $part_sha512 = $deployFile->registerFilepart($repoPath, $tmpFilepart, 
                                                            $file_present_in_repo);
               unlink($tmpFilepart);
               
               $multiparts[] = $part_sha512;
            }
         }
         if (feof($fdIn)) {
            break;
         }

         $data = fread ( $fdIn, 1024*1024 );
         $fdPart = gzopen ($tmpFilepart, 'a');
         gzwrite($fdPart, $data, strlen($data));
         gzclose($fdPart);
      } while (1);

      $new_entry['multiparts'] = $multiparts;

      //get current order json
      $datas = json_decode(PluginFusioninventoryDeployOrder::getJson($params['orders_id']), TRUE);

      //add new entry
      $datas['associatedFiles'][$sha512] = $new_entry;
      $datas['jobs']['associatedFiles'][] = $sha512;

      //update order
      PluginFusioninventoryDeployOrder::updateOrderJson($params['orders_id'], $datas);

      return TRUE;
   }

   
   

   static function removeFileInRepo($sha512, $orders_id) {
      global $DB;

      $repoPath = GLPI_PLUGIN_DOC_DIR."/fusioninventory/files/repository/";

      $order = new PluginFusioninventoryDeployOrder;
      $rows = $order->find("id != '$orders_id'
            AND json LIKE '%".substr($sha512, 0, 6 )."%'
            AND json LIKE '%$sha512%'"
      );
      if (count($rows) > 0) {
         //file found in other order, do not remove part in repo
         return FALSE;
      }

      //get current order json
      $datas = json_decode(PluginFusioninventoryDeployOrder::getJson($orders_id), TRUE);
      $multiparts = $datas['associatedFiles'][$sha512]['multiparts'];

      //parse all files part
      foreach ($multiparts as $part_sha512) {
         $dir = $repoPath.self::getDirBySha512($part_sha512).'/';

         //delete file parts
         unlink($dir.$part_sha512.'.gz');
      }

      return TRUE;
   }

   
   
   function checkPresenceFile($sha512) {
      $order = new PluginFusioninventoryDeployOrder;

      $rows = $order->find("json LIKE '%$sha512%'");
      if (count($rows) > 0) {
         return TRUE;
      }
      return FALSE;
   }

   

   static function getMaxUploadSize() {

      $max_upload = (int)(ini_get('upload_max_filesize'));
      $max_post = (int)(ini_get('post_max_size'));
      $memory_limit = (int)(ini_get('memory_limit'));

      return __('Max file size', 'fusioninventory')

         ." : ".min($max_upload, $max_post, $memory_limit).__('Mio', 'fusioninventory');

   } 
   
   static function processFilesize($filesize) {
      if ($filesize >= (1024 * 1024 * 1024)) {
         $filesize = round($filesize / (1024 * 1024 * 1024), 1)."GiB";
      } elseif ($filesize >= 1024 * 1024) {
         $filesize = round($filesize /  (1024 * 1024), 1)."MiB";

      } elseif ($filesize >= 1024) {
         $filesize = round($filesize / 1024, 1)."KB";

      } else {
         $filesize = $filesize."B";
      }
      return $filesize;
   }

}

?>